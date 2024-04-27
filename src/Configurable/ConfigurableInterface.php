<?php

namespace App\Configurable;

interface ConfigurableInterface
{
    public static function getName(): string;

    public static function getConfiguration(): array;

    public function isConfigured(): bool;
}
