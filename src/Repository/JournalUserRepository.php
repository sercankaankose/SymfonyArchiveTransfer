<?php

namespace App\Repository;

use App\Entity\JournalUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JournalUser>
 *
 * @method JournalUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method JournalUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method JournalUser[]    findAll()
 * @method JournalUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JournalUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JournalUser::class);
    }

//    /**
//     * @return JournalUser[] Returns an array of JournalUser objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('j')
//            ->andWhere('j.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('j.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?JournalUser
//    {
//        return $this->createQueryBuilder('j')
//            ->andWhere('j.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
