<?php

namespace App\Controller;

use App\Entity\Assistant;
use App\Repository\RendezVousRepository;
use App\Repository\EtatRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/assistant')]
final class AssistantController extends AbstractController
{
    #[Route('/{id}/rendezvous', name: 'app_assistant_rendezvous', methods: ['GET'])]
    public function rendezvous(Assistant $assistant, RendezVousRepository $rendezVousRepository, EtatRepository $etatRepository, Request $request): Response
    {
        $user = $this->getUser();

        // Allow only the assistant owner or admins to view the appointments
        if (!$this->isGranted('ROLE_ADMIN')) {
            if (!$user instanceof Assistant || $user->getId() !== $assistant->getId()) {
                throw $this->createAccessDeniedException('Accès non autorisé.');
            }
        }

        $etatId = $request->query->get('etat');
        $etatId = $etatId !== null && $etatId !== '' ? (int)$etatId : null;

        $jourFilter = $request->query->get('jour');
        $aujourdhui = $jourFilter === 'aujourdhui';

        $dateString = $request->query->get('date');
        $dateSpecifique = null;
        if ($dateString) {
            try {
                $dateSpecifique = new \DateTime($dateString);
            } catch (\Exception $e) {
                $dateSpecifique = null;
            }
        }

        $medecin = $assistant->getMedecin();

        // Met à jour automatiquement les RDV confirmés passés à "réalisé"
        $updated = $rendezVousRepository->updatePastConfirmedToRealise();
        if ($updated > 0) {
            $this->addFlash('success', sprintf('%d rendez-vous mis à jour en "réalisé".', $updated));
        }

        $rendezvous = [];
        if ($medecin) {
            $rendezvous = $rendezVousRepository->findByMedecinAndEtat($medecin, $etatId, $aujourdhui, $dateSpecifique);
        }

        return $this->render('mes_rendez_vous/index.html.twig', [
            'rendezVous' => $rendezvous,
            'etats' => $etatRepository->findAll(),
            'etatSelectionne' => $etatId,
            'jourFilter' => $jourFilter,
            'dateSelectionnee' => $dateString,
            'role' => 'assistant',
        ]);
    }
}
