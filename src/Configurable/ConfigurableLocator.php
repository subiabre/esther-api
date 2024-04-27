<?php

namespace App\Configurable;

class ConfigurableLocator
{
    /** @var ConfigurableInterface[] */
    private array $configurables;

    /**
     * @param iterable<ConfigurableInterface> $configurables
     */
    public function __construct(
        iterable $configurables,
    ) {
        $this->configurables = \iterator_to_array($configurables);
    }

    /**
     * @return ConfigurableInterface[]
     */
    public function getServices(): array
    {
        return $this->configurables;
    }

    public function getServiceByName(string $name): ?ConfigurableInterface
    {
        $result = null;

        foreach ($this->configurables as $configurable) {
            if ($configurable::getName() === $name) {
                $result = $configurable;
            }
        }

        return $result;
    }
}
