<?php

declare(strict_types=1);

namespace Solido\Symfony\Tests;

use Symfony\Component\Filesystem\Filesystem;

trait WebTestCaseTrait
{
    /**
     * {@inheritdoc}
     */
    public static function tearDownAfterClass(): void
    {
        self::bootKernel();
        self::ensureKernelShutdown();

        $fs = new Filesystem();
        $fs->remove(static::$kernel->getBuildDir());
        $fs->remove(static::$kernel->getCacheDir());
        $fs->remove(static::$kernel->getLogDir());

        static::$kernel = null;
    }
}
