<?php

declare(strict_types=1);

namespace Solido\Symfony\DTO\Extension;

use Solido\DtoManagement\Proxy\Builder\ProxyBuilder;
use Solido\Symfony\Annotation\Lock;

/** @internal */
final class MissingLockExtension extends MissingPackageExtension
{
    use AttributeReaderTrait;

    public function __construct()
    {
        parent::__construct('symfony/expression-language', 'Lock');
    }

    public function extend(ProxyBuilder $proxyBuilder): void
    {
        foreach ($proxyBuilder->class->getMethods() as $reflectionMethod) {
            if ($reflectionMethod->isPrivate() || $reflectionMethod->isFinal()) {
                continue;
            }

            $annotation = $this->getAttribute($reflectionMethod, Lock::class);
            if ($annotation === null) {
                continue;
            }

            parent::extend($proxyBuilder);
        }
    }
}
