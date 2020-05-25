<?php

declare(strict_types=1);

namespace Solido\Symfony\DTO\DataTransformer;

use Solido\DataTransformers\TransformerExtension as BaseExtension;
use function Safe\sprintf;

class TransformerExtension extends BaseExtension
{
    protected function generateCode(string $transformer): string
    {
        return sprintf('
try {
    %s;
} catch (\Solido\DataTransformers\Exception\TransformationFailedException $exception) {
    throw new \Symfony\Component\Form\Exception\TransformationFailedException(
        \'Transformation failed: \'.$exception->getMessage(),
        $exception->getCode(),
        $exception,
        $exception->getInvalidMessage(),
        $exception->getInvalidMessageParameters()
    );
}
', parent::generateCode($transformer));
    }
}
