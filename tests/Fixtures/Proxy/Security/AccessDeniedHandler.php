<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\Proxy\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, AccessDeniedException $accessDeniedException): Response
    {
        return new Response($accessDeniedException->getMessage(), Response::HTTP_FORBIDDEN);
    }
}
