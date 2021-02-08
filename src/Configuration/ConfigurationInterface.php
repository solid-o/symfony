<?php

declare(strict_types=1);

namespace Solido\Symfony\Configuration;

interface ConfigurationInterface
{
    /**
     * Returns the alias name for an annotated configuration.
     */
    public function getAliasName(): string;
}
