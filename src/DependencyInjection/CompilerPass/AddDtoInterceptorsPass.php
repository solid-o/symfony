<?php

declare(strict_types=1);

namespace Solido\Symfony\DependencyInjection\CompilerPass;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Error;
use Kcs\ClassFinder\Finder\RecursiveFinder;
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
use function function_exists;
use function in_array;
use function interface_exists;
use function is_dir;
use function is_string;
use function is_subclass_of;
use function mkdir;
use function Safe\array_combine;
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
        if (function_exists(AnnotationRegistry::class . '::registerUniqueLoader')) {
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

        $locators = [];
        $iterator = new Processor($container, $namespaces);
        foreach ($iterator as $interface => $interfaceDefinition) {
            if (in_array($interface, $exclude, true)) {
                continue;
            }

            if (isset($locators[$interface])) {
                // How can this case be possible?!
                $arguments = array_merge($locators[$interface]->getArgument(0), $interfaceDefinition->getArgument(0));
                $locators[$interface]->setArguments([array_values(array_combine($arguments, $arguments))]);
            } else {
                $locators[$interface] = $interfaceDefinition;
            }
        }

        foreach ($locators as $interface => $serviceLocator) {
            assert(is_string($interface));

            $this->processLocator($container, $serviceLocator);
            $container->register($interface, $interface)
                ->setFactory([new Reference(ResolverInterface::class), 'resolve'])
                ->addArgument($interface)
                ->setPublic(true);
        }

        $definition->setArgument(0, $locators);
        $container->setParameter('solido.dto-management.versions', $iterator->getVersions());

        $this->generateClassMap($cacheDir, $container->getParameter('kernel.cache_dir') . '/dto-proxies-map.php');
    }

    private function processLocator(ContainerBuilder $container, ServiceClosureArgument $argument): void
    {
        $locator = $container->getDefinition((string) $argument->getValues()[0]);
        /** @var ServiceClosureArgument[] $versions */
        $versions = $locator->getArgument(0);

        foreach ($versions as &$version) {
            $definition = $container->findDefinition((string) $version->getValues()[0]);
            $definition->setShared(false);

            $className = $definition->getClass();
            if ($className === null || (! class_exists($className) && ! interface_exists($className))) {
                throw new Error(sprintf('"%s" class does not exist', $className));
            }

            try {
                $proxyClass = $this->proxyFactory->generateProxy($className, ['throw_empty' => true]);
            } catch (EmptyBuilderException $e) {
                continue;
            }

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
