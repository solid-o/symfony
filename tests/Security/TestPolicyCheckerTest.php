<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Security;

use Solido\PolicyChecker\DataCollector\PolicyCheckerDataCollector;
use Solido\PolicyChecker\PolicyCheckerInterface;
use Solido\PolicyChecker\Test\TestPolicyChecker;
use Solido\Symfony\Tests\Fixtures\PolicyChecker\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class TestPolicyCheckerTest extends WebTestCase
{
    use DumpTrait;

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

    public function testShouldNotCheckForAllowedPolicyOnFormInvalidException(): void
    {
        TestPolicyChecker::defaultDeny();
        TestPolicyChecker::addGrant(PolicyCheckerInterface::EFFECT_ALLOW, ['urn:::::user:user-id'], ['InvalidFoo'], ['*']);

        $client = static::createClient();
        $client->enableProfiler();

        $client->request('GET', '/invalid-form');

        $profile = $client->getProfile();
        $collector = $profile->getCollector('security');

        self::assertInstanceOf(PolicyCheckerDataCollector::class, $collector);
        self::assertStringMatchesFormat(<<<EOF
array:1 [
  0 => array:5 [
    "action" => "InvalidFoo"
    "resource" => "*"
    "subject" => "urn:::::user:user-id"
    "context" => array:1 [
      "sourceIP" => "127.0.0.1"
    ]
    "result" => true
  ]
]\n
EOF, $this->getDump($collector->getPolicyPermissions()));
    }
}
