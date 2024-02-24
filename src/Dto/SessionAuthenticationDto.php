<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class SessionAuthenticationDto
{
    public function __construct(
        #[Assert\Email()]
        #[Assert\NotBlank()]
        public readonly string $email,

        #[Assert\NotBlank()]
        #[Assert\Length(min: 12)]
        public readonly string $password
    ) {
    }
}
