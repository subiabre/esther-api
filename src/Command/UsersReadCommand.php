<?php

namespace App\Command;

use App\Console\ConsoleStyle;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:users:get',
    description: 'Retrieves a User resource',
)]
class UsersReadCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('id', InputArgument::REQUIRED, 'User id or email');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new ConsoleStyle($input, $output);

        $user = $this->userRepository->findOneByIdOrEmail($input->getArgument('id'));
        if (!$user) {
            $io->error("No User found with the given id or email.");

            return Command::FAILURE;
        }

        $io->userTable($user);

        return Command::SUCCESS;
    }
}
