<?php

namespace App\Repository;

use App\Entity\Paiement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Paiement>
 */
class PaiementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Paiement::class);
    }

    /**
     * Retourne les paiements d’un utilisateur
     */
    public function findByUser($user): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.facture', 'f')
            ->join('f.reservation', 'r')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.datePaiement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les paiements réussis
     */
    public function findSuccessful(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.statut = :statut')
            ->setParameter('statut', 'réussi')
            ->getQuery()
            ->getResult();
    }
}
