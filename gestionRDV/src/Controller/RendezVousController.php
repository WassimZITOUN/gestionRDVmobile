<?php

namespace App\Controller;

use App\Entity\RendezVous;
use App\Entity\Patient;
use App\Entity\Etat;
use App\Entity\Medecin;
use App\Repository\MedecinRepository;
use App\Repository\RendezVousRepository;
use App\Repository\EtatRepository;
use App\Service\CreneauDisponibiliteService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/rendez-vous')]
final class RendezVousController extends AbstractController
{
    #[Route('/nouveau', name: 'app_rendez_vous_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        MedecinRepository $medecinRepo,
        CreneauDisponibiliteService $creneauService,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof Patient) {
            $this->addFlash('danger', 'Vous devez etre connecte en tant que patient.');
            return $this->redirectToRoute('app_login');
        }

        // Recuperer le medecin selectionne (optionnel via query param)
        $medecinId = $request->query->get('medecin');
        $medecinSelectionne = $medecinId ? $medecinRepo->find($medecinId) : null;

        // Si un creneau est soumis (POST)
        if ($request->isMethod('POST') && $request->request->has('creneau')) {
            if (!$this->isCsrfTokenValid('choix_creneau', $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF invalide');
            }

            $medecinId = $request->request->get('medecin');
            $date = $request->request->get('date');
            $heure = $request->request->get('heure');
            $duree = (int) $request->request->get('duree', 60);

            $medecin = $medecinRepo->find($medecinId);
            if (!$medecin) {
                $this->addFlash('danger', 'Medecin invalide.');
                return $this->redirectToRoute('app_rendez_vous_new');
            }

            [$h, $m] = explode(':', $heure);
            $debut = new \DateTime($date);
            $debut->setTime((int)$h, (int)$m);
            $fin = (clone $debut)->modify("+{$duree} minutes");

            // Verifier que le creneau est toujours disponible
            if (!$creneauService->isCreneauDisponible($medecin, $debut, $fin)) {
                $this->addFlash('danger', 'Ce creneau n\'est plus disponible. Veuillez en choisir un autre.');
                return $this->redirectToRoute('app_rendez_vous_new', ['medecin' => $medecinId]);
            }

            $rdv = new RendezVous();
            $rdv->setPatient($user);
            $rdv->setMedecin($medecin);
            $rdv->setDebut($debut);
            $rdv->setFin($fin);

            $etat = $em->getRepository(Etat::class)->find(1); // "demande"
            $rdv->setEtat($etat);

            $em->persist($rdv);
            $em->flush();

            $this->addFlash('success', 'Votre demande de rendez-vous a ete envoyee avec succes !');
            return $this->redirectToRoute('app_mes_rendez_vous');
        }

        // Afficher les creneaux disponibles
        $medecins = $medecinRepo->findAll();
        $creneaux = [];

        if ($medecinSelectionne) {
            // Creneaux pour un medecin specifique
            $creneaux = $creneauService->getProchainsCréneaux($medecinSelectionne, 30, 50);
            foreach ($creneaux as &$c) {
                $c['medecin'] = $medecinSelectionne;
            }
        } else {
            // Creneaux pour tous les medecins
            $creneaux = $creneauService->getProchainsCréneauxTousMedecins($medecins, 14, 30);
        }

        return $this->render('rendez_vous/new.html.twig', [
            'medecins' => $medecins,
            'medecinSelectionne' => $medecinSelectionne,
            'creneaux' => $creneaux,
        ]);
    }

    #[Route('/{id}/etat', name: 'app_rendez_vous_change_etat', methods: ['POST'])]
    public function changeEtat(Request $request, RendezVous $rendezVou, EtatRepository $etatRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        // Recupere la valeur demandee des maintenant pour gerer le cas patient (annulation)
        $requestedEtatId = $request->request->get('etat');

        // permission: admin OR medecin owner OR assistant of the medecin
        $medecin = $rendezVou->getMedecin();

        $allowed = false;
        if ($this->isGranted('ROLE_ADMIN')) {
            $allowed = true;
        } elseif ($user instanceof \App\Entity\Medecin && $user->getId() === $medecin->getId()) {
            $allowed = true;
        } elseif ($user instanceof \App\Entity\Assistant && $user->getMedecin() && $user->getMedecin()->getId() === $medecin->getId()) {
            // Assistant: autorise a confirmer (2), refuser (4) ou marquer realise (5)
            if ($requestedEtatId !== null && in_array((int)$requestedEtatId, [2, 4, 5], true)) {
                $allowed = true;
            }
        } elseif ($user instanceof \App\Entity\Patient && $rendezVou->getPatient() && $user->getId() === $rendezVou->getPatient()->getId()) {
            // Le patient ne peut que ANNULER (etat id = 3)
            if ($requestedEtatId !== null && (int)$requestedEtatId === 3) {
                $allowed = true;
            }
        }

        if (!$allowed) {
            throw $this->createAccessDeniedException('Acces non autorise');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('change_etat'.$rendezVou->getId(), $token)) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_mes_rendez_vous');
        }

        $etatId = $requestedEtatId;
        if ($etatId === null) {
            $this->addFlash('danger', 'Etat non specifie.');
            return $this->redirectToRoute('app_mes_rendez_vous');
        }

        // Regle metier: un RDV annule par le patient ne peut pas etre confirme ni refuse par l'assistant
        if (
            $user instanceof \App\Entity\Assistant
            && $rendezVou->getEtat()
            && (int)$rendezVou->getEtat()->getId() === 3
            && in_array((int)$etatId, [2, 4], true)
        ) {
            $this->addFlash('danger', 'Impossible de modifier (confirmer/refuser) un rendez-vous annule par le patient.');
            return $this->redirectToRoute('app_mes_rendez_vous');
        }

        $etat = $etatRepository->find((int)$etatId);
        if (!$etat) {
            $this->addFlash('danger', 'Etat invalide.');
            return $this->redirectToRoute('app_mes_rendez_vous');
        }

        $rendezVou->setEtat($etat);
        $entityManager->flush();

        $this->addFlash('success', 'Etat mis a jour.');
        return $this->redirectToRoute('app_mes_rendez_vous');
    }
}
