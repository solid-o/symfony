<?php

declare(strict_types=1);

namespace Solido\Symfony\DTO;

use Generator;
use Solido\DtoManagement\InterfaceResolver\ResolverInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function Safe\sprintf;

class ArgumentResolver implements ArgumentValueResolverInterface
{
    private ResolverInterface $resolver;

    public function __construct(ResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        /** @phpstan-var class-string $class */
        $class = $argument->getType();

        return $class !== null && $this->resolver->has($class);
    }

    public function resolve(Request $request, ArgumentMetadata $argument): Generator
    {
        /** @phpstan-var class-string $class */
        $class = $argument->getType();

        try {
            yield $this->resolver->resolve($class, $request->attributes->get('_version'));
        } catch (ServiceNotFoundException $exception) {
            throw new NotFoundHttpException(sprintf('%s object not found for version %s.', $class, $exception->getId()), $exception);
        }
    }
}
