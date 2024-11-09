<?php

namespace App\Service;

use App\Entity\Image;

class PhotoInferenceImageMatch
{
    public readonly Image $image;

    public function __construct(
        private array $match
    ) {
        $this->image = $match['item']['image'];
    }

    public function __toString()
    {
        return sprintf(
            "<comment>%s</comment> [distance: %s] [src: %s]",
            $this->match['item']['image']->getFilename(),
            $this->match['score'],
            $this->match['item']['image']->getSrc(),
        );
    }
}
