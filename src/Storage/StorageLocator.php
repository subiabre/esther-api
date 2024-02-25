<?php

namespace App\Storage;

use League\Flysystem\Filesystem;

class StorageLocator
{
    /**
     * @param iterable<DriverInterface> $drivers
     */
    public function __construct(
        private iterable $drivers
    ) {
    }

    public function getDriver(): DriverInterface
    {
        return $this->drivers[0];
    }

    public function getFilesystem(): Filesystem
    {
        return new Filesystem($this->getDriver()->getAdapter());
    }
}
