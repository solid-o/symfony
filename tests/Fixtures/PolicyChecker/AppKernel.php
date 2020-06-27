<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\PolicyChecker;

use Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle;
use Solido\Symfony\SolidoBundle;
use Solido\Symfony\Tests\Fixtures\TestKernel;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends TestKernel
{
    /**
     * {@inheritdoc}
     */
    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new SensioFrameworkExtraBundle(),
            new SecurityBundle(),
            new SolidoBundle(),
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
