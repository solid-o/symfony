<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Security;

use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Dumper\CliDumper;

trait DumpTrait
{
    private function getDump(Data $data): string
    {
        $output = fopen('php://memory', 'r+b');
        $dumper = new CliDumper($output);
        $dumper->dump($data);

        rewind($output);
        return stream_get_contents($output);
    }
}
