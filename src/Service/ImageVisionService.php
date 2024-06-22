<?php

namespace App\Service;

use App\Configurable\ConfigurableInterface;
use App\Entity\Image;
use App\Entity\Portrait;

class ImageVisionService
{
    /** @var VisionInterface[] */
    private array $visions = [];

    public function __construct(
        iterable $instanceof
    ) {
        $visions = \iterator_to_array($instanceof);

        foreach ($visions as $vision) {
            if (
                $vision instanceof ConfigurableInterface &&
                !$vision->isConfigured()
            ) {
                continue;
            }

            $this->visions[] = $vision;
        }
    }

    /**
     * @return Portrait[]
     */
    public function getPortraits(Image $image): array
    {
        $portraits = [];

        $faces = $this->visions[0]->getFaces($image);
        foreach ($faces as $face) {
            $portrait = new Portrait;
            $portrait->setWidth($face['width']);
            $portrait->setHeight($face['height']);
            $portrait->setOffsetX($face['offsetX']);
            $portrait->setOffsetY($face['offsetY']);
            $portrait->setImage($image);

            $portraits[] = $portrait;
        }

        return $portraits;
    }
}
