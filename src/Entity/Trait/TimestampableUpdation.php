<?php

namespace App\Entity\Trait;

use ApiPlatform\Metadata as API;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

trait TimestampableUpdation
{
    #[API\ApiProperty(writable: false)]
    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
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
