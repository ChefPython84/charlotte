<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * Exemple : trouver les réservations d’un utilisateur
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('r.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Exemple : trouver les réservations d’une salle
     */
    public function findBySalle(int $salleId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.salle = :salleId')
            ->setParameter('salleId', $salleId)
            ->orderBy('r.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
