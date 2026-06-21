<?php

declare(strict_types=1);

use Solido\Symfony\Tests\Fixtures\BodyConverter\AppKernel as BodyConverterKernel;
use Solido\Symfony\Tests\Fixtures\PolicyChecker\AppKernel as PolicyCheckerKernel;
use Solido\Symfony\Tests\Fixtures\Proxy\AppKernel as ProxyKernel;
use Solido\Symfony\Tests\Fixtures\View\AppKernel as ViewKernel;
use Symfony\Component\HttpKernel\KernelInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * @phpstan-type Scenario array{name: string, kernel: class-string<KernelInterface>}
 * @phpstan-type Result array{scenario: string, mode: 'cold'|'warm', iterations: int, average_ms: float, min_ms: float, max_ms: float, peak_memory_mb: float}
 */

$iterations = parsePositiveIntOption($argv, '--iterations', 5);
$output = parseStringOption($argv, '--output');
$childKernel = parseStringOption($argv, '--child-kernel');

if ($childKernel !== null) {
    $cold = parseStringOption($argv, '--child-mode') === 'cold';
    $environment = parseStringOption($argv, '--child-env') ?? 'bench';
    $cleanAfter = parseStringOption($argv, '--child-clean-after') === '1';
    echo json_encode(runChildMeasurement($childKernel, $cold, $environment, $cleanAfter), JSON_THROW_ON_ERROR) . "\n";
    exit(0);
}

/** @var Scenario[] $scenarios */
$scenarios = [
    ['name' => 'body_converter', 'kernel' => BodyConverterKernel::class],
    ['name' => 'view', 'kernel' => ViewKernel::class],
    ['name' => 'proxy_dto', 'kernel' => ProxyKernel::class],
    ['name' => 'policy_checker', 'kernel' => PolicyCheckerKernel::class],
];

$results = [];
foreach ($scenarios as $scenario) {
    if (! class_exists($scenario['kernel'])) {
        continue;
    }

    $results[] = runScenario($scenario, $iterations, cold: true);
    $results[] = runScenario($scenario, $iterations, cold: false);
}

writeTable($results);

if ($output !== null) {
    file_put_contents($output, json_encode($results, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n");
}

/**
 * @param string[] $argv
 */
function parsePositiveIntOption(array $argv, string $name, int $default): int
{
    $value = parseStringOption($argv, $name);
    if ($value === null) {
        return $default;
    }

    $value = (int) $value;

    return max(1, $value);
}

/**
 * @param string[] $argv
 */
function parseStringOption(array $argv, string $name): string|null
{
    foreach ($argv as $index => $argument) {
        if ($argument === $name && array_key_exists($index + 1, $argv)) {
            return $argv[$index + 1];
        }

        $prefix = $name . '=';
        if (str_starts_with($argument, $prefix)) {
            return (string) substr($argument, strlen($prefix));
        }
    }

    return null;
}

/**
 * @param Scenario $scenario
 *
 * @return Result
 */
function runScenario(array $scenario, int $iterations, bool $cold): array
{
    $durations = [];
    $peakMemory = 0.0;

    for ($i = 0; $i < $iterations; ++$i) {
        $measurement = runSubprocessMeasurement($scenario['kernel'], $cold, $scenario['name'] . '_' . ($cold ? 'cold' : 'warm') . '_' . $i);
        $durations[] = $measurement['duration_ms'];
        $peakMemory = max($peakMemory, $measurement['peak_memory_mb']);
    }

    /** @var 'cold'|'warm' $mode */
    $mode = $cold ? 'cold' : 'warm';

    return [
        'scenario' => $scenario['name'],
        'mode' => $mode,
        'iterations' => $iterations,
        'average_ms' => array_sum($durations) / count($durations),
        'min_ms' => min($durations),
        'max_ms' => max($durations),
        'peak_memory_mb' => $peakMemory,
    ];
}

/**
 * @param class-string<KernelInterface> $kernelClass
 *
 * @return array{duration_ms: float, peak_memory_mb: float}
 */
function runSubprocessMeasurement(string $kernelClass, bool $cold, string $environment): array
{
    if (! $cold) {
        runChildProcess($kernelClass, 'cold', $environment, cleanAfter: false);
    }

    return runChildProcess($kernelClass, $cold ? 'cold' : 'warm', $environment, cleanAfter: true);
}

/**
 * @param class-string<KernelInterface> $kernelClass
 *
 * @return array{duration_ms: float, peak_memory_mb: float}
 */
function runChildProcess(string $kernelClass, string $mode, string $environment, bool $cleanAfter): array
{
    $descriptorSpec = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $pipes = [];
    $process = proc_open([
        PHP_BINARY,
        __FILE__,
        '--child-kernel=' . $kernelClass,
        '--child-mode=' . $mode,
        '--child-env=' . $environment,
        '--child-clean-after=' . ($cleanAfter ? '1' : '0'),
    ], $descriptorSpec, $pipes);

    if (! is_resource($process)) {
        throw new RuntimeException('Unable to start benchmark child process.');
    }

    $output = stream_get_contents($pipes[1]);
    $errorOutput = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);
    if ($exitCode !== 0) {
        throw new RuntimeException($errorOutput !== '' ? $errorOutput : $output);
    }

    $decoded = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    if (! is_array($decoded) || ! isset($decoded['duration_ms'], $decoded['peak_memory_mb'])) {
        throw new RuntimeException('Benchmark child process returned an invalid payload: ' . $output);
    }

    return [
        'duration_ms' => (float) $decoded['duration_ms'],
        'peak_memory_mb' => (float) $decoded['peak_memory_mb'],
    ];
}

/**
 * @param class-string<KernelInterface> $kernelClass
 *
 * @return array{duration_ms: float, peak_memory_mb: float}
 */
function runChildMeasurement(string $kernelClass, bool $cold, string $environment, bool $cleanAfter): array
{
    if (! class_exists($kernelClass)) {
        throw new RuntimeException('Kernel class does not exist: ' . $kernelClass);
    }

    if ($cold) {
        cleanupKernelRuntime($kernelClass, $environment);
    }

    $kernel = new $kernelClass($environment, false);

    $start = microtime(true);
    $kernel->boot();
    $duration = (microtime(true) - $start) * 1000;
    $peakMemory = memory_get_peak_usage(true) / 1024 / 1024;

    $kernel->shutdown();

    if ($cleanAfter) {
        cleanupKernelRuntime($kernelClass, $environment);
    }

    return [
        'duration_ms' => $duration,
        'peak_memory_mb' => $peakMemory,
    ];
}

/**
 * @param class-string<KernelInterface> $kernelClass
 */
function cleanupKernelRuntime(string $kernelClass, string $environment): void
{
    $reflection = new ReflectionClass($kernelClass);
    $fileName = $reflection->getFileName();
    if ($fileName === false) {
        return;
    }

    $rootDir = dirname($fileName);
    foreach ([
        $rootDir . '/var/cache/' . $environment,
        $rootDir . '/var/build/' . $environment,
        $rootDir . '/logs/' . $environment,
        $rootDir . '/cache/' . $environment,
        $rootDir . '/build/' . $environment,
    ] as $path) {
        removeDirectory($path);
    }
}

function removeDirectory(string $path): void
{
    $root = realpath(dirname($path));
    if ($root === false || ! is_dir($path)) {
        return;
    }

    $normalizedRoot = $root . DIRECTORY_SEPARATOR;
    $normalizedPath = realpath($path);
    if ($normalizedPath === false || ! str_starts_with($normalizedPath . DIRECTORY_SEPARATOR, $normalizedRoot)) {
        return;
    }

    foreach (new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    ) as $file) {
        $filePath = $file->getPathname();
        if ($file->isDir() && ! $file->isLink()) {
            rmdir($filePath);
            continue;
        }

        if (is_file($filePath) || is_link($filePath)) {
            unlink($filePath);
        }
    }

    rmdir($path);
}

/**
 * @param Result[] $results
 */
function writeTable(array $results): void
{
    $headers = ['Scenario', 'Mode', 'Iterations', 'Avg ms', 'Min ms', 'Max ms', 'Peak MB'];
    $rows = array_map(static fn (array $result) => [
        $result['scenario'],
        $result['mode'],
        (string) $result['iterations'],
        number_format($result['average_ms'], 2, '.', ''),
        number_format($result['min_ms'], 2, '.', ''),
        number_format($result['max_ms'], 2, '.', ''),
        number_format($result['peak_memory_mb'], 2, '.', ''),
    ], $results);

    $widths = array_map(strlen(...), $headers);
    foreach ($rows as $row) {
        foreach ($row as $index => $value) {
            $widths[$index] = max($widths[$index], strlen($value));
        }
    }

    writeRow($headers, $widths);
    writeRow(array_map(static fn (int $width) => str_repeat('-', $width), $widths), $widths);
    foreach ($rows as $row) {
        writeRow($row, $widths);
    }
}

/**
 * @param string[] $row
 * @param int[] $widths
 */
function writeRow(array $row, array $widths): void
{
    $cells = [];
    foreach ($row as $index => $value) {
        $cells[] = sprintf('%-' . $widths[$index] . 's', $value);
    }

    echo implode('  ', $cells) . "\n";
}
