<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Security;

use Solido\PolicyChecker\DataCollector\PolicyCheckerDataCollector;
use Solido\Symfony\Tests\Fixtures\PolicyChecker\AppKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Dumper\CliDumper;

class PolicyCheckerIntegrationTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new AppKernel('test', true);
    }

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        $fs = new Filesystem();
        $fs->remove(__DIR__.'/../../var');
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
    "context" => Symfony\Component\VarDumper\Cloner\Data {#%d
      -data: array:2 [
        0 => array:1 [
          0 => array:1 [
            1 => 1
          ]
        ]
        1 => array:1 [
          "sourceIP" => "127.0.0.1"
        ]
      ]
      -position: 0
      -key: 0
      -maxDepth: 20
      -maxItemsPerDepth: -1
      -useRefHandles: -1
      -context: []
    }
    "result" => false
  ]
]\n
EOF, $this->getDump($collector->getPolicyPermissions()));
    }

    private function getDump(Data $data): string
    {
        $output = fopen('php://memory', 'r+b');
        $dumper = new CliDumper($output);
        $dumper->dump($data);

        rewind($output);
        return stream_get_contents($output);
    }
}
