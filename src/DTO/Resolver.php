<?php

declare(strict_types=1);

namespace Solido\Symfony\DTO;

use Solido\DtoManagement\Finder\ServiceLocatorRegistryInterface;
use Solido\DtoManagement\InterfaceResolver\Resolver as BaseResolver;
use Symfony\Component\HttpFoundation\RequestStack;

class Resolver extends BaseResolver
{
    public function __construct(ServiceLocatorRegistryInterface $registry, private RequestStack|null $requestStack = null)
    {
        parent::__construct($registry);
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(string $interface, mixed $version = null)
    {
        if ($version === null && $this->requestStack !== null) {
            $version = $this->requestStack->getCurrentRequest();
        }

        return parent::resolve($interface, $version);
    }
}
