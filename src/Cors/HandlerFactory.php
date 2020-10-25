<?php

declare(strict_types=1);

namespace Solido\Symfony\Cors;

use Solido\Cors\RequestHandlerInterface;

use function Safe\preg_match;

class HandlerFactory
{
    /** @var array<string, mixed> */
    private array $configuration;

    /**
     * @param array<string, mixed> $configuration
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
        foreach ($this->configuration['paths'] as $pathRegex => $config) {
            if (isset($config['host']) && ! preg_match('#' . $config['host'] . '#', $host)) {
                continue;
            }

            if (! preg_match('#' . $pathRegex . '#', $path)) {
                continue;
            }

            return $config['factory']();
        }

        return $this->configuration['factory']();
    }
}
