<?php

namespace App\Controller;

use App\Entity\Patient;
use App\Entity\Medecin;
use App\Entity\Assistant;
use App\Model\RegistrationModel;
use App\Entity\User; // Garder User pour le type hinting et les propriétés communes
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use LogicException;
use App\Repository\MedecinRepository;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    #[Route('/register', name: 'app_register')]
public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, MedecinRepository $repo): Response
{

    $model = new RegistrationModel();
    $medecins = $repo->getLesMedecins();
    $form = $this->createForm(RegistrationFormType::class, $model, [
        'medecins_choix' => $medecins,
    ]);

    $form->handleRequest($request);
 



    if ($form->isSubmitted() && $form->isValid()) {
        
        $userType = $form->get('userType')->getData();
    $plainPassword = $form->get('plainPassword')->getData();

    // Créer l'entité selon le type choisi et définir le rôle
    switch ($userType) {
        case 'patient':
            $entityToPersist = new Patient();
            $roles = ['ROLE_PATIENT'];
            break;
        case 'medecin':
            $entityToPersist = new Medecin();
            $roles = ['ROLE_MEDECIN'];
            break;
        case 'assistant':
            $entityToPersist = new Assistant();
            $roles = ['ROLE_ASSISTANT'];
            break;
        default:
            throw new LogicException("Type d'utilisateur non supporté.");
    }


        $entityToPersist->setNom($form->get('nom')->getData());
        $entityToPersist->setPrenom($form->get('prenom')->getData());
        $entityToPersist->setEmail($form->get('email')->getData());
        $entityToPersist->setRoles($roles);

        if ($entityToPersist instanceof Assistant) {
            $entityToPersist->setMedecin($form->get('medecin')->getData());
        }


        $entityToPersist->setPassword(
            $userPasswordHasher->hashPassword($entityToPersist, $plainPassword)
        );

        try {
            $entityManager->persist($entityToPersist);
            $entityManager->flush();

            $this->addFlash('success', 'Inscription réussie ! Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_accueil');
        } catch (UniqueConstraintViolationException $e) {
            $this->addFlash('danger', 'Cette adresse email est déjà utilisée. Veuillez en choisir une autre.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Une erreur est survenue lors de l\'inscription. Veuillez réessayer.');
        }
    }

    return $this->render('registration/register.html.twig', [
        'registrationForm' => $form->createView(),
    ]);
}

}