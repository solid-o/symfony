<?php

declare(strict_types=1);

namespace Solido\Symfony\DTO\Proxy;

use Closure;
use Laminas\Code\Generator\ClassGenerator;
use ProxyManager\Configuration;
use ProxyManager\GeneratorStrategy\GeneratorStrategyInterface;
use ReflectionClass;
use Symfony\Component\Config\ConfigCacheFactory;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Config\Resource\ReflectionClassResource;
use function restore_error_handler;
use function set_error_handler;
use function str_replace;
use function trim;
use const DIRECTORY_SEPARATOR;

class CacheWriterGeneratorStrategy implements GeneratorStrategyInterface
{
    private Configuration $configuration;
    private Closure $emptyErrorHandler;
    private bool $debug;

    public function __construct(Configuration $configuration, bool $debug)
    {
        $this->configuration = $configuration;
        $this->debug = $debug;
        $this->emptyErrorHandler = static function () {
        };
    }

    public function generate(ClassGenerator $classGenerator): string
    {
        $className = trim($classGenerator->getNamespaceName(), '\\') . '\\' . trim($classGenerator->getName(), '\\');
        $fileName = $this->configuration->getProxiesTargetDir() . DIRECTORY_SEPARATOR . str_replace('\\', '', $className) . '.php';

        $code = $classGenerator->generate();

        $cacheFactory = new ConfigCacheFactory($this->debug);
        $cache = $cacheFactory->cache($fileName, static function (ConfigCacheInterface $cache) use ($code, $classGenerator) {
            /** @phpstan-var class-string $superClass */
            $superClass = $classGenerator->getExtendedClass();
            $cache->write('<?php ' . $code, [new ReflectionClassResource(new ReflectionClass($superClass))]);
        });

        set_error_handler($this->emptyErrorHandler);
        try {
            require $cache->getPath();
        } finally {
            restore_error_handler();
        }

        return $code;
    }
}
