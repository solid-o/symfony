<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\PolicyChecker\User;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class UserAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        // TODO: Implement start() method.
    }

    public function supports(Request $request): bool
    {
        return true;
    }

    public function authenticate(Request $request): Passport
    {
        return new Passport(
            new UserBadge('', static fn () => new User()),
            new CustomCredentials(static fn () => true, []),
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return null;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }
}
