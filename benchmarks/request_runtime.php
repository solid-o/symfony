<?php

declare(strict_types=1);

use Solido\Symfony\Tests\Fixtures\Proxy\AppKernel;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__) . '/vendor/autoload.php';

$iterations = parseRuntimePositiveIntOption($argv, '--iterations', 100);
$environment = 'bench_runtime_' . getmypid();

$scenarios = [
    [
        'name' => 'dto_route',
        'path' => '/routed-dto',
        'server' => [
            'HTTP_ACCEPT' => 'application/json',
            'PHP_AUTH_USER' => 'user',
            'PHP_AUTH_PW' => 'user',
            'HTTP_X_VERSION' => '20171215',
        ],
    ],
    [
        'name' => 'protected_dto_controller',
        'path' => '/protected',
        'server' => [
            'HTTP_ACCEPT' => 'application/json',
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'admin',
            'HTTP_X_VERSION' => '20171215',
        ],
    ],
    [
        'name' => 'semver_controller',
        'path' => '/semver/1.1',
        'server' => [
            'HTTP_ACCEPT' => 'application/json',
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'admin',
            'HTTP_X_VERSION' => '20171215',
        ],
    ],
];

cleanupRuntimeKernel($environment);

$kernel = new AppKernel($environment, false);
$kernel->boot();

$results = [];
foreach ($scenarios as $scenario) {
    $results[] = runRuntimeScenario($kernel, $scenario, $iterations);
}

$kernel->shutdown();
cleanupRuntimeKernel($environment);

writeRuntimeTable($results);

/**
 * @param string[] $argv
 * @param positive-int $default
 *
 * @return positive-int
 */
function parseRuntimePositiveIntOption(array $argv, string $name, int $default): int
{
    foreach ($argv as $index => $argument) {
        if ($argument === $name && isset($argv[$index + 1])) {
            $value = (int) $argv[$index + 1];
            if ($value < 1) {
                return 1;
            }

            return $value;
        }

        $prefix = $name . '=';
        if (str_starts_with($argument, $prefix)) {
            $value = (int) substr($argument, strlen($prefix));
            if ($value < 1) {
                return 1;
            }

            return $value;
        }
    }

    return $default;
}

/**
 * @param array{name: string, path: string, server: array<string, string>} $scenario
 * @param positive-int $iterations
 *
 * @return array{scenario: string, iterations: int, average_ms: float, min_ms: float, max_ms: float, peak_memory_mb: float, status_code: int}
 */
function runRuntimeScenario(AppKernel $kernel, array $scenario, int $iterations): array
{
    $durations = [];
    $statusCode = 0;

    for ($i = 0; $i < $iterations; ++$i) {
        $request = Request::create($scenario['path'], 'GET', [], [], [], $scenario['server']);

        $start = microtime(true);
        $response = $kernel->handle($request);
        $durations[] = (microtime(true) - $start) * 1000;
        $statusCode = $response->getStatusCode();

        $kernel->terminate($request, $response);
    }

    if ($durations === []) {
        throw new RuntimeException('Cannot run a benchmark scenario with zero iterations.');
    }

    return [
        'scenario' => $scenario['name'],
        'iterations' => $iterations,
        'average_ms' => array_sum($durations) / count($durations),
        'min_ms' => min($durations),
        'max_ms' => max($durations),
        'peak_memory_mb' => memory_get_peak_usage(true) / 1024 / 1024,
        'status_code' => $statusCode,
    ];
}

/** @param list<array{scenario: string, iterations: int, average_ms: float, min_ms: float, max_ms: float, peak_memory_mb: float, status_code: int}> $results */
function writeRuntimeTable(array $results): void
{
    $headers = ['Scenario', 'Iterations', 'Avg ms', 'Min ms', 'Max ms', 'Peak MB', 'Status'];
    $rows = array_map(static fn (array $result) => [
        $result['scenario'],
        (string) $result['iterations'],
        number_format($result['average_ms'], 3, '.', ''),
        number_format($result['min_ms'], 3, '.', ''),
        number_format($result['max_ms'], 3, '.', ''),
        number_format($result['peak_memory_mb'], 2, '.', ''),
        (string) $result['status_code'],
    ], $results);

    $widths = array_map(strlen(...), $headers);
    foreach ($rows as $row) {
        foreach ($row as $index => $value) {
            $widths[$index] = max($widths[$index], strlen($value));
        }
    }

    writeRuntimeRow($headers, $widths);
    writeRuntimeRow(array_map(static fn (int $width) => str_repeat('-', $width), $widths), $widths);
    foreach ($rows as $row) {
        writeRuntimeRow($row, $widths);
    }
}

/**
 * @param string[] $row
 * @param int[] $widths
 */
function writeRuntimeRow(array $row, array $widths): void
{
    $cells = [];
    foreach ($row as $index => $value) {
        $cells[] = sprintf('%-' . $widths[$index] . 's', $value);
    }

    echo implode('  ', $cells) . "\n";
}

function cleanupRuntimeKernel(string $environment): void
{
    $reflection = new ReflectionClass(AppKernel::class);
    $fileName = $reflection->getFileName();
    if ($fileName === false) {
        return;
    }

    $rootDir = dirname($fileName);
    $paths = [
        $rootDir . '/var/cache/' . $environment,
        $rootDir . '/var/build/' . $environment,
        $rootDir . '/logs/' . $environment,
    ];
    foreach ($paths as $path) {
        removeRuntimeDirectory($path);
    }
}

function removeRuntimeDirectory(string $path): void
{
    $root = realpath(dirname($path));
    if ($root === false || ! is_dir($path)) {
        return;
    }

    $normalizedPath = realpath($path);
    if (
        $normalizedPath === false ||
        ! str_starts_with($normalizedPath . DIRECTORY_SEPARATOR, $root . DIRECTORY_SEPARATOR)
    ) {
        return;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($files as $file) {
        if (! $file instanceof SplFileInfo) {
            continue;
        }

        $filePath = $file->getPathname();
        if (! $file->isDir() || $file->isLink()) {
            if (is_file($filePath) || is_link($filePath)) {
                unlink($filePath);
            }

            continue;
        }

        rmdir($filePath);
    }

    rmdir($path);
}
