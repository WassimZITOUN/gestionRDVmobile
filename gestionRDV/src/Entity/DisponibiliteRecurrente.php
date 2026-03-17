<?php

namespace App\Entity;

use App\Repository\DisponibiliteRecurrenteRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DisponibiliteRecurrenteRepository::class)]
#[ORM\Table(name: 'disponibilite_recurrente')]
class DisponibiliteRecurrente
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Medecin::class, inversedBy: 'disponibilitesRecurrentes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Medecin $medecin = null;

    /**
     * Jour de la semaine (1=lundi, 2=mardi, ..., 7=dimanche)
     * Conforme a ISO-8601 et compatible avec PHP DateTimeInterface::format('N')
     */
    #[ORM\Column(type: 'smallint')]
    #[Assert\Range(min: 1, max: 7, notInRangeMessage: 'Le jour doit etre entre 1 (lundi) et 7 (dimanche)')]
    private ?int $jourSemaine = null;

    /**
     * Heure de debut du creneau (ex: "09:00")
     */
    #[ORM\Column(type: 'time')]
    private ?\DateTimeInterface $heureDebut = null;

    /**
     * Heure de fin du creneau (ex: "12:00")
     */
    #[ORM\Column(type: 'time')]
    private ?\DateTimeInterface $heureFin = null;

    /**
     * Duree d'un RDV en minutes (par defaut 60)
     */
    #[ORM\Column(type: 'smallint', options: ['default' => 60])]
    #[Assert\Positive]
    private int $dureeRdvMinutes = 60;

    /**
     * Permet de desactiver temporairement un creneau sans le supprimer
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $actif = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMedecin(): ?Medecin
    {
        return $this->medecin;
    }

    public function setMedecin(?Medecin $medecin): static
    {
        $this->medecin = $medecin;
        return $this;
    }

    public function getJourSemaine(): ?int
    {
        return $this->jourSemaine;
    }

    public function setJourSemaine(int $jourSemaine): static
    {
        $this->jourSemaine = $jourSemaine;
        return $this;
    }

    public function getHeureDebut(): ?\DateTimeInterface
    {
        return $this->heureDebut;
    }

    public function setHeureDebut(\DateTimeInterface $heureDebut): static
    {
        $this->heureDebut = $heureDebut;
        return $this;
    }

    public function getHeureFin(): ?\DateTimeInterface
    {
        return $this->heureFin;
    }

    public function setHeureFin(\DateTimeInterface $heureFin): static
    {
        $this->heureFin = $heureFin;
        return $this;
    }

    public function getDureeRdvMinutes(): int
    {
        return $this->dureeRdvMinutes;
    }

    public function setDureeRdvMinutes(int $dureeRdvMinutes): static
    {
        $this->dureeRdvMinutes = $dureeRdvMinutes;
        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;
        return $this;
    }

    /**
     * Retourne le nom du jour en francais
     */
    public function getJourNom(): string
    {
        $jours = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche'
        ];
        return $jours[$this->jourSemaine] ?? '';
    }

    /**
     * Genere les creneaux horaires pour cette plage
     * @return string[] Liste des heures de debut de creneau (ex: ['09:00', '10:00', '11:00'])
     */
    public function genererCreneaux(): array
    {
        $creneaux = [];

        if (!$this->heureDebut || !$this->heureFin) {
            return $creneaux;
        }

        $debut = \DateTime::createFromInterface($this->heureDebut);
        $fin = \DateTime::createFromInterface($this->heureFin);

        while ($debut < $fin) {
            // Verifier que le creneau complet tient dans la plage
            $finCreneau = (clone $debut)->modify("+{$this->dureeRdvMinutes} minutes");
            if ($finCreneau <= $fin) {
                $creneaux[] = $debut->format('H:i');
            }
            $debut->modify("+{$this->dureeRdvMinutes} minutes");
        }

        return $creneaux;
    }
}
