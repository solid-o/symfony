<?php

declare(strict_types=1);

namespace Solido\Symfony\Annotation;

use Attribute;
use Solido\Symfony\Configuration\ConfigurationInterface;

#[Attribute]
class Sunset implements ConfigurationInterface
{
    public function __construct(public string $date)
    {
    }

    public function getAliasName(): string
    {
        return 'solido_sunset';
    }
}
