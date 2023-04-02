<?php

declare(strict_types=1);

namespace Solido\Symfony\DTO\Extension;

use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Laminas\Code\Generator\PropertyValueGenerator;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Solido\DtoManagement\Proxy\Builder\ProxyBuilder;
use Solido\Symfony\DTO\GetSubscribedServicesGenerator;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

use function Safe\sprintf;
use function sha1;
use function uniqid;

trait SubscribedServicesGeneratorTrait
{
    private ProxyBuilder $builder;

    /** @param array<string, string> $services */
    private function addServices(array $services): void
    {
        $methods = $this->builder->getExtraMethods();
        $method = null;

        foreach ($methods as $methodGenerator) {
            if ($methodGenerator instanceof GetSubscribedServicesGenerator) {
                $method = $methodGenerator;
                break;
            }
        }

        if (! isset($method)) {
            $containerName = '_container_' . sha1(uniqid('container-', true));

            $this->builder->addInterface(ServiceSubscriberInterface::class);
            $this->builder->addProperty(new PropertyGenerator($containerName, new PropertyValueGenerator(null, ContainerInterface::class), PropertyGenerator::FLAG_PRIVATE), '');

            $setContainerBody = sprintf('%s$this->%s = $container;', $this->builder->hasMethod('setContainer') ? 'parent::setContainer($container); ' : '', $containerName);
            $this->builder->addMethod(new MethodGenerator('setContainer', [
                new ParameterGenerator('container', ContainerInterface::class),
            ], MethodGenerator::FLAG_PUBLIC, $setContainerBody, '@required'));

            $method = new GetSubscribedServicesGenerator($this->builder->hasMethod('getSubscribedServices'), $containerName);
            $this->builder->addMethod($method);
        }

        foreach ($services as $service => $class) {
            $method->addService($service, $class);
        }
    }

    /** @internal */
    public function getContainerName(): string
    {
        $methods = $this->builder->getExtraMethods();
        foreach ($methods as $methodGenerator) {
            if ($methodGenerator instanceof GetSubscribedServicesGenerator) {
                return $methodGenerator->getContainerName();
            }
        }

        throw new RuntimeException('Cannot find method generator');
    }
}
