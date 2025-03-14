<?php

declare(strict_types=1);

namespace Solido\Symfony;

use Solido\Common\Urn\Urn;
use Solido\Symfony\DependencyInjection\CompilerPass\AddDtoInterceptorsPass;
use Solido\Symfony\DependencyInjection\CompilerPass\PolicyCheckerCollectorTemplatePass;
use Solido\Symfony\DependencyInjection\CompilerPass\RegisterBodyConverterDecoders;
use Solido\Symfony\DependencyInjection\CompilerPass\RegisterDtoExtensionsPass;
use Solido\Symfony\DependencyInjection\CompilerPass\RegisterDtoProxyCasterPass;
use Solido\Symfony\DependencyInjection\CompilerPass\RegisterSecurityExtensionLanguageProviderPass;
use Solido\Symfony\DependencyInjection\CompilerPass\RegisterSerializerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use function assert;
use function file_exists;
use function is_string;
use function spl_autoload_register;

class SolidoBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container
            ->addCompilerPass(new RegisterDtoProxyCasterPass())
            ->addCompilerPass(new RegisterDtoExtensionsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 40)
            ->addCompilerPass(new RegisterBodyConverterDecoders())
            ->addCompilerPass(new RegisterSerializerPass())
            ->addCompilerPass(new PolicyCheckerCollectorTemplatePass())
            ->addCompilerPass(new RegisterSecurityExtensionLanguageProviderPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 20)
            ->addCompilerPass(new AddDtoInterceptorsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 15);
    }

    public function boot(): void
    {
        assert($this->container !== null);
        if ($this->container->hasParameter('solido.urn.urn_default_domain')) {
            $defaultDomain = $this->container->getParameter('solido.urn.urn_default_domain');
            assert(is_string($defaultDomain));

            Urn::$defaultDomain = $defaultDomain;
        }

        $cacheDir = $this->container->hasParameter('kernel.build_dir') ?
            $this->container->getParameter('kernel.build_dir') :
            $this->container->getParameter('kernel.cache_dir');

        assert(is_string($cacheDir));
        $dtoMapFile = $cacheDir . '/dto-proxies-map.php';
        if (! file_exists($dtoMapFile)) {
            return;
        }

        $classMap = require $dtoMapFile;
        spl_autoload_register(static function (string $className) use (&$classMap): void {
            if (! isset($classMap[$className])) {
                return;
            }

            require $classMap[$className];
        });
    }
}
