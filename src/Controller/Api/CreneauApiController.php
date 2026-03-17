<?php

namespace App\Controller\Api;

use App\Entity\Etat;
use App\Entity\Medecin;
use App\Entity\Patient;
use App\Entity\RendezVous;
use App\Repository\EtatRepository;
use App\Repository\MedecinRepository;
use App\Service\CreneauDisponibiliteService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class CreneauApiController extends AbstractController
{
    /**
     * GET /api/medecins
     * Liste de tous les médecins
     */
    #[Route('/medecins', name: 'api_medecins', methods: ['GET'])]
    public function listMedecins(MedecinRepository $medecinRepo): JsonResponse
    {
        $medecins = $medecinRepo->getLesMedecins();

        return $this->json(
            array_map(fn(Medecin $m) => [
                'id'     => $m->getId(),
                'nom'    => $m->getNom(),
                'prenom' => $m->getPrenom(),
            ], $medecins)
        );
    }

    /**
     * GET /api/medecins/{id}/creneaux
     * Créneaux disponibles d'un médecin
     */
    #[Route('/medecins/{id}/creneaux', name: 'api_medecin_creneaux', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function creneauxMedecin(
        Medecin $medecin,
        CreneauDisponibiliteService $creneauService,
        Request $request
    ): JsonResponse {
        $nombreJours = (int) $request->query->get('jours', 30);
        $maxCreneaux = (int) $request->query->get('max', 50);

        $creneaux = $creneauService->getProchainsCréneaux($medecin, $nombreJours, $maxCreneaux);

        return $this->json(
            array_map(fn(array $c) => [
                'datetime' => $c['datetime']->format('Y-m-d\TH:i:s'),
                'date'     => $c['date'],
                'heure'    => $c['heure'],
                'label'    => $c['label'],
                'duree'    => $c['duree'],
            ], $creneaux)
        );
    }

    /**
     * POST /api/rendez-vous/nouveau
     * Demande de rendez-vous par un patient
     * Body JSON : { "medecin_id": 1, "datetime_debut": "2026-03-15T09:00:00" }
     */
    #[Route('/rendez-vous/nouveau', name: 'api_rendez_vous_nouveau', methods: ['POST'])]
    #[IsGranted('ROLE_PATIENT')]
    public function nouveau(
        Request $request,
        MedecinRepository $medecinRepo,
        EtatRepository $etatRepo,
        CreneauDisponibiliteService $creneauService,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var Patient $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);

        if (!isset($data['medecin_id'], $data['datetime_debut'])) {
            return $this->json(['error' => 'Champs requis : medecin_id, datetime_debut.'], 400);
        }

        $medecin = $medecinRepo->find((int)$data['medecin_id']);
        if (!$medecin) {
            return $this->json(['error' => 'Médecin introuvable.'], 404);
        }

        try {
            $debut = new \DateTime($data['datetime_debut']);
        } catch (\Exception) {
            return $this->json(['error' => 'Format de date invalide. Utiliser ISO 8601 : Y-m-d\TH:i:s'], 400);
        }

        // Récupère la durée depuis les disponibilités du médecin ce jour-là
        $duree = 60; // valeur par défaut
        $creneaux = $creneauService->getProchainsCréneaux($medecin, 60, 200);
        foreach ($creneaux as $c) {
            if ($c['datetime']->format('Y-m-d\TH:i:s') === $debut->format('Y-m-d\TH:i:s')) {
                $duree = $c['duree'];
                break;
            }
        }

        $fin = (clone $debut)->modify("+{$duree} minutes");

        if (!$creneauService->isCreneauDisponible($medecin, $debut, $fin)) {
            return $this->json(['error' => 'Ce créneau n\'est plus disponible.'], 409);
        }

        $etatDemande = $etatRepo->find(1);
        if (!$etatDemande) {
            return $this->json(['error' => 'État "demandé" introuvable.'], 500);
        }

        $rdv = new RendezVous();
        $rdv->setPatient($user);
        $rdv->setMedecin($medecin);
        $rdv->setDebut($debut);
        $rdv->setFin($fin);
        $rdv->setEtat($etatDemande);

        $em->persist($rdv);
        $em->flush();

        return $this->json([
            'message' => 'Votre demande de rendez-vous a été envoyée avec succès.',
            'rdv' => [
                'id'    => $rdv->getId(),
                'debut' => $rdv->getDebut()->format('Y-m-d\TH:i:s'),
                'fin'   => $rdv->getFin()->format('Y-m-d\TH:i:s'),
                'etat'  => ['id' => $rdv->getEtat()->getId(), 'libelle' => $rdv->getEtat()->getLibelle()],
            ],
        ], 201);
    }
}
