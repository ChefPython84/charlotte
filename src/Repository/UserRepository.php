<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Exemple : chercher un utilisateur par email
     */
    public function findOneByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve tous les utilisateurs ayant au moins un des rôles spécifiés.
     * @param array $roles ex: ['ROLE_ADMIN', 'ROLE_GESTIONNAIRE']
     * @return User[]
     */
    public function findUsersByRoles(array $roles): array
    {
        $qb = $this->createQueryBuilder('u');
        
        // Crée une condition 'OR' pour chaque rôle
        $qb->andWhere('u.role IN (:roles)')
           ->setParameter('roles', $roles);

        return $qb->getQuery()->getResult();
    }
}

