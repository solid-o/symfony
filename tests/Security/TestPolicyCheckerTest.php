<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Security;

use Solido\PolicyChecker\PolicyCheckerInterface;
use Solido\PolicyChecker\Test\TestPolicyChecker;
use Solido\Symfony\Tests\Fixtures\PolicyChecker\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class TestPolicyCheckerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new TestKernel('test', true);
    }

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        $fs = new Filesystem();
        $fs->remove(__DIR__.'/../Fixtures/PolicyChecker/var');
    }

    public function testShouldRegisterTestPolicyChecker(): void
    {
        static::bootKernel();
        $policyChecker = static::$container->get(PolicyCheckerInterface::class);

        self::assertInstanceOf(TestPolicyChecker::class, $policyChecker);
    }
}
