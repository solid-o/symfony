<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Config;

use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Solido\Symfony\Annotation\Security;
use Solido\Symfony\Annotation\View;
use Solido\Symfony\Config\ControllerCacheWarmer;
use Solido\Symfony\EventListener\BadResponseExceptionSubscriber;
use Solido\Symfony\EventListener\ControllerListener;
use Symfony\Component\Config\ConfigCacheFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

use function Safe\tempnam;
use function sys_get_temp_dir;

class ControllerCacheWarmerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy|RouterInterface
     */
    private $router;

    /**
     * @var ObjectProphecy|ControllerResolverInterface
     */
    private $controllerResolver;

    private string $cacheDir;
    private ConfigCacheFactory $cacheFactory;
    private ControllerListener $listener;
    private ControllerCacheWarmer $warmer;

    protected function setUp(): void
    {
        $this->router = $this->prophesize(RouterInterface::class);
        $this->controllerResolver = $this->prophesize(ControllerResolverInterface::class);

        $this->cacheDir = tempnam(sys_get_temp_dir(), 'ccw');
        unlink($this->cacheDir);
        mkdir($this->cacheDir, 0777, true);

        $this->cacheFactory = new ConfigCacheFactory(true);
        $this->listener = new ControllerListener($this->cacheFactory, $this->cacheDir, new AnnotationReader());
        $this->warmer = new ControllerCacheWarmer(
            $this->listener,
            $this->router->reveal(),
            $this->controllerResolver->reveal()
        );
    }

    public function testShouldBeOptional(): void
    {
        self::assertTrue($this->warmer->isOptional());
    }

    public function testShouldWorkWithEmptyRouteCollection(): void
    {
        $this->router->getRouteCollection()->willReturn(new RouteCollection());
        self::assertEquals([], $this->warmer->warmUp($this->cacheDir));
    }

    public function testShouldSkipRoutesWithEmptyController(): void
    {
        $collection = new RouteCollection();
        $collection->add('one', new Route('/one'));
        $collection->add('two', new Route('/two'));

        $this->router->getRouteCollection()->willReturn($collection);
        self::assertEquals([], $this->warmer->warmUp($this->cacheDir));
    }

    public function testShouldProcessAdditionalControllers(): void
    {
        $collection = new RouteCollection();
        $this->router->getRouteCollection()->willReturn($collection);

        $this->warmer->addAdditionalController([TestControllerForControllerCacheWarmer::class, 'testAction']);
        self::assertEquals([
            $this->cacheDir . '/solido_attributes/' . str_replace('\\', '', TestControllerForControllerCacheWarmer::class) . '/testAction.php',
        ], $this->warmer->warmUp($this->cacheDir));
    }

    public function testShouldSkipRoutesWithFunctionController(): void
    {
        $collection = new RouteCollection();
        $collection->add('one', new Route('/one', [
            '_controller' => 'strtolower',
        ]));

        $this->controllerResolver->getController(Argument::type(Request::class))
            ->willReturn('strtolower');

        $this->router->getRouteCollection()->willReturn($collection);
        self::assertEquals([], $this->warmer->warmUp($this->cacheDir));
    }

    public function testShouldSkipRoutesWithClosureController(): void
    {
        $collection = new RouteCollection();
        $collection->add('one', new Route('/one', [
            '_controller' => 'strtolower',
        ]));

        $this->controllerResolver->getController(Argument::type(Request::class))
            ->willReturn(static function () {});

        $this->router->getRouteCollection()->willReturn($collection);
        self::assertEquals([], $this->warmer->warmUp($this->cacheDir));
    }

    public function testShouldReadAttributesOnInvokeMethodIfPresent(): void
    {
        $collection = new RouteCollection();
        $collection->add('one', new Route('/one', [
            '_controller' => TestControllerForControllerCacheWarmer::class,
        ]));

        $this->controllerResolver->getController(Argument::type(Request::class))
            ->willReturn(new TestControllerForControllerCacheWarmer());

        $this->router->getRouteCollection()->willReturn($collection);
        self::assertEquals([
            $this->cacheDir . '/solido_attributes/' . str_replace('\\', '', TestControllerForControllerCacheWarmer::class) . '/__invoke.php',
        ], $this->warmer->warmUp($this->cacheDir));
    }

    public function testShouldReadAttributesOnAction(): void
    {
        $collection = new RouteCollection();
        $collection->add('one', new Route('/one', [
            '_controller' => TestControllerForControllerCacheWarmer::class . ':test',
        ]));

        $this->controllerResolver->getController(Argument::type(Request::class))
            ->willReturn([new TestControllerForControllerCacheWarmer(), 'testAction']);

        $this->router->getRouteCollection()->willReturn($collection);
        self::assertEquals([
            $this->cacheDir . '/solido_attributes/' . str_replace('\\', '', TestControllerForControllerCacheWarmer::class) . '/testAction.php',
        ], $this->warmer->warmUp($this->cacheDir));
    }

    public function testShouldReadAttributesOnInterfaceAction(): void
    {
        $collection = new RouteCollection();
        $collection->add('one', new Route('/one', [
            '_controller' => TestControllerForControllerCacheWarmer::class . ':test',
            '_solido_dto_interface' => TestControllerForControllerCacheWarmerInterface::class,
        ]));

        $this->controllerResolver->getController(Argument::type(Request::class))
            ->willReturn([new TestControllerForControllerCacheWarmer(), 'testAction']);

        $this->router->getRouteCollection()->willReturn($collection);
        self::assertEquals([
            $this->cacheDir . '/solido_attributes/' . str_replace('\\', '', TestControllerForControllerCacheWarmerInterface::class) . '/testAction.php',
        ], $this->warmer->warmUp($this->cacheDir));
    }
}

interface TestControllerForControllerCacheWarmerInterface
{
    /**
     * @Security("testable()")
     */
    public function testAction(Request $request);
}

class TestControllerForControllerCacheWarmer implements TestControllerForControllerCacheWarmerInterface
{
    /**
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function __invoke(Request $request)
    {
    }

    /**
     * @View()
     */
    public function testAction(Request $request)
    {
    }
}
