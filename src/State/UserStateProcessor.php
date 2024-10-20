<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserStateProcessor implements ProcessorInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $userPasswordHasher,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * @param User $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        $user = $this->userRepository->find($data->getId());
        if (!$user) {
            throw new \Exception("User not found");
        }

        $entries = $user->getLogEntries();
        foreach ($entries as $entry) {
            if ($entry->getUsername() === $data->getUserIdentifier()) {
                continue;
            }

            $entry->setUsername($data->getUserIdentifier());
            $this->entityManager->persist($entry);
        }

        if ($data->getPlainPassword()) {
            $data->setPassword($this->userPasswordHasher->hashPassword($data, $data->getPlainPassword()));
            $data->eraseCredentials();
        }

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}
