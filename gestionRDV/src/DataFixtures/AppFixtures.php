<?php

namespace App\DataFixtures;

use App\Entity\Assistant;
use App\Entity\DisponibiliteRecurrente;
use App\Entity\Etat;
use App\Entity\Medecin;
use App\Entity\Patient;
use App\Entity\RendezVous;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Créer les états
        $etats = $this->createEtats($manager);

        // 2. Créer des médecins
        $medecins = $this->createMedecins($manager);

        // 3. Créer des patients
        $patients = $this->createPatients($manager);

        // 4. Créer des assistants
        $assistants = $this->createAssistants($manager, $medecins);

        // 5. Créer des rendez-vous
        $this->createRendezVous($manager, $medecins, $patients, $etats);

        // 6. Créer des disponibilités récurrentes
        $nbDisponibilites = $this->createDisponibilitesRecurrentes($manager, $medecins);

        // 7. Creer des utilisateurs LDAP (uid, sans mot de passe bcrypt)
        $ldapUsers = $this->createLdapUsers($manager);

        $manager->flush();

        echo "Fixtures chargees avec succes!\n";
        echo "Resume:\n";
        echo "   - " . count($etats) . " etats crees\n";
        echo "   - " . count($medecins) . " medecins crees\n";
        echo "   - " . count($patients) . " patients crees\n";
        echo "   - " . count($assistants) . " assistants crees\n";
        echo "   - " . $nbDisponibilites . " disponibilites recurrentes creees\n";
        echo "   - " . count($ldapUsers) . " utilisateurs LDAP crees\n";
        echo "\nComptes de test (classique - email/bcrypt):\n";
        echo "   Medecin: medecin1@test.fr / password123\n";
        echo "   Patient: patient1@test.fr / password123\n";
        echo "   Assistant: assistant1@test.fr / password123\n";
        echo "\nComptes de test (LDAP - uid/mdp LDAP):\n";
        echo "   Patient LDAP: jean.dupont (mot de passe gere par le serveur LDAP)\n";
        echo "   Medecin LDAP: dr.martin (mot de passe gere par le serveur LDAP)\n";
        echo "   Assistant LDAP: aide.leroy (mot de passe gere par le serveur LDAP)\n";
    }

    private function createEtats(ObjectManager $manager): array
    {
        $etatsData = [
            'demandé',
            'confirmé',
            'annulé',
            'refusé',
            'réalisé'
        ];

        $etats = [];
        foreach ($etatsData as $libelle) {
            $etat = new Etat();
            $etat->setLibelle($libelle);
            $manager->persist($etat);
            $etats[$libelle] = $etat;
        }

        return $etats;
    }

    private function createMedecins(ObjectManager $manager): array
    {
        $medecinsData = [
            ['nom' => 'Dupont', 'prenom' => 'Jean', 'email' => 'medecin1@test.fr'],
            ['nom' => 'Martin', 'prenom' => 'Marie', 'email' => 'medecin2@test.fr'],
            ['nom' => 'Bernard', 'prenom' => 'Pierre', 'email' => 'medecin3@test.fr'],
            ['nom' => 'Dubois', 'prenom' => 'Sophie', 'email' => 'medecin4@test.fr'],
        ];

        $medecins = [];
        foreach ($medecinsData as $data) {
            $medecin = new Medecin();
            $medecin->setNom($data['nom']);
            $medecin->setPrenom($data['prenom']);
            $medecin->setEmail($data['email']);
            $medecin->setRoles(['ROLE_MEDECIN']);

            $hashedPassword = $this->passwordHasher->hashPassword($medecin, 'password123');
            $medecin->setPassword($hashedPassword);

            $manager->persist($medecin);
            $medecins[] = $medecin;
        }

        return $medecins;
    }

    private function createPatients(ObjectManager $manager): array
    {
        $patientsData = [
            ['nom' => 'Leroy', 'prenom' => 'Thomas', 'email' => 'patient1@test.fr'],
            ['nom' => 'Moreau', 'prenom' => 'Julie', 'email' => 'patient2@test.fr'],
            ['nom' => 'Simon', 'prenom' => 'Lucas', 'email' => 'patient3@test.fr'],
            ['nom' => 'Laurent', 'prenom' => 'Emma', 'email' => 'patient4@test.fr'],
            ['nom' => 'Lefebvre', 'prenom' => 'Nathan', 'email' => 'patient5@test.fr'],
            ['nom' => 'Michel', 'prenom' => 'Léa', 'email' => 'patient6@test.fr'],
            ['nom' => 'Garcia', 'prenom' => 'Hugo', 'email' => 'patient7@test.fr'],
            ['nom' => 'David', 'prenom' => 'Chloé', 'email' => 'patient8@test.fr'],
        ];

        $patients = [];
        foreach ($patientsData as $data) {
            $patient = new Patient();
            $patient->setNom($data['nom']);
            $patient->setPrenom($data['prenom']);
            $patient->setEmail($data['email']);
            $patient->setRoles(['ROLE_PATIENT']);

            $hashedPassword = $this->passwordHasher->hashPassword($patient, 'password123');
            $patient->setPassword($hashedPassword);

            $manager->persist($patient);
            $patients[] = $patient;
        }

        return $patients;
    }

    private function createAssistants(ObjectManager $manager, array $medecins): array
    {
        $assistantsData = [
            ['nom' => 'Petit', 'prenom' => 'Alice', 'email' => 'assistant1@test.fr', 'medecin_index' => 0],
            ['nom' => 'Robert', 'prenom' => 'Marc', 'email' => 'assistant2@test.fr', 'medecin_index' => 1],
        ];

        $assistants = [];
        foreach ($assistantsData as $data) {
            $assistant = new Assistant();
            $assistant->setNom($data['nom']);
            $assistant->setPrenom($data['prenom']);
            $assistant->setEmail($data['email']);
            $assistant->setRoles(['ROLE_ASSISTANT']);
            $assistant->setMedecin($medecins[$data['medecin_index']]);

            $hashedPassword = $this->passwordHasher->hashPassword($assistant, 'password123');
            $assistant->setPassword($hashedPassword);

            $manager->persist($assistant);
            $assistants[] = $assistant;
        }

        return $assistants;
    }

    private function createRendezVous(ObjectManager $manager, array $medecins, array $patients, array $etats): void
    {
        $now = new \DateTime();

        // RDV demandé (aujourd'hui + 5 jours, 9h-10h)
        $rdv1 = new RendezVous();
        $debut1 = (clone $now)->modify('+5 days')->setTime(9, 0);
        $rdv1->setDebut($debut1);
        $rdv1->setFin((clone $debut1)->modify('+1 hour'));
        $rdv1->setMedecin($medecins[0]);
        $rdv1->setPatient($patients[0]);
        $rdv1->setEtat($etats['demandé']);
        $manager->persist($rdv1);

        // RDV demandé (aujourd'hui + 7 jours, 14h-15h)
        $rdv2 = new RendezVous();
        $debut2 = (clone $now)->modify('+7 days')->setTime(14, 0);
        $rdv2->setDebut($debut2);
        $rdv2->setFin((clone $debut2)->modify('+1 hour'));
        $rdv2->setMedecin($medecins[1]);
        $rdv2->setPatient($patients[1]);
        $rdv2->setEtat($etats['demandé']);
        $manager->persist($rdv2);

        // RDV confirmé (aujourd'hui + 3 jours, 10h-11h)
        $rdv3 = new RendezVous();
        $debut3 = (clone $now)->modify('+3 days')->setTime(10, 0);
        $rdv3->setDebut($debut3);
        $rdv3->setFin((clone $debut3)->modify('+1 hour'));
        $rdv3->setMedecin($medecins[0]);
        $rdv3->setPatient($patients[2]);
        $rdv3->setEtat($etats['confirmé']);
        $manager->persist($rdv3);

        // RDV confirmé (aujourd'hui + 4 jours, 15h-16h)
        $rdv4 = new RendezVous();
        $debut4 = (clone $now)->modify('+4 days')->setTime(15, 0);
        $rdv4->setDebut($debut4);
        $rdv4->setFin((clone $debut4)->modify('+1 hour'));
        $rdv4->setMedecin($medecins[2]);
        $rdv4->setPatient($patients[3]);
        $rdv4->setEtat($etats['confirmé']);
        $manager->persist($rdv4);

        // RDV confirmé (demain, 9h-10h)
        $rdv5 = new RendezVous();
        $debut5 = (clone $now)->modify('+1 day')->setTime(9, 0);
        $rdv5->setDebut($debut5);
        $rdv5->setFin((clone $debut5)->modify('+1 hour'));
        $rdv5->setMedecin($medecins[1]);
        $rdv5->setPatient($patients[4]);
        $rdv5->setEtat($etats['confirmé']);
        $manager->persist($rdv5);

        // RDV annulé (hier, 14h-15h)
        $rdv6 = new RendezVous();
        $debut6 = (clone $now)->modify('-1 day')->setTime(14, 0);
        $rdv6->setDebut($debut6);
        $rdv6->setFin((clone $debut6)->modify('+1 hour'));
        $rdv6->setMedecin($medecins[0]);
        $rdv6->setPatient($patients[5]);
        $rdv6->setEtat($etats['annulé']);
        $manager->persist($rdv6);

        // RDV refusé (aujourd'hui + 10 jours, 11h-12h)
        $rdv7 = new RendezVous();
        $debut7 = (clone $now)->modify('+10 days')->setTime(11, 0);
        $rdv7->setDebut($debut7);
        $rdv7->setFin((clone $debut7)->modify('+1 hour'));
        $rdv7->setMedecin($medecins[3]);
        $rdv7->setPatient($patients[6]);
        $rdv7->setEtat($etats['refusé']);
        $manager->persist($rdv7);

        // RDV réalisé (il y a 5 jours, 10h-11h)
        $rdv8 = new RendezVous();
        $debut8 = (clone $now)->modify('-5 days')->setTime(10, 0);
        $rdv8->setDebut($debut8);
        $rdv8->setFin((clone $debut8)->modify('+1 hour'));
        $rdv8->setMedecin($medecins[1]);
        $rdv8->setPatient($patients[7]);
        $rdv8->setEtat($etats['réalisé']);
        $manager->persist($rdv8);

        // RDV réalisé (il y a 3 jours, 16h-17h)
        $rdv9 = new RendezVous();
        $debut9 = (clone $now)->modify('-3 days')->setTime(16, 0);
        $rdv9->setDebut($debut9);
        $rdv9->setFin((clone $debut9)->modify('+1 hour'));
        $rdv9->setMedecin($medecins[2]);
        $rdv9->setPatient($patients[0]);
        $rdv9->setEtat($etats['réalisé']);
        $manager->persist($rdv9);

        // RDV demandé (aujourd'hui + 2 jours, 11h-12h)
        $rdv10 = new RendezVous();
        $debut10 = (clone $now)->modify('+2 days')->setTime(11, 0);
        $rdv10->setDebut($debut10);
        $rdv10->setFin((clone $debut10)->modify('+1 hour'));
        $rdv10->setMedecin($medecins[3]);
        $rdv10->setPatient($patients[1]);
        $rdv10->setEtat($etats['demandé']);
        $manager->persist($rdv10);
    }

    private function createDisponibilitesRecurrentes(ObjectManager $manager, array $medecins): int
    {
        $data = [
            ['medecin' => 0, 'jour' => 1, 'debut' => '09:00', 'fin' => '12:00'],
            ['medecin' => 0, 'jour' => 3, 'debut' => '14:00', 'fin' => '18:00'],
            ['medecin' => 1, 'jour' => 2, 'debut' => '08:30', 'fin' => '12:30'],
            ['medecin' => 2, 'jour' => 4, 'debut' => '13:00', 'fin' => '17:00'],
            ['medecin' => 3, 'jour' => 5, 'debut' => '09:00', 'fin' => '16:00'],
        ];

        foreach ($data as $row) {
            $dispo = new DisponibiliteRecurrente();
            $dispo->setMedecin($medecins[$row['medecin']]);
            $dispo->setJourSemaine($row['jour']);
            $dispo->setHeureDebut(new \DateTimeImmutable($row['debut']));
            $dispo->setHeureFin(new \DateTimeImmutable($row['fin']));
            $dispo->setDureeRdvMinutes(60);
            $dispo->setActif(true);
            $manager->persist($dispo);
        }

        return count($data);
    }

    private function createLdapUsers(ObjectManager $manager): array
    {
        $ldapUsers = [];

        // Patient LDAP
        $patient = new Patient();
        $patient->setUid('jean.dupont');
        $patient->setEmail('jean.dupont@slam.lab');
        $patient->setNom('Dupont');
        $patient->setPrenom('Jean');
        $patient->setRoles(['ROLE_PATIENT']);
        // Pas de mot de passe bcrypt : auth via LDAP
        $patient->setPassword('');
        $manager->persist($patient);
        $ldapUsers[] = $patient;

        // Medecin LDAP
        $medecin = new Medecin();
        $medecin->setUid('dr.martin');
        $medecin->setEmail('dr.martin@slam.lab');
        $medecin->setNom('Martin');
        $medecin->setPrenom('Pierre');
        $medecin->setRoles(['ROLE_MEDECIN']);
        $medecin->setPassword('');
        $manager->persist($medecin);
        $ldapUsers[] = $medecin;

        // Assistant LDAP
        $assistant = new Assistant();
        $assistant->setUid('aide.leroy');
        $assistant->setEmail('aide.leroy@slam.lab');
        $assistant->setNom('Leroy');
        $assistant->setPrenom('Sophie');
        $assistant->setRoles(['ROLE_ASSISTANT']);
        $assistant->setPassword('');
        $assistant->setMedecin($medecin);
        $manager->persist($assistant);
        $ldapUsers[] = $assistant;

        return $ldapUsers;
    }
}
