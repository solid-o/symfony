<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\Proxy\Model\Interfaces;

use Symfony\Component\Routing\Annotation\Route;

interface RoutedInterface
{
    /**
     * @Route("/routed-dto")
     */
    public function routed(): self;
}
