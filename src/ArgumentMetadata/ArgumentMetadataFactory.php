<?php

declare(strict_types=1);

namespace Solido\Symfony\ArgumentMetadata;

use ReflectionFunctionAbstract;
use ReflectionMethod;
use Solido\DtoManagement\Proxy\ProxyInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadataFactoryInterface;

use function get_parent_class;
use function is_array;
use function is_subclass_of;

class ArgumentMetadataFactory implements ArgumentMetadataFactoryInterface
{
    private ArgumentMetadataFactoryInterface $decorated;

    public function __construct(ArgumentMetadataFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * @param string|object|array<mixed> $controller The controller to resolve the arguments for
     * @phpstan-param string|object|array{0: object, 1: string} $controller
     *
     * @return ArgumentMetadata[]
     */
    public function createArgumentMetadata($controller, ?ReflectionFunctionAbstract $reflector = null): array
    {
        if (is_array($controller) && is_subclass_of($controller[0], ProxyInterface::class)) {
            $controller[0] = get_parent_class($controller[0]);
            // @phpstan-ignore-next-line
            $reflector = new ReflectionMethod(...$controller);
        } elseif ($controller instanceof ProxyInterface) {
            $controller = [get_parent_class($controller), '__invoke'];
            // @phpstan-ignore-next-line
            $reflector = new ReflectionMethod(...$controller);
        }

        // @phpstan-ignore-next-line
        return $this->decorated->createArgumentMetadata($controller, $reflector);
    }
}
