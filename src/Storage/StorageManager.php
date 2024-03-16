<?php

namespace App\Storage;

use App\ApiResource\Storage;

class StorageManager
{
    private string $configDir;

    public function __construct(
        private string $rootDir
    ) {
        $configDir = sprintf(
            "%s%svar%s%s",
            \rtrim($this->rootDir, '\/'),
            \DIRECTORY_SEPARATOR,
            \DIRECTORY_SEPARATOR,
            "storage"
        );

        if (!\file_exists($configDir)) {
            \mkdir($configDir, 0777, true);
        }

        if (!\is_dir($configDir)) {
            throw new \Exception("The path $configDir is not a directory");
        }

        $this->configDir = $configDir;
    }

    public function get(string $name): ?Storage
    {
        $result = @\file_get_contents($this->path($this->configDir, $name));

        if (!$result) {
            return null;
        }

        $storage = \json_decode($result, true);

        return new Storage(
            $storage['name'],
            $storage['config']
        );
    }

    public function set(Storage $storage): Storage
    {
        \file_put_contents(
            $this->path($this->configDir, $storage->getName()),
            json_encode([
                'name' => $storage->getName(),
                'config' => $storage->getConfig()
            ])
        );

        return $storage;
    }

    private function path(...$values): string
    {
        $args = [];
        foreach ($values as $value) {
            $args = [...$args, \DIRECTORY_SEPARATOR, $value];
        }

        return sprintf(
            str_repeat("%s", count($args)),
            ...$args
        );
    }
}
