<?php declare(strict_types=1);

namespace Solido\Symfony\Tests;

use Solido\DtoManagement\InterfaceResolver\ResolverInterface;
use Solido\Symfony\Tests\Fixtures\Proxy\AppKernel;
use Solido\Symfony\Tests\Fixtures\Proxy\Model\Interfaces\ExcludedInterface;
use Solido\Symfony\Tests\Fixtures\Proxy\Model\Interfaces\UserInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\VarDumper\Dumper\CliDumper;

class DtoIntegrationTest extends WebTestCase
{
    use WebTestCaseTrait;

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
            'HTTP_X_VERSION' => '20171215',
        ]);

        $response = $client->getResponse();
        self::assertEquals(201, $response->getStatusCode());
        self::assertEquals('{"id":"what_a_nice_id"}', $response->getContent());
    }

    /**
     * @requires PHP >= 8.0
     */
    public function testShouldLoadRoutingFromAttributesOnDtoInterface(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);
        $client->request('GET', '/routed-with-attribute', [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'PHP_AUTH_USER' => 'user',
            'PHP_AUTH_PW' => 'user',
            'HTTP_X_VERSION' => '20171215',
        ]);

        $response = $client->getResponse();
        self::assertEquals(201, $response->getStatusCode());
        self::assertEquals('{"id":"what_a_nice_attribute"}', $response->getContent());
    }

    public function testShouldLoadRoutingFromDtoInvokableInterface(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);
        $client->request('GET', '/routed-invokable', [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'PHP_AUTH_USER' => 'user',
            'PHP_AUTH_PW' => 'user',
            'HTTP_X_VERSION' => '20171215',
        ]);

        $response = $client->getResponse();
        self::assertEquals(202, $response->getStatusCode());
        self::assertEquals('{"id":"what_a_nice_id"}', $response->getContent());
    }

    public function testShouldThrowAccessDeniedExceptionIfRoleDoesNotMatch(): void
    {
        $client = self::createClient();
        $client->request('GET', '/protected', [], [], [
            'PHP_AUTH_USER' => 'user',
            'PHP_AUTH_PW' => 'user',
            'HTTP_X_VERSION' => '20171215',
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
            'HTTP_X_VERSION' => '20171215',
        ]);

        $response = $client->getResponse();
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('"CIAO"', $response->getContent());
    }

    /**
     * @requires PHP >= 8.0
     */
    public function testShouldThrowAccessDeniedExceptionIfRoleDoesNotMatchWithAttribute(): void
    {
        $client = self::createClient();
        $client->request('GET', '/protected', [], [], [
            'PHP_AUTH_USER' => 'user',
            'PHP_AUTH_PW' => 'user',
            'HTTP_X_VERSION' => '20210124',
        ]);

        $response = $client->getResponse();
        self::assertEquals(403, $response->getStatusCode());
    }

    /**
     * @requires PHP >= 8.0
     */
    public function testShouldExecuteOperationsIfRolesAreCorrectWithAttribute(): void
    {
        $client = self::createClient();
        $client->request('GET', '/protected', [], [], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'admin',
            'HTTP_X_VERSION' => '20210124',
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
            'HTTP_X_VERSION' => '20171215',
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
            'HTTP_X_VERSION' => '20171215',
        ]);

        $response = $client->getResponse();
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('"test"', $response->getContent());

        self::ensureKernelShutdown();

        $client = self::createClient();
        $client->request('GET', '/semver/1.1', [], [], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'admin',
            'HTTP_X_VERSION' => '20171215',
        ]);

        $response = $client->getResponse();
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('"test1.1"', $response->getContent());

        self::ensureKernelShutdown();

        $client = self::createClient();
        $client->request('GET', '/semver/2.0-alpha-1', [], [], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'admin',
            'HTTP_X_VERSION' => '20171215',
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
        $user = $container->get(ResolverInterface::class)->resolve(UserInterface::class, '20171215');

        $dumper = new CliDumper();
        $cloner = $container->get('var_dumper.cloner');

        self::assertStringMatchesFormat(<<<DUMP
Solido\\Symfony\\Tests\\Fixtures\\Proxy\\Model\\v2017\\v20171215\\User (proxy) {#%d
  +barPublic: "pubb"
%a
}
DUMP
            , $dumper->dump($cloner->cloneVar($user), true));
    }

    public function testShouldAcquireALock(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);
        $client->request('GET', '/routed-and-locked', [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'admin',
            'HTTP_X_VERSION' => '1.0',
        ]);

        $response = $client->getResponse();
        self::assertEquals(202, $response->getStatusCode());
        self::assertEquals('{"id":"what_a_nice_id","locked":false}', $response->getContent());
    }

    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new AppKernel('test', true);
    }
}
