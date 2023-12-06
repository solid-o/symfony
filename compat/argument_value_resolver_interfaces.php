<?php

declare(strict_types=1);

namespace Symfony\Component\HttpKernel\Controller {
    if (!interface_exists(ArgumentValueResolverInterface::class)) {
        interface ArgumentValueResolverInterface {
        }
    }

    if (!interface_exists(ValueResolverInterface::class)) {
        interface ValueResolverInterface {
        }
    }
}
