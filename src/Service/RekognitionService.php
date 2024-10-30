<?php

namespace App\Service;

use App\Configurable\ConfigurableInterface;
use App\Configurable\ConfigurableManager;
use App\Entity\Image;
use App\Storage\LocalDriver;
use Aws\Rekognition\RekognitionClient;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;

class RekognitionService implements VisionInterface, ConfigurableInterface
{
    public const FACE_BOX_MARGIN_MAX = 0.6;
    public const FACE_CONFIDENCE_MIN = 70;

    /**
     * @link https://docs.aws.amazon.com/rekognition/latest/APIReference/API_Image.html#API_Image_Contents
     */
    public const IMAGE_MAX_SIZE = 5242880;

    private array $config;

    private ImageManager $imageManager;

    public function __construct(
        private ConfigurableManager $configurableManager,
        private RoutesService $routesService,
    ) {
        $service = $configurableManager->get(self::getName());

        $this->config = $service ? $service->getConfig() : self::getConfiguration();

        $this->imageManager = new ImageManager(new ImagickDriver());
    }

    public static function getName(): string
    {
        return 'rekognition';
    }

    public static function getConfiguration(): array
    {
        return [
            'region' => null,
            'credentials' => [
                'key' => null,
                'secret' => null
            ]
        ];
    }

    public function isConfigured(): bool
    {
        return $this->config['credentials']['key'] !== null;
    }

    public function getFaces(Image $image): array
    {
        $rekognition = new RekognitionClient($this->config);

        if (!in_array($image->getMetadata()->mimeType, ['image/jpeg', 'image/png'])) {
            return [];
        }

        $detections = $rekognition->detectFaces([
            'Image' => [
                'Bytes' => $this->readImage($image)
            ],
            'Attributes' => ['DEFAULT']
        ])->toArray();

        $faces = [];
        foreach ($detections['FaceDetails'] as $detection) {
            if ($detection['Confidence'] < self::FACE_CONFIDENCE_MIN) {
                continue;
            }

            /** @var array{Width: float, Height: float, Left: float, Top: float} */
            $box = $detection['BoundingBox'];

            $boxSize = $this->calcBoxSize($box, $image);
            $offsetX = $this->calcOffsetX($box, $image);
            $offsetY = $this->calcOffsetY($box, $image);
            $padding = min($offsetX, $offsetY, (int) (self::FACE_BOX_MARGIN_MAX * $boxSize));

            $faces[] = [
                'width'   => $boxSize + ($padding * 2),
                'height'  => $boxSize + ($padding * 2),
                'offsetX' => $offsetX - $padding,
                'offsetY' => $offsetY - $padding
            ];
        }

        return $faces;
    }

    private function calcOffsetX(array $box, Image $image): int
    {
        return (int) round($box['Left'] * $image->getMetadata()->width);
    }

    private function calcOffsetY(array $box, Image $image): int
    {
        return (int) round($box['Top'] * $image->getMetadata()->height);
    }

    private function calcBoxSize(array $box, Image $image): int
    {
        return (int) min(
            $box['Width'] * $image->getMetadata()->width,
            $box['Height'] * $image->getMetadata()->height
        );
    }

    /**
     * @return string
     */
    private function readImage(Image $image): string
    {
        $path = $this->routesService->getLocalUrlAsPath($image->getSrc());

        $imagesize = $image->getMetadata()->filesize;
        if ($imagesize < self::IMAGE_MAX_SIZE) {
            return \file_get_contents($path);
        }

        $file = $this->imageManager->read(\fopen($path, 'r'));
        $quality = (int) ((self::IMAGE_MAX_SIZE * 100) / $imagesize);

        return (string) $file->toJpeg($quality);
    }
}
