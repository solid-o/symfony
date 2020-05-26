<?php

declare(strict_types=1);

namespace Solido\Symfony\DTO\Extension;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use ProxyManager\Generator\Util\UniqueIdentifierGenerator;
use ReflectionParameter;
use Solido\DtoManagement\Proxy\Builder\Interceptor;
use Solido\DtoManagement\Proxy\Builder\ProxyBuilder;
use Solido\DtoManagement\Proxy\Extension\ExtensionInterface;
use Solido\Symfony\Annotation\Security;
use Solido\Symfony\DTO\Security\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage as BaseExpressionLanguage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use function array_map;
use function array_merge;
use function assert;
use function count;
use function implode;
use function Safe\sprintf;
use function var_export;

class SecurityExtension implements ExtensionInterface
{
    use SubscribedServicesGeneratorTrait;

    private Reader $reader;
    private BaseExpressionLanguage $expressionLanguage;

    public function __construct(?Reader $reader = null, ?BaseExpressionLanguage $expressionLanguage = null)
    {
        $this->reader = $reader ?? new AnnotationReader();
        $this->expressionLanguage = $expressionLanguage ?? new ExpressionLanguage();
    }

    public function extend(ProxyBuilder $proxyBuilder): void
    {
        $this->builder = $proxyBuilder;

        foreach ($proxyBuilder->properties->getAccessibleProperties() as $property) {
            $annotation = $this->reader->getPropertyAnnotation($property, Security::class);
            if ($annotation === null) {
                continue;
            }

            assert($annotation instanceof Security);
            $proxyBuilder->addPropertyInterceptor($property->getName(), new Interceptor($this->generateCode($annotation, ['value'])));
        }

        foreach ($proxyBuilder->class->getMethods() as $reflectionMethod) {
            if ($reflectionMethod->isPrivate() || $reflectionMethod->isFinal()) {
                continue;
            }

            $annotation = $this->reader->getMethodAnnotation($reflectionMethod, Security::class);
            if ($annotation === null) {
                continue;
            }

            assert($annotation instanceof Security);

            $code = $this->generateCode($annotation, array_map(static fn (ReflectionParameter $parameter) => $parameter->getName(), $reflectionMethod->getParameters()));
            $proxyBuilder->addMethodInterceptor($reflectionMethod->getName(), new Interceptor($code));
        }

        unset($this->builder);
    }

    /**
     * @param string[] $parameters
     */
    protected function generateCode(Security $annotation, array $parameters): string
    {
        $this->addServices([
            'security.authorization_checker' => AuthorizationCheckerInterface::class,
            'security.token_storage' => TokenStorageInterface::class,
        ]);

        $property = UniqueIdentifierGenerator::getIdentifier('check');
        $message = var_export($annotation->message ?: 'Expression "' . $annotation->expression . '" denied access.', true);

        if (count($parameters) > 0) {
            $usedParams = ' use (' . implode(', ', array_map(static fn (string $name) => '$' . $name, $parameters)) . ')';
        } else {
            $usedParams = '';
        }

        if ($annotation->onInvalid === Security::RETURN_NULL) {
            $onInvalid = 'return new ReturnValue(null);';
        } else {
            $onInvalid = sprintf('throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException(%s);', $message);
        }

        $code = $this->expressionLanguage->compile($annotation->expression, array_merge(['auth_checker', 'token', 'object', 'user'], $parameters));

        return sprintf('
$%1$s = function ()%2$s: bool {
    $auth_checker = $this->%3$s->get(\'security.authorization_checker\');
    $token = $this->%3$s->get(\'security.token_storage\')->getToken();
    $object = $this;
    $user = null !== $token ? $token->getUser() : null;

    return %4$s;
};

if (! $%1$s()) {
    %5$s
}
', $property, $usedParams, $this->getContainerName(), $code, $onInvalid);
    }
}
