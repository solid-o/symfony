<?php

declare(strict_types=1);

namespace Solido\Symfony\Config;

use InvalidArgumentException;
use Solido\Symfony\EventListener\ControllerListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\Routing\RouterInterface;

use function get_class;
use function is_array;
use function method_exists;

/**
 * Warms up the controller listener cache for solido attributes.
 */
class ControllerCacheWarmer implements CacheWarmerInterface
{
    private ControllerListener $listener;
    private RouterInterface $router;
    private ControllerResolverInterface $controllerResolver;

    public function __construct(ControllerListener $listener, RouterInterface $router, ControllerResolverInterface $controllerResolver)
    {
        $this->listener = $listener;
        $this->router = $router;
        $this->controllerResolver = $controllerResolver;
    }

    public function isOptional(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function warmUp(string $cacheDir): array
    {
        $files = [];

        $routes = $this->router->getRouteCollection();
        foreach ($routes as $route) {
            $className = $route->getDefault('_solido_dto_interface');
            $routeController = $route->getDefault('_controller');
            if ($routeController === null) {
                continue;
            }

            $request = new Request();
            $request->attributes->set('_controller', $routeController);
            try {
                $controller = $this->controllerResolver->getController($request);
            } catch (InvalidArgumentException $e) {
                continue;
            }

            /** @phpstan-var object|array{0: object, 1: string} $controller */
            if ($className === null) {
                if (! is_array($controller) && method_exists($controller, '__invoke')) {
                    $controller = [$controller, '__invoke'];
                }

                if (is_array($controller)) {
                    $className = ControllerListener::getRealClass(get_class($controller[0]));
                }
            }

            if ($className === null) {
                continue;
            }

            /** @phpstan-var array{0: object, 1: string} $controller */
            $cache = $this->listener->getConfigCache($className, $controller[1], $cacheDir);
            $files[] = $cache->getPath();
        }

        return $files;
    }
}
