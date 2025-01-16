<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\EventListener\Compat;

use Solido\Symfony\Tests\Fixtures\Proxy\AppKernel;
use Solido\Symfony\Tests\WebTestCaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\DataCollector\RequestDataCollector;
use Symfony\Component\HttpKernel\KernelInterface;

class SymfonyIsGrantedCompatibilityTest extends WebTestCase
{
    use WebTestCaseTrait;

    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new AppKernel('test', true);
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
