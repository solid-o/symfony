<?php

declare(strict_types=1);

namespace Solido\Symfony\Annotation;

use Attribute;
use Solido\Symfony\Configuration\ConfigurationInterface;
use Symfony\Component\HttpFoundation\Response;

#[Attribute]
class View implements ConfigurationInterface
{
    /** @param string[] $groups */
    public function __construct(
        public int $statusCode = Response::HTTP_OK,
        public string|null $groupsProvider = null,
        public string|null $serializationType = null,
        public array|null $groups = null,
    ) {
    }

    public function getAliasName(): string
    {
        return 'solido_view';
    }
}
