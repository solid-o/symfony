<?php

declare(strict_types=1);

namespace Solido\Symfony\VarDumper;

use Solido\DtoManagement\Proxy\ProxyInterface;
use Symfony\Component\VarDumper\Cloner\Stub;

use function get_parent_class;
use function strpos;

final class ProxyCaster
{
    /**
     * @param array<string, mixed> $a
     *
     * @return array<string, mixed>
     */
    public static function castDtoProxy(ProxyInterface $proxy, array $a, Stub $stub, bool $isNested): array
    {
        $original = $a;
        $prefix = "\0" . $proxy::class . "\0";
        $valueHolder = null;

        foreach ($a as $key => $value) {
            if (strpos($key, $prefix . 'valueHolder') === 0) {
                $valueHolder = $value;
                unset($a[$key]);
            }

            if (strpos($key, $prefix . '_container_') !== 0) {
                continue;
            }

            unset($a[$key]);
        }

        if ($valueHolder === null) {
            return $original;
        }

        $a += (array) $valueHolder;
        $stub->class = get_parent_class($proxy) . ' (proxy)';

        return $a;
    }
}
