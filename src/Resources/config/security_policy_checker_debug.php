<?php

declare(strict_types=1);

use Solido\PolicyChecker\DataCollector\PolicyCheckerDataCollector;
use Solido\PolicyChecker\TraceablePolicyChecker;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(TraceablePolicyChecker::class)
            ->decorate('solido.security.policy_checker')
            ->args([
                service(TraceablePolicyChecker::class . '.inner'),
                service('logger')->ignoreOnInvalid(),
            ])

        ->set(PolicyCheckerDataCollector::class)
            ->decorate('data_collector.security')
            ->args([
                service(PolicyCheckerDataCollector::class . '.inner'),
                service(TraceablePolicyChecker::class)->ignoreOnInvalid(),
            ])
            ->tag('monolog.logger', ['channel' => 'security_policy'])
            ->tag('data_collector', ['template' => '@Solido/data_collector/security.html.twig', 'id' => 'security', 'priority' => 260]);
};
