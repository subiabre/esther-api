<?php

namespace App\State;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Person;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\User\UserInterface;

class PeopleStateProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        #[Autowire(service: CollectionProvider::class)]
        private ProviderInterface $collectionProvider,
        #[Autowire(service: ItemProvider::class)]
        private ProviderInterface $itemProvider
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();

        if (!$user instanceof UserInterface) {
            return null;
        }

        if ($operation instanceof CollectionOperationInterface) {
            $people = $this->collectionProvider->provide($operation, $uriVariables, $context);

            if ($this->security->isGranted('ROLE_ADMIN')) {
                return $people;
            }

            $safePeople = [];
            foreach ($people as $person) {
                $safePeople[] = $this->scopePortraits($user, $person);
            }

            return new TraversablePaginator(
                new \ArrayIterator($safePeople),
                $people->getCurrentPage(),
                $people->getItemsPerPage(),
                $people->getTotalItems()
            );
        }

        $person = $this->itemProvider->provide($operation, $uriVariables, $context);

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return $person;
        }

        return $this->scopePortraits($user, $person);
    }

    private function scopePortraits(User $user, Person $person): Person
    {
        $safePerson = new Person();
        $safePerson->setId($person->getId());
        $safePerson->setName($person->getName());

        $portraits = $person->getPortraits();
        foreach ($portraits as $portrait) {
            if (!$user->hasRoles($portrait->getImage()->getPhoto()->getRoles())) {
                continue;
            }

            $safePerson->addPortrait($portrait);
        }

        return $safePerson;
    }
}
