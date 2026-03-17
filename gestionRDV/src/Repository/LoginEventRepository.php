<?php

namespace App\Repository;

use App\Entity\LoginEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoginEvent>
 */
class LoginEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginEvent::class);
    }

    /**
     * Récupère les derniers événements de connexion pour un utilisateur
     */
    public function findRecentByUser($user, int $limit = 10): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.user = :user')
            ->setParameter('user', $user)
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les connexions par type dans une période
     */
    public function countByTypeInPeriod(string $type, \DateTimeInterface $start, \DateTimeInterface $end): int
    {
        return $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.type = :type')
            ->andWhere('l.createdAt BETWEEN :start AND :end')
            ->setParameter('type', $type)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les événements de connexion par IP
     */
    public function findByIp(string $ip, int $limit = 20): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.ip = :ip')
            ->setParameter('ip', $ip)
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
