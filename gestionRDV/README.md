# GestionRDV - Système de Gestion de Rendez-vous Médicaux

Application web de gestion de rendez-vous médicaux développée avec Symfony 6.4. Elle permet aux patients de prendre rendez-vous avec des médecins, et aux médecins/assistants de gérer leurs plannings.

---

## Table des matières

- [Installation rapide](#installation-rapide)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration](#configuration)
- [Lancement de l'application](#lancement-de-lapplication)
- [Utilisation](#utilisation)
  - [Inscription](#inscription)
  - [Connexion](#connexion)
  - [Espace Patient](#espace-patient)
  - [Espace Médecin](#espace-médecin)
  - [Espace Assistant](#espace-assistant)
- [Fonctionnalités](#fonctionnalités)
- [Structure du projet](#structure-du-projet)
- [API REST](#api-rest)
- [Commandes utiles](#commandes-utiles)
- [Dépannage](#dépannage)

---

## Installation rapide

Pour les utilisateurs pressés, voici toutes les commandes à exécuter :

```bash
# Cloner le projet
git clone https://github.com/VOTRE_USERNAME/gestionRDV.git
cd gestionRDV

# Installer les dépendances
composer install

# Copier et configurer l'environnement
cp .env .env.local
# Éditez .env.local pour configurer DATABASE_URL

# Créer la base de données et exécuter les migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Charger les données de test (fixtures)
php bin/console doctrine:fixtures:load

# Lancer le serveur
symfony server:start
```

Connectez-vous avec `medecin1@test.fr` / `password123` ou `patient1@test.fr` / `password123`

---

## Prérequis

Avant de commencer, assurez-vous d'avoir installé :

| Logiciel | Version minimale |
|----------|------------------|
| PHP | 8.1+ |
| Composer | 2.x |
| MySQL / MariaDB | 8.0+ / 10.11+ |
| Symfony CLI (recommandé) | 5.x |

**Extensions PHP requises :**
- `ext-ctype`
- `ext-iconv`
- `ext-pdo_mysql`

---

## Installation

### 1. Cloner le dépôt

```bash
git clone https://github.com/VOTRE_USERNAME/gestionRDV.git
cd gestionRDV
```

### 2. Installer les dépendances PHP

```bash
composer install
```

Cette commande installe toutes les dépendances nécessaires, notamment :
- Symfony 6.4 et ses composants
- Doctrine ORM pour la base de données
- API Platform pour l'API REST
- Lexik JWT pour l'authentification API
- Twig pour les templates
- Turbo pour la navigation fluide

### 3. Configurer l'environnement

Copiez le fichier `.env` en `.env.local` et modifiez les valeurs selon votre configuration :

```bash
cp .env .env.local
```

Éditez `.env.local` :

```env
# Configuration de la base de données
DATABASE_URL="mysql://VOTRE_USER:VOTRE_PASSWORD@127.0.0.1:3306/gestionRDV?charset=utf8mb4"

# Environnement (dev ou prod)
APP_ENV=dev
```

### 4. Créer la base de données

```bash
# Créer la base de données
php bin/console doctrine:database:create

# Exécuter les migrations
php bin/console doctrine:migrations:migrate
```

### 5. Charger les données initiales (Fixtures)

Le projet inclut des fixtures qui créent automatiquement :
- Les 5 états de rendez-vous (demandé, confirmé, annulé, refusé, réalisé)
- 4 médecins de test
- 8 patients de test
- 2 assistants de test
- Des rendez-vous exemples
- Des indisponibilités exemples

**Charger les fixtures :**

```bash
php bin/console doctrine:fixtures:load
```

Confirmez avec `yes` quand demandé (cela vide la base avant de la remplir).

**Comptes de test créés :**

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Médecin | `medecin1@test.fr` | `password123` |
| Médecin | `medecin2@test.fr` | `password123` |
| Patient | `patient1@test.fr` | `password123` |
| Patient | `patient2@test.fr` | `password123` |
| Assistant | `assistant1@test.fr` | `password123` |
| Assistant | `assistant2@test.fr` | `password123` |

**Alternative : Initialiser manuellement les états**

Si vous ne souhaitez pas utiliser les fixtures, vous pouvez créer uniquement les états :

```bash
php bin/console doctrine:query:sql "INSERT INTO etat (libelle) VALUES ('demandé'), ('confirmé'), ('annulé'), ('refusé'), ('réalisé');"
```

### 6. Générer les clés JWT (pour l'API)

```bash
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
```

Ajoutez la passphrase dans `.env.local` :

```env
JWT_PASSPHRASE=votre_passphrase
```

---

## Configuration

### Variables d'environnement importantes

| Variable | Description | Valeur par défaut |
|----------|-------------|-------------------|
| `DATABASE_URL` | URL de connexion MySQL | - |
| `APP_ENV` | Environnement (`dev` ou `prod`) | `dev` |
| `APP_SECRET` | Clé secrète pour la sécurité | - |
| `JWT_PASSPHRASE` | Passphrase pour les tokens JWT | - |
| `SESSION_IDLE_TIMEOUT` | Timeout d'inactivité (secondes) | `1800` |

---

## Lancement de l'application

### Option 1 : Avec Symfony CLI (recommandé)

```bash
symfony server:start
```

L'application sera accessible à : `https://127.0.0.1:8000`

### Option 2 : Avec le serveur PHP intégré

```bash
php -S localhost:8000 -t public
```

L'application sera accessible à : `http://localhost:8000`

### Option 3 : Nuage Pédagogique

Pour accéder à l'application déployée sur le nuage pédagogique :

```
https://s5-XXXX.nuage-peda.fr/gestionRDV/public/index.php
```

---

## Utilisation

### Inscription

1. Accédez à la page d'inscription : `/register`
2. Choisissez votre type de compte :
   - **Patient** : Pour prendre des rendez-vous
   - **Médecin** : Pour gérer vos disponibilités et rendez-vous
   - **Assistant** : Pour aider un médecin à gérer ses rendez-vous
3. Remplissez le formulaire avec vos informations
4. Si vous êtes assistant, sélectionnez le médecin auquel vous serez rattaché

### Connexion

1. Accédez à la page de connexion : `/login`
2. Entrez votre email et mot de passe
3. Vous serez redirigé vers votre espace personnel

---

### Espace Patient

**Accès :** `/mes-rendez-vous`

#### Prendre un rendez-vous

1. Cliquez sur **"Nouveau rendez-vous"** ou accédez à `/rendez-vous/nouveau`
2. Sélectionnez un médecin (ou laissez "Tous les médecins")
3. Choisissez un créneau disponible parmi ceux proposés
4. Confirmez votre demande

Le rendez-vous sera créé avec l'état **"demandé"** et devra être confirmé par le médecin ou son assistant.

#### Gérer vos rendez-vous

- **Consulter** : Visualisez tous vos rendez-vous et leur état
- **Modifier** : Changez la date ou le médecin (remet l'état à "demandé")
- **Annuler** : Annulez un rendez-vous à venir

#### Filtrer les rendez-vous

Utilisez les filtres disponibles :
- Par état (demandé, confirmé, annulé, etc.)
- Par date (aujourd'hui ou date spécifique)

---

### Espace Médecin

**Accès :** `/mes-rendez-vous` et `/disponibilite`

#### Gérer vos disponibilités

1. Accédez à `/disponibilite`
2. Cliquez sur **"Nouvelle disponibilité"**
3. Configurez :
   - **Jour de la semaine** : Lundi à Dimanche
   - **Heure de début** : Ex: 09:00
   - **Heure de fin** : Ex: 12:00
   - **Durée des RDV** : 30, 45, 60 ou 90 minutes
4. Activez ou désactivez vos disponibilités selon vos besoins

Les créneaux sont générés automatiquement à partir de vos disponibilités récurrentes.

#### Gérer les rendez-vous

Depuis `/mes-rendez-vous`, vous pouvez :
- **Confirmer** un rendez-vous demandé
- **Refuser** un rendez-vous
- **Marquer comme réalisé** un rendez-vous passé

Les rendez-vous confirmés passés sont automatiquement marqués comme "réalisés".

---

### Espace Assistant

**Accès :** `/mes-rendez-vous`

En tant qu'assistant, vous gérez les rendez-vous du médecin auquel vous êtes rattaché.

#### Actions disponibles

- **Confirmer** les demandes de rendez-vous
- **Refuser** les demandes
- **Marquer comme réalisé** les rendez-vous passés
- **Consulter** l'ensemble des rendez-vous du médecin

Vous avez les mêmes filtres que le médecin (par état, par date).

---

## Fonctionnalités

### Gestion des rendez-vous

| Fonctionnalité | Patient | Médecin | Assistant |
|----------------|---------|---------|-----------|
| Créer un RDV | Oui | - | - |
| Modifier un RDV | Oui (ses RDV) | - | - |
| Annuler un RDV | Oui | Oui | - |
| Confirmer un RDV | - | Oui | Oui |
| Refuser un RDV | - | Oui | Oui |
| Marquer réalisé | - | Oui | Oui |

### États des rendez-vous

```
demandé --> confirmé --> réalisé
    |           |
    v           v
 refusé      annulé
```

| État | Description |
|------|-------------|
| **Demandé** | RDV créé par le patient, en attente de validation |
| **Confirmé** | RDV validé par le médecin/assistant |
| **Annulé** | RDV annulé par le patient ou le médecin |
| **Refusé** | RDV refusé par le médecin/assistant |
| **Réalisé** | RDV passé et effectué |

### Autres fonctionnalités

- **Thème clair/sombre** : Basculez entre les thèmes via le bouton dans l'en-tête
- **Navigation fluide** : Utilisation de Turbo pour des transitions rapides
- **Déconnexion automatique** : Après 30 minutes d'inactivité
- **Génération intelligente des créneaux** : Exclusion automatique des créneaux déjà réservés

---

## Structure du projet

```
gestionRDV/
├── config/                 # Configuration Symfony
│   ├── packages/           # Configuration des bundles
│   └── jwt/                # Clés JWT
├── migrations/             # Migrations Doctrine
├── public/                 # Point d'entrée web
├── src/
│   ├── Controller/         # Contrôleurs
│   │   ├── AccueilController.php
│   │   ├── AssistantController.php
│   │   ├── DisponibiliteController.php
│   │   ├── MedecinController.php
│   │   ├── MesRendezVousController.php
│   │   ├── RegistrationController.php
│   │   ├── RendezVousController.php
│   │   └── SecurityController.php
│   ├── Entity/             # Entités Doctrine
│   │   ├── User.php        # Classe abstraite
│   │   ├── Patient.php
│   │   ├── Medecin.php
│   │   ├── Assistant.php
│   │   ├── RendezVous.php
│   │   ├── Etat.php
│   │   └── DisponibiliteRecurrente.php
│   ├── Form/               # Formulaires
│   ├── Repository/         # Repositories
│   └── Service/            # Services métier
├── templates/              # Templates Twig
├── .env                    # Variables d'environnement
├── composer.json           # Dépendances PHP
└── README.md               # Ce fichier
```

---

## API REST

L'application expose une API REST sécurisée par JWT.

### Authentification

```bash
# Obtenir un token
curl -X POST https://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"username": "email@exemple.com", "password": "motdepasse"}'
```

Réponse :
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

### Utiliser l'API

```bash
curl https://localhost:8000/api/endpoint \
  -H "Authorization: Bearer VOTRE_TOKEN"
```

---

## Commandes utiles

### Développement

```bash
# Vider le cache
php bin/console cache:clear

# Lancer le serveur
symfony server:start

# Vérifier les routes
php bin/console debug:router
```

### Base de données

```bash
# Créer une migration après modification d'entité
php bin/console make:migration

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# Voir le statut des migrations
php bin/console doctrine:migrations:status

# Valider le schéma
php bin/console doctrine:schema:validate
```

### Workflow Git (équipe)

```bash
# Avant de push
php bin/console make:migration
php bin/console doctrine:migrations:migrate

# Après un pull
php bin/console doctrine:migrations:migrate
```

---

## Dépannage

### Erreur "Access Denied"

- Vérifiez que vous êtes connecté
- Vérifiez que votre rôle vous permet d'accéder à cette page

### Pas de créneaux disponibles

- Vérifiez que le médecin a configuré ses disponibilités dans `/disponibilite`
- Vérifiez que les disponibilités sont actives

### Erreur de base de données

```bash
# Vérifier la connexion
php bin/console doctrine:database:create --if-not-exists

# Vérifier les migrations
php bin/console doctrine:migrations:status

# Forcer la migration
php bin/console doctrine:migrations:migrate --no-interaction
```

### Erreur JWT

```bash
# Vérifier que les clés existent
ls config/jwt/

# Regénérer les clés si nécessaire
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
```

### Page blanche ou erreur 500

```bash
# Vider le cache
php bin/console cache:clear

# Vérifier les logs
tail -f var/log/dev.log
```

---

## Technologies utilisées

- **Framework** : Symfony 6.4
- **ORM** : Doctrine 3.5
- **Base de données** : MySQL / MariaDB
- **Authentification** : Symfony Security + JWT
- **Frontend** : Twig, Turbo, Flatpickr
- **API** : API Platform 4.2

---

## Licence

Projet développé dans le cadre du BTS SIO option SLAM.