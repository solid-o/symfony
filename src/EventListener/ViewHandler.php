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

use function class_exists;
use function get_class;
use function is_array;
use function is_object;
use function method_exists;
use function Safe\preg_match;
use function stripos;
use function trim;

class ViewHandler implements EventSubscriberInterface
{
    private SerializerInterface $serializer;
    private ?TokenStorageInterface $tokenStorage;
    private string $responseCharset;

    public function __construct(
        SerializerInterface $serializer,
        ?TokenStorageInterface $tokenStorage,
        string $responseCharset
    ) {
        $this->serializer = $serializer;
        $this->tokenStorage = $tokenStorage;
        $this->responseCharset = $responseCharset;
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
        $headers['Content-Type'] = $request->getMimeType($request->attributes->get('_format', 'html')) . '; charset=' . $this->responseCharset;

        if ($request->attributes->has('_deprecated')) {
            $notice = $request->attributes->get('_deprecated');
            $headers['X-Deprecated'] = $notice === true ? 'This endpoint has been deprecated and will be discontinued in a future version. Please upgrade your application.' : $notice;
        }

        try {
            $content = $this->handle($result, $request);
            $response = new Response($content, $result->statusCode, $headers);
            $response->headers->set('Vary', 'Accept', false);
        } catch (UnsupportedFormatException $e) {
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
     *
     * @return mixed|string
     */
    private function handle(View $view, Request $request)
    {
        $format = $request->attributes->get('_format') ?? 'json';

        $result = $view->result;
        $context = [
            'groups' => $view->serializationGroups,
            'type' => $view->serializationType,
            'serialize_null' => $view->serializeNull,
            'enable_max_depth' => $view->enableMaxDepthChecks,
        ];

        return $this->serializer->serialize($result, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => 'onView',
            KernelEvents::CONTROLLER => 'onController',
        ];
    }
}
