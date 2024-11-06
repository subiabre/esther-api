<?php

namespace App\EventListener;

use App\Entity\Photo;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;

#[AsEntityListener(event: 'postPersist', method: 'setCode', entity: Photo::class)]
class PhotoListener
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function setCode(Photo $photo, PostPersistEventArgs $event): void
    {
        if ($photo->getCode() !== null) {
            return;
        }

        $photo->setCode($photo->getId());

        $this->entityManager->persist($photo);
        $this->entityManager->flush();
    }
}
