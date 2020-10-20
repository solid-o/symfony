<?php

declare(strict_types=1);

namespace Solido\Symfony\Urn;

use Solido\Common\Urn\UrnConverter;
use Solido\Common\Urn\UrnConverterInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;

class UrnClassCacheWarmer extends CacheWarmer
{
    private UrnConverterInterface $urnConverter;

    public function __construct(UrnConverterInterface $urnConverter)
    {
        $this->urnConverter = $urnConverter;
    }

    public function isOptional(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp(string $cacheDir): array
    {
        if (! $this->urnConverter instanceof UrnConverter) {
            return [];
        }

        $this->urnConverter->getUrnClassMap($cacheDir);

        return [
            $cacheDir . '/urn/class_to_object.php',
        ];
    }
}
