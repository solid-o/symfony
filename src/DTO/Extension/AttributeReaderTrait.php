<?php

declare(strict_types=1);

namespace Solido\Symfony\DTO\Extension;

use ReflectionMethod;
use ReflectionProperty;
use Reflector;

trait AttributeReaderTrait
{
    /**
     * @param ReflectionMethod|ReflectionProperty $reflector
     * @phpstan-param class-string $attributeClass
     */
    private function getAttribute(Reflector $reflector, string $attributeClass): object|null
    {
        foreach ($reflector->getAttributes($attributeClass) as $attribute) {
            return $attribute->newInstance();
        }

        return null;
    }
}
