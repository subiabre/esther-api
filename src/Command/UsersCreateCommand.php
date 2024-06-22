<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:users:create',
    description: 'Creates a User resource',
)]
class UsersCreateCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $userPasswordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED)
            ->addArgument('password', InputArgument::REQUIRED)
            ->addOption('add-role', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $user = new User;
        $user->setRoles($input->getOption('add-role'));
        $user->setEmail($input->getArgument('email'));
        $user->setPassword($this->userPasswordHasher->hashPassword($user, $input->getArgument('password')));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->table([], [
            ['<info>User</info>', sprintf('#%d', $user->getId())],
            new TableSeparator(),
            ['id', $user->getId()],
            ['email', $user->getEmail()],
            ['roles', join(', ', $user->getRoles())]
        ]);

        return Command::SUCCESS;
    }
}
