<?php

declare(strict_types=1);

namespace Solido\Symfony\DTO\Extension;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use ProxyManager\Generator\Util\UniqueIdentifierGenerator;
use ReflectionParameter;
use Solido\DtoManagement\Proxy\Builder\ProxyBuilder;
use Solido\DtoManagement\Proxy\Builder\Wrapper;
use Solido\DtoManagement\Proxy\Extension\ExtensionInterface;
use Solido\Symfony\Annotation\Lock;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use function array_map;
use function array_merge;
use function assert;
use function class_exists;
use function count;
use function implode;
use function Safe\sprintf;

class LockExtension implements ExtensionInterface
{
    use AttributeReaderTrait;
    use SubscribedServicesGeneratorTrait;

    private ?ExpressionLanguage $expressionLanguage;

    public function __construct(?Reader $reader = null, ?ExpressionLanguage $expressionLanguage = null)
    {
        $this->reader = $reader;
        if ($reader === null && class_exists(AnnotationReader::class)) {
            $this->reader = new AnnotationReader();
        }

        $this->expressionLanguage = $expressionLanguage;
    }

    public function extend(ProxyBuilder $proxyBuilder): void
    {
        $this->builder = $proxyBuilder;
        foreach ($proxyBuilder->class->getMethods() as $reflectionMethod) {
            if ($reflectionMethod->isPrivate() || $reflectionMethod->isFinal()) {
                continue;
            }

            $annotation = $this->getAttribute($reflectionMethod, Lock::class);
            if ($annotation === null) {
                continue;
            }

            assert($annotation instanceof Lock);
            [$head, $tail] = $this->generateCode($annotation, array_map(static fn (ReflectionParameter $parameter) => $parameter->getName(), $reflectionMethod->getParameters()));
            $proxyBuilder->addMethodWrapper($reflectionMethod->getName(), new Wrapper($head, $tail));
        }

        unset($this->builder);
    }

    /**
     * @param string[] $parameters
     *
     * @return string[]
     * @phpstan-return array{0: string, 1: string}
     */
    protected function generateCode(Lock $annotation, array $parameters): array
    {
        $this->addServices([
            'lock.default.factory' => LockFactory::class,
            'security.token_storage' => '?' . TokenStorageInterface::class,
        ]);

        $property = UniqueIdentifierGenerator::getIdentifier('lock');
        if (count($parameters) > 0) {
            $usedParams = ' use (' . implode(', ', array_map(static fn (string $name) => '$' . $name, $parameters)) . ')';
        } else {
            $usedParams = '';
        }

        $code = ($this->expressionLanguage ?? $this->getExpressionLanguage())
            ->compile($annotation->expression, array_merge(['object', 'user'], $parameters));

        $head = sprintf('
$%1$s = (function ()%2$s: \Symfony\Component\Lock\LockInterface {
    $object = $this;
    $user = $tokenStorage = null;
    try {
        $tokenStorage = $this->%3$s->get(\'security.token_storage\');
    } catch (\Psr\Container\ContainerExceptionInterface $ex) {
        // Do nothing
    }

    if ($tokenStorage !== null) {
        $token = $tokenStorage->getToken();
        $user = $token !== null ? $token->getUser() : null;
    }

    return $this->%3$s->get(\'lock.default.factory\')->createLock(%4$s);
})();

$%1$s->acquire(true);
try {
', $property, $usedParams, $this->getContainerName(), $code);

        $tail = sprintf('
} finally {
    $%1$s->release();
}
', $property);

        return [$head, $tail];
    }

    private function getExpressionLanguage(): ExpressionLanguage
    {
        return $this->expressionLanguage ?? new ExpressionLanguage();
    }
}
