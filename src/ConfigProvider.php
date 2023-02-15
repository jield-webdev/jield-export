<?php

namespace Jield\Export;

use Jield\Export\Command\UpdateIndex;
use Jield\Export\Factory\ConsoleServiceFactory;
use Jield\Export\Service\ConsoleService;
use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            ConfigAbstractFactory::class => $this->getConfigAbstractFactory(),
            'service_manager'            => $this->getServiceMangerConfig(),
            'laminas-cli'                => $this->getCommandConfig(),
        ];
    }

    public function getCommandConfig(): array
    {
        return [
            'commands' => [
                'export:send' => UpdateIndex::class,
            ]
        ];
    }

    public function getServiceMangerConfig(): array
    {
        return [
            'factories' => [
                ConsoleService::class => ConsoleServiceFactory::class,
            ],
        ];
    }

    public function getConfigAbstractFactory(): array
    {
        return [

        ];
    }
}
