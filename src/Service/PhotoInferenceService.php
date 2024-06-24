<?php

namespace App\Service;

use App\Entity\Image;
use App\Entity\Photo;
use Fuse\Fuse;

class PhotoInferenceService
{
    /**
     * @param Photo $photo
     * @param Image[] $images
     * @return array
     */
    public function matchPhotoImages(
        Photo $photo,
        array $images,
        float $threshold = 0.2
    ): array {
        $needle = $photo->getImages()[0];

        $choices = [];
        foreach ($images as $image) {
            if ($image->getId() === $needle->getId()) {
                continue;
            }

            $filename = $image->getSrcFilename();
            $choices[$filename] = [
                'filename' => $filename,
                'image' => $image
            ];
        }

        \ksort($choices);

        $fuse = new Fuse(
            \array_values($choices),
            [
                'includeScore' => true,
                'minMatchCharLength' => 3,
                'shouldSort' => true,
                'threshold' => $threshold,
                'keys' => ['filename']
            ]
        );

        return \array_map(function ($match) {
            return new PhotoInferenceImageMatch($match);
        }, $fuse->search($needle->getSrcFilename()));
    }
}
