<?php

declare(strict_types=1);

namespace Solido\Symfony\EventListener\Compat\SensioFrameworkExtraBundle;

use Doctrine\Common\Annotations\Reader;
use LogicException;
use ReflectionAttribute;
use ReflectionClass;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use UnexpectedValueException;

use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function interface_exists;
use function is_array;
use function is_object;
use function is_string;
use function method_exists;
use function Safe\sprintf;

use const PHP_VERSION_ID;

class ControllerListener implements EventSubscriberInterface
{
    private ?Reader $reader;

    public function __construct(?Reader $reader = null)
    {
        $this->reader = $reader;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (! interface_exists(ConfigurationInterface::class)) {
            return;
        }

        $request = $event->getRequest();
        $className = $request->attributes->get('_solido_dto_interface');
        if ($className === null) {
            return;
        }

        $controller = $event->getController();
        if ((is_object($controller) || is_string($controller)) && method_exists($controller, '__invoke')) {
            $controller = [$controller, '__invoke'];
        }

        if (! is_array($controller)) {
            return;
        }

        $object = new ReflectionClass($className);
        $method = $object->getMethod($controller[1]);

        if ($this->reader !== null) {
            $classConfigurations = $this->getConfigurations($this->reader->getClassAnnotations($object));
            $methodConfigurations = $this->getConfigurations($this->reader->getMethodAnnotations($method));
        } else {
            $classConfigurations = [];
            $methodConfigurations = [];
        }

        if (80000 <= PHP_VERSION_ID) {
            $classAttributes = array_map(
                static fn (ReflectionAttribute $attribute) => $attribute->newInstance(),
                $object->getAttributes(ConfigurationAnnotation::class, ReflectionAttribute::IS_INSTANCEOF)
            );
            $classConfigurations = array_merge($classConfigurations, $this->getConfigurations($classAttributes));

            $methodAttributes = array_map(
                static fn (ReflectionAttribute $attribute) => $attribute->newInstance(),
                $method->getAttributes(ConfigurationAnnotation::class, ReflectionAttribute::IS_INSTANCEOF)
            );
            $methodConfigurations = array_merge($methodConfigurations, $this->getConfigurations($methodAttributes));
        }

        $configurations = [];
        foreach (array_merge(array_keys($classConfigurations), array_keys($methodConfigurations)) as $key) {
            if (! array_key_exists($key, $classConfigurations)) {
                $configurations[$key] = $methodConfigurations[$key];
            } elseif (! array_key_exists($key, $methodConfigurations)) {
                $configurations[$key] = $classConfigurations[$key];
            } else {
                if (is_array($classConfigurations[$key])) {
                    if (! is_array($methodConfigurations[$key])) {
                        throw new UnexpectedValueException('Configurations should both be an array or both not be an array.');
                    }

                    $configurations[$key] = array_merge($classConfigurations[$key], $methodConfigurations[$key]);
                } else {
                    // method configuration overrides class configuration
                    $configurations[$key] = $methodConfigurations[$key];
                }
            }
        }

        $request = $event->getRequest();
        foreach ($configurations as $key => $attributes) {
            $request->attributes->set($key, $attributes);
        }
    }

    /**
     * @param object[] $annotations
     *
     * @return array<string, ConfigurationInterface|ConfigurationInterface[]>
     */
    private function getConfigurations(array $annotations): array
    {
        /** @var array<string, ConfigurationInterface|ConfigurationInterface[]> $configurations */
        $configurations = [];
        foreach ($annotations as $configuration) {
            if (! ($configuration instanceof ConfigurationInterface)) {
                continue;
            }

            $index = '_' . $configuration->getAliasName();
            if ($configuration->allowArray()) {
                /* @phpstan-ignore-next-line */
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
        if (! interface_exists(ConfigurationInterface::class)) {
            return [];
        }

        return [
            KernelEvents::CONTROLLER => ['onKernelController', -15],
        ];
    }
}
