<?php

namespace App\Command;

use App\Storage\StorageLocator;
use App\Storage\StorageManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:storage:list',
    description: 'Retrieves the available Storages',
)]
class StorageListCommand extends Command
{
    public function __construct(
        private StorageLocator $storageLocator,
        private StorageManager $storageManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->table(
            ['name', 'config'],
            $this->getStorageRows()
        );

        return Command::SUCCESS;
    }

    private function getStorageRows(): array
    {
        $drivers = $this->storageLocator->getDrivers();

        return array_map(function ($driver) {
            $storage = $this->storageManager->get($driver::getName());

            return [
                $storage->getName(),
                json_encode($storage->getConfig(), \JSON_PRETTY_PRINT)
            ];
        }, $drivers);
    }
}
