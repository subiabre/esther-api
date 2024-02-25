<?php

namespace App\Storage;

use League\Flysystem\FilesystemAdapter;

interface DriverInterface
{
    public function getAdapter(): FilesystemAdapter;

    /**
     * @return string[]
     */
    public function getPublicUrl(): array;
}
