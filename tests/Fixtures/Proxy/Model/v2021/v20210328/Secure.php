<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\Proxy\Model\v2021\v20210328;

use Solido\Symfony\Tests\Fixtures\Proxy\Model\Interfaces\SecureInterface;
use Symfony\Component\HttpFoundation\Request;

class Secure implements SecureInterface
{
    /**
     * @inheritDoc
     */
    public function routed(Request $request): SecureInterface
    {
        return $this;
    }
}
