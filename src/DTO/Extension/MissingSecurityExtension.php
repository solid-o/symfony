<?php

declare(strict_types=1);

namespace Solido\Symfony\DTO\Extension;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Solido\DtoManagement\Proxy\Builder\ProxyBuilder;

use function class_exists;

/**
 * @internal
 */
final class MissingSecurityExtension extends MissingPackageExtension
{
    use SecurityExtensionTrait;

    public function __construct(?Reader $reader = null)
    {
        parent::__construct('symfony/expression-language', 'Security');

        $this->reader = $reader;
        if ($reader !== null || ! class_exists(AnnotationReader::class)) {
            return;
        }

        $this->reader = new AnnotationReader();
    }

    public function extend(ProxyBuilder $proxyBuilder): void
    {
        foreach ($proxyBuilder->properties->getAccessibleProperties() as $property) {
            $annotation = $this->getAttribute($property);
            if ($annotation === null) {
                continue;
            }

            parent::extend($proxyBuilder);
        }

        foreach ($proxyBuilder->class->getMethods() as $reflectionMethod) {
            if ($reflectionMethod->isPrivate() || $reflectionMethod->isFinal()) {
                continue;
            }

            $annotation = $this->getAttribute($reflectionMethod);
            if ($annotation === null) {
                continue;
            }

            parent::extend($proxyBuilder);
        }
    }
}
