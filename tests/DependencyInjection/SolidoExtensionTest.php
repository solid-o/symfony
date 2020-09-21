<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\DependencyInjection;

use Kcs\Serializer\Bundle\DependencyInjection\CompilerPass\NamingStrategyPass;
use Kcs\Serializer\Bundle\DependencyInjection\SerializerExtension;
use PHPUnit\Framework\TestCase;
use Solido\Serialization\Adapter\KcsSerializerAdapter;
use Solido\Serialization\SerializerInterface;
use Solido\Symfony\DependencyInjection\CompilerPass\RegisterSerializerPass;
use Solido\Symfony\DependencyInjection\SolidoExtension;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler\TestServiceContainerRealRefPass;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler\TestServiceContainerWeakRefPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\EventDispatcher\EventDispatcher;
use function Safe\tempnam;

class SolidoExtensionTest extends TestCase
{
    private ParameterBag $parameterBag;
    private ContainerBuilder $container;
    private SolidoExtension $extension;

    protected function setUp(): void
    {
        $this->parameterBag = new ParameterBag([
            'kernel.debug' => true,
            'kernel.cache_dir' => tempnam(sys_get_temp_dir(), 'solido-di'),
        ]);
        $this->container = new ContainerBuilder($this->parameterBag);
        $this->extension = new SolidoExtension();
    }

    public function testShouldRegisterSerializerInterfaceAlias(): void
    {
        $this->container->addCompilerPass(new NamingStrategyPass());
        $this->container->addCompilerPass(new RegisterSerializerPass());
        $this->container->addCompilerPass(new TestServiceContainerWeakRefPass());
        $this->container->addCompilerPass(new TestServiceContainerRealRefPass());

        $this->container->registerExtension(new SerializerExtension());
        $this->container->registerExtension($this->extension);

        $this->container->register('event_dispatcher', EventDispatcher::class);

        $this->container->loadFromExtension('kcs_serializer', []);
        $this->container->loadFromExtension($this->extension->getAlias(), [
            'serializer' => [
                'enabled' => true,
            ],
        ]);

        $this->container->compile();

        self::assertTrue($this->container->hasAlias(SerializerInterface::class));
        self::assertEquals(KcsSerializerAdapter::class, $this->container->findDefinition(SerializerInterface::class)->getClass());
    }
}
