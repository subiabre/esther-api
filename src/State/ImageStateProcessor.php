<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Image;
use App\Service\ImageManipulationService;
use App\Service\ImageMetadataService;
use Doctrine\ORM\EntityManagerInterface;

class ImageStateProcessor implements ProcessorInterface
{
    private array $IMAGE_THUMB_SIZES = [
        320
    ];

    public function __construct(
        private ImageMetadataService $imageMetadataService,
        private ImageManipulationService $imageManipulationService,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @param Image $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Image
    {
        $data->setMetadata($this->imageMetadataService->generateImageMetadata($data));
        
        foreach ($this->IMAGE_THUMB_SIZES as $size) {
            $thumb = $this->imageManipulationService->generateImageThumb($data, $size);

            $data->addThumb($thumb);
        }

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}
