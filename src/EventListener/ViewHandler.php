<?php

declare(strict_types=1);

namespace Solido\Symfony\EventListener;

use Doctrine\Common\Util\ClassUtils;
use ReflectionClass;
use ReflectionException;
use Solido\Serialization\Exception\UnsupportedFormatException;
use Solido\Serialization\SerializerInterface;
use Solido\Symfony\Annotation\View as ViewAnnotation;
use Solido\Symfony\Serialization\View\Context;
use Solido\Symfony\Serialization\View\View;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use function assert;
use function class_exists;
use function get_class;
use function is_array;
use function is_object;
use function is_string;
use function method_exists;
use function preg_match;
use function stripos;
use function trim;

class ViewHandler implements EventSubscriberInterface
{
    /**
     * DO NOT add typehint here: this event listener will be added to the preloaded classes list
     * and will break with a fatal error if the class is not found (security-core package not installed)
     *
     * @var TokenStorageInterface|null
     */
    private $tokenStorage; // phpcs:ignore

    public function __construct(
        private readonly SerializerInterface $serializer,
        TokenStorageInterface|null $tokenStorage,
        private readonly string $responseCharset,
    ) {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Handles the result of a controller, serializing it when needed.
     */
    public function onView(ViewEvent $event): void
    {
        $request = $event->getRequest();
        $result = $event->getControllerResult();
        if ($result instanceof Response) {
            return;
        }

        $annotation = $request->attributes->get('_solido_view', $request->attributes->get('_route_view') ? new ViewAnnotation() : null);
        if (! $annotation instanceof ViewAnnotation) {
            return;
        }

        if (! $result instanceof View) {
            $view = new View($result, $annotation->statusCode);
            $view->serializationType = $annotation->serializationType;

            $method = $annotation->groupsProvider;
            $groups = $annotation->groups;

            if ($method) {
                $viewContext = Context::create($request, $this->tokenStorage);
                $view->serializationGroups = $result->$method($viewContext);
            } elseif ($groups) {
                $view->serializationGroups = $groups;
            }

            $result = $view;
        }

        $headers = $result->headers;
        $requestFormat = $request->attributes->get('_format', 'html');
        assert(is_string($requestFormat));

        $headers['Content-Type'] = $request->getMimeType($requestFormat) . '; charset=' . $this->responseCharset;

        if ($request->attributes->has('_deprecated')) {
            $notice = $request->attributes->get('_deprecated');
            $headers['X-Deprecated'] = $notice === true ? 'This endpoint has been deprecated and will be discontinued in a future version. Please upgrade your application.' : $notice;
        }

        try {
            $content = $this->handle($result, $request);
            $response = new Response($content, $result->statusCode, $headers);
            $response->headers->set('Vary', 'Accept', false);
        } catch (UnsupportedFormatException) {
            $response = new Response(null, Response::HTTP_NOT_ACCEPTABLE);
        }

        $event->setResponse($response);
    }

    /**
     * Checks the controller for the deprecated annotation.
     *
     * @throws ReflectionException
     */
    public function onController(ControllerEvent $event): void
    {
        /** @phpstan-var string|object|array{0: object, 1: string} $controller */
        $controller = $event->getController();
        if (is_object($controller) && method_exists($controller, '__invoke')) {
            $controller = [$controller, '__invoke'];
        }

        if (! is_array($controller)) {
            return;
        }

        /** @phpstan-var class-string $className */
        $className = class_exists(ClassUtils::class) ? ClassUtils::getClass($controller[0]) : get_class($controller[0]);
        $object = new ReflectionClass($className);
        $method = $object->getMethod($controller[1]);

        $doc = $method->getDocComment();
        if ($doc === false || stripos($doc, '@deprecated') === false || ! preg_match('#^(?:/\*\*|\s*+\*)\s*+@deprecated(.*)$#mi', $doc, $matches)) {
            return;
        }

        $request = $event->getRequest();
        $request->attributes->set('_deprecated', isset($matches[1]) && $matches[1] ? trim($matches[1]) : true);
    }

    /**
     * Serializes the view with given serialization groups
     * and given type.
     */
    private function handle(View $view, Request $request): string|null
    {
        $format = $request->attributes->get('_format') ?? 'json';
        assert(is_string($format));

        $result = $view->result;
        $context = [
            'groups' => $view->serializationGroups,
            'type' => $view->serializationType,
            'serialize_null' => $view->serializeNull,
            'enable_max_depth' => $view->enableMaxDepthChecks,
        ];

        $serialized = $this->serializer->serialize($result, $format, $context);
        assert($serialized === null || is_string($serialized));

        return $serialized;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => 'onView',
            KernelEvents::CONTROLLER => 'onController',
        ];
    }
}
