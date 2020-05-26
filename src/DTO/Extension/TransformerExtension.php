<?php

declare(strict_types=1);

namespace Solido\Symfony\DTO\Extension;

use Solido\DataTransformers\TransformerExtension as BaseExtension;
use Solido\DtoManagement\Proxy\Builder\ProxyBuilder;
use function Safe\sprintf;

class TransformerExtension extends BaseExtension
{
    use SubscribedServicesGeneratorTrait;

    public function extend(ProxyBuilder $proxyBuilder): void
    {
        $this->builder = $proxyBuilder;
        parent::extend($proxyBuilder);

        unset($this->builder);
    }

    protected function generateCode(string $transformer, string $parameterName): string
    {
        $this->addServices([$transformer => $transformer]);

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
}
