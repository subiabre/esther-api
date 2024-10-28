<?php

namespace App\Service;

use App\Entity\Image;
use App\Entity\Photo;
use App\Entity\PhotoDateRange;
use Fuse\Fuse;

class PhotoInferenceService
{
    public const DATE_FILENAME_PATTERN = '/^[0-9-]+([a-z]+)?+/';

    public const DATE_MODIFIER_1Y = 'a';
    public const DATE_MODIFIER_3Y = 'b';
    public const DATE_MODIFIER_5Y = 'c';

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
        $needlename = \strtoupper($needle->getSrcFilename());

        \preg_match("/$pattern/", $needlename, $patternMatches);
        if (empty($patternMatches)) {
            return [];
        }

        $key = \preg_quote(\preg_replace("/$pattern/", "", $needlename));

        $choices = [];
        foreach ($images as $image) {
            if ($image->getId() === $needle->getId()) {
                continue;
            }

            $filename = \strtoupper($image->getSrcFilename());
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

    public function parseDateInFilename(string $filename): ?PhotoDateRange
    {
        \preg_match(self::DATE_FILENAME_PATTERN, $filename, $dateInFilename);

        if (empty($dateInFilename)) {
            return null;
        }

        $date = $dateInFilename[0];

        if (\strlen($date) < 4) {
            return null;
        }

        if (\strlen($date) === 4) {
            $date = new \DateTime(\sprintf("%d-01-01", $date));

            if ($date > new \DateTime()) {
                return null;
            }

            $min = clone $date->setDate($date->format('Y'), 1, 1)->setTime(0, 0, 0);
            $max = clone $date->setDate($date->format('Y'), 12, 31)->setTime(0, 0, 0);
        } else {
            try {
                $date = new \DateTime($date);
            } catch (\Exception $e) {
                return null;
            }

            if ($date > new \DateTime()) {
                return null;
            }

            $min = clone $date;
            $max = clone $date;
        }

        $modifier = $dateInFilename[1] ?? false;
        if ($modifier === self::DATE_MODIFIER_5Y) {
            $min = $min->modify('-5 years');
            $max = $max->modify('+5 years');
        }

        if ($modifier === self::DATE_MODIFIER_3Y) {
            $min = $min->modify('-3 years');
            $max = $max->modify('+3 years');
        }

        if ($modifier === self::DATE_MODIFIER_1Y) {
            $min = $min->modify('-1 year');
            $max = $max->modify('+1 year');
        }

        return new PhotoDateRange($min, $max);
    }
}
