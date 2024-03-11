<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class PhotoAddress
{
    public function __construct(
        #[ORM\Column(type: Types::STRING)]
        public string $fullName,

        #[ORM\Column(type: Types::STRING)]
        public string $shortName,

        #[ORM\Column(type: Types::JSON)]
        public array $components,
    ) {
    }
}
