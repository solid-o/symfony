<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\BodyConverter\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\VarDumper\Test\VarDumperTestTrait;

class TestController extends AbstractController
{
    use VarDumperTestTrait;

    public function indexAction(Request $request): Response
    {
        return new Response($this->getDump($request->request->all()));
    }
}
