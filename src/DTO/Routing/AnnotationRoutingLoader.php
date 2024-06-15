<?php

declare(strict_types=1);

namespace Solido\Symfony\DTO\Routing;

use LogicException;
use ReflectionClass;
use ReflectionMethod;
use Solido\DtoManagement\Finder\ServiceLocatorRegistryInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Routing\Annotation\Route as RouteAnnotation;
use Symfony\Component\Routing\Attribute\Route as RouteAttribute;
use Symfony\Component\Routing\Loader\AttributeClassLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

use function is_string;
use function sort;
use function str_starts_with;
use function substr;

class AnnotationRoutingLoader extends AttributeClassLoader
{
    public function __construct(private readonly ServiceLocatorRegistryInterface|null $locator, string $env)
    {
        parent::__construct($env);
    }

    public function supports(mixed $resource, string|null $type = null): bool
    {
        return $this->locator !== null && $type === 'dto_annotations';
    }

    public function load(mixed $class, string|null $type = null): RouteCollection
    {
        if (! is_string($class) || substr($class, -1) !== '\\') {
            throw new InvalidConfigurationException('DTO annotations route must define a namespace ending in "\\"');
        }

        $routeCollection = new RouteCollection();
        if ($this->locator === null) {
            return $routeCollection;
        }

        $interfaces = $this->locator->getInterfaces();
        sort($interfaces);

        foreach ($interfaces as $interface) {
            if (! str_starts_with($interface, $class)) {
                continue;
            }

            $collection = new RouteCollection();
            $reflectionClass = new ReflectionClass($interface);
            $globals = $this->getGlobals($reflectionClass);

            $filename = $reflectionClass->getFileName();
            if ($filename !== false) {
                $collection->addResource(new FileResource($filename));
            }

            foreach ($reflectionClass->getMethods() as $method) {
                $this->defaultRouteIndex = 0;
                foreach ($this->getAnnotations($method) as $annot) {
                    $this->addRoute($collection, $annot, $globals, $reflectionClass, $method);
                }
            }

            if ($collection->count() !== 0 || ! $reflectionClass->hasMethod('__invoke')) {
                $routeCollection->addCollection($collection);
                continue;
            }

            $globals = $this->resetGlobals();
            foreach ($this->getAnnotations($reflectionClass) as $annot) {
                $this->addRoute($collection, $annot, $globals, $reflectionClass, $reflectionClass->getMethod('__invoke'));
            }

            $routeCollection->addCollection($collection);
        }

        return $routeCollection;
    }

    /**
     * Configures the _controller default parameter and eventually the HTTP method
     * requirement of a given Route instance.
     *
     * @param mixed $annot The annotation class instance
     *
     * @throws LogicException When the service option is specified on a method.
     */
    protected function configureRoute(Route $route, ReflectionClass $class, ReflectionMethod $method, mixed $annot): void
    {
        $route->setDefault('_route_view', true);
        $route->setDefault('_solido_dto_interface', $class->getName());

        // controller
        if ($method->getName() === '__invoke') {
            $route->setDefault('_controller', $class->getName() . '::__invoke');
        } else {
            $route->setDefault('_controller', $class->getName() . '::' . $method->getName());
        }
    }

    /**
     * @param ReflectionClass|ReflectionMethod $reflection
     *
     * @return array<RouteAttribute|RouteAnnotation>
     */
    private function getAnnotations(object $reflection): iterable
    {
        foreach ($reflection->getAttributes(RouteAttribute::class) as $attribute) {
            yield $attribute->newInstance();
        }

        foreach ($reflection->getAttributes(RouteAnnotation::class) as $attribute) {
            yield $attribute->newInstance();
        }
    }

    /** @return array<string, mixed> */
    private function resetGlobals(): array
    {
        return [
            'path' => null,
            'localized_paths' => [],
            'requirements' => [],
            'options' => [],
            'defaults' => [],
            'schemes' => [],
            'methods' => [],
            'host' => '',
            'condition' => '',
            'name' => '',
            'priority' => 0,
        ];
    }
}
