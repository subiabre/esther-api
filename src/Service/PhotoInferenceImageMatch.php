<?php

namespace App\Service;

use App\Entity\Image;

class PhotoInferenceImageMatch
{
    public function __construct(
        public readonly Image $image
    ) {
    }

    public function __toString()
    {
        return sprintf(
            "<comment>%s</comment> [%s]",
            $this->image->getSrcFilename(),
            $this->image->getSrc()
        );
    }
}
