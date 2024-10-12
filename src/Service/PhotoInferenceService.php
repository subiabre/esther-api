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
    public function matchPhotoImagesByFuzzy(
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

    public function matchPhotoImagesByRegex(
        Photo $photo,
        array $images,
        string $pattern
    ): array {
        $needle = $photo->getImages()[0];

        \preg_match("/$pattern/", $needle->getSrcFilename(), $patternMatches);
        if (empty($patternMatches)) {
            return [];
        }

        $key = \preg_replace("/$patternMatches[0]/", "", $needle->getSrcFilename());

        $choices = [];
        foreach ($images as $image) {
            if ($image->getId() === $needle->getId()) {
                continue;
            }

            $filename = $image->getSrcFilename();

            if (!\preg_match("/$key$pattern/", $filename)) {
                continue;
            }

            $choices[$filename] = [
                'score' => \strlen(\str_replace($key, "", $filename)),
                'item' => ['image' => $image]
            ];
        }

        \ksort($choices);

        return \array_map(function ($match) {
            return new PhotoInferenceImageMatch($match);
        }, \array_values($choices));
    }
}
