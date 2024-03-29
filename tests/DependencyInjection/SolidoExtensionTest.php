<?php

declare(strict_types=1);

namespace Solido\Symfony\Tests\DependencyInjection;

use Kcs\Serializer\Bundle\DependencyInjection\CompilerPass\NamingStrategyPass;
use Kcs\Serializer\Bundle\DependencyInjection\SerializerExtension;
use PHPUnit\Framework\TestCase;
use Solido\Serialization\Adapter\KcsSerializerAdapter;
use Solido\Serialization\SerializerInterface;
use Solido\Symfony\DependencyInjection\CompilerPass\RegisterSerializerPass;
use Solido\Symfony\DependencyInjection\SolidoExtension;
use stdClass;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler\TestServiceContainerRealRefPass;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler\TestServiceContainerWeakRefPass;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadataFactory;

use function get_debug_type;
use function tempnam;
use function sys_get_temp_dir;

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

        $this->container->registerExtension(new class extends Extension {
            public function load(array $configs, ContainerBuilder $container): void
            {
                $container->register('argument_metadata_factory', ArgumentMetadataFactory::class);
            }

            public function getAlias(): string
            {
                return 'framework';
            }
        });
        $this->container->registerExtension(new SerializerExtension());
        $this->container->registerExtension($this->extension);

        $this->container->register('event_dispatcher', EventDispatcher::class);

        $this->container->loadFromExtension('framework', []);
        $this->container->loadFromExtension('kcs_serializer', []);
        $this->container->loadFromExtension($this->extension->getAlias(), [
            'serializer' => ['enabled' => true],
        ]);

        $this->container->compile();

        self::assertTrue($this->container->hasAlias(SerializerInterface::class));
        self::assertEquals(KcsSerializerAdapter::class, $this->container->findDefinition(SerializerInterface::class)->getClass());
    }

    /**
     * @dataProvider provideInvalidGroups
     */
    public function testShouldThrowOnInvalidSerializationGroupsType($data): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('expected array, ' . get_debug_type($data) . ' given');

        $this->container->registerExtension($this->extension);
        $this->container->loadFromExtension($this->extension->getAlias(), [
            'serializer' => ['groups' => $data],
        ]);

        $this->container->compile();
    }

    public function provideInvalidGroups()
    {
        yield [''];
        yield [42];
        yield [new stdClass()];
    }
}
