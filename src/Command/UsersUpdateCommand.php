<?php

namespace App\Command;

use App\Console\ConsoleStyle;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:users:update',
    description: 'Updates the User resource',
)]
class UsersUpdateCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $userPasswordHasher,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('id', InputArgument::REQUIRED, 'User id or email')
            ->addOption('email', null, InputOption::VALUE_OPTIONAL)
            ->addOption('password', null, InputOption::VALUE_OPTIONAL)
            ->addOption('add-role', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY)
            ->addOption('remove-role', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new ConsoleStyle($input, $output);

        $user = $this->userRepository->findOneByIdOrEmail($input->getArgument('id'));
        if (!$user) {
            $io->error("No User found with the given id or email.");

            return Command::FAILURE;
        }

        $email = $input->getOption('email');
        if ($email) {
            $user->setEmail($email);
        }

        $password = $input->getOption('password');
        if ($password) {
            $user->setPassword($this->userPasswordHasher->hashPassword($user, $password));
        }

        $rolesToAdd = $input->getOption('add-role');
        if ($rolesToAdd) {
            $user->setRoles(array_unique([
                ...$user->getRoles(),
                ...User::parseRoles($rolesToAdd)
            ]));
        }

        $rolesToRemove = $input->getOption('remove-role');
        if ($rolesToRemove) {
            $user->setRoles(array_diff(
                $user->getRoles(),
                User::parseRoles($rolesToRemove)
            ));
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->userTable($user);

        return Command::SUCCESS;
    }
}
