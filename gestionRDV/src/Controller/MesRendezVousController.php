<?php

namespace App\Controller;

use App\Entity\Patient;
use App\Entity\Medecin;
use App\Entity\Assistant;
use App\Repository\EtatRepository;
use App\Repository\RendezVousRepository;
use App\Repository\MedecinRepository;
use App\Service\CreneauDisponibiliteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\RendezVous;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Etat;

final class MesRendezVousController extends AbstractController
{
    #[Route('/mes-rendez-vous', name: 'app_mes_rendez_vous')]
    public function index(
        Request $request,
        RendezVousRepository $rendezVousRepository,
        EtatRepository $etatRepository
    ): Response {
        $user = $this->getUser();
        $medecin = null;

        if ($user instanceof Patient) {
            $role = 'patient';
        } elseif ($user instanceof Medecin) {
            $role = 'medecin';
            $medecin = $user;
        } elseif ($user instanceof Assistant) {
            $role = 'assistant';
            $medecin = $user->getMedecin();
            if (!$medecin) {
                throw $this->createAccessDeniedException('Aucun medecin associe a votre compte assistant.');
            }
        } else {
            throw $this->createAccessDeniedException('Vous devez etre connecte en tant que patient, medecin ou assistant.');
        }

        // Met a jour automatiquement les RDV confirmes passes a "realise"
        $rendezVousRepository->updatePastConfirmedToRealise();

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

        // Recuperation des RDV selon le role
        if ($role === 'patient') {
            $rendezVous = $rendezVousRepository->findByPatientAndEtat($user, $etatId);
        } else {
            // Medecin ou Assistant: affiche les RDV du medecin
            $rendezVous = $rendezVousRepository->findByMedecinAndEtat($medecin, $etatId, $aujourdhui, $dateSpecifique);
        }

        return $this->render('mes_rendez_vous/index.html.twig', [
            'rendezVous' => $rendezVous,
            'etats' => $etatRepository->findAll(),
            'etatSelectionne' => $etatId,
            'jourFilter' => $jourFilter,
            'dateSelectionnee' => $dateString,
            'role' => $role,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_mes_rendez_vous_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        Request $request,
        RendezVous $rendezVou,
        EntityManagerInterface $entityManager,
        MedecinRepository $medecinRepo,
        CreneauDisponibiliteService $creneauService
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof Patient) {
            $this->addFlash('danger', 'Vous devez etre connecte en tant que patient.');
            return $this->redirectToRoute('app_login');
        }

        // Verifier que le patient est bien le proprietaire
        if ($rendezVou->getPatient()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce rendez-vous.');
        }

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
                return $this->redirectToRoute('app_mes_rendez_vous_edit', ['id' => $rendezVou->getId()]);
            }

            [$h, $m] = explode(':', $heure);
            $debut = new \DateTime($date);
            $debut->setTime((int)$h, (int)$m);
            $fin = (clone $debut)->modify("+{$duree} minutes");

            // Verifier que le creneau est toujours disponible
            if (!$creneauService->isCreneauDisponible($medecin, $debut, $fin)) {
                $this->addFlash('danger', 'Ce creneau n\'est plus disponible. Veuillez en choisir un autre.');
                return $this->redirectToRoute('app_mes_rendez_vous_edit', ['id' => $rendezVou->getId()]);
            }

            // Mettre a jour le RDV
            $rendezVou->setMedecin($medecin);
            $rendezVou->setDebut($debut);
            $rendezVou->setFin($fin);

            // Remettre l'etat a "demande"
            $etat = $entityManager->getRepository(Etat::class)->find(1);
            $rendezVou->setEtat($etat);

            $entityManager->flush();

            $this->addFlash('success', 'Votre rendez-vous a ete modifie avec succes.');
            return $this->redirectToRoute('app_mes_rendez_vous');
        }

        // Afficher les creneaux disponibles pour modifier le RDV
        $medecinId = $request->query->get('medecin');
        $medecinSelectionne = $medecinId ? $medecinRepo->find($medecinId) : $rendezVou->getMedecin();

        $medecins = $medecinRepo->findAll();
        $creneaux = [];

        if ($medecinSelectionne) {
            $creneaux = $creneauService->getProchainsCréneaux($medecinSelectionne, 30, 50);
            foreach ($creneaux as &$c) {
                $c['medecin'] = $medecinSelectionne;
            }
        } else {
            $creneaux = $creneauService->getProchainsCréneauxTousMedecins($medecins, 14, 30);
        }

        return $this->render('mes_rendez_vous/edit.html.twig', [
            'rendez_vou' => $rendezVou,
            'medecins' => $medecins,
            'medecinSelectionne' => $medecinSelectionne,
            'creneaux' => $creneaux,
        ]);
    }

    #[Route('/mes-rendez-vous/{id}/cancel', name: 'app_mes_rendez_vous_cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cancel(
        Request $request,
        RendezVous $rendezVous,
        EtatRepository $etatRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Verification CSRF
        if (!$this->isCsrfTokenValid('cancel' . $rendezVous->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $user = $this->getUser();

        // Securite : seul le patient ou le medecin concerne peut annuler
        if (
            ($this->isGranted('ROLE_PATIENT') && $rendezVous->getPatient() !== $user) &&
            ($this->isGranted('ROLE_MEDECIN') && $rendezVous->getMedecin() !== $user)
        ) {
            throw $this->createAccessDeniedException();
        }

        // Etats non annulables
        if (in_array($rendezVous->getEtat()->getLibelle(), ['annulé', 'refusé', 'réalisé'])) {
            $this->addFlash('warning', 'Ce rendez-vous ne peut plus etre annule.');
            return $this->redirectToRoute('app_mes_rendez_vous');
        }

        // Recuperation de l'etat "annule"
        $etatAnnule = $etatRepository->findOneBy(['libelle' => 'annulé']);

        if (!$etatAnnule) {
            throw new \LogicException('Etat "annule" introuvable en base.');
        }

        $rendezVous->setEtat($etatAnnule);
        $entityManager->flush();

        $this->addFlash('success', 'ss Le rendez-vous a bien ete annule.');

        return $this->redirectToRoute('app_mes_rendez_vous');
    }

    #[Route('/{id}', name: 'app_mes_rendez_vous_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(RendezVous $rendezVou): Response
    {
        return $this->render('mes_rendez_vous/show.html.twig', [
            'rendez_vou' => $rendezVou,
        ]);
    }
}
