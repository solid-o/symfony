<?php

declare(strict_types=1);

namespace Solido\Symfony\Security\Voter;

use Solido\Common\Urn\UrnGeneratorInterface;
use Solido\PolicyChecker\PolicyCheckerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

use function array_key_first;
use function count;
use function method_exists;

class PolicyVoter implements VoterInterface
{
    public function __construct(
        private readonly PolicyCheckerInterface $policyChecker,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        $user = $this->getUser($token);
        if ($user === null || ($subject !== null && ! $subject instanceof UrnGeneratorInterface)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        // Subject could be null (in case of collection add operation)
        $resource = $subject?->getUrn();
        $action = count($attributes) > 0 ? $attributes[array_key_first($attributes)] : '';

        return $this->policyChecker->check($user->getUrn(), $action, $resource, $this->getContext()) ?
            VoterInterface::ACCESS_GRANTED :
            VoterInterface::ACCESS_DENIED;
    }

    /** @return array<string, string|string[]> */
    protected function getContext(): array
    {
        if (method_exists($this->requestStack, 'getMainRequest')) { /* @phpstan-ignore-line */
            $request = $this->requestStack->getMainRequest();
        } else {
            $request = $this->requestStack->getMasterRequest(); /* @phpstan-ignore-line */
        }

        if ($request === null) {
            return [];
        }

        $context['sourceIP'] = $request->getClientIp() ?? '';

        return $context;
    }

    private function getUser(TokenInterface $token): UrnGeneratorInterface|null
    {
        $user = $token->getUser();
        if (! $user instanceof UrnGeneratorInterface) {
            return null;
        }

        return $user;
    }
}
