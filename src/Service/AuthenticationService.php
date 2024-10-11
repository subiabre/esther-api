<?php

namespace App\Service;

use App\Entity\User;

class AuthenticationService
{
    public const SESSION_TOKEN_ALGO = 'sha256';

    public function __construct(
        private string $appSecret
    ) {}

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

    public static function parseRoles(array $input): array
    {
        $roles = [];
        foreach ($input as $role) {
            if (str_contains($role, ',')) {
                $roles = [...$roles, ...explode(',', $role)];
            } else {
                $roles[] = $role;
            }
        }

        return \array_map(function ($role) {
            return \strtoupper(sprintf("ROLE_%s", ltrim($role, "ROLE_")));
        }, \array_unique($roles));
    }
}
