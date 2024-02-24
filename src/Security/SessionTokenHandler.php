<?php

namespace App\Security;

use App\Repository\SessionRepository;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class SessionTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private SessionRepository $sessionRepository
    ) {
    }

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        $session = $this->sessionRepository->findOneBy(['token' => $accessToken]);
        if (!$session) {
            throw new AuthenticationCredentialsNotFoundException();
        }

        return new UserBadge($session->getUser()->getUserIdentifier());
    }
}
