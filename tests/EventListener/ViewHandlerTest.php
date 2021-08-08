<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\EventListener;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Refugis\DoctrineExtra\ObjectIteratorInterface;
use Solido\Pagination\PagerIterator;
use Solido\Pagination\PageToken;
use Solido\Serialization\Exception\UnsupportedFormatException;
use Solido\Serialization\SerializerInterface;
use Solido\Symfony\Annotation\View as ViewAnnotation;
use Solido\Symfony\EventListener\ViewHandler;
use Solido\Symfony\Serialization\View\View;
use Solido\Symfony\Tests\Fixtures\TestObject;
use Solido\Symfony\Tests\Fixtures\View\AppKernel;
use Solido\Symfony\Tests\Fixtures\View\Controller\TestController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ViewHandlerTest extends WebTestCase
{
    use ProphecyTrait;

    /**
     * @var SerializerInterface|ObjectProphecy
     */
    private ObjectProphecy $serializer;

    /**
     * @var HttpKernelInterface|ObjectProphecy
     */
    private ObjectProphecy $httpKernel;

    /**
     * @var TokenStorageInterface|ObjectProphecy
     */
    private ObjectProphecy $tokenStorage;

    private ViewHandler $viewHandler;
    private string $defaultResponseCharset;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->serializer = $this->prophesize(SerializerInterface::class);
        $this->httpKernel = $this->prophesize(HttpKernelInterface::class);
        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $this->defaultResponseCharset = 'UTF-8';

        $this->viewHandler = new ViewHandler(
            $this->serializer->reveal(),
            $this->tokenStorage->reveal(),
            $this->defaultResponseCharset
        );
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
    public static function setUpBeforeClass(): void
    {
        $fs = new Filesystem();
        $fs->remove(__DIR__.'/../../var');
    }

    public function skipProvider(): array
    {
        $tests = [];

        $tests[] = [new Request(), new Response()];

        $request = new Request();
        $request->attributes->set('_solido_view', new \stdClass());
        $tests[] = [$request, ['foo' => 'bar']];

        return $tests;
    }

    /**
     * @dataProvider skipProvider
     */
    public function testSkip(Request $request, $result): void
    {
        $event = new ViewEvent($this->httpKernel->reveal(), $request, HttpKernelInterface::MASTER_REQUEST, $result);

        $this->serializer->serialize(Argument::cetera())->shouldNotBeCalled();
        $this->viewHandler->onView($event);

        self::assertNull($event->getResponse());
    }

    public function testShouldSetStatusCode(): void
    {
        $annotation = new ViewAnnotation();
        $annotation->statusCode = Response::HTTP_CREATED;

        $request = new Request();
        $request->attributes->set('_solido_view', $annotation);

        $event = new ViewEvent($this->httpKernel->reveal(), $request, HttpKernelInterface::MASTER_REQUEST, new TestObject());

        $this->serializer->serialize(Argument::type(TestObject::class), Argument::cetera())->shouldBeCalled();
        $this->viewHandler->onView($event);

        self::assertEquals(Response::HTTP_CREATED, $event->getResponse()->getStatusCode());
    }

    public function testShouldSerializeWithCorrectGroups(): void
    {
        $annotation = new ViewAnnotation();
        $annotation->groups = ['group_foo', 'bar_bar'];

        $request = new Request();
        $request->attributes->set('_solido_view', $annotation);

        $event = new ViewEvent($this->httpKernel->reveal(), $request, HttpKernelInterface::MASTER_REQUEST, new TestObject());

        $this->serializer
            ->serialize(Argument::type(TestObject::class), Argument::any(), Argument::withEntry('groups', ['group_foo', 'bar_bar']))
            ->shouldBeCalled();

        $this->viewHandler->onView($event);
    }

    public function testShouldCallSerializationGroupProvider(): void
    {
        $annotation = new ViewAnnotation();
        $annotation->groupsProvider = 'testGroupProvider';

        $request = new Request();
        $request->attributes->set('_solido_view', $annotation);

        $event = new ViewEvent($this->httpKernel->reveal(), $request, HttpKernelInterface::MASTER_REQUEST, new TestObject());
        $this->serializer
            ->serialize(Argument::type(TestObject::class), Argument::any(), Argument::withEntry('groups', ['foobar']))
            ->shouldBeCalled()
        ;

        $this->viewHandler->onView($event);
    }

    public function testShouldSetResponseCode405IfFormatIsNotSupported(): void
    {
        $request = new Request();
        $request->attributes->set('_solido_view', new ViewAnnotation());

        $event = new ViewEvent($this->httpKernel->reveal(), $request, HttpKernelInterface::MASTER_REQUEST, new \stdClass());

        $this->serializer
            ->serialize(Argument::any(), Argument::any(), Argument::type('array'))
            ->willThrow(new UnsupportedFormatException())
        ;

        $this->viewHandler->onView($event);

        self::assertEquals(Response::HTTP_NOT_ACCEPTABLE, $event->getResponse()->getStatusCode());
    }

    public function testShouldSerializeInvalidFormAndSetBadRequestStatus(): void
    {
        $request = new Request();
        $request->attributes->set('_solido_view', new ViewAnnotation());

        $form = $this->prophesize(Form::class);
        $form->isSubmitted()->willReturn(true);
        $form->isValid()->willReturn(false);

        $event = new ViewEvent($this->httpKernel->reveal(), $request, HttpKernelInterface::MASTER_REQUEST, $form->reveal());

        $this->serializer
            ->serialize($form->reveal(), Argument::any(), Argument::type('array'))
            ->shouldBeCalled();

        $this->viewHandler->onView($event);
        self::assertEquals(Response::HTTP_BAD_REQUEST, $event->getResponse()->getStatusCode());
    }

    public function testShouldCallSubmitOnUnsubmittedForms(): void
    {
        $request = new Request();
        $request->attributes->set('_solido_view', new ViewAnnotation());

        $form = $this->prophesize(Form::class);
        $form->isSubmitted()->willReturn(false);
        $form->isValid()->willReturn(false);

        $form->submit(null)->shouldBeCalled();

        $event = new ViewEvent($this->httpKernel->reveal(), $request, HttpKernelInterface::MASTER_REQUEST, $form->reveal());
        $this->viewHandler->onView($event);
    }

    public function provideIterator(): iterable
    {
        yield [new \ArrayIterator(['foo' => 'bar'])];
        yield [new class() implements \IteratorAggregate {
            public function getIterator(): \Generator
            {
                yield from ['foo' => 'bar'];
            }
        }];
    }

    /**
     * @dataProvider provideIterator
     */
    public function testShouldTransformAnIteratorIntoAnArrayBeforeSerializing(iterable $iterator): void
    {
        $request = new Request();
        $request->attributes->set('_solido_view', new ViewAnnotation());

        $event = new ViewEvent($this->httpKernel->reveal(), $request, HttpKernelInterface::MASTER_REQUEST, $iterator);
        $this->serializer
            ->serialize(['foo' => 'bar'], Argument::any(), Argument::type('array'))
            ->shouldBeCalled();

        $this->viewHandler->onView($event);
    }

    public function testShouldAddXTotalCountHeaderForEntityIterators(): ObjectProphecy
    {
        $request = new Request();
        $request->attributes->set('_solido_view', new ViewAnnotation());

        $iterator = $this->prophesize(ObjectIteratorInterface::class);
        $iterator->count()->willReturn(42);
        $iterator->rewind()->willReturn();
        $iterator->valid()->willReturn(false);

        $event = new ViewEvent($this->httpKernel->reveal(), $request, HttpKernelInterface::MASTER_REQUEST, $iterator->reveal());

        $this->serializer
            ->serialize(Argument::type('array'), Argument::any(), Argument::type('array'))
            ->shouldBeCalled();

        $this->viewHandler->onView($event);
        self::assertEquals(42, $event->getResponse()->headers->get('X-Total-Count'));

        return $iterator;
    }

    /**
     * @depends testShouldAddXTotalCountHeaderForEntityIterators
     */
    public function testShouldUnwrapIteratorFromIteratorAggregate(ObjectProphecy $iterator): void
    {
        $result = new class($iterator->reveal()) implements \IteratorAggregate {
            private $iterator;

            public function __construct(\Iterator $iterator)
            {
                $this->iterator = $iterator;
            }

            public function getIterator(): \Iterator
            {
                return $this->iterator;
            }
        };

        $request = new Request();
        $request->attributes->set('_solido_view', new ViewAnnotation());

        $event = new ViewEvent($this->httpKernel->reveal(), $request, HttpKernelInterface::MASTER_REQUEST, $result);

        $this->serializer
            ->serialize(Argument::type('array'), Argument::any(), Argument::type('array'))
            ->shouldBeCalled();

        $this->viewHandler->onView($event);
        self::assertEquals(42, $event->getResponse()->headers->get('X-Total-Count'));
    }

    public function testShouldAddXContinuationTokenHeaderForPagerIterators(): void
    {
        $request = new Request();
        $request->attributes->set('_solido_view', new ViewAnnotation());

        $iterator = $this->prophesize(PagerIterator::class);
        $iterator->getNextPageToken()->willReturn(new PageToken((new \DateTimeImmutable('1991-11-24 02:00:00'))->getTimestamp(), 1, 1275024653));
        $iterator->rewind()->shouldBeCalled();
        $iterator->valid()->willReturn(false);

        $event = new ViewEvent($this->httpKernel->reveal(), $request, HttpKernelInterface::MASTER_REQUEST, $iterator->reveal());

        $this->serializer
            ->serialize(Argument::type('array'), Argument::any(), Argument::type('array'))
            ->shouldBeCalled();

        $this->viewHandler->onView($event);
        self::assertEquals('bfdew0_1_l347bh', $event->getResponse()->headers->get('X-Continuation-Token'));
    }

    public function testViewObjectShouldBeCorrectlyHandled(): void
    {
        $request = new Request();
        $request->attributes->set('_solido_view', new ViewAnnotation());

        $result = new View(['foobar' => 'no no no'], Response::HTTP_PAYMENT_REQUIRED);
        $event = new ViewEvent($this->httpKernel->reveal(), $request, HttpKernelInterface::MASTER_REQUEST, $result);

        $this->serializer
            ->serialize(Argument::type('array'), Argument::any(), Argument::type('array'))
            ->will(function () {
                return '{"foobar": "no no no"}';
            })
            ->shouldBeCalled();

        $this->viewHandler->onView($event);
        self::assertEquals(Response::HTTP_PAYMENT_REQUIRED, $event->getResponse()->getStatusCode());
    }

    public function testDeprecatedAnnotationShouldBeHandled(): void
    {
        $controller = new TestController();

        $request = new Request();
        $request->attributes->set('_controller', [$controller, 'deprecatedAction']);

        $event = new ControllerEvent($this->httpKernel->reveal(), $request->attributes->get('_controller'), $request, HttpKernelInterface::MASTER_REQUEST);
        $this->viewHandler->onController($event);

        self::assertTrue($request->attributes->has('_deprecated'));
    }

    public function testDeprecatedWithCommentAnnotationShouldBeHandled(): void
    {
        $controller = new TestController();

        $request = new Request();
        $request->attributes->set('_controller', [$controller, 'deprecatedWithNoticeAction']);

        $event = new ControllerEvent($this->httpKernel->reveal(), $request->attributes->get('_controller'), $request, HttpKernelInterface::MASTER_REQUEST);
        $this->viewHandler->onController($event);

        self::assertEquals('With Notice', $request->attributes->get('_deprecated'));
    }

    public function testShouldSetCorrectSerializationType(): void
    {
        $client = static::createClient();
        $client->request('GET', '/custom-serialization-type', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = $client->getResponse();

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('[{"data":"foobar","additional":"foo"},{"test":"barbar","additional":"foo"}]', $response->getContent());
    }

    public function testShouldSetCorrectSerializationTypeWhenProcessingAnIterator(): void
    {
        $client = static::createClient();
        $client->request('GET', '/custom-serialization-type-iterator', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = $client->getResponse();

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('[{"data":"foobar","additional":"foo"},{"test":"barbar","additional":"foo"}]', $response->getContent());
    }

    public function testShouldSetEmitXDeprecatedHeader(): void
    {
        $client = static::createClient();
        $client->request('GET', '/deprecated', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = $client->getResponse();

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertEquals('This endpoint has been deprecated and will be discontinued in a future version. Please upgrade your application.', $response->headers->get('X-Deprecated'));
    }

    public function testShouldSetResponseCharsetInContentType(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $response = $client->getResponse();

        self::assertMatchesRegularExpression('/; charset=UTF-8/', $response->headers->get('Content-Type'));
    }

    public function testCorrectlySerializesControllerResult(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = $client->getResponse();

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertEquals('{"test_foo":"foo.test"}', $response->getContent());
    }
}
