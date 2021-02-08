<?php

declare(strict_types=1);

namespace Solido\Symfony\EventListener;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Persistence\Proxy;
use LogicException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Solido\DtoManagement\Proxy\ProxyInterface;
use Solido\Symfony\Configuration\ConfigurationInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
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

use const PHP_VERSION_ID;

/**
 * The ControllerListener class parses annotation blocks located in
 * controller classes.
 */
class ControllerListener implements EventSubscriberInterface
{
    private ?Reader $reader;

    public function __construct(?Reader $reader = null)
    {
        $this->reader = $reader;
    }

    /**
     * Modifies the Request object to apply configuration information found in
     * controllers annotations like the template to render or HTTP caching
     * configuration.
     */
    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $className = $request->attributes->get('_solido_dto_interface');

        /** @phpstan-var object|array{0: object, 1: string} $controller */
        $controller = $event->getController();
        if ($className === null) {
            if (! is_array($controller) && method_exists($controller, '__invoke')) {
                $controller = [$controller, '__invoke'];
            }

            if (is_array($controller)) {
                $className = self::getRealClass(get_class($controller[0]));
            }
        }

        /** @phpstan-var array{0: object, 1: string} $controller */
        $object = new ReflectionClass($className);
        $method = $object->getMethod($controller[1]);

        $classConfigurations = $this->getConfigurations($this->getClassAttributes($object));
        $methodConfigurations = $this->getConfigurations($this->getMethodAttributes($method));

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

        $controllerName = sprintf(
            '.solido.dto.%s.%s:%s',
            $className,
            $controller[0] instanceof ProxyInterface ? get_parent_class($controller[0]) : get_class($controller[0]),
            $method->name,
        );

        $request->attributes->set('_controller', $controllerName);
    }

    /**
     * @param object[] $annotations
     *
     * @return ConfigurationInterface[]|ConfigurationInterface[][]
     */
    private function getConfigurations(array $annotations): array
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER => 'onKernelController'];
    }

    private static function getRealClass(string $class): string
    {
        if (class_exists(Proxy::class)) {
            $pos = strrpos($class, '\\' . Proxy::MARKER . '\\');
            if ($pos === false) {
                return $class;
            }

            return substr($class, $pos + Proxy::MARKER_LENGTH + 2);
        }

        return $class;
    }

    /**
     * @return object[]
     */
    private function getClassAttributes(ReflectionClass $object): array
    {
        $annotations = $this->reader !== null ? $this->reader->getClassAnnotations($object) : [];
        if (PHP_VERSION_ID >= 80000) {
            $attributes = $object->getAttributes(ConfigurationInterface::class, ReflectionAttribute::IS_INSTANCEOF);
            array_push(
                $annotations,
                ...array_map(static fn (ReflectionAttribute $attribute) => $attribute->newInstance(), $attributes)
            );
        }

        return $annotations;
    }

    /**
     * @return object[]
     */
    private function getMethodAttributes(ReflectionMethod $method): array
    {
        $annotations = $this->reader !== null ? $this->reader->getMethodAnnotations($method) : [];
        if (PHP_VERSION_ID >= 80000) {
            $attributes = $method->getAttributes(ConfigurationInterface::class, ReflectionAttribute::IS_INSTANCEOF);
            array_push(
                $annotations,
                ...array_map(static fn (ReflectionAttribute $attribute) => $attribute->newInstance(), $attributes)
            );
        }

        return $annotations;
    }
}
