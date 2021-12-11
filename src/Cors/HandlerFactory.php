<?php

declare(strict_types=1);

namespace Solido\Symfony\Cors;

use Solido\Cors\RequestHandlerInterface;

use function Safe\preg_match;

class HandlerFactory
{
    /**
     * @var array<string, mixed>
     * @phpstan-var array{paths: array<array{host?: string, path: string, factory: callable(): RequestHandlerInterface}>, factory: callable(): RequestHandlerInterface}
     */
    private array $configuration;

    /**
     * @param array<string, mixed> $configuration
     * @phpstan-param array{paths: array<array{host?: string, path: string, factory: callable(): RequestHandlerInterface}>, factory: callable(): RequestHandlerInterface} $configuration
     */
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Creates a new RequestHandler based on passed path.
     */
    public function factory(string $path, string $host): ?RequestHandlerInterface
    {
        foreach ($this->configuration['paths'] as $config) {
            if (isset($config['host']) && ! preg_match('#' . $config['host'] . '#', $host)) {
                continue;
            }

            $pathRegex = $config['path'];
            if (! preg_match('#' . $pathRegex . '#', $path)) {
                continue;
            }

            return $config['factory']();
        }

        return $this->configuration['factory']();
    }
}
