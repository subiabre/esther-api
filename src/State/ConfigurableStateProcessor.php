<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Configurable;
use App\Configurable\ConfigurableLocator;
use App\Configurable\ConfigurableManager;

class ConfigurableStateProcessor implements ProcessorInterface
{
    public function __construct(
        private ConfigurableLocator $configurableLocator,
        private ConfigurableManager $configurableManager
    ) {
    }

    /**
     * @param Configurable $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Configurable
    {
        $driver = $this->configurableLocator->getServiceByName($uriVariables['name']);
        $storage = $this->configurableManager->get($uriVariables['name']);

        $data->setConfig(\array_intersect_key(
            \array_merge($storage->getConfig(), $data->getConfig()),
            $driver::getConfiguration()
        ));

        return $this->configurableManager->set($data);
    }
}
