<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Storage;
use App\Storage\StorageManager;

class StorageStateProcessor implements ProcessorInterface
{
    public function __construct(
        private StorageManager $storageManager
    ) {
    }

    /**
     * @param Storage $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Storage
    {
        return $this->storageManager->set($data);
    }
}
