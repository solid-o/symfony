<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\Proxy\Model\Interfaces;

use Solido\PatchManager\PatchManagerInterface;
use Solido\Symfony\Annotation\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route("/routed-invokable")]
#[View(statusCode: Response::HTTP_ACCEPTED)]
interface RoutedInvokableInterface
{
    public function __invoke(PatchManagerInterface $patchManager): self;
}
