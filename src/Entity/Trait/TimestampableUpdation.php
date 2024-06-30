<?php

namespace App\Entity\Trait;

use ApiPlatform\Metadata as API;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation\Timestampable;

trait TimestampableUpdation
{
    #[Timestampable(on: 'update')]
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
