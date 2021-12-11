<?php

declare(strict_types=1);

namespace Solido\Symfony\ArgumentMetadata;

use Solido\DtoManagement\Proxy\ProxyInterface;
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
     * {@inheritdoc}
     */
    public function createArgumentMetadata($controller): array
    {
        if (is_array($controller) && is_subclass_of($controller[0], ProxyInterface::class)) {
            $controller[0] = get_parent_class($controller[0]);
        } elseif ($controller instanceof ProxyInterface) {
            $controller = [get_parent_class($controller), '__invoke'];
        }

        return $this->decorated->createArgumentMetadata($controller);
    }
}
