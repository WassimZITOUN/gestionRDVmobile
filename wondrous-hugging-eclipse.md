# Plan — Double authentification (LDAP + classique)

## Contexte

Actuellement les utilisateurs s'authentifient via email + mot de passe stocké en BDD (bcrypt).
L'objectif est de supporter **deux méthodes d'authentification en parallèle** :
1. Authentification **classique** (email + bcrypt) — les comptes existants restent valides
2. Authentification **LDAP** (`uid` + mot de passe vérifié sur `192.168.1.106`)

**Décisions actées :**
- Détection automatique par format d'identifiant :
  - Identifiant contient `@` → authentification classique (bcrypt)
  - Identifiant sans `@` → authentification LDAP
- Rôles (patient/médecin/assistant) : gérés manuellement en BDD
- Double authentification : les comptes existants (email + bcrypt) restent valides et fonctionnels
- Branche dédiée : `feat/LDAP` créée depuis `MAJsiteWassim`

## Config LDAP

| Paramètre | Valeur |
|-----------|--------|
| Host | `192.168.1.106` |
| Port | `389` |
| User base DN | `ou=people,dc=slam,dc=lab` |
| DN de bind | `uid={uid},ou=people,dc=slam,dc=lab` |

## Architecture après implémentation

```
Login (identifiant + password)
      │
      ├─ identifiant contient "@"
      │  ↓
      │  Auth classique : bcrypt BDD
      │  Email trouvé ? → vérifier password
      │
      └─ identifiant sans "@" (uid)
         ↓
         LDAP bind (192.168.1.106)  ← vérifie le mot de passe
         │ succès
         ↓
         BDD MySQL  ← charge l'utilisateur par uid
                      uid trouvé ? → charger rôle (patient/médecin/assistant)
                      sinon → erreur "Compte non configuré (LDAP)"
      │
      ▼
Session (Symfony web) / JWT (app mobile)
```

---

## Phase 0 — Créer la branche (gestionRDV)

```bash
cd gestionRDV
git checkout MAJsiteWassim
git checkout -b feat/LDAP
```

---

## Phase 1 — Symfony : adapter l'entité User

**Fichiers critiques :**
- `gestionRDV/src/Entity/User.php`

### 1.1 Modifier l'entité

- Ajouter le champ `uid` (varchar 180, unique, **nullable**) → identifiant LDAP optionnel
  - Les utilisateurs classiques ne remplissent pas ce champ
  - Les utilisateurs LDAP remplissent ce champ et utilisent leur uid LDAP
- `getUserIdentifier()` reste `$this->email` pour la compatibilité existante
- **`password` reste non-nullable** dans la plupart des cas
  - Les utilisateurs classiques ont un bcrypt
  - Les utilisateurs LDAP peuvent avoir `null` ou un bcrypt en fallback
- `email` reste non-nullable (identifiant principal pour la méthode classique)

### 1.2 Créer la migration Doctrine

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

Résultat en BDD :
```sql
ALTER TABLE user ADD uid VARCHAR(180) UNIQUE NULL;
-- email et password restent inchangés (non-nullable)
```

---

## Phase 2 — Symfony : installer le composant LDAP

```bash
composer require symfony/ldap
```

> Nécessite l'extension PHP `php-ldap` : `sudo apt install php-ldap` ou activer dans php.ini

---

## Phase 3 — Symfony : Authenticator hybride

**Fichier à créer :** `gestionRDV/src/Security/DualAuthAuthenticator.php`

Logique (détection automatique par format d'identifiant) :
1. Lire l'identifiant (`_username`) + mot de passe depuis le formulaire de login
2. **Si l'identifiant contient `@`** (adresse email) :
   - Chercher l'utilisateur par `email`
   - Vérifier le `password` avec bcrypt (comportement existant)
3. **Sinon** (format uid) :
   - Tenter un bind LDAP sur `uid={identifiant},ou=people,dc=slam,dc=lab` avec le mot de passe
   - Si bind réussi → charger l'utilisateur depuis la BDD par `uid`
   - Si l'utilisateur n'existe pas en BDD → erreur "Compte non configuré (LDAP)"
4. Retourner le `Passport` Symfony avec les credentials appropriés

**Fichier à modifier :** `gestionRDV/config/packages/security.yaml`

```yaml
services:
    app.ldap:
        class: Symfony\Component\Ldap\Ldap
        arguments: ['@app.ldap.adapter']
    app.ldap.adapter:
        class: Symfony\Component\Ldap\Adapter\ExtLdap\Adapter
        arguments:
            - host: 192.168.1.106
              port: 389
              version: 3

security:
    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email          # ← reste email pour compatibilité

    firewalls:
        main:
            custom_authenticators:
                - App\Security\DualAuthAuthenticator
            logout:
                path: app_logout
```

---

## Phase 4 — Symfony : adapter le formulaire de login

**Fichier à modifier :** `gestionRDV/templates/security/login.html.twig`
- Changer le label du champ `_username` de "Email" en "Identifiant (email ou uid)"
- Pas besoin de deux champs séparés — la détection se fait en backend

**Fichier à modifier :** `gestionRDV/src/Controller/SecurityController.php`
- Aucun changement majeur : le "last username" reste inchangé (affiche l'identifiant saisi)

---

## Phase 5 — Symfony : API JWT hybride

Le firewall `/api/login` utilise `json_login` + Lexik JWT.
Créer un authenticator JSON avec double authentification :

**Fichier à créer :** `gestionRDV/src/Security/DualApiAuthenticator.php`

Logique (même logique que `DualAuthAuthenticator`, mais pour JSON) :
1. Lit `identifier` + `password` depuis le JSON body (ou `email`/`uid` séparé selon le format choisi)
2. Détection par format :
   - Si contient `@` → vérifier bcrypt
   - Sinon → bind LDAP
3. Charge le user depuis BDD (par `email` ou `uid` selon la méthode)
4. Retourne Passport → Lexik JWT génère le token

---

## Phase 6 — Node.js : authentification hybride (mobile)

**Package à installer :**
```bash
cd gestion-rdv-mobile/server && npm install ldapjs
```

**Fichier à créer :** `server/services/ldap.js`

```javascript
// bindUser(uid, password)
// → bind sur uid={uid},ou=people,dc=slam,dc=lab
// → retourne true si succès, false sinon
```

**Fichier à modifier :** `server/routes/login.js`

Adapter la logique selon le format d'identifiant reçu :
```javascript
const identifier = req.body.email || req.body.identifier;
const password = req.body.password;

if (identifier.includes('@')) {
  // Authentification classique : email + bcrypt
  // 1. SELECT * FROM user JOIN patient WHERE email = ?
  // 2. Vérifier bcrypt (logique existante)
  // 3. Si OK → JWT retourné
} else {
  // Authentification LDAP : uid
  // 1. ldap.bindUser(identifier, password)
  // 2. Si OK → SELECT * FROM user JOIN patient WHERE uid = ?
  // 3. Si patient trouvé → JWT retourné
  // 4. Sinon → erreur "Compte non configuré (LDAP)"
}
```

**Fichier à modifier :** `server/.env`
```
LDAP_URL=ldap://192.168.1.106:389
LDAP_BASE_DN=ou=people,dc=slam,dc=lab
```

---

## Phase 7 — Jeu d'essai LDAP + BDD

### 7.1 Credentials admin LDAP

| Paramètre | Valeur |
|-----------|--------|
| Admin DN | `cn=admin,dc=slam,dc=lab` |
| Admin password | `admin` |

### 7.2 Créer les utilisateurs de test dans le LDAP

Utiliser l'admin bind pour créer des entrées LDAP de test (via `ldapadd` ou fichier `.ldif`) :

**Fichier à créer :** `gestionRDV/ldap/jeu_essai.ldif`

```ldif
# Patient de test
dn: uid=jean.dupont,ou=people,dc=slam,dc=lab
objectClass: inetOrgPerson
uid: jean.dupont
cn: Jean Dupont
sn: Dupont
givenName: Jean
userPassword: patient123

# Médecin de test
dn: uid=dr.martin,ou=people,dc=slam,dc=lab
objectClass: inetOrgPerson
uid: dr.martin
cn: Dr Martin
sn: Martin
givenName: Pierre
userPassword: medecin123

# Assistant de test
dn: uid=aide.leroy,ou=people,dc=slam,dc=lab
objectClass: inetOrgPerson
uid: aide.leroy
cn: Aide Leroy
sn: Leroy
givenName: Sophie
userPassword: assistant123
```

**Commande d'import :**
```bash
ldapadd -x -H ldap://192.168.1.106 \
  -D "cn=admin,dc=slam,dc=lab" -w admin \
  -f ldap/jeu_essai.ldif
```

### 7.3 Créer les enregistrements BDD correspondants

Adapter `gestionRDV/src/DataFixtures/AppFixtures.php` :
- Créer des users avec **email + password bcrypt** (utilisateurs classiques existants)
- Créer des users avec **uid** (utilisateurs LDAP)
- Relier aux tables `patient`, `medecin`, `assistant`

```php
// Utilisateur classique : email + password bcrypt
$patientClassique = new Patient();
$patientClassique->setEmail('patient1@test.fr');
$patientClassique->setPassword(hash_password('patient123')); // bcrypt
$patientClassique->setNom('Dupont');
$patientClassique->setPrenom('Jean');
// uid non défini (null)

// Utilisateur LDAP : uid (pas de password bcrypt, ou optionnel)
$patientLdap = new Patient();
$patientLdap->setEmail('jean.dupont@slam.lab'); // optionnel, pour info
$patientLdap->setUid('jean.dupont'); // doit correspondre au uid LDAP
$patientLdap->setNom('Dupont');
$patientLdap->setPrenom('Jean');
// setPassword() optionnel pour LDAP

// Médecin LDAP
$medecin = new Medecin();
$medecin->setUid('dr.martin');
// ...
```

### 7.4 Optionnel : récupérer nom/prénom depuis LDAP automatiquement

Dans le `DualAuthAuthenticator`, après un bind LDAP réussi, faire une recherche admin pour récupérer `givenName` et `sn` et les stocker/mettre à jour en BDD si le user est nouveau (just-in-time provisioning partiel). Ceci permet de pré-remplir les informations utilisateur depuis LDAP.

---

## Fichiers à créer/modifier

| Action | Fichier | Détails |
|--------|---------|---------|
| Créer branche | `gestionRDV` → `feat/LDAP` depuis `MAJsiteWassim` | |
| Modifier | `gestionRDV/src/Entity/User.php` | Ajouter champ `uid` (nullable) |
| Créer migration | `gestionRDV/migrations/VersionXXX.php` | ADD uid VARCHAR(180) UNIQUE NULL |
| Créer | `gestionRDV/src/Security/DualAuthAuthenticator.php` | Authentification hybride (email ou uid) |
| Créer | `gestionRDV/src/Security/DualApiAuthenticator.php` | Idem, pour API JSON |
| Modifier | `gestionRDV/config/packages/security.yaml` | Ajouter LDAP service + authenticator |
| Modifier | `gestionRDV/templates/security/login.html.twig` | Label "Identifiant (email ou uid)" |
| Modifier | `gestionRDV/src/Controller/SecurityController.php` | Aucun changement majeur |
| Modifier | `gestionRDV/src/DataFixtures/AppFixtures.php` | Ajouter users LDAP + garder users classiques |
| Créer | `server/services/ldap.js` | Service LDAP bindUser() |
| Modifier | `server/routes/login.js` | Logique hybride email vs uid |
| Modifier | `server/.env` | LDAP_URL + LDAP_BASE_DN |
| Modifier | `src/screens/LoginScreen.js` | Label "Identifiant (email ou uid)" |
| Modifier | `src/context/AuthContext.js` | Adapter champ envoyé au serveur |

---

## Tests

### Authentification classique (email + bcrypt)

1. Login Symfony avec `patient1@test.fr` + mot de passe bcrypt valide → session ouverte ✓ (régression)
2. Login Symfony avec email invalide → erreur auth
3. Login Symfony avec email valide + mauvais mot de passe → erreur auth
4. `POST /api/login { email: "patient1@test.fr", password: "***" }` → JWT retourné
5. App mobile : login avec email existant → JWT retourné + accès aux RDV

### Authentification LDAP (uid + LDAP bind)

6. Login Symfony avec `jean.dupont` (uid LDAP) + mot de passe LDAP valide → session ouverte
7. Login Symfony avec uid invalide (absent en LDAP) → erreur auth
8. Login Symfony avec uid valide LDAP mais absent en BDD → erreur "Compte non configuré (LDAP)"
9. Login Symfony avec uid LDAP + mauvais mot de passe → erreur auth
10. `POST /api/login { identifier: "jean.dupont", password: "***" }` → JWT retourné
11. App mobile : login avec uid LDAP → JWT retourné + accès aux RDV

### Régression

12. Migration Doctrine OK → champ `uid` ajouté en BDD (nullable)
13. Comptes existants (email/bcrypt) conservent leur accès

---

## Points d'attention

- Extension PHP `php8.x-ldap` doit être activée (pour les appels LDAP Symfony)
- Le serveur LDAP `192.168.1.106` est accessible uniquement depuis le réseau école
- Les users LDAP en BDD doivent avoir un `uid` identique à leur uid LDAP
- Le champ `email` reste l'identifiant Symfony (`getUserIdentifier()`) — pas de changement
- **Compatibilité** : les utilisateurs classiques (email/bcrypt) continuent de fonctionner sans modification
- Détection automatique par format : `@` = email/bcrypt, pas de `@` = uid/LDAP
- Si le serveur LDAP est indisponible, les utilisateurs LDAP ne peuvent pas se connecter (pas de fallback à bcrypt)
  - Optionnel : ajouter un fallback bcrypt pour les utilisateurs LDAP qui ont aussi un mot de passe stocké
