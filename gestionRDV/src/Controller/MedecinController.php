<?php

namespace App\Controller;

use App\Entity\Medecin;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/medecin')]
final class MedecinController extends AbstractController
{
    #[Route('/{id}/assistants', name: 'app_medecin_assistants', methods: ['GET'])]
    public function assistants(Medecin $medecin): Response
    {
        $user = $this->getUser();

        // Vérifier que l'utilisateur est le médecin concerné
        if (!$this->isGranted('ROLE_ADMIN')) {
            if (!$user instanceof Medecin || $user->getId() !== $medecin->getId()) {
                throw $this->createAccessDeniedException('Accès non autorisé.');
            }
        }

        return $this->render('medecin/assistants.html.twig', [
            'medecin' => $medecin,
            'assistants' => $medecin->getAssistants(),
        ]);
    }
}
