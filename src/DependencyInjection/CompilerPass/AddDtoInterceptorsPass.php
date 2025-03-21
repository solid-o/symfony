<?php

declare(strict_types=1);

namespace Solido\Symfony\DependencyInjection\CompilerPass;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Error;
use Kcs\ClassFinder\Finder\RecursiveFinder;
use ReflectionClass;
use RuntimeException;
use Solido\DtoManagement\Exception\EmptyBuilderException;
use Solido\DtoManagement\Finder\ServiceLocatorRegistry;
use Solido\DtoManagement\InterfaceResolver\ResolverInterface;
use Solido\DtoManagement\Proxy\Factory\AccessInterceptorFactory;
use Solido\Symfony\DependencyInjection\DTO\Processor;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

use function array_merge;
use function array_values;
use function assert;
use function class_exists;
use function dirname;
use function file_put_contents;
use function function_exists;
use function in_array;
use function interface_exists;
use function is_dir;
use function is_string;
use function is_subclass_of;
use function mkdir;
use function sprintf;
use function str_replace;
use function strpos;
use function var_export;

class AddDtoInterceptorsPass implements CompilerPassInterface
{
    private AccessInterceptorFactory $proxyFactory;

    public function process(ContainerBuilder $container): void
    {
        if ($container->getParameter('kernel.build_dir')) {
            $container->setParameter('solido.dto-management.proxy_cache_dir', '%kernel.build_dir%/dto-proxies');
        } else {
            $container->setParameter('solido.dto-management.proxy_cache_dir', '%kernel.cache_dir%/dto-proxies');
        }

        $cacheDir = $container->getParameterBag()->resolveValue(
            $container->getParameter('solido.dto-management.proxy_cache_dir'),
        );
        assert(is_string($cacheDir));

        if (! @mkdir($cacheDir, 0777, true) && ! is_dir($cacheDir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $cacheDir));
        }

        $factory = $container->get('solido.dto-management.proxy_factory');
        assert($factory instanceof AccessInterceptorFactory);

        $this->proxyFactory = $factory;
        if (function_exists(AnnotationRegistry::class . '::registerUniqueLoader')) {
            // @phpstan-ignore-next-line
            AnnotationRegistry::registerUniqueLoader('class_exists');
        }

        $definition = $container->findDefinition(ServiceLocatorRegistry::class);
        $namespaces = $exclude = [];
        foreach ($definition->getTag('solido.dto_service_locator_registry.namespace') as $tag) {
            $namespaces[] = $tag['value'];
        }

        foreach ($definition->getTag('solido.dto_service_locator_registry.exclude') as $tag) {
            $exclude[] = $tag['value'];
        }

        /** @var array<string, ServiceClosureArgument> $locators */
        $locators = [];
        $iterator = new Processor($container, $namespaces);

        foreach ($iterator as $interface => $interfaceArg) {
            assert(is_string($interface));
            assert($interfaceArg instanceof ServiceClosureArgument);
            if (in_array($interface, $exclude, true)) {
                continue;
            }

            if (isset($locators[$interface])) {
                // How can this case be possible?!
                $ref = $locators[$interface]->getValues()[0];
                assert($ref instanceof Reference);
                $def = $container->findDefinition((string) $ref);

                $argRef = $interfaceArg->getValues()[0];
                assert($argRef instanceof Reference);
                $argDef = $container->findDefinition((string) $argRef);

                // @phpstan-ignore-next-line
                $arguments = array_merge($def->getArgument(0), $argDef->getArgument(0));
                $def->setArguments([array_values($arguments)]);
            } else {
                $locators[$interface] = $interfaceArg;
            }
        }

        foreach ($locators as $interface => $serviceLocator) {
            assert(is_string($interface));

            $this->processLocator($container, $serviceLocator);
            $container->register($interface, $interface)
                ->setFactory([new Reference(ResolverInterface::class), 'resolve'])
                ->addArgument($interface)
                ->setShared(false)
                ->setPublic(true);
        }

        $definition->setArgument(0, $locators);
        $container->setParameter('solido.dto-management.versions', $iterator->getVersions());

        $kernelCacheDir = $container->hasParameter('kernel.build_dir') ?
            $container->getParameter('kernel.build_dir') :
            $container->getParameter('kernel.cache_dir');
        assert(is_string($kernelCacheDir));

        $this->generateClassMap($cacheDir, $kernelCacheDir . '/dto-proxies-map.php');
    }

    private function processLocator(ContainerBuilder $container, ServiceClosureArgument $argument): void
    {
        $locator = $container->getDefinition((string) $argument->getValues()[0]);
        /** @var ServiceClosureArgument[] $versions */
        $versions = $locator->getArgument(1);

        foreach ($versions as &$version) {
            $definition = $container->findDefinition((string) $version->getValues()[0]);
            $definition->setShared(false);

            $className = $definition->getClass();
            if ($className === null || (! class_exists($className) && ! interface_exists($className))) {
                throw new Error(sprintf('"%s" class does not exist', $className));
            }

            try {
                $proxyClass = $this->proxyFactory->generateProxy($className, ['throw_empty' => true]);
            } catch (EmptyBuilderException) { /* @phpstan-ignore-line */
                continue;
            }

            $definition->setClass($proxyClass);
            if (! is_subclass_of($proxyClass, ServiceSubscriberInterface::class)) {
                continue;
            }

            foreach ($proxyClass::getSubscribedServices() as $key => $class) {
                $definition->addTag('container.service_subscriber', ['key' => $key, 'id' => $key]);
            }
        }
    }

    private function generateClassMap(string $cacheDir, string $outFile): void
    {
        $map = [];
        $finder = new RecursiveFinder($cacheDir);

        foreach ($finder as $class => $reflector) {
            assert(is_string($class));
            assert($reflector instanceof ReflectionClass);
            if (strpos($class, "class@anonymous\x00") === 0) {
                continue;
            }

            $map[$class] = $reflector->getFileName();
        }

        $exportedMap = var_export($map, true);
        $exportedMap = str_replace(dirname($outFile), "' . __DIR__ . '", $exportedMap);
        $exportedMap = str_replace("'' . ", '', $exportedMap);

        file_put_contents($outFile, '<?php return ' . $exportedMap . ';');
    }
}
