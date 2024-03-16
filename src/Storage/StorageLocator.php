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

    public function getDriver(): DriverInterface
    {
        return $this->drivers[0];
    }

    public function getFilesystem(): Filesystem
    {
        $driver = $this->getDriver();

        return new Filesystem(
            $driver->getAdapter(),
            [
                'public_url' => $driver->getPublicUrl()
            ]
        );
    }
}
