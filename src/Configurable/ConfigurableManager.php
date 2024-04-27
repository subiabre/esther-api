<?php

namespace App\Configurable;

use App\ApiResource\Configurable;
use App\Service\RoutesService;

class ConfigurableManager
{
    private string $configDir;

    public function __construct(
        private RoutesService $routesService
    ) {
        $configDir = $this->routesService->buildAbsolutePath('var', 'configurables');

        if (!\file_exists($configDir)) {
            \mkdir($configDir, 0777, true);
        }

        if (!\is_dir($configDir)) {
            throw new \Exception("The path $configDir is not a directory");
        }

        $this->configDir = $configDir;
    }

    public function get(string $name): ?Configurable
    {
        $result = @\file_get_contents($this->path($this->configDir, $name));

        if (!$result) {
            return null;
        }

        $storage = \json_decode($result, true);

        return new Configurable(
            $storage['name'],
            $storage['config']
        );
    }

    public function set(Configurable $storage): Configurable
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
