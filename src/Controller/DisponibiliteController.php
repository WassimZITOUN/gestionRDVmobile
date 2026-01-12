<?php

namespace App\Controller;

use App\Entity\DisponibiliteRecurrente;
use App\Entity\Medecin;
use App\Form\DisponibiliteRecurrenteType;
use App\Repository\DisponibiliteRecurrenteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/disponibilite')]
#[IsGranted('ROLE_MEDECIN')]
class DisponibiliteController extends AbstractController
{
    #[Route('/', name: 'disponibilite_index', methods: ['GET'])]
    public function index(DisponibiliteRecurrenteRepository $repo): Response
    {
        $medecin = $this->getUser();
        if (!$medecin instanceof Medecin) {
            throw $this->createAccessDeniedException();
        }

        // Grouper par jour de la semaine
        $disponibilites = $repo->findAllByMedecin($medecin);
        $parJour = [];
        foreach ($disponibilites as $dispo) {
            $parJour[$dispo->getJourSemaine()][] = $dispo;
        }

        return $this->render('disponibilite/index.html.twig', [
            'disponibilitesParJour' => $parJour,
            'jours' => [
                1 => 'Lundi',
                2 => 'Mardi',
                3 => 'Mercredi',
                4 => 'Jeudi',
                5 => 'Vendredi',
                6 => 'Samedi',
                7 => 'Dimanche'
            ],
        ]);
    }

    #[Route('/new', name: 'disponibilite_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, DisponibiliteRecurrenteRepository $repo): Response
    {
        $medecin = $this->getUser();
        if (!$medecin instanceof Medecin) {
            throw $this->createAccessDeniedException();
        }

        $dispo = new DisponibiliteRecurrente();
        $dispo->setMedecin($medecin);
        $dispo->setActif(true);

        $form = $this->createForm(DisponibiliteRecurrenteType::class, $dispo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Validation : heure fin > heure debut
            if ($dispo->getHeureFin() <= $dispo->getHeureDebut()) {
                $this->addFlash('danger', 'L\'heure de fin doit être postérieure à l\'heure de début.');
                return $this->render('disponibilite/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            // Verifier chevauchement
            $overlaps = $repo->findOverlapping(
                $medecin,
                $dispo->getJourSemaine(),
                $dispo->getHeureDebut(),
                $dispo->getHeureFin()
            );

            if (!empty($overlaps)) {
                $this->addFlash('danger', 'Cette plage horaire chevauche une disponibilité existante.');
                return $this->render('disponibilite/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $em->persist($dispo);
            $em->flush();

            $this->addFlash('success', 'Disponibilité ajoutée avec succès.');
            return $this->redirectToRoute('disponibilite_index');
        }

        return $this->render('disponibilite/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'disponibilite_edit', methods: ['GET', 'POST'])]
    public function edit(DisponibiliteRecurrente $dispo, Request $request, EntityManagerInterface $em, DisponibiliteRecurrenteRepository $repo): Response
    {
        $medecin = $this->getUser();
        if (!$medecin instanceof Medecin || $dispo->getMedecin()->getId() !== $medecin->getId()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(DisponibiliteRecurrenteType::class, $dispo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($dispo->getHeureFin() <= $dispo->getHeureDebut()) {
                $this->addFlash('danger', 'L\'heure de fin doit être postérieure à l\'heure de début.');
                return $this->render('disponibilite/edit.html.twig', [
                    'form' => $form->createView(),
                    'dispo' => $dispo,
                ]);
            }

            // Verifier chevauchement (en excluant soi-meme)
            $overlaps = $repo->findOverlapping(
                $medecin,
                $dispo->getJourSemaine(),
                $dispo->getHeureDebut(),
                $dispo->getHeureFin()
            );
            $overlaps = array_filter($overlaps, fn($o) => $o->getId() !== $dispo->getId());

            if (!empty($overlaps)) {
                $this->addFlash('danger', 'Cette plage horaire chevauche une disponibilité existante.');
                return $this->render('disponibilite/edit.html.twig', [
                    'form' => $form->createView(),
                    'dispo' => $dispo,
                ]);
            }

            $em->flush();
            $this->addFlash('success', 'Disponibilité mise à jour.');
            return $this->redirectToRoute('disponibilite_index');
        }

        return $this->render('disponibilite/edit.html.twig', [
            'form' => $form->createView(),
            'dispo' => $dispo,
        ]);
    }

    #[Route('/{id}', name: 'disponibilite_delete', methods: ['POST'])]
    public function delete(DisponibiliteRecurrente $dispo, Request $request, EntityManagerInterface $em): Response
    {
        $medecin = $this->getUser();
        if (!$medecin instanceof Medecin || $dispo->getMedecin()->getId() !== $medecin->getId()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete' . $dispo->getId(), $request->request->get('_token'))) {
            $em->remove($dispo);
            $em->flush();
            $this->addFlash('success', 'Disponibilité supprimée.');
        }

        return $this->redirectToRoute('disponibilite_index');
    }
}
