<?php

namespace App\Service;

use App\Entity\Image;
use App\Entity\Photo;
use App\Entity\PhotoDateRange;
use Fuse\Fuse;

class PhotoInferenceService
{
    public const DATE_MODIFIER_X1 = 'a';
    public const DATE_MODIFIER_X3 = 'b';
    public const DATE_MODIFIER_X5 = 'c';

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
        $filedate = explode(' ', $filename)[0];

        try {
            $date = new \DateTime($filedate);

            $max = clone $date;
            $min = clone $date;
        } catch (\Exception $e) {
            return null;
        }

        \preg_match('/^[0-9]{4}([a-c])?/', $filedate, $year);
        if ($modifier = $year[1] ?? false) {
            $modifiers = [
                self::DATE_MODIFIER_X1 => '1 year',
                self::DATE_MODIFIER_X3 => '3 years',
                self::DATE_MODIFIER_X5 => '5 years'
            ];

            $max->modify(\sprintf('+%s', $modifiers[$modifier]));
            $min->modify(\sprintf('-%s', $modifiers[$modifier]));
        }

        \preg_match('/-[0-9]{2}([a-c])?-/', $filedate, $dateMonth);
        if (empty($dateMonth)) {
            $max->modify('last day of december');
            $min->modify('first day of january');
        }

        if ($modifier = $dateMonth[1] ?? false) {
            $modifiers = [
                self::DATE_MODIFIER_X1 => '1 month',
                self::DATE_MODIFIER_X3 => '3 months',
                self::DATE_MODIFIER_X5 => '5 months'
            ];

            $max->modify(\sprintf('+%s', $modifiers[$modifier]));
            $min->modify(\sprintf('-%s', $modifiers[$modifier]));
        }

        \preg_match('/-[0-9]{2}([a-c])?$/', $filedate, $dateDay);
        if (empty($dateDay)) {
            $max->modify('last day of this month');
            $min->modify('first day of this month');
        }

        if ($modifier = $dateDay[1] ?? false) {
            $modifiers = [
                self::DATE_MODIFIER_X1 => '1 dateDay',
                self::DATE_MODIFIER_X3 => '3 days',
                self::DATE_MODIFIER_X5 => '5 days'
            ];

            $max->modify(\sprintf('+%s', $modifiers[$modifier]));
            $min->modify(\sprintf('-%s', $modifiers[$modifier]));
        }

        return new PhotoDateRange($min, $max);
    }
}
