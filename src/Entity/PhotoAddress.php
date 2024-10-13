<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Embeddable]
#[Gedmo\Loggable()]
class PhotoAddress
{
    public function __construct(
        #[ORM\Column(type: Types::STRING, nullable: true)]
        #[Gedmo\Versioned()]
        public ?string $fullName,

        #[ORM\Column(type: Types::STRING, nullable: true)]
        #[Gedmo\Versioned()]
        public ?string $shortName,

        #[ORM\Column(type: Types::JSON, nullable: true)]
        #[Gedmo\Versioned()]
        public ?array $components,

        #[ORM\Column(type: Types::STRING, nullable: true)]
        #[Gedmo\Versioned()]
        public ?string $reference,
    ) {
    }
}
