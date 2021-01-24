<?php

declare(strict_types=1);

namespace Solido\Symfony\DTO;

use Solido\DtoManagement\Finder\ServiceLocatorRegistryInterface;
use Solido\DtoManagement\InterfaceResolver\Resolver as BaseResolver;
use Symfony\Component\HttpFoundation\RequestStack;

class Resolver extends BaseResolver
{
    private ?RequestStack $requestStack;

    public function __construct(ServiceLocatorRegistryInterface $registry, ?RequestStack $requestStack = null)
    {
        parent::__construct($registry);
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $interface, $version = null)
    {
        if ($version === null && $this->requestStack !== null) {
            $version = $this->requestStack->getCurrentRequest();
        }

        return parent::resolve($interface, $version);
    }
}
