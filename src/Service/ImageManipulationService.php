<?php

namespace App\Service;

use App\Entity\ImageThumb;
use App\Storage\StorageLocator;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use League\Flysystem\Filesystem;

class ImageManipulationService
{
    public const IMAGE_RESIZED_QUALITY = 96;
    public const IMAGE_RESIZED_FILENAME = 'resized';

    public const IMAGE_CROPPED_QUALITY = 98;
    public const IMAGE_CROPPED_FILENAME = 'cropped';

    private ImageManager $manager;

    private Filesystem $storage;

    public function __construct(StorageLocator $storageLocator)
    {
        $this->manager = new ImageManager(new ImagickDriver);

        $this->storage = $storageLocator->getFilesystem();
    }

    public function getStorage(): Filesystem
    {
        return $this->storage;
    }

    public function setStorage(Filesystem $storage): static
    {
        $this->storage = $storage;

        return $this;
    }

    public function generateImageThumb(string $path, int $width = 420): ImageThumb
    {
        $data = \fopen($path, 'r');
        $data = $this->manager->read($data)->scaleDown($width);
        $path = sprintf(
            "%s_%s.webp",
            self::IMAGE_RESIZED_FILENAME,
            hash('md5', $path),
        );

        $this->storage->writeStream(
            $path,
            $data->toWebp(self::IMAGE_RESIZED_QUALITY)->toFilePointer()
        );

        $thumb = new ImageThumb;
        $thumb->setSrc($this->storage->publicUrl($path));
        $thumb->setWidth($data->width());
        $thumb->setHeight($data->height());

        return $thumb;
    }

    public function crop(
        string $path,
        int $width,
        int $height,
        int $offsetX,
        int $offsetY,
    ): string {
        $data = \fopen($path, 'r');
        $data = $this->manager->read($data)->crop(
            $width,
            $height,
            $offsetX,
            $offsetY,
            '00000000'
        );

        $path = sprintf(
            "%s_%s.webp",
            self::IMAGE_CROPPED_FILENAME,
            hash('md5', join('', [
                $path,
                $width,
                $height,
                $offsetX,
                $offsetY
            ]))
        );

        $this->storage->writeStream(
            $path,
            $data->toWebp(self::IMAGE_CROPPED_QUALITY)->toFilePointer()
        );

        return $this->storage->publicUrl($path);
    }
}
