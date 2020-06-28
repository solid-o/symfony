<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\Proxy\Model\v2017\v20171215;

use Solido\PatchManager\PatchManagerInterface;
use Solido\Symfony\Tests\Fixtures\Proxy\Model\Interfaces\RoutedInterface;
use Solido\Symfony\Tests\Fixtures\Proxy\Model\Interfaces\RoutedInvokableInterface;

class RoutedInvokable implements RoutedInvokableInterface
{
    /**
     * @var string
     */
    public $id;

    public function __invoke(PatchManagerInterface $patchManager): self
    {
        $this->id = 'what_a_nice_id';

        return $this;
    }
}
