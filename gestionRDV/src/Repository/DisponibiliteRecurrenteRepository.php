<?php

namespace App\Repository;

use App\Entity\DisponibiliteRecurrente;
use App\Entity\Medecin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DisponibiliteRecurrente>
 */
class DisponibiliteRecurrenteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DisponibiliteRecurrente::class);
    }

    /**
     * Recupere les disponibilites actives d'un medecin pour un jour donne
     * @param int $jourSemaine 1=lundi...7=dimanche
     * @return DisponibiliteRecurrente[]
     */
    public function findByMedecinAndJour(Medecin $medecin, int $jourSemaine): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.medecin = :medecin')
            ->andWhere('d.jourSemaine = :jour')
            ->andWhere('d.actif = true')
            ->setParameter('medecin', $medecin)
            ->setParameter('jour', $jourSemaine)
            ->orderBy('d.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recupere toutes les disponibilites actives d'un medecin
     * @return DisponibiliteRecurrente[]
     */
    public function findActiveByMedecin(Medecin $medecin): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.medecin = :medecin')
            ->andWhere('d.actif = true')
            ->setParameter('medecin', $medecin)
            ->orderBy('d.jourSemaine', 'ASC')
            ->addOrderBy('d.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recupere toutes les disponibilites d'un medecin (actives ou non)
     * @return DisponibiliteRecurrente[]
     */
    public function findAllByMedecin(Medecin $medecin): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.medecin = :medecin')
            ->setParameter('medecin', $medecin)
            ->orderBy('d.jourSemaine', 'ASC')
            ->addOrderBy('d.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Verifie si un medecin a au moins une disponibilite definie
     */
    public function hasDisponibilites(Medecin $medecin): bool
    {
        $count = $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->andWhere('d.medecin = :medecin')
            ->andWhere('d.actif = true')
            ->setParameter('medecin', $medecin)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Verifie s'il y a un chevauchement avec une disponibilite existante
     * @return DisponibiliteRecurrente[]
     */
    public function findOverlapping(Medecin $medecin, int $jourSemaine, \DateTimeInterface $heureDebut, \DateTimeInterface $heureFin): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.medecin = :medecin')
            ->andWhere('d.jourSemaine = :jour')
            ->andWhere('d.heureDebut < :fin')
            ->andWhere('d.heureFin > :debut')
            ->setParameter('medecin', $medecin)
            ->setParameter('jour', $jourSemaine)
            ->setParameter('debut', $heureDebut)
            ->setParameter('fin', $heureFin)
            ->getQuery()
            ->getResult();
    }
}
