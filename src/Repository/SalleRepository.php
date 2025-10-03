<?php

namespace App\Repository;

use App\Entity\Salle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Salle>
 */
class SalleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Salle::class);
    }

    /**
     * Exemple : trouver les salles disponibles dans une ville donnée
     */
    public function findByVille(string $ville): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.ville = :ville')
            ->setParameter('ville', $ville)
            ->orderBy('s.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
