<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class ScopesVoter extends Voter
{
    public const VIEW = 'SCOPE_VIEW';
    public const EDIT = 'SCOPE_EDIT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT])
            && method_exists($subject, 'getScopes');
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var null|User */
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        if ($user->hasRoles(['ROLE_ADMIN'])) {
            return true;
        }

        foreach ($subject->getScopes() as $scope) {
            if (\in_array($scope->getRole(), $user->getRoles())) {
                return true;
            }
        }

        return false;
    }
}
