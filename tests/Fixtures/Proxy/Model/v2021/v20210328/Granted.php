<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\Proxy\Model\v2021\v20210328;

use Solido\Symfony\Tests\Fixtures\Proxy\Model\Interfaces\GrantedInterface;
use Symfony\Component\HttpFoundation\Request;

class Granted implements GrantedInterface
{
    /**
     * @inheritDoc
     */
    public function routed(Request $request): self
    {
        return $this;
    }
}
