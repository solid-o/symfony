<?php

declare(strict_types=1);

namespace Solido\Symfony\DTO\Extension;

use Doctrine\Common\Annotations\Reader;
use ReflectionMethod;
use ReflectionProperty;
use Reflector;
use Solido\Symfony\Annotation\Security;

use function assert;

use const PHP_VERSION_ID;

trait SecurityExtensionTrait
{
    private ?Reader $reader;

    /**
     * @param ReflectionMethod|ReflectionProperty $reflector
     */
    private function getAttribute(Reflector $reflector): ?Security
    {
        if (PHP_VERSION_ID >= 80000) {
            foreach ($reflector->getAttributes(Security::class) as $attribute) {
                $instance = $attribute->newInstance();
                assert($instance instanceof Security);

                return $instance;
            }
        }

        if ($this->reader === null) {
            return null;
        }

        $annotation = null;
        if ($reflector instanceof ReflectionProperty) {
            $annotation = $this->reader->getPropertyAnnotation($reflector, Security::class);
        } elseif ($reflector instanceof ReflectionMethod) {
            $annotation = $this->reader->getMethodAnnotation($reflector, Security::class);
        }

        return $annotation;
    }
}
