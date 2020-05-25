<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures;

use Solido\Symfony\Serialization\View\Context;

class TestObject
{
    public function testGroupProvider(Context $context): array
    {
        return ['foobar'];
    }
}
