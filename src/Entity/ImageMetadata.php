<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class ImageMetadata
{
    public function __construct(
        #[ORM\Column(type: Types::INTEGER)]
        public readonly int $width,

        #[ORM\Column(type: Types::INTEGER)]
        public readonly int $height,

        #[ORM\Column(type: Types::INTEGER)]
        public readonly int $filesize,

        #[ORM\Column(type: Types::STRING)]
        public readonly string $mimeType,

        #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
        public readonly \DateTimeImmutable $lastModified,

        #[ORM\Column(type: Types::JSON)]
        public readonly array $exif,
    ) {
    }
}
