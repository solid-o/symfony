<?php

declare(strict_types=1);

namespace Symfony\Component\Routing\Loader {
    if (!class_exists(AttributeClassLoader::class)) {
        abstract class AttributeClassLoader extends AnnotationClassLoader {
            public function __construct(string $env = null)
            {
                parent::__construct(null, $env);
            }
        }
    }
}
