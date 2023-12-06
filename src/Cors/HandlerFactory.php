<?php

declare(strict_types=1);

namespace Solido\Symfony\Cors;

use Solido\Cors\RequestHandlerInterface;

use function preg_match;

class HandlerFactory
{
    /**
     * @param array<string, mixed> $configuration
     * @phpstan-param array{paths: array<array{host?: string, path: string, factory: callable(): RequestHandlerInterface}>, factory: callable(): RequestHandlerInterface} $configuration
     */
    public function __construct(private readonly array $configuration)
    {
    }

    /**
     * Creates a new RequestHandler based on passed path.
     */
    public function factory(string $path, string $host): RequestHandlerInterface|null
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
