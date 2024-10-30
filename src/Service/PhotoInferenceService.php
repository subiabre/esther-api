<?php

namespace App\Service;

use App\Entity\Image;
use App\Entity\Photo;
use App\Entity\PhotoDateRange;
use App\Range\DateRange;
use Fuse\Fuse;

class PhotoInferenceService
{
    public const REGEX_PARTIAL_ISO8601 = '[0-9]{4}(-[0-9]{2}){0,2}(T[0-9]{2}(:[0-9]{2}){0,2}(Z|\+[0-9:]+)?)?';

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

    public function getDateRangeInFilename(string $filename): ?PhotoDateRange
    {
        $pattern = \sprintf(
            '/^%s(%s%s)?/s',
            self::REGEX_PARTIAL_ISO8601,
            \preg_quote(DateRange::OPERATOR),
            self::REGEX_PARTIAL_ISO8601
        );

        \preg_match($pattern, $filename, $matches);

        if (empty($matches)) {
            return null;
        }

        $range = DateRange::fromString($matches[0]);

        return new PhotoDateRange($range->lower, $range->upper);
    }
}
