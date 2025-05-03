<?php

declare(strict_types=1);

namespace Solido\Symfony\Config;

use Closure;
use InvalidArgumentException;
use Solido\Symfony\EventListener\ControllerListener;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\Routing\RouterInterface;

use function get_class;
use function is_array;
use function is_string;
use function method_exists;

/**
 * Warms up the controller listener cache for solido attributes.
 */
class ControllerCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var string[][]
     * @phpstan-var array{0: class-string, 1: string}[]
     */
    private array $additionalControllers = [];

    public function __construct(private ControllerListener $listener, private RouterInterface $router, private ControllerResolverInterface $controllerResolver)
    {
    }

    /**
     * @param string[] $additionalController
     * @phpstan-param array{0: class-string, 1: string} $additionalController
     */
    public function addAdditionalController(array $additionalController): void
    {
        $this->additionalControllers[] = $additionalController;
    }

    public function isOptional(): bool
    {
        return true;
    }

    /** @inheritDoc */
    public function warmUp(string $cacheDir, string|null $buildDir = null): array
    {
        $files = [];

        $routes = $this->router->getRouteCollection();
        foreach ($routes as $route) {
            /** @phpstan-var class-string<object> $className */
            $className = $route->getDefault('_solido_dto_interface');
            $routeController = $route->getDefault('_controller');
            if ($routeController === null) {
                continue;
            }

            $request = new Request();
            $request->attributes->set('_controller', $routeController);
            try {
                $controller = $this->controllerResolver->getController($request);
            } catch (InvalidArgumentException) {
                continue;
            }

            /** @phpstan-var object|array{0: object, 1: string} $controller */
            $cache = $this->processController($controller, $className, $buildDir ?? $cacheDir); // @phpstan-ignore-line
            if ($cache === null) {
                continue;
            }

            $files[] = $cache->getPath();
        }

        foreach ($this->additionalControllers as $controller) {
            $cache = $this->processController($controller, null, $buildDir ?? $cacheDir);
            if ($cache === null) {
                continue;
            }

            $files[] = $cache->getPath();
        }

        return $files;
    }

    /**
     * @phpstan-param Closure|object|array{0: object|class-string, 1: string} $controller
     * @phpstan-param class-string|null $className
     */
    private function processController(array|object|callable $controller, string|null $className, string $cacheDir): ConfigCacheInterface|null
    {
        if ($controller instanceof Closure) {
            return null;
        }

        if ($className === null) {
            if (! is_array($controller) && method_exists($controller, '__invoke')) {
                $controller = [$controller, '__invoke'];
            }

            if (is_array($controller)) {
                $className = ControllerListener::getRealClass(is_string($controller[0]) ? $controller[0] : get_class($controller[0]));
            }
        }

        if ($className === null) {
            return null;
        }

        /** @phpstan-var array{0: object, 1: string} $controller */
        return $this->listener->getConfigCache($className, $controller[1], $cacheDir);
    }
}
