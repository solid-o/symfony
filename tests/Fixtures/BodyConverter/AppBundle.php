<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\BodyConverter;

use Solido\Symfony\DependencyInjection\CompilerPass\RegisterBodyConverterDecoders;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AppBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterBodyConverterDecoders());
    }
}
