<?php

declare(strict_types=1);

namespace Solido\Symfony\Serialization\View;

use Stringable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use function is_object;

class Context
{
    private UserInterface|null $user;

    public function __construct(private Request $request, TokenStorageInterface|null $tokenStorage = null)
    {
        $token = $tokenStorage?->getToken();

        if ($token === null || ! is_object($token->getUser()) || $token->getUser() instanceof Stringable) {
            $this->user = null;
        } else {
            $this->user = $token->getUser();
        }
    }

    public static function create(Request $request, TokenStorageInterface|null $tokenStorage = null): self
    {
        return new self($request, $tokenStorage);
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getUser(): UserInterface|null
    {
        return $this->user;
    }
}
