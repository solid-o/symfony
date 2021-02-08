<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\Proxy\Model\v2017\v20171215;

use Solido\PatchManager\PatchManagerInterface;
use Solido\Symfony\Tests\Fixtures\Proxy\Model\Interfaces\RoutedInterface;

class Routed implements RoutedInterface
{
    /**
     * @var string
     */
    public $id;

    public function routed(PatchManagerInterface $patchManager): RoutedInterface
    {
        $this->id = 'what_a_nice_id';

        return $this;
    }

    public function routedWithAttribute(PatchManagerInterface $patchManager): RoutedInterface
    {
        $this->id = 'what_a_nice_attribute';

        return $this;
    }
}
