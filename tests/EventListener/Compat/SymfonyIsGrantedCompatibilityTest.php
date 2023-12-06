<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\EventListener\Compat;

use Solido\Symfony\Tests\Fixtures\Proxy\AppKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\DataCollector\RequestDataCollector;
use Symfony\Component\HttpKernel\KernelInterface;

class SymfonyIsGrantedCompatibilityTest extends WebTestCase
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

    public function testShouldBeCompatibleWithSensioFrameworkExtraAnnotations(): void
    {
        $client = self::createClient();
        $client->enableProfiler();

        $client->request('GET', '/routed-with-is-granted', [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'PHP_AUTH_USER' => 'user',
            'PHP_AUTH_PW' => 'user',
        ]);

        $response = $client->getResponse();

        $profile = $client->getProfile();
        $collector = $profile->getCollector('request');
        assert($collector instanceof RequestDataCollector);

        self::assertEquals(403, $response->getStatusCode());
    }
}
