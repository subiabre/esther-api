<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Image;
use App\Service\ImageMetadataService;
use Doctrine\ORM\EntityManagerInterface;

class ImageMetadataProcessor implements ProcessorInterface
{
    public function __construct(
        private ImageMetadataService $imageMetadataService,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @param Image $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Image
    {
        $data->setMetadata($this->imageMetadataService->generateImageMetadata($data));

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}
