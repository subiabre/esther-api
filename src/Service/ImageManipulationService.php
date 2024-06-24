<?php

namespace App\Service;

use App\Entity\Image;
use App\Entity\ImageThumb;
use App\Entity\Portrait;
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

    private Filesystem $storage;
    private ImageManager $manager;

    public function __construct(StorageLocator $storageLocator)
    {
        $this->storage = $storageLocator->getFilesystem();
        $this->manager = new ImageManager(new ImagickDriver);
    }

    public function generateImageThumb(Image $image, int $width = 420): ImageThumb
    {
        $data = \fopen($image->getSrc(), 'r');
        $data = $this->manager->read($data)->scaleDown($width);
        $path = sprintf(
            "%s_%s.webp",
            self::IMAGE_RESIZED_FILENAME,
            hash('md5', $image->getSrc()),
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
        Image $image,
        int $width,
        int $height,
        int $offsetX,
        int $offsetY,
    ): string {
        $crop = [
            $width,
            $height,
            $offsetX,
            $offsetY
        ];

        $data = \fopen($image->getSrc(), 'r');
        $data = $this->manager->read($data)->crop(...$crop);

        $path = sprintf(
            "%s_%s.webp",
            self::IMAGE_CROPPED_FILENAME,
            hash('md5', join('', [$image->getSrc(), ...$crop]))
        );

        $this->storage->writeStream(
            $path,
            $data->toWebp(self::IMAGE_CROPPED_QUALITY)->toFilePointer()
        );

        return $this->storage->publicUrl($path);
    }
}
