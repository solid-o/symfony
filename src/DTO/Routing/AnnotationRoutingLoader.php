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
use function Safe\sort;
use function Safe\substr;
use function strpos;

class AnnotationRoutingLoader extends AnnotationClassLoader
{
    private ?ServiceLocatorRegistry $locator;

    public function __construct(ServiceLocatorRegistryInterface $locator, Reader $reader)
    {
        $this->locator = $locator instanceof ServiceLocatorRegistry ? $locator : null;
        parent::__construct($reader);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, ?string $type = null): bool
    {
        return $this->locator !== null && $type === 'dto_annotations';
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, ?string $type = null): RouteCollection
    {
        if (substr($resource, -1) !== '\\') {
            throw new InvalidConfigurationException('DTO annotations route must define a namespace ending in "\\"');
        }

        $routeCollection = new RouteCollection();
        if ($this->locator === null) {
            return $routeCollection;
        }

        $interfaces = $this->locator->getInterfaces();
        sort($interfaces);

        foreach ($interfaces as $interface) {
            if (strpos($interface, $resource) !== 0) {
                continue;
            }

            $collection = new RouteCollection();
            $class = new ReflectionClass($interface);
            $globals = $this->getGlobals($class);

            $filename = $class->getFileName();
            if ($filename !== false) {
                $collection->addResource(new FileResource($filename));
            }

            foreach ($class->getMethods() as $method) {
                $this->defaultRouteIndex = 0;
                foreach ($this->reader->getMethodAnnotations($method) as $annot) {
                    if (! $annot instanceof RouteAnnotation) {
                        continue;
                    }

                    $this->addRoute($collection, $annot, $globals, $class, $method);
                }
            }

            if ($collection->count() !== 0 || ! $class->hasMethod('__invoke')) {
                $routeCollection->addCollection($collection);
                continue;
            }

            $globals = $this->resetGlobals();
            foreach ($this->reader->getClassAnnotations($class) as $annot) {
                if (! $annot instanceof RouteAnnotation) {
                    continue;
                }

                $this->addRoute($collection, $annot, $globals, $class, $class->getMethod('__invoke'));
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
