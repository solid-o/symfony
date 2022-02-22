<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\PolicyChecker\User;

use Solido\Common\Urn\Urn;
use Solido\Common\Urn\UrnGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface, UrnGeneratorInterface
{
    public function getRoles(): array
    {
        return [];
    }

    public function getPassword(): ?string
    {
        return null;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function getUsername(): string
    {
        return 'test_username';
    }

    public function eraseCredentials(): void
    {
    }

    public function getUrn(): Urn
    {
        return new Urn('user-id', 'user');
    }

    /**
     * @inheritDoc
     */
    public function getUserIdentifier(): string
    {
        return $this->getUsername();
    }
}
