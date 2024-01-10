<?php

namespace App\Repository;

use App\Entity\Issues;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Issues>
 *
 * @method Issues|null find($id, $lockMode = null, $lockVersion = null)
 * @method Issues|null findOneBy(array $criteria, array $orderBy = null)
 * @method Issues[]    findAll()
 * @method Issues[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IssuesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Issues::class);
    }

//    /**
//     * @return Issues[] Returns an array of Issues objects
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

//    public function findOneBySomeField($value): ?Issues
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
