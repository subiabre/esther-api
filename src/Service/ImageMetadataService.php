<?php

namespace App\Service;

use App\Entity\ImageMetadata;

class ImageMetadataService
{
    public function getImageMetadata(string $path): ImageMetadata
    {
        $filename = \pathinfo($path)['filename'];
        $safepath = \str_replace($filename, \urlencode($filename), $path);

        $imginfo = \getimagesize($safepath);

        $filesize = $this->getFileSize($safepath);
        $filedate = $this->getFileDate($safepath);

        return new ImageMetadata(
            $imginfo[0],
            $imginfo[1],
            $imginfo['mime'],
            $filesize,
            $filedate
        );
    }

    private function getHeaders(string $path): array
    {
        $headers = \get_headers($path, true);

        $results = [];
        foreach ($headers as $key => $value) {
            $results[strtolower($key)] = $value;
        }

        return $results;
    }

    public function getFileSize(string $path): ?int
    {
        try {
            $size = \filesize($path);
        } catch (\ErrorException $e) {
            $size = $this->getKey($this->getHeaders($path), 'content-length');
            if (!$size) {
                return null;
            }
        }

        return $size;
    }

    public function getFileDate(string $path): ?\DateTimeImmutable
    {
        try {
            $date = sprintf("@%d", \filemtime($path));
        } catch (\ErrorException $e) {
            $date = $this->getKey($this->getHeaders($path), 'last-modified');
            if (!$date) {
                return null;
            }
        }

        return \DateTimeImmutable::createFromInterface(new \DateTime($date));
    }

    public function getExif(string $path): array
    {
        $exif = @\exif_read_data($path, null, true) ?: [];

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

    public function getKey(array $exif, string ...$keys)
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
