<?php

namespace App\Service;

use App\Entity\Medecin;
use App\Repository\DisponibiliteRecurrenteRepository;
use App\Repository\RendezVousRepository;

class CreneauDisponibiliteService
{
    public function __construct(
        private DisponibiliteRecurrenteRepository $dispoRepo,
        private RendezVousRepository $rdvRepo
    ) {}

    /**
     * Genere les prochains creneaux disponibles pour un medecin
     *
     * @param Medecin $medecin Le medecin concerne
     * @param int $nombreJours Nombre de jours a scanner (defaut: 30)
     * @param int $maxCreneaux Nombre max de creneaux a retourner (defaut: 50)
     * @return array Liste de creneaux ['datetime' => DateTime, 'label' => 'Lundi 15 janvier a 09:00']
     */
    public function getProchainsCréneaux(Medecin $medecin, int $nombreJours = 30, int $maxCreneaux = 50): array
    {
        $creneauxDisponibles = [];
        $dateDebut = new \DateTime('today');
        $dateFin = (clone $dateDebut)->modify("+{$nombreJours} days");

        // Heure actuelle pour filtrer les creneaux passes aujourd'hui
        $maintenant = new \DateTime();

        $dateCursor = clone $dateDebut;

        while ($dateCursor <= $dateFin && count($creneauxDisponibles) < $maxCreneaux) {
            $jourSemaine = (int) $dateCursor->format('N'); // 1=lundi...7=dimanche

            // Recuperer les disponibilites pour ce jour
            $disponibilites = $this->dispoRepo->findByMedecinAndJour($medecin, $jourSemaine);

            foreach ($disponibilites as $dispo) {
                $creneauxDuJour = $dispo->genererCreneaux();

                foreach ($creneauxDuJour as $heure) {
                    [$h, $m] = explode(':', $heure);
                    $dateTimeCreneau = (clone $dateCursor)->setTime((int)$h, (int)$m);

                    // Ne pas proposer les creneaux passes
                    if ($dateTimeCreneau <= $maintenant) {
                        continue;
                    }

                    // Verifier qu'il n'y a pas deja un RDV
                    $finCreneau = (clone $dateTimeCreneau)->modify("+{$dispo->getDureeRdvMinutes()} minutes");
                    $rdvExistants = $this->rdvRepo->findOverlapping($medecin, $dateTimeCreneau, $finCreneau);

                    // Filtrer les RDV annules ou refuses (ils ne bloquent pas le creneau)
                    $rdvExistants = array_filter($rdvExistants, function($rdv) {
                        $etatLibelle = strtolower($rdv->getEtat()->getLibelle());
                        return !in_array($etatLibelle, ['annulé', 'refusé', 'annule', 'refuse']);
                    });

                    if (empty($rdvExistants)) {
                        $creneauxDisponibles[] = [
                            'datetime' => $dateTimeCreneau,
                            'date' => $dateTimeCreneau->format('Y-m-d'),
                            'heure' => $heure,
                            'label' => $this->formatLabel($dateTimeCreneau),
                            'duree' => $dispo->getDureeRdvMinutes(),
                        ];

                        if (count($creneauxDisponibles) >= $maxCreneaux) {
                            break 3; // Sortir des 3 boucles
                        }
                    }
                }
            }

            $dateCursor->modify('+1 day');
        }

        return $creneauxDisponibles;
    }

    /**
     * Recupere les creneaux disponibles pour TOUS les medecins
     * Utile pour afficher une liste globale
     *
     * @param Medecin[] $medecins Liste des medecins
     * @param int $nombreJours Nombre de jours a scanner
     * @param int $maxParMedecin Nombre max de creneaux par medecin
     * @return array Liste triee par date/heure
     */
    public function getProchainsCréneauxTousMedecins(array $medecins, int $nombreJours = 14, int $maxParMedecin = 20): array
    {
        $tousCreneaux = [];

        foreach ($medecins as $medecin) {
            $creneaux = $this->getProchainsCréneaux($medecin, $nombreJours, $maxParMedecin);
            foreach ($creneaux as $creneau) {
                $creneau['medecin'] = $medecin;
                $tousCreneaux[] = $creneau;
            }
        }

        // Trier par date/heure
        usort($tousCreneaux, fn($a, $b) => $a['datetime'] <=> $b['datetime']);

        return $tousCreneaux;
    }

    /**
     * Verifie si un creneau specifique est disponible
     *
     * @param Medecin $medecin Le medecin
     * @param \DateTime $debut Debut du creneau
     * @param \DateTime $fin Fin du creneau
     * @return bool True si disponible
     */
    public function isCreneauDisponible(Medecin $medecin, \DateTime $debut, \DateTime $fin): bool
    {
        // Verifier que ce n'est pas dans le passe
        if ($debut <= new \DateTime()) {
            return false;
        }

        // Verifier qu'il y a une disponibilite pour ce jour/heure
        $jourSemaine = (int) $debut->format('N');
        $disponibilites = $this->dispoRepo->findByMedecinAndJour($medecin, $jourSemaine);

        $dansPlage = false;
        foreach ($disponibilites as $dispo) {
            $heureDebutDispo = \DateTime::createFromInterface($dispo->getHeureDebut());
            $heureFinDispo = \DateTime::createFromInterface($dispo->getHeureFin());

            $heureDebutCreneau = (clone $debut)->setDate(1970, 1, 1);
            $heureFinCreneau = (clone $fin)->setDate(1970, 1, 1);

            if ($heureDebutCreneau >= $heureDebutDispo && $heureFinCreneau <= $heureFinDispo) {
                $dansPlage = true;
                break;
            }
        }

        if (!$dansPlage) {
            return false;
        }

        // Verifier qu'il n'y a pas de RDV existant
        $rdvExistants = $this->rdvRepo->findOverlapping($medecin, $debut, $fin);
        $rdvExistants = array_filter($rdvExistants, function($rdv) {
            $etatLibelle = strtolower($rdv->getEtat()->getLibelle());
            return !in_array($etatLibelle, ['annulé', 'refusé', 'annule', 'refuse']);
        });

        return empty($rdvExistants);
    }

    /**
     * Formate une date en label lisible en francais
     */
    private function formatLabel(\DateTimeInterface $dt): string
    {
        $jours = ['', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        $mois = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
                 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

        $jour = $jours[(int)$dt->format('N')];
        $numJour = $dt->format('j');
        $numMois = $mois[(int)$dt->format('n')];
        $heure = $dt->format('H:i');

        return "{$jour} {$numJour} {$numMois} à {$heure}";
    }
}
