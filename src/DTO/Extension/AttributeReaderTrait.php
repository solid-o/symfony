<?php

declare(strict_types=1);

namespace Solido\Symfony\DTO\Extension;

use Doctrine\Common\Annotations\Reader;
use ReflectionMethod;
use ReflectionProperty;
use Reflector;

use const PHP_VERSION_ID;

trait AttributeReaderTrait
{
    private ?Reader $reader;

    /**
     * @param ReflectionMethod|ReflectionProperty $reflector
     * @phpstan-param class-string $attributeClass
     */
    private function getAttribute(Reflector $reflector, string $attributeClass): ?object
    {
        if (PHP_VERSION_ID >= 80000) {
            foreach ($reflector->getAttributes($attributeClass) as $attribute) {
                return $attribute->newInstance();
            }
        }

        if ($this->reader === null) {
            return null;
        }

        $annotation = null;
        if ($reflector instanceof ReflectionProperty) {
            $annotation = $this->reader->getPropertyAnnotation($reflector, $attributeClass);
        } elseif ($reflector instanceof ReflectionMethod) {
            $annotation = $this->reader->getMethodAnnotation($reflector, $attributeClass);
        }

        return $annotation;
    }
}
