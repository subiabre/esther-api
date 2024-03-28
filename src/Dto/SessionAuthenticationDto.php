<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class SessionAuthenticationDto
{
    /**
     * The email of the User who is trying to authenticate.
     */
    #[Assert\Email()]
    #[Assert\NotBlank()]
    public readonly string $email;

    /**
     * The plain-text password of the User who is trying to authenticate.
     */
    #[Assert\NotBlank()]
    #[Assert\Length(min: 12)]
    public readonly string $password;

    public function __construct(
        string $email,
        string $password
    ) {
        $this->email = $email;
        $this->password = $password;
    }
}
