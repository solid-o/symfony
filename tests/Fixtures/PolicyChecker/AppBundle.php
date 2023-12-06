<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\PolicyChecker;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel;

class AppBundle extends Bundle implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        if (Kernel::MAJOR_VERSION < 7) {
            $container->prependExtensionConfig('security', [
                'enable_authenticator_manager' => true,
            ]);
        }
    }
}
