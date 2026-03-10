# Présentation du Projet : GestionRDV

## 1. Introduction
**GestionRDV** est une application web complète de gestion de rendez-vous médicaux développée avec **Symfony 6.4** et **PHP 8.1+**. Ce projet a été réalisé dans le cadre du BTS SIO option SLAM. Il met en relation des patients, des médecins et des assistants médicaux à travers une interface web fluide et propose également une API REST sécurisée.

L'objectif principal de l'application est de digitaliser et simplifier la prise de rendez-vous, la gestion des plannings médicaux et le suivi des consultations.

---

## 2. Fonctionnalités Principales

L'application est structurée autour de trois rôles distincts (RBAC - Role-Based Access Control) :

### 🧑‍⚕️ Espace Médecin (`ROLE_MEDECIN`)
- **Gestion des disponibilités :** Définition de créneaux récurrents (jours, heures, durée de la consultation).
- **Gestion des rendez-vous :** Validation (confirmation/refus), annulation et clôture (marquer comme réalisé) des demandes de rendez-vous.
- **Délégation :** Rattachement d'assistants pour la gestion de leur agenda.

### 💼 Espace Assistant (`ROLE_ASSISTANT`)
- **Gestion déléguée :** Accès et gestion de l'agenda du médecin auquel il est rattaché.
- **Suivi :** Confirmation, annulation ou refus des rendez-vous au nom du médecin.

### 🤒 Espace Patient (`ROLE_PATIENT`)
- **Prise de rendez-vous :** Recherche de médecins et sélection de créneaux disponibles. Le système génère dynamiquement les créneaux en fonction des disponibilités récurrentes du médecin et des rendez-vous déjà pris.
- **Suivi :** Consultation de l'historique et du statut des rendez-vous, annulation si nécessaire.

### ⚙️ Logique Métier Avancée
- **Machine à états des rendez-vous :** Les rendez-vous suivent un cycle de vie strict : `Demandé` ➔ `Confirmé` / `Refusé` ➔ `Réalisé` ou `Annulé`.
- **Génération intelligente des créneaux :** Un service métier (`CreneauDisponibiliteService`) calcule les créneaux disponibles en temps réel en excluant les horaires déjà réservés.
- **Tâches planifiées (Commandes) :** Mise à jour automatique des rendez-vous passés et création de sauvegardes via des commandes Symfony (`UpdatePastRendezVousCommand`, `BackupCommand`).

---

## 3. Architecture et Choix Techniques

Le projet respecte l'architecture **MVC (Modèle-Vue-Contrôleur)** inhérente à Symfony et s'appuie sur un écosystème technique moderne et robuste.

### Backend (Logique Serveur & API)
- **Framework :** Symfony 6.4 (LTS)
- **Langage :** PHP 8.1+ (Typage fort, attributs, enums).
- **Base de données & ORM :** MySQL / MariaDB avec **Doctrine ORM** (Migrations, Fixtures pour le jeu d'essai).
- **API REST :** Implémentation via **API Platform 4.2**, permettant une exposition propre et standardisée des ressources.
- **Sécurité :** Symfony Security couplé à **Lexik JWT Authentication** pour sécuriser les appels API par tokens.

### Frontend (Interface Utilisateur)
- **Moteur de template :** Twig
- **Réactivité :** Utilisation de **Symfony UX (Turbo)** pour des transitions de page fluides sans rechargement complet (Single Page Application feel) et **Stimulus** pour les composants interactifs.
- **Composants visuels :** CSS natif (Global CSS), intégration de librairies externes comme Flatpickr pour la sélection de dates.

### Outils de Qualité et Déploiement
- **Tests :** Utilisation de **PHPUnit** pour les tests automatisés (ex: `RendezVousControllerTest.php`).
- **Gestion de dépendances :** Composer.
- **Versionning :** Git.

---

## 4. Compétences Techniques Démontrées (Pour le Portfolio)

La réalisation de ce projet permet de mettre en avant les compétences suivantes pour un profil de Développeur Full-Stack / Backend :

1. **Maîtrise d'un Framework Backend complexe (Symfony) :**
   - Création de contrôleurs, formulaires, services métier (`CreneauDisponibiliteService`), et commandes console (`Command/`).
   - Utilisation de l'injection de dépendances, des événements (Event Subscribers) et des variables d'environnement.

2. **Modélisation de Base de Données & ORM (Doctrine) :**
   - Conception d'un schéma relationnel avec héritage (ex: `User` parent de `Patient`, `Medecin`, `Assistant`).
   - Maîtrise des relations complexes (OneToMany, ManyToOne), des migrations et des DataFixtures.

3. **Conception d'API REST (API Platform) :**
   - Exposition de ressources, gestion de la sérialisation, mise en place d'une authentification Stateless via JSON Web Tokens (JWT).

4. **Sécurité Web :**
   - Implémentation d'un système d'authentification et d'autorisation basé sur les rôles (RBAC).
   - Protection des routes (Web et API) et hachage des mots de passe.

5. **Développement Frontend Moderne :**
   - Intégration de templates Twig dynamiques.
   - Utilisation de Hotwire/Turbo pour optimiser les performances de navigation et l'expérience utilisateur (UX) sans framework JS lourd.

6. **Qualité et Tests :**
   - Écriture de tests fonctionnels avec PHPUnit pour garantir la robustesse du code.

7. **Logique Algorithmique :**
   - Développement d'algorithmes de calcul de plages horaires disponibles en croisant des indisponibilités et des durées variables.