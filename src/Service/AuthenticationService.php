<?php

namespace App\Service;

use App\Entity\User;

class AuthenticationService
{
    public const SESSION_TOKEN_ALGO = 'sha256';

    public function __construct(
        private string $appSecret
    ) {
    }

    public function generateSessionToken(User $user): string
    {
        return hash(
            self::SESSION_TOKEN_ALGO,
            join('', [
                microtime(true),
                $this->appSecret,
                random_bytes(32),
                $user->getUserIdentifier()
            ])
        );
    }
}
