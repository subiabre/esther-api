<?php

namespace App\Repository;

use App\Entity\ImageThumb;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ImageThumb>
 *
 * @method ImageThumb|null find($id, $lockMode = null, $lockVersion = null)
 * @method ImageThumb|null findOneBy(array $criteria, array $orderBy = null)
 * @method ImageThumb[]    findAll()
 * @method ImageThumb[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImageThumbRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImageThumb::class);
    }

    //    /**
    //     * @return ImageThumb[] Returns an array of ImageThumb objects
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

    //    public function findOneBySomeField($value): ?ImageThumb
    //    {
    //        return $this->createQueryBuilder('i')
    //            ->andWhere('i.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
