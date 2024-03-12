<?php

namespace App\Service;

use App\Entity\Image;
use App\Entity\ImageMetadata;

class ImageMetadataService
{
    public function generateImageMetadata(Image $image): ImageMetadata
    {
        $src = $image->getSrc();

        $imginfo = \getimagesize($src);
        $headers = $this->getHeaders($src);

        $filesize = 0;
        if (\array_key_exists('content-length', $headers)) {
            $filesize = $headers['content-length'];
        } else {
            $filesize = $imginfo[0] * $imginfo[1] * $imginfo["bits"];
        }

        return new ImageMetadata(
            $imginfo[0],
            $imginfo[1],
            $filesize,
            $imginfo['mime'],
            $this->getLastModified($headers),
            $this->getExif($src)
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

    private function getLastModified(array $headers): ?\DateTimeImmutable
    {
        if (!\array_key_exists('last-modified', $headers)) {
            return null;
        }

        return \DateTimeImmutable::createFromFormat(
            \DateTime::RFC7231,
            $headers['last-modified']
        );
    }

    private function getExif(string $src): array
    {
        $exif = @\exif_read_data($src, null, true) ?: [];

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
}
