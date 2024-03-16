<?php

namespace App\Storage;

use League\Flysystem\FilesystemAdapter;

interface DriverInterface
{
    public static function getName(): string;

    /**
     * @return array An array of parameters that the driver expects
     */
    public static function getConfiguration(): array;

    public function getAdapter(): FilesystemAdapter;

    /**
     * @return string[]
     */
    public function getPublicUrl(): array;
}
