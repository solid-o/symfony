<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\Proxy\Model\Interfaces;

use Solido\Symfony\Annotation\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

interface GrantedInterface
{
    #[Route("/routed-with-is-granted")]
    #[View(statusCode: Response::HTTP_CREATED)]
    #[IsGranted('false')]
    public function routed(Request $request): self;
}
