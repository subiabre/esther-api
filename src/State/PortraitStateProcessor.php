<?php

namespace App\State;

use ApiPlatform\Metadata as API;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Portrait;
use App\Repository\ImageRepository;
use App\Service\ImageManipulationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PortraitStateProcessor implements ProcessorInterface
{
    public function __construct(
        private ImageRepository $imageRepository,
        private ImageManipulationService $imageManipulationService,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * @param Portrait $data
     */
    public function process(mixed $data, API\Operation $operation, array $uriVariables = [], array $context = []): Portrait
    {
        $image = $this->imageRepository->find($uriVariables['id']);
        if (!$image) {
            throw new NotFoundHttpException(sprintf("Image with id %s not found", $uriVariables['id']));
        }

        $data->setImage($image);

        $data->setSrc($this->imageManipulationService->crop(
            $data->getImage()->getSrc(),
            $data->getWidth(),
            $data->getHeight(),
            $data->getOffsetX(),
            $data->getOffsetY()
        ));

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}
