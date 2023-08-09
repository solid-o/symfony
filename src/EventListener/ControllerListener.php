<?php

declare(strict_types=1);

namespace Solido\Symfony\EventListener;

use Closure;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Persistence\Proxy;
use LogicException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Solido\DtoManagement\Proxy\ProxyInterface;
use Solido\Symfony\Configuration\ConfigurationInterface;
use Symfony\Component\Config\ConfigCacheFactoryInterface;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Config\Resource\ReflectionClassResource;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\VarExporter\VarExporter;
use UnexpectedValueException;

use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_push;
use function class_exists;
use function get_class;
use function get_parent_class;
use function is_array;
use function method_exists;
use function Safe\sprintf;
use function Safe\substr;
use function strrpos;
use function strtr;

use const PHP_VERSION_ID;

/**
 * The ControllerListener class parses annotation blocks located in
 * controller classes.
 *
 * @internal
 */
class ControllerListener implements EventSubscriberInterface
{
    private ConfigCacheFactoryInterface $cacheFactory;
    private string $cacheDir;
    private ?Reader $reader;

    public function __construct(
        ConfigCacheFactoryInterface $cacheFactory,
        string $cacheDir,
        ?Reader $reader = null
    ) {
        $this->cacheFactory = $cacheFactory;
        $this->cacheDir = $cacheDir;
        $this->reader = $reader;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER => 'onKernelController'];
    }

    /**
     * @internal
     *
     * @phpstan-return class-string
     */
    public static function getRealClass(string $class): string
    {
        if (class_exists(Proxy::class)) {
            $pos = strrpos($class, '\\' . Proxy::MARKER . '\\');
            if ($pos !== false) {
                $class = substr($class, $pos + Proxy::MARKER_LENGTH + 2);
            }
        }

        /** @phpstan-var class-string $class */
        return $class;
    }

    /**
     * Modifies the Request object to apply configuration information found in
     * controllers annotations like the template to render or HTTP caching
     * configuration.
     */
    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        /** @phpstan-var class-string<object> | null $className */
        $className = $request->attributes->get('_solido_dto_interface');

        /** @phpstan-var object|array{0: object, 1: string} $controller */
        $controller = $event->getController();
        if ($controller instanceof Closure) {
            return;
        }

        if ($className === null) {
            if (! is_array($controller) && method_exists($controller, '__invoke')) {
                $controller = [$controller, '__invoke'];
            }

            if (is_array($controller)) {
                $className = self::getRealClass(get_class($controller[0]));
            }
        }

        if ($className === null) {
            return;
        }

        /** @phpstan-var array{0: object, 1: string} $controller */
        [$classConfigurations, $methodConfigurations] = $this->getConfigurations($this->cacheDir, $className, $controller[1]);
        $configurations = [];
        foreach (array_merge(array_keys($classConfigurations), array_keys($methodConfigurations)) as $key) {
            if (! array_key_exists($key, $classConfigurations)) {
                $configurations[$key] = $methodConfigurations[$key];
            } elseif (! array_key_exists($key, $methodConfigurations)) {
                $configurations[$key] = $classConfigurations[$key];
            } elseif (is_array($classConfigurations[$key])) {
                if (! is_array($methodConfigurations[$key])) {
                    throw new UnexpectedValueException('Configurations should both be an array or both not be an array');
                }

                $configurations[$key] = array_merge($classConfigurations[$key], $methodConfigurations[$key]);
            } else {
                // method configuration overrides class configuration
                $configurations[$key] = $methodConfigurations[$key];
            }
        }

        foreach ($configurations as $key => $attributes) {
            $request->attributes->set($key, $attributes);
        }

        if (! $request->attributes->has('_solido_dto_interface')) {
            return;
        }

        $object = new ReflectionClass($className);
        $method = $object->getMethod($controller[1]);

        $controllerName = sprintf(
            '.solido.dto.%s.%s:%s',
            $className,
            $controller[0] instanceof ProxyInterface ? get_parent_class($controller[0]) : get_class($controller[0]),
            $method->name,
        );

        $request->attributes->set('_controller', $controllerName);

        if (PHP_VERSION_ID <= 80000) {
            return;
        }

        $attributes = $event->getAttributes();
        foreach ($method->getAttributes() as $attribute) {
            $attributes[$attribute->getName()][] = $attribute->newInstance();
        }

        $event->setController($event->getController(), $attributes);
    }

    /**
     * @internal
     *
     * @phpstan-param class-string $className
     */
    public function getConfigCache(string $className, string $methodName, string $cacheDir): ConfigCacheInterface
    {
        $filename = sprintf('/solido_attributes/%s/%s.php', strtr($className, ['/' => '', '\\' => '']), $methodName);

        return $this->cacheFactory->cache($cacheDir . $filename, function (ConfigCacheInterface $cache) use ($className, $methodName): void {
            $object = new ReflectionClass($className);
            $method = $object->getMethod($methodName);

            $classConfigurations = $this->getConfigurationsFromAttributes($this->getClassAttributes($object));
            $methodConfigurations = $this->getConfigurationsFromAttributes($this->getMethodAttributes($method));

            $exported = VarExporter::export([$classConfigurations, $methodConfigurations]);
            $cache->write('<?php return ' . $exported . ';', [new ReflectionClassResource($object)]);
        });
    }

    /**
     * @phpstan-param class-string $className
     *
     * @return ConfigurationInterface[][]
     * @phpstan-return array{0: array<string, ConfigurationInterface|ConfigurationInterface[]>, 1: array<string, ConfigurationInterface|ConfigurationInterface[]>}
     */
    private function getConfigurations(string $cacheDir, string $className, string $methodName): array
    {
        if (! class_exists(VarExporter::class)) {
            throw new RuntimeException('Symfony VarExporter needs to be installed. Please run composer require symfony/var-exporter');
        }

        $cache = $this->getConfigCache($className, $methodName, $cacheDir);

        return require $cache->getPath();
    }

    /**
     * @param object[] $annotations
     *
     * @return ConfigurationInterface[]|ConfigurationInterface[][]
     */
    private function getConfigurationsFromAttributes(array $annotations): array
    {
        /** @phpstan-var array<string, ConfigurationInterface|ConfigurationInterface[]> $configurations */
        $configurations = [];
        foreach ($annotations as $configuration) {
            if (! ($configuration instanceof ConfigurationInterface)) {
                continue;
            }

            $index = '_' . $configuration->getAliasName();
            if (isset($configurations[$index])) {
                throw new LogicException(sprintf('Multiple "%s" annotations are not allowed.', $configuration->getAliasName()));
            }

            $configurations[$index] = $configuration;
        }

        return $configurations;
    }

    /** @return object[] */
    private function getClassAttributes(ReflectionClass $object): array
    {
        $annotations = $this->reader !== null ? $this->reader->getClassAnnotations($object) : [];
        if (PHP_VERSION_ID >= 80000) {
            $attributes = $object->getAttributes(ConfigurationInterface::class, ReflectionAttribute::IS_INSTANCEOF);
            array_push(
                $annotations,
                ...array_map(static fn (ReflectionAttribute $attribute) => $attribute->newInstance(), $attributes),
            );
        }

        return $annotations;
    }

    /** @return object[] */
    private function getMethodAttributes(ReflectionMethod $method): array
    {
        $annotations = $this->reader !== null ? $this->reader->getMethodAnnotations($method) : [];
        if (PHP_VERSION_ID >= 80000) {
            $attributes = $method->getAttributes(ConfigurationInterface::class, ReflectionAttribute::IS_INSTANCEOF);
            array_push(
                $annotations,
                ...array_map(static fn (ReflectionAttribute $attribute) => $attribute->newInstance(), $attributes),
            );
        }

        return $annotations;
    }
}
