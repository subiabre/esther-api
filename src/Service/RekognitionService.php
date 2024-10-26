<?php

namespace App\Service;

use App\Configurable\ConfigurableInterface;
use App\Configurable\ConfigurableManager;
use App\Entity\Image;
use App\Storage\LocalDriver;
use Aws\Rekognition\RekognitionClient;

class RekognitionService implements VisionInterface, ConfigurableInterface
{
    public const FACE_BOX_MARGIN_MAX = 0.6;
    public const FACE_CONFIDENCE_MIN = 70;

    public const IMAGE_MAX_SIZE = 5242880;

    private array $config;

    public function __construct(
        private ConfigurableManager $configurableManager,
        private LocalDriver $localDriver
    ) {
        $service = $configurableManager->get(self::getName());

        $this->config = $service ? $service->getConfig() : self::getConfiguration();
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

        if ($image->getMetadata()->filesize > self::IMAGE_MAX_SIZE) {
            return [];
        }

        if (!in_array($image->getMetadata()->mimeType, ['image/jpeg', 'image/png'])) {
            return [];
        }

        $path = $image->getSrc();
        if ($this->localDriver->isLocalPath($path)) {
            $path = $this->localDriver->getAbsolutePath($path);
        }

        $detections = $rekognition->detectFaces([
            'Image' => [
                'Bytes' => \file_get_contents($path)
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
}
