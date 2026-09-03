<?php

declare(strict_types=1);

namespace Solido\Symfony\Command;

use RuntimeException;
use Solido\Smithy\GeneratorConfig;
use Solido\Smithy\Type\TypeOverride;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

use function dirname;
use function explode;
use function file_put_contents;
use function is_array;
use function is_string;
use function method_exists;
use function sprintf;
use function str_contains;

#[AsCommand(name: 'solido:smithy:dump', description: 'Dump a Smithy model generated from Solido DTO metadata.')]
final class SmithyDumpCommand extends Command
{
    /** @param array<string, mixed> $config */
    public function __construct(
        private readonly object $generator,
        private readonly array $config,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'Output path. Defaults to solido.smithy.output.')
            ->addOption('namespace', null, InputOption::VALUE_REQUIRED, 'Smithy namespace. Defaults to solido.smithy.namespace.')
            ->addOption('service-name', null, InputOption::VALUE_REQUIRED, 'Smithy service name. Defaults to solido.smithy.service_name.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (! method_exists($this->generator, 'generateResult')) {
            throw new RuntimeException('The configured Smithy generator does not expose generateResult().');
        }

        $namespace = $this->option($input, 'namespace') ?? $this->stringConfig('namespace');
        $serviceName = $this->option($input, 'service-name') ?? $this->stringConfig('service_name');
        $target = $this->option($input, 'output') ?? $this->stringConfig('output');
        if ($namespace === null || $serviceName === null) {
            throw new RuntimeException('Both Smithy namespace and service name must be configured.');
        }

        $result = $this->generator->generateResult(new GeneratorConfig(
            namespace: $namespace,
            serviceName: $serviceName,
            dtoNamespaces: $this->dtoNamespaces(),
            version: $this->stringConfig('version') ?? '1.0',
            output: $target,
            typeOverrides: $this->typeOverrides(),
        ));

        $errorOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        foreach ($result->diagnostics() as $diagnostic) {
            $errorOutput->writeln(sprintf('[%s] %s: %s', $diagnostic->severity, $diagnostic->source, $diagnostic->message));
        }

        if ($target === null) {
            $output->write($result->smithy);

            return Command::SUCCESS;
        }

        $this->filesystem->mkdir(dirname($target));
        file_put_contents($target, $result->smithy);
        $output->writeln(sprintf('Smithy model written to %s', $target));

        return Command::SUCCESS;
    }

    /** @return string[] */
    private function dtoNamespaces(): array
    {
        $namespaces = $this->config['dto_namespaces'] ?? [];
        if (! is_array($namespaces)) {
            return [];
        }

        $result = [];
        foreach ($namespaces as $namespace) {
            if (! is_string($namespace)) {
                continue;
            }

            $result[] = $namespace;
        }

        return $result;
    }

    private function option(InputInterface $input, string $name): string|null
    {
        $value = $input->getOption($name);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function stringConfig(string $name): string|null
    {
        $value = $this->config[$name] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /** @return TypeOverride[] */
    private function typeOverrides(): array
    {
        $configured = $this->config['type_overrides'] ?? [];
        if (! is_array($configured)) {
            return [];
        }

        $overrides = [];
        foreach ($configured as $source => $target) {
            if (! is_string($source) || ! is_string($target)) {
                continue;
            }

            if (! str_contains($source, '::')) {
                $overrides[] = TypeOverride::type($source, $target);
                continue;
            }

            [$class, $member] = explode('::', $source, 2);
            $overrides[] = TypeOverride::member($class, $member, $target);
        }

        return $overrides;
    }
}
