<?php

namespace App\Service;

use App\Configurable\ConfigurableInterface;
use App\Configurable\ConfigurableManager;
use App\Entity\Image;
use App\Storage\LocalDriver;
use Aws\Rekognition\RekognitionClient;

class RekognitionService implements VisionInterface, ConfigurableInterface
{
    public const FACE_BOX_MARGIN = 1.5;
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
            $box = $detection['BoundingBox'];
            $boxSize = (int) round(self::FACE_BOX_MARGIN * max(
                $box["Width"] * $image->getMetadata()->width,
                $box["Height"] * $image->getMetadata()->height
            ));

            $faces[] = [
                'width' => $boxSize,
                'height' => $boxSize,
                'offsetX' => round(($box["Left"] * $image->getMetadata()->width) - ($boxSize / 3.5)),
                'offsetY' => round(($box["Top"] * $image->getMetadata()->height) - ($boxSize / 3.5))
            ];
        }

        return $faces;
    }
}
