<?php

declare(strict_types=1);

namespace Solido\Symfony\DataTransformer;

use Solido\DataTransformers\Exception\TransformationFailedException as SolidoTransformationFailedException;
use Solido\DataTransformers\TransformerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

use function is_int;

class FormTransformerAdapter implements DataTransformerInterface
{
    private TransformerInterface $transformer;

    public function __construct(TransformerInterface $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * Always return null, solido transformers are one-way.
     *
     * @param mixed $value
     *
     * @return null
     */
    public function transform($value)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        try {
            return $this->transformer->transform($value);
        } catch (SolidoTransformationFailedException $exception) {
            $code = $exception->getCode();
            if (! is_int($code)) {
                $code = 0;
            }

            throw new TransformationFailedException(
                'Transformation failed: ' . $exception->getMessage(),
                $code,
                $exception
            );
        }
    }
}
