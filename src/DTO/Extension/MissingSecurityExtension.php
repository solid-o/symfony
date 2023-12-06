<?php

declare(strict_types=1);

namespace Solido\Symfony\DTO\Extension;

use Solido\DtoManagement\Proxy\Builder\ProxyBuilder;
use Solido\Symfony\Annotation\Security;

/** @internal */
final class MissingSecurityExtension extends MissingPackageExtension
{
    use AttributeReaderTrait;

    public function __construct()
    {
        parent::__construct('symfony/expression-language', 'Security');
    }

    public function extend(ProxyBuilder $proxyBuilder): void
    {
        foreach ($proxyBuilder->properties->getAccessibleProperties() as $property) {
            $annotation = $this->getAttribute($property, Security::class);
            if ($annotation === null) {
                continue;
            }

            parent::extend($proxyBuilder);
        }

        foreach ($proxyBuilder->class->getMethods() as $reflectionMethod) {
            if ($reflectionMethod->isPrivate() || $reflectionMethod->isFinal()) {
                continue;
            }

            $annotation = $this->getAttribute($reflectionMethod, Security::class);
            if ($annotation === null) {
                continue;
            }

            parent::extend($proxyBuilder);
        }
    }
}
