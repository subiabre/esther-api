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
        $result = null;

        foreach ($this->drivers as $driver) {
            if ($driver::getName() === $name) {
                $result = $driver;
            }
        }

        return $result;
    }

    public function getFilesystem(string $name = 'local'): Filesystem
    {
        $driver = $name ? $this->getDriverByName($name) : $this->drivers[0];

        return new Filesystem(
            $driver->getAdapter(),
            [
                'public_url' => $driver->getPublicUrl()
            ]
        );
    }
}
