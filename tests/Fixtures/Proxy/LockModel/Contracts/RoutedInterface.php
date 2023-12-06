<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\Proxy\LockModel\Contracts;

use Solido\Symfony\Annotation\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

interface RoutedInterface
{
    #[Route("/routed-and-locked")]
    #[View(statusCode: Response::HTTP_ACCEPTED)]
    public function routed(Request $request): self;
}
