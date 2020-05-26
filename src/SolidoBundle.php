<?php

declare(strict_types=1);

namespace Solido\Symfony;

use Solido\Symfony\DependencyInjection\CompilerPass\AddDtoInterceptorsPass;
use Solido\Symfony\DependencyInjection\CompilerPass\RegisterBodyConverterDecoders;
use Solido\Symfony\DependencyInjection\CompilerPass\RegisterDtoExtensionsPass;
use Solido\Symfony\DependencyInjection\CompilerPass\RegisterDtoProxyCasterPass;
use Solido\Symfony\DependencyInjection\CompilerPass\RegisterSerializerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SolidoBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container
            ->addCompilerPass(new RegisterDtoProxyCasterPass())
            ->addCompilerPass(new RegisterDtoExtensionsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 40)
            ->addCompilerPass(new RegisterBodyConverterDecoders())
            ->addCompilerPass(new RegisterSerializerPass())
            ->addCompilerPass(new AddDtoInterceptorsPass());
    }
}
