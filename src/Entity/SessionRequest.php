<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class SessionRequest
{
    public function __construct(
        #[ORM\Column(type: Types::STRING, nullable: true)]
        public readonly ?string $origin,

        #[ORM\Column(type: Types::STRING, nullable: true)]
        public readonly ?string $userAgent,
    ) {
    }
}
