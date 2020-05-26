<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\Proxy\Transformer;

use Solido\DataTransformers\TransformerInterface;

class TestTransform implements TransformerInterface
{
    public function transform($value)
    {
        return \strtoupper($value);
    }
}
