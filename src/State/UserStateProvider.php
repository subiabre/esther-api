<?php

namespace App\State;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Loggable\Entity\LogEntry;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class UserStateProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        #[Autowire(service: CollectionProvider::class)]
        private ProviderInterface $collectionProvider,
        #[Autowire(service: ItemProvider::class)]
        private ProviderInterface $itemProvider
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $users = $this->collectionProvider->provide($operation, $uriVariables, $context);

            $usersWithLogEntries = [];
            foreach ($users as $user) {
                $usersWithLogEntries[] = $this->getLogEntries($user);
            }

            return new TraversablePaginator(
                new \ArrayIterator($usersWithLogEntries),
                $users->getCurrentPage(),
                $users->getItemsPerPage(),
                $users->getTotalItems()
            );
        }

        $user = $this->itemProvider->provide($operation, $uriVariables, $context);

        return $this->getLogEntries($user);
    }

    private function getLogEntries(User $user): User
    {
        $repo = $this->entityManager->getRepository(LogEntry::class);
        $entries = $repo->findBy(['username' => $user->getUserIdentifier()]);

        $user->setLogEntries($entries);

        return $user;
    }
}
