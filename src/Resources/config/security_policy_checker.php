<?php

declare(strict_types=1);

use Solido\PolicyChecker\PolicyChecker;
use Solido\PolicyChecker\PolicyCheckerInterface;
use Solido\Symfony\Security\Voter\PolicyVoter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->alias(PolicyCheckerInterface::class, PolicyChecker::class)
        ->alias('solido.security.policy_checker', PolicyCheckerInterface::class)
        ->set(PolicyChecker::class)
            ->args([tagged_iterator('solido.security.policy_checker.voter')])

        ->set(PolicyVoter::class)
            ->args([
                service('solido.security.policy_checker'),
                service('request_stack'),
            ])
            ->tag('security.voter', ['priority' => 15]);
};
