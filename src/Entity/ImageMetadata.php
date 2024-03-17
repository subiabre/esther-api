<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class ImageMetadata
{
    public function __construct(
        #[ORM\Column(type: Types::INTEGER)]
        public int $width,

        #[ORM\Column(type: Types::INTEGER)]
        public int $height,

        #[ORM\Column(type: Types::STRING)]
        public string $mimeType,

        #[ORM\Column(type: Types::INTEGER)]
        public int $filesize,

        #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
        public ?\DateTimeImmutable $filedate,

        #[ORM\Column(type: Types::JSON)]
        public array $exif,
    ) {
    }
}
