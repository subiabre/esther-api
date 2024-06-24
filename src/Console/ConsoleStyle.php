<?php

namespace App\Console;

use App\Entity\User;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConsoleStyle extends SymfonyStyle
{
    public function userTable(User $user)
    {
        $this->table([], [
            ['<info>User</info>', sprintf('#%d', $user->getId())],
            new TableSeparator(),
            ['id', $user->getId()],
            ['email', $user->getEmail()],
            ['roles', join(', ', $user->getRoles())],
            ['sessions', $user->getSessions()->count()],
            ['created', $user->getDateCreated()?->format(\DateTime::RFC3339)],
            ['updated', $user->getDateUpdated()?->format(\DateTime::RFC3339)]
        ]);
    }
}
