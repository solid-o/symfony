<?php declare(strict_types=1);

namespace Solido\Symfony\Tests;

use Solido\DtoManagement\InterfaceResolver\ResolverInterface;
use Solido\Symfony\Tests\Fixtures\Proxy\AppKernel;
use Solido\Symfony\Tests\Fixtures\Proxy\Model\Interfaces\ExcludedInterface;
use Solido\Symfony\Tests\Fixtures\Proxy\Model\Interfaces\UserInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\VarDumper\Dumper\CliDumper;

class DtoIntegrationTest extends WebTestCase
{
    public function testShouldReturn401IfNotLoggedIn(): void
    {
        $client = self::createClient();
        $client->request('GET', '/');

        $response = $client->getResponse();
        self::assertEquals(401, $response->getStatusCode());
    }

    public function testShouldLoadRoutingFromDtoInterface(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);
        $client->request('GET', '/routed-dto', [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'PHP_AUTH_USER' => 'user',
            'PHP_AUTH_PW' => 'user',
        ]);

        $response = $client->getResponse();
        self::assertEquals(201, $response->getStatusCode());
        self::assertEquals('{"id":"what_a_nice_id"}', $response->getContent());
    }

    public function testShouldThrowAccessDeniedExceptionIfRoleDoesNotMatch(): void
    {
        $client = self::createClient();
        $client->request('GET', '/protected', [], [], [
            'PHP_AUTH_USER' => 'user',
            'PHP_AUTH_PW' => 'user',
        ]);

        $response = $client->getResponse();
        self::assertEquals(403, $response->getStatusCode());
    }

    public function testShouldExecuteOperationsIfRolesAreCorrect(): void
    {
        $client = self::createClient();
        $client->request('GET', '/protected', [], [], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'admin',
        ]);

        $response = $client->getResponse();
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('"CIAO"', $response->getContent());
    }

    public function testShouldReturnNullIfOnInvalidFlagsIsSet(): void
    {
        $client = self::createClient();
        $client->request('GET', '/unavailable', [], [], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'admin',
        ]);

        $response = $client->getResponse();
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('null', $response->getContent());
    }

    public function testShouldRetrieveTheCorrectSemVerDto(): void
    {
        $client = self::createClient();
        $client->request('GET', '/semver/1.0', [], [], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'admin',
        ]);

        $response = $client->getResponse();
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('"test"', $response->getContent());

        self::ensureKernelShutdown();

        $client = self::createClient();
        $client->request('GET', '/semver/1.1', [], [], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'admin',
        ]);

        $response = $client->getResponse();
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('"test1.1"', $response->getContent());

        self::ensureKernelShutdown();

        $client = self::createClient();
        $client->request('GET', '/semver/2.0-alpha-1', [], [], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'admin',
        ]);

        $response = $client->getResponse();
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('"test2.0-alpha-1"', $response->getContent());
    }

    public function testExcludedInterfacesShouldNotBeRegistered(): void
    {
        $client = self::createClient();
        $client->getKernel()->boot();

        $container = $client->getContainer();
        self::assertFalse($container->get(ResolverInterface::class)->has(ExcludedInterface::class));
    }

    public function testDtoAreNotSharedServices(): void
    {
        $client = self::createClient();
        $client->getKernel()->boot();

        $container = $client->getContainer();
        $resolver = $container->get(ResolverInterface::class);

        $dto1 = $resolver->resolve(UserInterface::class);
        $dto2 = $resolver->resolve(UserInterface::class);

        self::assertNotSame($dto1, $dto2);
    }

    public function testProxyCasterIsRegistered(): void
    {
        $client = self::createClient();
        $client->getKernel()->boot();

        $container = $client->getContainer();
        $user = $container->get(ResolverInterface::class)->resolve(UserInterface::class);

        $dumper = new CliDumper();
        $cloner = $container->get('var_dumper.cloner');

        self::assertStringMatchesFormat(<<<DUMP
Solido\\Symfony\\Tests\\Fixtures\\Proxy\\Model\\v2017\\v20171215\\User (proxy) {#%d
  +barPublic: "pubb"
  +barBar: "test"
  +foobar: "ciao"
}
DUMP
            , $dumper->dump($cloner->cloneVar($user), true));
    }

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
    public static function tearDownAfterClass(): void
    {
        self::bootKernel();
        self::ensureKernelShutdown();

        $fs = new Filesystem();
        $fs->remove(static::$kernel->getCacheDir());
        $fs->remove(static::$kernel->getLogDir());
    }
}
