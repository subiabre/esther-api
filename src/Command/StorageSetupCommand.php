<?php

namespace App\Command;

use App\ApiResource\Storage;
use App\Storage\StorageLocator;
use App\Storage\StorageManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:storage:setup',
    description: 'Post install Storage setup',
)]
class StorageSetupCommand extends Command
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

        $drivers = $this->storageLocator->getDrivers();
        foreach ($drivers as $driver) {
            $this->storageManager->set(new Storage(
                $driver::getName(),
                $driver::getConfiguration()
            ));
        }

        $io->success('Storage services setup successful.');

        return Command::SUCCESS;
    }
}
