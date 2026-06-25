<?php

namespace App\Repository;

use App\Entity\LuckyNumber;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LuckyNumber>
 */
class LuckyNumberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LuckyNumber::class);
    }

    public function saveLuckyNumber(int $number): void
    {
        $luckyNumber = new LuckyNumber();
        $luckyNumber->setValue($number);

        $em = $this->getEntityManager();
        $em->persist($luckyNumber);
        $em->flush();
    }

    public function findOddLuckyNumber(int $maxValue = 50): array
    {
        $qb = $this->createQueryBuilder('l');
        
        $qb->andWhere('MOD(l.value, 2) = 1', 'l.value < :maxValue');
        
        $qb->setParameter('maxValue', $maxValue);

        $qb->orderBy('l.value', 'DESC');

        $qb->setMaxResults(5);

        $query = $qb->getQuery();

        $result = $query->getArrayResult();

        return $result;
    }

    public function findEvenLuckyNumber(): array
    {
        $qb = $this->createQueryBuilder('l');
        
        $qb->andWhere('MOD(l.value, 2) = 0');

        $query = $qb->getQuery();

        $result = $query->getArrayResult();

        return $result;
    }
    

    //    /**
    //     * @return LuckyNumber[] Returns an array of LuckyNumber objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('l.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?LuckyNumber
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}