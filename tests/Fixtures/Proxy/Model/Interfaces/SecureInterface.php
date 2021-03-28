<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\Proxy\Model\Interfaces;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Solido\Symfony\Annotation\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

interface SecureInterface
{
    /**
     * @Route("/routed-with-security")
     * @View(statusCode=Response::HTTP_CREATED)
     * @Security("is_granted(false)")
     */
    public function routed(Request $request): self;
}
