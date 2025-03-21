<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Security;

use Solido\PolicyChecker\DataCollector\PolicyCheckerDataCollector;
use Solido\Symfony\Tests\Fixtures\PolicyChecker\AppKernel;
use Solido\Symfony\Tests\WebTestCaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

class PolicyCheckerIntegrationTest extends WebTestCase
{
    use DumpTrait;
    use WebTestCaseTrait;

    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new AppKernel('test', true);
    }

    public function testShouldCheckForAllowingPolicy(): void
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request('GET', '/foos');

        $profile = $client->getProfile();
        $collector = $profile->getCollector('security');

        self::assertInstanceOf(PolicyCheckerDataCollector::class, $collector);
        self::assertStringMatchesFormat(<<<EOF
array:1 [
  0 => array:5 [
    "action" => "ListFoo"
    "resource" => "*"
    "subject" => "urn:::::user:user-id"
    "context" => array:1 [
      "sourceIP" => "127.0.0.1"
    ]
    "result" => false
  ]
]\n
EOF, $this->getDump($collector->getPolicyPermissions()));
    }
}
