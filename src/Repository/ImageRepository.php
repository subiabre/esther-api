<?php

namespace App\Repository;

use App\Entity\Image;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Image>
 *
 * @method Image|null find($id, $lockMode = null, $lockVersion = null)
 * @method Image|null findOneBy(array $criteria, array $orderBy = null)
 * @method Image[]    findAll()
 * @method Image[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Image::class);
    }

    /**
     * @return Image[] Returns Images without a Photo
     */
    public function findAll(): array
    {
        return $this->createQueryBuilder('i')
            ->orderBy('i.src', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Image[] Returns Images without a Photo
     */
    public function findDangling(): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.photo IS NULL')
            ->orderBy('i.src', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Image[] Returns an array of Image objects
     */
    public function findByRange(int $start, int $end): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.id >= :start')
            ->andWhere('i.id <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('i.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    //    /**
    //     * @return Image[] Returns an array of Image objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('i')
    //            ->andWhere('i.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('i.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Image
    //    {
    //        return $this->createQueryBuilder('i')
    //            ->andWhere('i.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
