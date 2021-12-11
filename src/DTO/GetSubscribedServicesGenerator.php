<?php

declare(strict_types=1);

namespace Solido\Symfony\DTO;

use Laminas\Code\Generator\MethodGenerator;

use function array_keys;
use function array_map;
use function implode;
use function Safe\sprintf;

class GetSubscribedServicesGenerator extends MethodGenerator
{
    /** @var array<string, string> */
    private array $services = [];
    private bool $callParent;
    private string $containerName;

    public function __construct(bool $callParent, string $containerName)
    {
        parent::__construct('getSubscribedServices', [], MethodGenerator::FLAG_PUBLIC | MethodGenerator::FLAG_STATIC, null, '{@inheritDoc}');

        $this->setReturnType('array');
        $this->callParent = $callParent;
        $this->containerName = $containerName;
    }

    public function addService(string $name, string $class): void
    {
        $this->services[$name] = $class;
        $this->updateBody();
    }

    public function getContainerName(): string
    {
        return $this->containerName;
    }

    private function updateBody(): void
    {
        $callParent = $this->callParent ? 'parent::getSubscribedServices() + ' : '';
        $services = array_map(static fn (string $k, string $v) => sprintf("'%s' => '%s'", $k, $v), array_keys($this->services), $this->services);

        $this->body = sprintf('
return %s[
    %s,
];
', $callParent, implode(",\n    ", $services));
    }
}
