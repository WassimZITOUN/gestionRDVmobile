<?php

namespace App\Controller;

use App\Entity\RendezVous;
use App\Form\RendezVousType;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Patient;
use App\Entity\Etat;
use App\Entity\Medecin;
use App\Repository\IndisponibiliteRepository;
use App\Repository\EtatRepository;


#[Route('/rendez/vous/new')]
final class RendezVousController extends AbstractController
{
    /*#[Route(name: 'app_rendez_vous_index', methods: ['GET'])]
    public function index(RendezVousRepository $rendezVousRepository): Response
    {
        // Met à jour automatiquement les RDV confirmés passés à "réalisé"
        $rendezVousRepository->updatePastConfirmedToRealise();

        return $this->render('rendez_vous/index.html.twig', [
            'rendez_vouses' => $rendezVousRepository->findAll(),
        ]);
    }*/

    #[Route(name: 'app_rendez_vous_new', methods: ['GET', 'POST'])]
public function new(
    Request $request,
    EntityManagerInterface $entityManager,
    IndisponibiliteRepository $indispoRepo,
    RendezVousRepository $rendezRepo
): Response {
    $user = $this->getUser();

    if (!$user instanceof Patient) {
        $this->addFlash('danger', 'Vous devez être connecté en tant que patient.');
        return $this->redirectToRoute('app_login');
    }

    $rendezVous = new RendezVous();
    $rendezVous->setPatient($user);

    $etat = $entityManager->getRepository(Etat::class)->find(1);
    $rendezVous->setEtat($etat);

    /* =========================
       PHASE 2 : clic sur créneau
       ========================= */
    if ($request->request->has('creneau')) {

        if (!$this->isCsrfTokenValid(
            'choix_creneau',
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException();
        }

        $creneau = $request->request->get('creneau');
        [$h, $m] = explode(':', $creneau);

        $date = new \DateTime($request->request->get('date'));
        $medecinId = $request->request->get('medecin');

        $medecin = $entityManager->getRepository(Medecin::class)->find($medecinId);
        $rendezVous->setMedecin($medecin);

        $debut = (clone $date)->setTime((int)$h, (int)$m);
        $rendezVous->setDebut($debut);
        $rendezVous->setFin((clone $debut)->modify('+1 hour'));

        $entityManager->persist($rendezVous);
        $entityManager->flush();

        $this->addFlash('success', 'Votre demande de rendez-vous a bien été envoyée.');

        return $this->redirectToRoute('app_mes_rendez_vous');
    }

    /* =========================
       PHASE 1 : date + médecin
       ========================= */
    $form = $this->createForm(RendezVousType::class, $rendezVous);
    $form->handleRequest($request);

    if ($form->isSubmitted()) {
        // Vérifier que la date et le médecin sont bien sélectionnés
        $medecin = $rendezVous->getMedecin();
        $date = $rendezVous->getDebut();

        if (!$date || !$medecin) {
            $this->addFlash('danger', 'Veuillez sélectionner une date et un médecin.');
            return $this->render('rendez_vous/new.html.twig', [
                'form' => $form,
            ]);
        }

        // Vérifier que la date n'est pas dans le passé
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        if ($date < $today) {
            $this->addFlash('danger', 'Veuillez sélectionner une date future.');
            return $this->render('rendez_vous/new.html.twig', [
                'form' => $form,
            ]);
        }

        if ($form->isValid()) {
            $creneauxPossibles = [
            '09:00','10:00',
            '11:00','13:00',
            '14:00','15:00',
            '16:00',
        ];

        $creneauxDispo = [];

        foreach ($creneauxPossibles as $c) {
            [$h, $m] = explode(':', $c);
            $start = (clone $date)->setTime($h, $m);
            $end = (clone $start)->modify('+1 hour');

            if (
                empty($rendezRepo->findOverlapping($medecin, $start, $end)) &&
                empty($indispoRepo->findOverlapping($medecin, $start, $end))
            ) {
                $creneauxDispo[] = $c;
            }

        }

        return $this->render('rendez_vous/choix_creneau.html.twig', [
            'rendezVous' => $rendezVous,
            'creneaux' => $creneauxDispo,
        ]);
        }
    }

    return $this->render('rendez_vous/new.html.twig', [
        'form' => $form,
    ]);
}

    

    #[Route('/{id}/edit', name: 'app_rendez_vous_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, RendezVous $rendezVou, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RendezVousType::class, $rendezVou);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // combine selected date and chosen time slot (creneau) into debut when editing
            $creneau = null;
            if ($form->has('creneau')) {
                $creneau = $form->get('creneau')->getData();
            }

            $date = $rendezVou->getDebut();
            if ($date instanceof \DateTimeInterface && $creneau) {
                [$h, $m] = explode(':', $creneau);
                $dt = (clone $date)->setTime((int)$h, (int)$m);
                $rendezVou->setDebut($dt);
                $rendezVou->setFin((clone $dt)->modify('+1 hour'));
            } else {
                $debut = $rendezVou->getDebut();
                if ($debut instanceof \DateTimeInterface) {
                    $rendezVou->setFin((clone $debut)->modify('+1 hour'));
                }
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_rendez_vous_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('rendez_vous/edit.html.twig', [
            'rendez_vou' => $rendezVou,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_rendez_vous_delete', methods: ['POST'])]
    public function delete(Request $request, RendezVous $rendezVou, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$rendezVou->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($rendezVou);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_rendez_vous_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/etat', name: 'app_rendez_vous_change_etat', methods: ['POST'])]
    public function changeEtat(Request $request, RendezVous $rendezVou, EtatRepository $etatRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        // Récupère la valeur demandée dès maintenant pour gérer le cas patient (annulation)
        $requestedEtatId = $request->request->get('etat');
        
        // permission: admin OR medecin owner OR assistant of the medecin
        $medecin = $rendezVou->getMedecin();

        $allowed = false;
        if ($this->isGranted('ROLE_ADMIN')) {
            $allowed = true;
        } elseif ($user instanceof \App\Entity\Medecin && $user->getId() === $medecin->getId()) {
            $allowed = true;
        } elseif ($user instanceof \App\Entity\Assistant && $user->getMedecin() && $user->getMedecin()->getId() === $medecin->getId()) {
            // Assistant uniquement: autorisé à confirmer (2) ou refuser (4)
            if ($requestedEtatId !== null && in_array((int)$requestedEtatId, [2, 4], true)) {
                $allowed = true;
            }
        } elseif ($user instanceof \App\Entity\Patient && $rendezVou->getPatient() && $user->getId() === $rendezVou->getPatient()->getId()) {
            // Le patient ne peut que ANNULER (etat id = 3)
            if ($requestedEtatId !== null && (int)$requestedEtatId === 3) {
                $allowed = true;
            }
        }

        if (!$allowed) {
            throw $this->createAccessDeniedException('Accès non autorisé');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('change_etat'.$rendezVou->getId(), $token)) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_mes_rendez_vous');
        }

        $etatId = $requestedEtatId;
        if ($etatId === null) {
            $this->addFlash('danger', 'État non spécifié.');
            return $this->redirectToRoute('app_mes_rendez_vous');
        }

        // Règle métier: un RDV annulé par le patient ne peut pas être confirmé ni refusé par l'assistant
        if (
            $user instanceof \App\Entity\Assistant
            && $rendezVou->getEtat()
            && (int)$rendezVou->getEtat()->getId() === 3
            && in_array((int)$etatId, [2, 4], true)
        ) {
            $this->addFlash('danger', 'Impossible de modifier (confirmer/refuser) un rendez-vous annulé par le patient.');
            return $this->redirectToRoute('app_mes_rendez_vous');
        }

        $etat = $etatRepository->find((int)$etatId);
        if (!$etat) {
            $this->addFlash('danger', 'État invalide.');
            return $this->redirectToRoute('app_mes_rendez_vous');
        }

        $rendezVou->setEtat($etat);
        $entityManager->flush();

        $this->addFlash('success', 'État mis à jour.');
        return $this->redirectToRoute('app_mes_rendez_vous');
    }
}
