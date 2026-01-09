<?php

namespace App\Controller;

use App\Entity\Patient;
use App\Entity\Medecin;
use App\Repository\EtatRepository;
use App\Repository\RendezVousRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\RendezVous;
use App\Form\RendezVousType; 
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\IndisponibiliteRepository;
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

        if (in_array('ROLE_PATIENT', $user->getRoles())) {
            $role = 'patient';
        } elseif (in_array('ROLE_MEDECIN', $user->getRoles())) {
            $role = 'medecin';
        } else {
            throw $this->createAccessDeniedException('Vous devez être connecté en tant que patient ou médecin.');
        }

        // Met à jour automatiquement les RDV confirmés passés à "réalisé"
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

        // Récupération des RDV selon le rôle
        if ($role === 'patient') {
            $rendezVous = $rendezVousRepository->findByPatientAndEtat($user, $etatId);
        } else {
            $rendezVous = $rendezVousRepository->findByMedecinAndEtat($user, $etatId, $aujourdhui, $dateSpecifique);
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
    IndisponibiliteRepository $indispoRepo,
    RendezVousRepository $rendezRepo
): Response {
    $user = $this->getUser();
    if (!$user instanceof Patient) {
        $this->addFlash('danger', 'Vous devez être connecté en tant que patient.');
        return $this->redirectToRoute('app_login');
    }

    // Remettre l'état à "demandé"
    $etat = $entityManager->getRepository(Etat::class)->find(1);
    $rendezVou->setEtat($etat);

    // PHASE 2 : clic sur créneau
    if ($request->request->has('creneau')) {
        if (!$this->isCsrfTokenValid('choix_creneau', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $creneau = $request->request->get('creneau');
        [$h, $m] = explode(':', $creneau);

        $date = new \DateTime($request->request->get('date'));
        $medecinId = $request->request->get('medecin');
        $medecin = $entityManager->getRepository(Medecin::class)->find($medecinId);

        $rendezVou->setMedecin($medecin);
        $debut = (clone $date)->setTime((int)$h, (int)$m);
        $rendezVou->setDebut($debut);
        $rendezVou->setFin((clone $debut)->modify('+1 hour'));

        $entityManager->flush();

        $this->addFlash('success', 'Votre rendez-vous a été mis à jour.');

        return $this->redirectToRoute('app_mes_rendez_vous');
    }

    // PHASE 1 : date + médecin
    $form = $this->createForm(RendezVousType::class, $rendezVou);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $medecin = $rendezVou->getMedecin();
        $date = $rendezVou->getDebut();

        // Vérifier que la date n'est pas dans le passé
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        if ($date < $today) {
            $this->addFlash('danger', 'Veuillez sélectionner une date future.');
            return $this->render('mes_rendez_vous/edit.html.twig', [
                'form' => $form->createView(),
                'rendez_vou' => $rendezVou,
            ]);
        }

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
            'rendezVous' => $rendezVou,
            'creneaux' => $creneauxDispo,
        ]);
    }

    return $this->render('mes_rendez_vous/edit.html.twig', [
        'form' => $form->createView(),
        'rendez_vou' => $rendezVou,
    ]);
}


    #[Route('/mes-rendez-vous/{id}/cancel', name: 'app_mes_rendez_vous_cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cancel(
        RendezVous $rendezVous,
        EtatRepository $etatRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        // Sécurité : seul le patient ou le médecin concerné peut annuler
        if (
            ($this->isGranted('ROLE_PATIENT') && $rendezVous->getPatient() !== $user) &&
            ($this->isGranted('ROLE_MEDECIN') && $rendezVous->getMedecin() !== $user)
        ) {
            throw $this->createAccessDeniedException();
        }

        // États non annulables
        if (in_array($rendezVous->getEtat()->getLibelle(), ['annulé', 'refusé', 'realisé'])) {
            $this->addFlash('warning', 'Ce rendez-vous ne peut plus être annulé.');
            return $this->redirectToRoute('app_mes_rendez_vous');
        }

        // Récupération de l'état "annulé"
        $etatAnnule = $etatRepository->findOneBy(['libelle' => 'annulé']);

        if (!$etatAnnule) {
            throw new \LogicException('État "annulé" introuvable en base.');
        }

        $rendezVous->setEtat($etatAnnule);
        $entityManager->flush();

        $this->addFlash('success', 'Le rendez-vous a bien été annulé.');

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
