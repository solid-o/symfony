<?php

declare(strict_types=1);

namespace Solido\Symfony\Urn;

use Solido\Common\Urn\UrnConverter;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;

class UrnClassCacheWarmer extends CacheWarmer
{
    private UrnConverter $urnConverter;

    public function __construct(UrnConverter $urnConverter)
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
        $this->urnConverter->getUrnClassMap($cacheDir);

        return [
            $cacheDir . '/urn/class_to_object.php',
        ];
    }
}
