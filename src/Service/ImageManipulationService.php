<?php

namespace App\Service;

use App\Entity\Image;
use App\Entity\ImageThumb;
use App\Storage\StorageLocator;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use League\Flysystem\Filesystem;

class ImageManipulationService
{
    public const IMAGE_RESIZED_QUALITY = 95;
    public const IMAGE_RESIZED_FILENAME = 'resized';

    private Filesystem $storage;
    private ImageManager $manager;

    public function __construct(StorageLocator $storageLocator)
    {
        $this->storage = $storageLocator->getFilesystem();
        $this->manager = new ImageManager(new ImagickDriver);
    }

    public function generateImageThumb(Image $image, int $width): ImageThumb
    {
        $data = \fopen($image->getSrc(), 'r');
        $data = $this->manager->read($data)->resize($width);
        $path = sprintf(
            "%d_%s.webp",
            $image->getId(),
            self::IMAGE_RESIZED_FILENAME
        );

        $this->storage->writeStream(
            $path,
            $data->toWebp(self::IMAGE_RESIZED_QUALITY)->toFilePointer()
        );

        $thumb = new ImageThumb;
        $thumb->setSrc($path);
        $thumb->setWidth($data->width());
        $thumb->setHeight($data->height());

        return $thumb;
    }
}
