<?php

declare(strict_types=1);

namespace Solido\Symfony\DTO\Extension;

use Doctrine\Common\Annotations\Reader;
use LogicException;
use Solido\DataTransformers\TransformerExtension as BaseExtension;
use Solido\DataTransformers\TransformerInterface;
use Solido\DtoManagement\Proxy\Builder\ProxyBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use function class_exists;
use function is_subclass_of;
use function Safe\sprintf;

class TransformerExtension extends BaseExtension
{
    use SubscribedServicesGeneratorTrait;

    private ContainerBuilder $container;

    public function __construct(ContainerBuilder $container, ?Reader $reader = null)
    {
        parent::__construct($reader);

        $this->container = $container;
    }

    public function extend(ProxyBuilder $proxyBuilder): void
    {
        $this->builder = $proxyBuilder;
        parent::extend($proxyBuilder);

        unset($this->builder);
    }

    protected function generateCode(string $transformer, string $parameterName): string
    {
        if ($this->container->has($transformer)) {
            $definition = $this->container->findDefinition($transformer);
            $class = $definition->getClass() ?? $transformer;

            $this->addServices([$transformer => $class]);
        } else {
            $this->addServices([$transformer => $transformer]);
        }

        return sprintf('
try {
    $transformer = $this->%s->get(\'%s\');
    $%s = $transformer->transform($%s);
} catch (\Solido\DataTransformers\Exception\TransformationFailedException $exception) {
    throw new \Symfony\Component\Form\Exception\TransformationFailedException(
        \'Transformation failed: \'.$exception->getMessage(),
        $exception->getCode(),
        $exception,
        $exception->getInvalidMessage(),
        $exception->getInvalidMessageParameters()
    );
}
', $this->getContainerName(), $transformer, $parameterName, $parameterName);
    }

    protected function assertExists(string $transformer): void
    {
        if ($this->container->has($transformer)) {
            $definition = $this->container->findDefinition($transformer);
            $class = $definition->getClass() ?? $transformer;

            if (! class_exists($class) || ! is_subclass_of($class, TransformerInterface::class)) {
                throw new LogicException(sprintf('Transformer class "%s" does not exist or does not implement "%s".', $class, TransformerInterface::class));
            }

            return;
        }

        parent::assertExists($transformer);
    }
}
