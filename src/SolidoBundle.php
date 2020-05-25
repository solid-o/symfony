<?php

declare(strict_types=1);

namespace Solido\Symfony;

use Solido\Symfony\DependencyInjection\CompilerPass\RegisterBodyConverterDecoders;
use Solido\Symfony\DependencyInjection\CompilerPass\RegisterSerializerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SolidoBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container
            ->addCompilerPass(new RegisterBodyConverterDecoders())
            ->addCompilerPass(new RegisterSerializerPass());
    }
}
