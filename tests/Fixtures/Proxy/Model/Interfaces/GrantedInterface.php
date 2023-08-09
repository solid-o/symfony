<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\Proxy\Model\Interfaces;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Solido\Symfony\Annotation\View;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

interface GrantedInterface
{
    #[Route("/routed-with-is-granted")]
    #[View(statusCode: Response::HTTP_CREATED)]
    #[IsGranted(new Expression("is_granted(false)"))]
    public function routed(Request $request): self;
}
