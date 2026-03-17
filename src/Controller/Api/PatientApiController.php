<?php

namespace App\Controller\Api;

use App\Entity\Etat;
use App\Entity\Patient;
use App\Entity\RendezVous;
use App\Repository\EtatRepository;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class PatientApiController extends AbstractController
{
    /**
     * GET /api/me
     * Retourne les infos du patient connecté
     */
    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        return $this->json([
            'id'     => $user->getId(),
            'email'  => $user->getUserIdentifier(),
            'nom'    => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'roles'  => $user->getRoles(),
        ]);
    }

    /**
     * GET /api/mes-rendez-vous
     * Liste des RDV du patient, filtrable par état (?etat=1)
     */
    #[Route('/mes-rendez-vous', name: 'api_mes_rendez_vous', methods: ['GET'])]
    #[IsGranted('ROLE_PATIENT')]
    public function index(
        Request $request,
        RendezVousRepository $rdvRepo
    ): JsonResponse {
        /** @var Patient $user */
        $user = $this->getUser();

        // Mise à jour automatique des RDV confirmés passés → réalisé
        $rdvRepo->updatePastConfirmedToRealise();

        $etatId = $request->query->get('etat');
        $etatId = ($etatId !== null && $etatId !== '') ? (int)$etatId : null;

        $rendezVous = $rdvRepo->findByPatientAndEtat($user, $etatId);

        return $this->json(
            array_map(fn(RendezVous $rdv) => $this->serializeRdv($rdv), $rendezVous)
        );
    }

    /**
     * GET /api/mes-rendez-vous/{id}
     * Détail d'un RDV du patient
     */
    #[Route('/mes-rendez-vous/{id}', name: 'api_mes_rendez_vous_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_PATIENT')]
    public function show(RendezVous $rendezVous): JsonResponse
    {
        /** @var Patient $user */
        $user = $this->getUser();

        if ($rendezVous->getPatient()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        return $this->json($this->serializeRdv($rendezVous));
    }

    /**
     * POST /api/mes-rendez-vous/{id}/cancel
     * Annuler un RDV du patient
     */
    #[Route('/mes-rendez-vous/{id}/cancel', name: 'api_mes_rendez_vous_cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_PATIENT')]
    public function cancel(
        RendezVous $rendezVous,
        EtatRepository $etatRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var Patient $user */
        $user = $this->getUser();

        if ($rendezVous->getPatient()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        $libelleEtat = $rendezVous->getEtat()->getLibelle();
        if (in_array($libelleEtat, ['annulé', 'refusé', 'réalisé'])) {
            return $this->json(['error' => 'Ce rendez-vous ne peut plus être annulé.'], 400);
        }

        $etatAnnule = $etatRepo->findOneBy(['libelle' => 'annulé']);
        if (!$etatAnnule) {
            return $this->json(['error' => 'État "annulé" introuvable.'], 500);
        }

        $rendezVous->setEtat($etatAnnule);
        $em->flush();

        return $this->json(['message' => 'Le rendez-vous a bien été annulé.']);
    }

    /**
     * Sérialise un RendezVous en tableau JSON
     */
    private function serializeRdv(RendezVous $rdv): array
    {
        $medecin = $rdv->getMedecin();
        $patient = $rdv->getPatient();

        return [
            'id'     => $rdv->getId(),
            'debut'  => $rdv->getDebut()?->format('Y-m-d\TH:i:s'),
            'fin'    => $rdv->getFin()?->format('Y-m-d\TH:i:s'),
            'etat'   => [
                'id'      => $rdv->getEtat()->getId(),
                'libelle' => $rdv->getEtat()->getLibelle(),
            ],
            'medecin' => [
                'id'     => $medecin->getId(),
                'nom'    => $medecin->getNom(),
                'prenom' => $medecin->getPrenom(),
            ],
            'patient' => [
                'id'     => $patient->getId(),
                'nom'    => $patient->getNom(),
                'prenom' => $patient->getPrenom(),
            ],
        ];
    }
}
