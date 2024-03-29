<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\Proxy\Model\Interfaces;

use Solido\PatchManager\PatchManagerInterface;
use Solido\Symfony\Annotation\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

interface RoutedInterface
{
    #[Route("/routed-dto")]
    #[View(statusCode: Response::HTTP_CREATED)]
    public function routed(PatchManagerInterface $patchManager): self;

    #[Route('/routed-with-attribute')]
    #[View(statusCode: Response::HTTP_CREATED)]
    public function routedWithAttribute(PatchManagerInterface $patchManager): self;
}
