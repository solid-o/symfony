<?php

declare(strict_types=1);

namespace Solido\Symfony\DependencyInjection\CompilerPass;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Error;
use Kcs\ClassFinder\Finder\RecursiveFinder;
use RuntimeException;
use Solido\DtoManagement\Finder\ServiceLocatorRegistryInterface;
use Solido\DtoManagement\Proxy\Factory\AccessInterceptorFactory;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use function assert;
use function class_exists;
use function interface_exists;
use function is_dir;
use function is_subclass_of;
use function mkdir;
use function Safe\file_put_contents;
use function Safe\sprintf;
use function strpos;
use function var_export;

class AddDtoInterceptorsPass implements CompilerPassInterface
{
    private AccessInterceptorFactory $proxyFactory;

    public function process(ContainerBuilder $container): void
    {
        $cacheDir = $container->getParameterBag()->resolveValue(
            $container->getParameter('solido.dto-management.proxy_cache_dir')
        );

        // @phpstan-ignore-next-line
        if (! @mkdir($cacheDir, 0777, true) && ! is_dir($cacheDir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $cacheDir));
        }

        $factory = $container->get('solido.dto-management.proxy_factory');
        assert($factory instanceof AccessInterceptorFactory);

        $this->proxyFactory = $factory;
        AnnotationRegistry::registerUniqueLoader('class_exists');

        $definition = $container->findDefinition(ServiceLocatorRegistryInterface::class);
        $interfaces = $definition->getArgument(0);

        foreach ($interfaces as $interface => $serviceLocator) {
            $this->processLocator($container, $serviceLocator);
        }

        $this->generateClassMap($cacheDir, $container->getParameter('kernel.cache_dir') . '/dto-proxies-map.php');
    }

    private function processLocator(ContainerBuilder $container, ServiceClosureArgument $argument): void
    {
        $locator = $container->getDefinition((string) $argument->getValues()[0]);
        /** @var ServiceClosureArgument[] $versions */
        $versions = $locator->getArgument(0);

        foreach ($versions as $version) {
            $definition = $container->findDefinition((string) $version->getValues()[0]);
            $className = $definition->getClass();
            if ($className === null || (! class_exists($className) && ! interface_exists($className))) {
                throw new Error(sprintf('"%s" class does not exist', $className));
            }

            $proxyClass = $this->proxyFactory->generateProxy($className);

            $definition->setClass($proxyClass);
            if (! is_subclass_of($proxyClass, ServiceSubscriberInterface::class)) {
                continue;
            }

            $definition->addTag('container.service_subscriber');
        }
    }

    private function generateClassMap(string $cacheDir, string $outFile): void
    {
        $map = [];
        $finder = new RecursiveFinder($cacheDir);

        foreach ($finder as $class => $reflector) {
            if (strpos($class, "class@anonymous\x00") === 0) {
                continue;
            }

            $map[$class] = $reflector->getFileName();
        }

        file_put_contents($outFile, '<?php return ' . var_export($map, true) . ';');
    }
}
