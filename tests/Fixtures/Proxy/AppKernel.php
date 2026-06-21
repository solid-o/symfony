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
        $bundles = [
            new FrameworkBundle(),
            new SolidoBundle(),
            new SecurityBundle(),
            new SerializerBundle(),
            new AppBundle(),
        ];

        if ($this->isDebug()) {
            $bundles[] = new DebugBundle();
        }

        return $bundles;
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__.'/config.yml');
    }
}
