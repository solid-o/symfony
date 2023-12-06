<?php

declare(strict_types=1);

namespace Solido\Symfony\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Lock
{
    public function __construct(public string $expression)
    {
    }
}
