<?php

namespace App\Entity\Trait;

use ApiPlatform\Metadata as API;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait TimestampableUpdation
{
    #[API\ApiProperty(writable: false)]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected $dateUpdated;

    public function getDateUpdated()
    {
        return $this->dateUpdated;
    }

    public function setDateUpdated(\DateTime $dateUpdated)
    {
        $this->dateUpdated = $dateUpdated;

        return $this;
    }
}
