<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\PolicyChecker\Controller;

use Solido\Symfony\Tests\Fixtures\Proxy\SemVerModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TestController extends AbstractController
{
    public function listFoo(): Response
    {
        return new JsonResponse([]);
    }
}
