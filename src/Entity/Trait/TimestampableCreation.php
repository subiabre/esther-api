<?php

namespace App\Entity\Trait;

use ApiPlatform\Metadata as API;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait TimestampableCreation
{
    #[API\ApiProperty(writable: false)]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected $dateCreated;

    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    public function setDateCreated(\DateTime $dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
