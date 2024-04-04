<?php

namespace App\Repository;

use App\Entity\Authoess;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Authoess>
 *
 * @method Authoess|null find($id, $lockMode = null, $lockVersion = null)
 * @method Authoess|null findOneBy(array $criteria, array $orderBy = null)
 * @method Authoess[]    findAll()
 * @method Authoess[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AuthoessRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Authoess::class);
    }

//    /**
//     * @return Authoess[] Returns an array of Authoess objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Authoess
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
