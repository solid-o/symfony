<?php

declare(strict_types=1);

namespace Solido\Symfony\EventListener;

use Doctrine\Common\Annotations\Reader;
use LogicException;
use ReflectionClass;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;
use Solido\DtoManagement\Proxy\ProxyInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use UnexpectedValueException;

use function array_key_exists;
use function array_keys;
use function array_merge;
use function get_class;
use function get_parent_class;
use function is_array;
use function Safe\sprintf;

/**
 * The ControllerListener class parses annotation blocks located in
 * controller classes.
 */
class ControllerListener implements EventSubscriberInterface
{
    private Reader $reader;

    public function __construct(Reader $reader)
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
        if ($className === null) {
            return;
        }

        /** @phpstan-var array{0: object, 1: string} $controller */
        $controller = $event->getController();
        $object = new ReflectionClass($className);
        $method = $object->getMethod($controller[1]);

        $classConfigurations = $this->getConfigurations($this->reader->getClassAnnotations($object));
        $methodConfigurations = $this->getConfigurations($this->reader->getMethodAnnotations($method));

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
            if ($configuration->allowArray()) {
                // @phpstan-ignore-next-line
                $configurations[$index][] = $configuration;
            } elseif (! isset($configurations[$index])) {
                $configurations[$index] = $configuration;
            } else {
                throw new LogicException(sprintf('Multiple "%s" annotations are not allowed.', $configuration->getAliasName()));
            }
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
}
