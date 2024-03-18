<?php

namespace App\Security\Voter;

use App\Entity\Session;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class SessionVoter extends Voter
{
    public const EDIT = 'SESSION_EDIT';
    public const VIEW = 'SESSION_VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW])
            && $subject instanceof Session;
    }

    /**
     * @param Session $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var User|null */
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        switch ($attribute) {
            case self::EDIT:
            case self::VIEW:
                return $user->hasRoles(['ROLE_ADMIN'])
                    || $subject->isOwnedBy($user);
                break;
        }

        return false;
    }
}
