<?php

declare(strict_types=1);

namespace Solido\Symfony\Tests\EventListener;

use Kcs\Serializer\SerializerBuilder;
use Solido\Atlante\Http\HeaderBag;
use Solido\Atlante\Requester\Exception\BadRequestException;
use Solido\Atlante\Requester\Response\BadResponse;
use Solido\Serialization\Adapter\KcsSerializerAdapter;
use Solido\Symfony\EventListener\BadResponseExceptionSubscriber;
use PHPUnit\Framework\TestCase;
use Solido\Symfony\Tests\Fixtures\View\AppKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class BadResponseExceptionSubscriberTest extends TestCase
{
    private BadResponseExceptionSubscriber $listener;

    protected function setUp(): void
    {
        $serializer = SerializerBuilder::create()->build();
        $this->listener = new BadResponseExceptionSubscriber(new KcsSerializerAdapter($serializer));
    }

    public function testShouldListenOnExceptionEvent(): void
    {
        self::assertArrayHasKey(KernelEvents::EXCEPTION, BadResponseExceptionSubscriber::getSubscribedEvents());
    }

    public function testShouldNotSetAResponseIfNotAnAtlanteGeneratedException(): void
    {
        $request = new Request();
        $kernel = new AppKernel('test', true);

        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, new \Exception());
        $this->listener->onException($event);

        self::assertFalse($event->isPropagationStopped());
        self::assertNull($event->getResponse());
    }

    public function testShouldSetAResponseIfExceptionIsAnAtlanteBadRequestException(): void
    {
        $request = new Request();
        $kernel = new AppKernel('test', true);

        $clientResponse = new BadResponse(new HeaderBag(), [
            'errors' => [
                'Invalid form',
            ],
            'name' => '',
            'children' => [
                [
                    'name' => 'field_1',
                    'children' => [],
                    'errors' => ['Bad bad value'],
                ],
            ],
        ]);

        $exception = new BadRequestException($clientResponse);

        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
        $this->listener->onException($event);

        self::assertTrue($event->isPropagationStopped());

        $response = $event->getResponse();
        self::assertJsonStringEqualsJsonString('{"detail": "","errors":["Invalid form"],"name":"","children":[{"errors":["Bad bad value"],"name":"field_1","children":[]}],"status":400,"title":"Bad Request","type":"http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html"}', $response->getContent());
    }
}
