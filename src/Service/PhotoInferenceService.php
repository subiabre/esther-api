<?php

namespace App\Service;

use App\Entity\Image;
use App\Entity\Photo;
use FuzzyWuzzy\Process;

class PhotoInferenceService
{
    /**
     * @param Photo $photo
     * @param Image[] $images
     * @return \FuzzyWuzzy\Collection
     */
    public function matchPhotoImages(
        Photo $photo,
        array $images,
        int $threshold = 90
    ): \FuzzyWuzzy\Collection {
        $needle = $photo->getImages()[0];

        $imagesAsChoices = [];
        $imagesByFilename = [];

        foreach ($images as $image) {
            $filename = $image->getSrcFilename();

            $imagesByFilename[$filename] = $image;

            if ($image->getId() !== $needle->getId()) {
                $imagesAsChoices[] = $filename;
            }
        }

        \asort($imagesAsChoices);

        $process = new Process();
        $matches = $process->extract($needle->getSrcFilename(), $imagesAsChoices);
        $matches = $matches->filter(function ($match) use ($threshold) {
            return $match[1] > $threshold;
        });

        return $matches->map(function ($match) use ($imagesByFilename) {
            return new class($match, $imagesByFilename)
            {
                public readonly int $score;

                public readonly Image $image;

                public function __construct($match, $images)
                {
                    $this->score = $match[1];
                    $this->image = $images[$match[0]];
                }

                public function __toString()
                {
                    return sprintf(
                        "<comment>%s</comment> (%s%%) [%s]",
                        $this->image->getSrcFilename(),
                        $this->score,
                        $this->image->getSrc()
                    );
                }
            };
        });
    }
}
