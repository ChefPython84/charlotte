<?php

namespace App\Repository;

use App\Entity\Avis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Avis>
 */
class AvisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Avis::class);
    }

    /**
     * Retourne les avis d’une salle
     */
    public function findBySalle($salle): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.salle = :salle')
            ->setParameter('salle', $salle)
            ->orderBy('a.dateAvis', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule la note moyenne d’une salle
     */
    public function getAverageNoteBySalle($salle): ?float
    {
        return $this->createQueryBuilder('a')
            ->select('AVG(a.note) as avgNote')
            ->andWhere('a.salle = :salle')
            ->setParameter('salle', $salle)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
