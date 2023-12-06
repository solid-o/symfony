<?php

declare(strict_types=1);

namespace Solido\Symfony\Urn;

use Solido\Common\Urn\UrnConverter;
use Solido\Common\Urn\UrnConverterInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;

class UrnClassCacheWarmer extends CacheWarmer
{
    public function __construct(
        private readonly UrnConverterInterface $urnConverter,
        private readonly string|null $buildDir,
    ) {
    }

    public function isOptional(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function warmUp(string $cacheDir, string|null $buildDir = null): array
    {
        if (! $this->urnConverter instanceof UrnConverter) {
            return [];
        }

        $this->urnConverter->getUrnClassMap($buildDir ?? $this->buildDir ?? $cacheDir);

        return [
            $cacheDir . '/urn/class_to_object.php',
        ];
    }
}
