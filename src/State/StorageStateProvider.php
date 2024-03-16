<?php

namespace App\State;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Storage;
use App\Storage\StorageLocator;
use App\Storage\StorageManager;

class StorageStateProvider implements ProviderInterface
{
    public function __construct(
        private StorageLocator $storageLocator,
        private StorageManager $storageManager
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof GetCollection) {
            return array_map(function ($driver) {
                $name = $driver::getName();
                $storage = $this->storageManager->get($name);

                if (!$storage) {
                    $storage = $this->storageManager->set(new Storage(
                        $name,
                        $driver::getConfiguration()
                    ));
                }

                return $storage;
            }, $this->storageLocator->getDrivers());
        }

        return $this->storageManager->get($uriVariables['name']);
    }
}
