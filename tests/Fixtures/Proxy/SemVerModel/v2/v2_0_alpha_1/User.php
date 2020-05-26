<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\Proxy\SemVerModel\v2\v2_0_alpha_1;

use Solido\DataTransformers\Annotation\Transform;
use Solido\Symfony\Annotation\Security;
use Solido\Symfony\Tests\Fixtures\Proxy\SemVerModel\Interfaces\UserInterface;
use Solido\Symfony\Tests\Fixtures\Proxy\Transformer\TestTransform;

class User implements UserInterface
{
    public $barBar = 'test';

    /**
     * @Transform(TestTransform::class)
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public $foobar = 'ciao';

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
        return 'test2.0-alpha-1';
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
