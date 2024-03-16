<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Storage;
use App\Storage\StorageLocator;
use App\Storage\StorageManager;

class StorageStateProcessor implements ProcessorInterface
{
    public function __construct(
        private StorageLocator $storageLocator,
        private StorageManager $storageManager
    ) {
    }

    /**
     * @param Storage $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Storage
    {
        $driver = $this->storageLocator->getDriverByName($uriVariables['name']);
        $storage = $this->storageManager->get($uriVariables['name']);

        $data->setConfig(\array_intersect_key(
            \array_merge($storage->getConfig(), $data->getConfig()),
            $driver::getConfiguration()
        ));

        return $this->storageManager->set($data);
    }
}
