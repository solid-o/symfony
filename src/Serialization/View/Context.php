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
    private Request $request;
    private ?UserInterface $user;

    public function __construct(Request $request, ?TokenStorageInterface $tokenStorage = null)
    {
        $this->request = $request;
        $token = $tokenStorage !== null ? $tokenStorage->getToken() : null;

        if ($token === null || ! is_object($token->getUser()) || $token->getUser() instanceof Stringable) {
            $this->user = null;
        } else {
            $this->user = $token->getUser();
        }
    }

    public static function create(Request $request, ?TokenStorageInterface $tokenStorage = null): self
    {
        return new self($request, $tokenStorage);
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }
}
