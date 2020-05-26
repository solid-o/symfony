<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\Proxy\Controller;

use Solido\Symfony\Tests\Fixtures\Proxy\Model\Interfaces\UserInterface;
use Solido\Symfony\Tests\Fixtures\Proxy\SemVerModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\VarDumper\Test\VarDumperTestTrait;

class TestController extends AbstractController
{
    use VarDumperTestTrait;

    public function indexAction(UserInterface $user): Response
    {
        $user->foobar = 'ciao';
    }

    public function protectedAction(UserInterface $user): Response
    {
        $user->foobar = 'ciao';

        return new Response($this->getDump($user->foobar));
    }

    public function unavailableAction(UserInterface $user): Response
    {
        return new Response($this->getDump($user->getTest()));
    }

    public function semverAction(SemVerModel\Interfaces\UserInterface $user): Response
    {
        return new Response($this->getDump($user->getFoo()));
    }
}
