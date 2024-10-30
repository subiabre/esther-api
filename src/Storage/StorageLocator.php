<?php

namespace App\Storage;

use League\Flysystem\Filesystem;

class StorageLocator
{
    /** @var DriverInterface[] */
    private array $drivers;

    /**
     * @param iterable<DriverInterface> $drivers
     */
    public function __construct(
        iterable $drivers,
    ) {
        $this->drivers = \iterator_to_array($drivers);
    }

    /**
     * @return DriverInterface[]
     */
    public function getDrivers(): array
    {
        return $this->drivers;
    }

    public function getDriverByName(string $name): ?DriverInterface
    {
        foreach ($this->drivers as $driver) {
            if ($driver::getName() === $name) {
                return $driver;
            }
        }

        return null;
    }

    public function getFilesystem(string $name = 'local'): Filesystem
    {
        $driver = $this->getDriverByName($name);

        return new Filesystem(
            $driver->getAdapter(),
            ['public_url' => $driver->getPublicUrl()]
        );
    }
}
