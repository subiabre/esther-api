<?php

namespace App\Command;

use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:users:list',
    description: 'Retrieves the collection of User resources',
)]
class UsersListCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->table(
            ['id', 'email', 'roles'],
            $this->getUserRows()
        );

        return Command::SUCCESS;
    }

    private function getUserRows(): array
    {
        $users = $this->userRepository->findAll();

        return array_map(function ($user) {
            return [
                $user->getId(),
                $user->getEmail(),
                join(', ', $user->getRoles())
            ];
        }, $users);
    }
}
