<?php

declare(strict_types=1);

namespace Solido\Symfony\Tests\Command;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Solido\Smithy\GenerationResult;
use Solido\Smithy\GeneratorConfig;
use Solido\Smithy\Model\ApiModel;
use Solido\Symfony\Command\SmithyDumpCommand;
use Symfony\Component\Console\Tester\CommandTester;

final class SmithyDumpCommandTest extends TestCase
{
    public function testRejectsInvalidSmithyNamespace(): void
    {
        $tester = new CommandTester(new SmithyDumpCommand(new SmithyGeneratorStub(), [
            'namespace' => 'NonSoloEventi\\Sdk',
            'service_name' => 'Api',
            'dto_namespaces' => ['App\\Model'],
        ]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid Smithy namespace "NonSoloEventi\\Sdk".');

        $tester->execute([]);
    }

    public function testDumpsWithValidSmithyNamespace(): void
    {
        $tester = new CommandTester(new SmithyDumpCommand(new SmithyGeneratorStub(), [
            'namespace' => 'non.solo.eventi.sdk',
            'service_name' => 'Api',
            'dto_namespaces' => ['App\\Model'],
        ]));

        self::assertSame(0, $tester->execute([]));
        self::assertStringContainsString('namespace non.solo.eventi.sdk', $tester->getDisplay());
    }
}

final class SmithyGeneratorStub
{
    public function generateResult(GeneratorConfig $config): GenerationResult
    {
        return new GenerationResult(
            new ApiModel($config->namespace, $config->serviceName, $config->version),
            'namespace ' . $config->namespace,
        );
    }
}
