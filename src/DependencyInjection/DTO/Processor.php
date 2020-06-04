<?php

declare(strict_types=1);

namespace Solido\Symfony\DependencyInjection\DTO;

use Generator;
use IteratorAggregate;
use Kcs\ClassFinder\Finder\ComposerFinder;
use ReflectionClass;
use Solido\DtoManagement\Finder\ServiceLocator;
use Symfony\Component\Config\Resource\ClassExistenceResource;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Reference;
use function array_filter;
use function array_values;
use function assert;
use function is_string;
use function iterator_to_array;
use function Safe\preg_match;
use function Safe\preg_replace;
use function str_replace;

class Processor implements IteratorAggregate
{
    /** @var string[] */
    private array $namespaces;
    /** @var array<string, string> */
    private array $versions;
    private ContainerBuilder $container;

    /**
     * @param string[] $namespaces
     */
    public function __construct(ContainerBuilder $container, array $namespaces)
    {
        $this->container = $container;
        $this->namespaces = $namespaces;
        $this->versions = [];
    }

    /**
     * Gets all the processed version numbers.
     *
     * @return string[]
     */
    public function getVersions(): array
    {
        return array_values($this->versions);
    }

    public function getIterator(): Generator
    {
        $this->versions = [];
        foreach ($this->namespaces as $namespace) {
            yield from $this->processNamespace($this->container, $namespace);
        }
    }

    /**
     * Searches through the base dir recursively for interfaces and their implementations.
     *
     * @return array<string, ServiceClosureArgument>
     */
    private function processNamespace(ContainerBuilder $container, string $namespace): array
    {
        $finder = new ComposerFinder();
        $finder->inNamespace($namespace);

        /** @phpstan-var array<class-string, ReflectionClass> $classes */
        $classes = iterator_to_array($finder);
        $interfaces = array_filter($classes, static fn (ReflectionClass $class) => $class->isInterface());
        $modelsByInterface = [];

        foreach ($interfaces as $interface => $unused) {
            $modelsByInterface[$interface] = $this->processInterface($container, $interface, $namespace, $classes);
        }

        $locators = [];
        foreach ($modelsByInterface as $interface => $versions) {
            $id = '.solido.dto.service_locator.' . $interface;
            $container->register($id, ServiceLocator::class)->addArgument($versions);
            $locators[$interface] = new ServiceClosureArgument(new Reference($id));
        }

        return $locators;
    }

    /**
     * @param array<string, ReflectionClass> $classes
     *
     * @return array<string, ServiceClosureArgument>
     *
     * @phpstan-param array<class-string, ReflectionClass> $classes
     */
    private function processInterface(ContainerBuilder $container, string $interface, string $namespace, array $classes): array
    {
        $container->addResource(new ClassExistenceResource($interface, true));
        $models = [];

        foreach ($classes as $class => $reflector) {
            if (! $reflector->isInstantiable() || ! $reflector->isSubclassOf($interface)) {
                continue;
            }

            $container->addResource(new ClassExistenceResource($class, true));
            if (! preg_match('/^' . str_replace('\\', '\\\\', $namespace) . '\\\\v\d+\\\\v(.+?)\\\\/', $class, $m)) {
                continue;
            }

            $version = str_replace('_', '.', $m[1]);
            assert(is_string($version));

            try {
                $definition = $container->findDefinition($reflector->getName());
                $definition = clone $definition;
            } catch (ServiceNotFoundException $e) {
                $definition = null;
            }

            if ($definition === null) {
                $definition = new Definition($reflector->getName());
                $definition->setAutowired(true);
                $definition->setAutoconfigured(true);
            }

            $definition->setShared(false);
            $definition->addTag('controller.service_arguments');

            $container->setDefinition($id = '.solido.dto.' . $interface . '.' . $class, $definition);

            $models[$version] = new ServiceClosureArgument(new Reference($id));
            $this->versions[$version] = preg_replace('/(?<=\d)\.(?=[a-z])/i', '-', $version);
        }

        return $models;
    }
}
