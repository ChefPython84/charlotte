<?php

namespace App\Repository;

use App\Entity\Facture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Facture>
 */
class FactureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Facture::class);
    }

    /**
     * Retourne les factures d’un utilisateur
     */
    public function findByUser($user): array
    {
        return $this->createQueryBuilder('f')
            ->join('f.reservation', 'r')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('f.dateFacture', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les factures non payées
     */
    public function findUnpaid(): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.statut != :statut')
            ->setParameter('statut', 'payée')
            ->getQuery()
            ->getResult();
    }
}
