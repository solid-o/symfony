<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\Proxy;

use Kcs\Serializer\Bundle\SerializerBundle;
use Solido\Symfony\SolidoBundle;
use Solido\Symfony\Tests\Fixtures\TestKernel;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends TestKernel
{
    /**
     * {@inheritdoc}
     */
    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new SolidoBundle(),
            new DebugBundle(),
            new SecurityBundle(),
            new SerializerBundle(),
            new AppBundle(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__.'/config.yml');
    }
}
