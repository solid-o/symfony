<?php

declare(strict_types=1);

namespace Solido\Symfony\Security;

use Closure;
use ProxyManager\Proxy\ProxyInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use Solido\Common\Urn\UrnGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ErrorController;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use function is_array;
use function is_callable;
use function is_object;
use function Safe\preg_replace;
use function Safe\sprintf;
use function ucfirst;

class ActionListener implements EventSubscriberInterface
{
    private TokenStorageInterface $tokenStorage;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        $request = $event->getRequest();
        if ($request->isMethod(Request::METHOD_OPTIONS) || $this->tokenStorage->getToken() === null || $request->attributes->has('_security')) {
            return;
        }

        $controller = $event->getController();
        if ($controller instanceof ErrorController) {
            return;
        }

        $object = null;
        if (is_array($controller)) {
            $r = new ReflectionMethod($controller[0], $controller[1]);
            $methodName = $r->getName();
            if (! $controller[0] instanceof AbstractController) {
                $reflClass = new ReflectionClass($controller[0]);
                $methodName .= $reflClass->isSubclassOf(ProxyInterface::class) && ($parent = $reflClass->getParentClass()) ? $parent->getShortName() : $reflClass->getShortName();
            }
        } elseif (is_object($controller) && is_callable([$controller, '__invoke'])) {
            $reflClass = new ReflectionClass($controller);
            $methodName = $reflClass->isSubclassOf(ProxyInterface::class) && ($parent = $reflClass->getParentClass()) ? $parent->getShortName() : $reflClass->getShortName();
        } else {
            $r = new ReflectionFunction(Closure::fromCallable($controller));
            $methodName = $r->getName();
        }

        $methodName = preg_replace('/action$/i', '', ucfirst($methodName));
        $item = null;

        foreach ($event->getArguments() as $argument) {
            if ($argument instanceof UrnGeneratorInterface) {
                $item = $argument;
                break;
            }
        }

        if (! $this->authorizationChecker->isGranted($methodName, $item ?? null)) {
            throw new AccessDeniedException(sprintf('Access denied: you don\'t have "%s" permission%s.', $methodName, $item instanceof UrnGeneratorInterface ? ' on resource "' . $item->getUrn() . '"' : ''));
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): iterable
    {
        yield KernelEvents::CONTROLLER_ARGUMENTS => ['onKernelControllerArguments', 2];
    }
}
