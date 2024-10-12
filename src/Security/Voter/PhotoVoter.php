<?php

namespace App\Security\Voter;

use App\Entity\Photo;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class PhotoVoter extends Voter
{
    public const VIEW = 'PHOTO_VIEW';
    public const EDIT = 'PHOTO_EDIT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT])
            && $subject instanceof Photo;
    }

    /**
     * @param Photo $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var User|null */
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        if ($user->hasRoles(['ROLE_ADMIN'])) {
            return true;
        }

        return $user->hasRoles($subject->getRoles());
    }
}
