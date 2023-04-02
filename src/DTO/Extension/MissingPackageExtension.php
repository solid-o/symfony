<?php

declare(strict_types=1);

namespace Solido\Symfony\DTO\Extension;

use Solido\DtoManagement\Proxy\Builder\ProxyBuilder;
use Solido\DtoManagement\Proxy\Extension\ExtensionInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

use function Safe\sprintf;

/** @internal */
abstract class MissingPackageExtension implements ExtensionInterface
{
    private string $packageName;
    private string $extensionName;

    public function __construct(string $packageName, string $extensionName)
    {
        $this->packageName = $packageName;
        $this->extensionName = $extensionName;
    }

    public function extend(ProxyBuilder $proxyBuilder): void
    {
        throw new InvalidConfigurationException(sprintf('Package %s is required to use %s DTO extension', $this->packageName, $this->extensionName));
    }
}
