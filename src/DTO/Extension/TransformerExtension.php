<?php

declare(strict_types=1);

namespace Solido\Symfony\DTO\Extension;

use Solido\DataTransformers\TransformerExtension as BaseExtension;
use Solido\DtoManagement\Proxy\Builder\ProxyBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function sprintf;

class TransformerExtension extends BaseExtension
{
    use SubscribedServicesGeneratorTrait;

    public function __construct(private readonly ContainerBuilder $container)
    {
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
$transformer = $this->%s->get(\'%s\');
$%s = $transformer->transform($%s);
', $this->getContainerName(), $transformer, $parameterName, $parameterName);
    }

    protected function assertExists(string $transformer): void
    {
        if ($this->container->has($transformer)) {
            $definition = $this->container->findDefinition($transformer);
            $transformer = $definition->getClass() ?? $transformer;
        }

        parent::assertExists($transformer);
    }
}
