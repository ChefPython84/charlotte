<?php

namespace App\Repository;

use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * Retourne toutes les notifications d’un utilisateur
     */
    public function findByUser($user): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.dateEnvoi', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les notifications non lues d’un utilisateur
     */
    public function findUnreadByUser($user): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->andWhere('n.estLu = false')
            ->setParameter('user', $user)
            ->orderBy('n.dateEnvoi', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
