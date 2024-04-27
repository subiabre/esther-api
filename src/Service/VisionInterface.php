<?php

namespace App\Service;

use App\Entity\Image;

interface VisionInterface
{
    /**
     * @return array{width: int, height: int, offsetX: int, offsetY: int}[]
     */
    public function getFaces(Image $image): array;
}
