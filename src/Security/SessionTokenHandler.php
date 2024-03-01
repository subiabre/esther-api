<?php

namespace App\Security;

use App\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class SessionTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private SessionRepository $sessionRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        $session = $this->sessionRepository->findOneBy(['token' => $accessToken]);
        
        if (!$session) {
            throw new AuthenticationCredentialsNotFoundException();
        }

        $session->setDateUpdated(new \DateTime());
        $this->entityManager->persist($session);
        $this->entityManager->flush();

        return new UserBadge($session->getUser()->getUserIdentifier());
    }
}
