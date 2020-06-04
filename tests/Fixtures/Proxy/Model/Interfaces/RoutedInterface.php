<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\Proxy\Model\Interfaces;

use Solido\Symfony\Annotation\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

interface RoutedInterface
{
    /**
     * @Route("/routed-dto")
     * @View(statusCode=Response::HTTP_CREATED)
     */
    public function routed(): self;
}
