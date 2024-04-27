<?php

namespace App\State;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Configurable;
use App\Configurable\ConfigurableLocator;
use App\Configurable\ConfigurableManager;

class ConfigurableStateProvider implements ProviderInterface
{
    public function __construct(
        private ConfigurableLocator $configurableLocator,
        private ConfigurableManager $configurableManager
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof GetCollection) {
            return array_map(function ($driver) {
                $name = $driver::getName();
                $storage = $this->configurableManager->get($name);

                if (!$storage) {
                    $storage = $this->configurableManager->set(new Configurable(
                        $name,
                        $driver::getConfiguration()
                    ));
                }

                return $storage;
            }, $this->configurableLocator->getServices());
        }

        return $this->configurableManager->get($uriVariables['name']);
    }
}
