<?php

namespace App\Service;

use App\Entity\Image;
use App\Entity\ImageMetadata;

class ImageMetadataService
{
    public function getImageMetadata(Image $image): ImageMetadata
    {
        $src = $image->getSrc();

        $imginfo = \getimagesize($src);
        $headers = $this->getHeaders($src);

        $filesize = $imginfo[0] * $imginfo[1] * $imginfo["bits"];

        if (\array_key_exists('content-length', $headers)) {
            $filesize = $headers['content-length'];
        }

        $filedate = new \DateTime();

        if (\array_key_exists('last-modified', $headers)) {
            $filedate = \DateTimeImmutable::createFromFormat(
                \DateTime::RFC7231,
                $headers['last-modified']
            );
        }

        return new ImageMetadata(
            $imginfo[0],
            $imginfo[1],
            $imginfo['mime'],
            $filesize,
            $filedate
        );
    }

    private function getHeaders(string $src): array
    {
        $headers = [];
        foreach (get_headers($src, true) as $header => $value) {
            $headers[strtolower($header)] = $value;
        }

        return $headers;
    }

    public function getExif(Image $image): array
    {
        $exif = @\exif_read_data($image->getSrc(), null, true) ?: [];

        return $this->cleanExif($exif);
    }

    private function cleanExif(array $exif): array
    {
        $data = [];
        foreach ($exif as $key => $value) {

            /**
             * Skips unsupported EXIF tags by PHP
             * @see https://bugs.php.net/bug.php?id=52840
             */
            if (str_starts_with($key, 'UndefinedTag')) continue;

            $data[$key] = (is_array($value))
                ? $this->cleanExif($value)
                : trim(mb_convert_encoding($value, 'UTF-8'));
        }

        return $data;
    }

    public function getExifKey(array $exif, string ...$keys)
    {
        $data = $exif;
        foreach ($keys as $key) {
            if (!array_key_exists($key, $data)) {
                return null;
            }

            $data = $data[$key];
        }

        return $data;
    }
}
