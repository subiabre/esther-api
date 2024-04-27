<?php

namespace App\ApiResource;

use ApiPlatform\Metadata as API;
use App\State\ConfigurableStateProcessor;
use App\State\ConfigurableStateProvider;

/**
 * Storages hold configuration necessary for the API to operate with an external storage service.
 */
#[API\GetCollection(
    provider: ConfigurableStateProvider::class,
    security: "is_granted('ROLE_ADMIN')"
)]
#[API\Patch(
    provider: ConfigurableStateProvider::class,
    processor: ConfigurableStateProcessor::class,
    security: "is_granted('ROLE_ADMIN')"
)]
class Configurable
{
    public function __construct(
        private string $name,
        private array $config
    ) {
    }

    /**
     * Represents the underlying storage service.
     */
    #[API\ApiProperty(identifier: true, writable: false)]
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * A collection of key-value pairs for the Configurable configuration options.
     */
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
