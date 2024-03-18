<?php

namespace App\ApiResource;

use ApiPlatform\Metadata as API;
use App\State\StorageStateProcessor;
use App\State\StorageStateProvider;

#[API\GetCollection(
    provider: StorageStateProvider::class,
    security: "is_granted('ROLE_ADMIN')"
)]
#[API\Patch(
    provider: StorageStateProvider::class,
    processor: StorageStateProcessor::class,
    security: "is_granted('ROLE_ADMIN')"
)]
class Storage
{
    public function __construct(
        private string $name,
        private array $config
    ) {
    }

    #[API\ApiProperty(identifier: true, writable: false)]
    public function getName(): string
    {
        return $this->name;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): static
    {
        $this->config = $config;

        return $this;
    }
}
