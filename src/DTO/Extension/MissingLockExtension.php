<?php

declare(strict_types=1);

namespace Solido\Symfony\DTO\Extension;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Solido\DtoManagement\Proxy\Builder\ProxyBuilder;
use Solido\Symfony\Annotation\Lock;

use function class_exists;

/** @internal */
final class MissingLockExtension extends MissingPackageExtension
{
    use AttributeReaderTrait;

    public function __construct(?Reader $reader = null)
    {
        parent::__construct('symfony/expression-language', 'Lock');

        $this->reader = $reader;
        if ($reader !== null || ! class_exists(AnnotationReader::class)) {
            return;
        }

        $this->reader = new AnnotationReader();
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
