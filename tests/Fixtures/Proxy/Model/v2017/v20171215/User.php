<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\Proxy\Model\v2017\v20171215;

use Solido\DataTransformers\Annotation\Transform;
use Solido\Symfony\Annotation\Security;
use Solido\Symfony\Tests\Fixtures\Proxy\Model\Interfaces\UserInterface;
use Solido\Symfony\Tests\Fixtures\Proxy\Transformer\TestTransform;

class User implements UserInterface
{
    public $barPublic = 'pubb';

    /**
     * @Security("true")
     */
    public $barBar = 'test';

    /**
     * @Transform(TestTransform::class)
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public $foobar = 'ciao';

    /**
     * @Transform("transformer.service_transformer")
     */
    public $bazbar = 'ciao';

    public function __construct()
    {
    }

    /**
     * @Transform(TestTransform::class)
     * @Security("value == 'ciao'")
     */
    public function setFoo(?string $value)
    {
        $this->foo = $value;
    }

    public function getFoo()
    {
        return 'test';
    }

    public function setBar()
    {
        $this->foobar = 'testtest';
    }

    public function fluent(): self
    {
        return $this;
    }

    /**
     * @Security("is_granted('ROLE_DENY')", onInvalid="null")
     */
    public function getTest(): ?string
    {
        return 'unavailable_test';
    }
}
