<?php

declare(strict_types=1);

namespace Solido\Symfony\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;

/**
 * @Annotation()
 */
class Sunset implements ConfigurationInterface
{
    /** @Required() */
    public string $date;

    public function getAliasName(): string
    {
        return 'rest_sunset';
    }

    public function allowArray(): bool
    {
        return false;
    }
}
