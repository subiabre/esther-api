<?php

namespace App\Repository;

use App\Entity\PhotoScope;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PhotoScope>
 *
 * @method PhotoScope|null find($id, $lockMode = null, $lockVersion = null)
 * @method PhotoScope|null findOneBy(array $criteria, array $orderBy = null)
 * @method PhotoScope[]    findAll()
 * @method PhotoScope[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PhotoScopeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhotoScope::class);
    }

    //    /**
    //     * @return PhotoScope[] Returns an array of PhotoScope objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?PhotoScope
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
