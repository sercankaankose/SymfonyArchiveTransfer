<?php

namespace App\Repository;

use App\Entity\JournalUser;
use App\Params\RoleParam;
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

    public function findByRoleOperator($user): array
    {
        return $this->createQueryBuilder('ju')
            ->innerJoin('ju.role', 'r')
            ->where('ju.person = :user')
            ->andWhere('r.role_name = :role')
            ->setParameter('user', $user)
            ->setParameter('role', RoleParam::ROLE_OPERATOR)
            ->getQuery()
            ->getResult();
    }
    public function findByRoleEditor($user): array
    {
        return $this->createQueryBuilder('ju')
            ->innerJoin('ju.role', 'r')
            ->where('ju.person = :user')
            ->andWhere('r.role_name = :role')
            ->setParameter('user', $user)
            ->setParameter('role', RoleParam::ROLE_EDITOR)
            ->getQuery()
            ->getResult();
    }
    public function userRoleInJournal($user, $journal, $roleName): bool
    {
        $qb = $this->createQueryBuilder('ju')
            ->innerJoin('ju.role', 'r')
            ->where('ju.person = :user')
            ->andWhere('ju.journal = :journal')
            ->andWhere('r.role_name = :role_name')
            ->setParameter('user', $user)
            ->setParameter('journal', $journal)
            ->setParameter('role_name', $roleName)
            ->getQuery();
        return (bool) $qb->getOneOrNullResult();

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
