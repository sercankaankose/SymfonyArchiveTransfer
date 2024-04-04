<?php

namespace App\Repository;


use App\Entity\Journal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @extends ServiceEntityRepository<Journal>
 *
 * @method Journal|null find($id, $lockMode = null, $lockVersion = null)
 * @method Journal|null findOneBy(array $criteria, array $orderBy = null)
 * @method Journal[]    findAll()
 * @method Journal[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JournalRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Journal::class);

    }

    public function searchByName($term)
    {
        return $this->createQueryBuilder('j')
            ->where('LOWER(j.name) LIKE LOWER(:term)')
            ->setParameter('term', '%' . strtolower($term) . '%')
            ->getQuery()
            ->getResult();
    }



//    public function findBySearchAndSort($search, $column, $dir, $start, $length)
//    {
//        $qb = $this->createQueryBuilder('j')
//            ->setFirstResult($start)
//            ->setMaxResults($length);
//
//        if (!empty($search)) {
//            $qb->andWhere($qb->expr()->orX(
//                $qb->expr()->like('j.name', ':search'),
//                $qb->expr()->like('j.issn', ':search'),
//                $qb->expr()->like('j.eIssn', ':search')
//            ))->setParameter('search', '%' . $search . '%');
//        }
//
//        $qb->orderBy('j.' . $column, $dir);
//
//        return $qb->getQuery()->getResult();
//    }
//
//
//    public function getTotalRecords()
//    {
//        return $this->createQueryBuilder('j')
//            ->select('COUNT(j.id)')->getQuery()->getSingleScalarResult();
//    }
//
//    public function getFilteredRecords($search)
//    {
//        $qb = $this->createQueryBuilder('j')
//            ->select('COUNT(j.id)');
//
//        if (!empty($search)) {
//            $qb->andWhere('j.name LIKE :search OR j.issn LIKE :search OR j.eIssn LIKE :search')
//                ->setParameter('search', '%' . $search . '%');
//        }
//
//        return $qb->getQuery()->getSingleScalarResult();
//    }

//    //---------------------------------------------
//    /**
//     * @return Journal[] Returns an array of Journal objects
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
//
//    public function findOneBySomeField($value): ?Journal
//    {
//        return $this->createQueryBuilder('j')
//            ->andWhere('j.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
