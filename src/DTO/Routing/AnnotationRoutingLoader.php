<?php

declare(strict_types=1);

namespace Solido\Symfony\DTO\Routing;

use Doctrine\Common\Annotations\Reader;
use LogicException;
use ReflectionClass;
use ReflectionMethod;
use Solido\DtoManagement\Finder\ServiceLocatorRegistry;
use Solido\DtoManagement\Finder\ServiceLocatorRegistryInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Routing\Annotation\Route as RouteAnnotation;
use Symfony\Component\Routing\Loader\AnnotationClassLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

use function is_string;
use function Safe\sort;
use function Safe\substr;
use function strpos;

use const PHP_VERSION_ID;

class AnnotationRoutingLoader extends AnnotationClassLoader
{
    private ?ServiceLocatorRegistry $locator;

    public function __construct(ServiceLocatorRegistryInterface $locator, ?Reader $reader = null)
    {
        $this->locator = $locator instanceof ServiceLocatorRegistry ? $locator : null;
        parent::__construct($reader);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $resource
     */
    public function supports($resource, ?string $type = null): bool
    {
        return $this->locator !== null && $type === 'dto_annotations';
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $class
     */
    public function load($class, ?string $type = null): RouteCollection
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
            if (strpos($interface, $class) !== 0) {
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
                    if (! $annot instanceof RouteAnnotation) {
                        continue;
                    }

                    $this->addRoute($collection, $annot, $globals, $reflectionClass, $method);
                }
            }

            if ($collection->count() !== 0 || ! $reflectionClass->hasMethod('__invoke')) {
                $routeCollection->addCollection($collection);
                continue;
            }

            $globals = $this->resetGlobals();
            foreach ($this->getAnnotations($reflectionClass) as $annot) {
                if (! $annot instanceof RouteAnnotation) {
                    continue;
                }

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
    protected function configureRoute(Route $route, ReflectionClass $class, ReflectionMethod $method, $annot): void
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
     * @return RouteAnnotation[]
     */
    private function getAnnotations(object $reflection): iterable
    {
        if (PHP_VERSION_ID >= 80000) {
            foreach ($reflection->getAttributes($this->routeAnnotationClass) as $attribute) {
                /** @phpstan-ignore-next-line */
                yield $attribute->newInstance();
            }
        }

        if ($this->reader === null) {
            return;
        }

        $anntotations = $reflection instanceof ReflectionClass
            ? $this->reader->getClassAnnotations($reflection)
            : $this->reader->getMethodAnnotations($reflection);

        foreach ($anntotations as $annotation) {
            if (! ($annotation instanceof $this->routeAnnotationClass)) {
                continue;
            }

            /** @phpstan-ignore-next-line */
            yield $annotation;
        }
    }

    /**
     * @return array<string, mixed>
     */
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
