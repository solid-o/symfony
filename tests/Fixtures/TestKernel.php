<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures;

use Psr\Log\NullLogger;
use ReflectionClass;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use function dirname;

abstract class TestKernel extends Kernel
{
    protected function build(ContainerBuilder $container): void
    {
        $container->register('logger', NullLogger::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function getKernelParameters(): array
    {
        return parent::getKernelParameters() + [
            'kernel.root_dir' => $this->getRootDir(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir(): string
    {
        return $this->getRootDir().'/var/cache/'.$this->environment;
    }

    public function getBuildDir(): string
    {
        return $this->getRootDir().'/var/build/'.$this->environment;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir(): string
    {
        return $this->getRootDir().'/logs/'.$this->environment;
    }

    private function getRootDir(): string
    {
        return dirname((new ReflectionClass(static::class))->getFileName());
    }
}
