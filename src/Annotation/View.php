<?php

declare(strict_types=1);

namespace Solido\Symfony\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Annotation()
 */
class View implements ConfigurationInterface
{
    public int $statusCode;
    public ?string $groupsProvider;
    public ?string $serializationType;

    /** @var string[] */
    public array $groups;

    public function __construct()
    {
        $this->statusCode = Response::HTTP_OK;
        $this->groups = [];
        $this->groupsProvider = null;
        $this->serializationType = null;
    }

    public function getAliasName(): string
    {
        return 'rest_view';
    }

    public function allowArray(): bool
    {
        return false;
    }
}
