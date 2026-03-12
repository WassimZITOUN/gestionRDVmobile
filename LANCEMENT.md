# Guide de Lancement : Application Mobile RDVGestion

Ce guide explique comment lancer l'application mobile pour le développement.

## Architecture de Connexion

L'application mobile (tournant sur un téléphone via Expo Go) se connecte à un serveur API (Node.js) qui lui-même communique avec la base de données MySQL.

Pour que cela fonctionne, le téléphone et le Mac Mini (qui héberge le serveur) doivent pouvoir communiquer directement.

```
Téléphone (Expo Go)
      │
      ├─► Wi-Fi / VPN (WireGuard)
      │
      ▼
Mac Mini — server/index.js (Node.js sur le port 3000)
      │
      └─► Base de données MySQL (gestionrdv)
```

---

## Prérequis

1.  **Réseau :** Le téléphone et le Mac Mini doivent être sur le **même réseau**.
    *   Soit connectés au même Wi-Fi.
    *   Soit tous les deux connectés au VPN WireGuard.
2.  **Services :**
    *   Le serveur MySQL doit être démarré sur le Mac Mini.
    *   Le serveur API Node.js doit être lancé.
3.  **Outils :**
    *   Node.js doit être installé sur le Mac Mini.
    *   L'application **Expo Go** doit être installée sur le téléphone.

---

## Lancement en 4 Étapes

### Étape 1 : Trouver l'IP du Mac Mini

Le mobile a besoin de l'adresse IP du Mac Mini pour s'y connecter.

Sur le Mac Mini, ouvrez un terminal et lancez :
```bash
ifconfig | grep "inet 192"
```
Vous devriez voir une ligne comme `inet 192.168.1.150...`. L'adresse IP à utiliser est `192.168.1.150`.

### Étape 2 : Lancer le Serveur API

Dans un premier terminal, naviguez vers le dossier du serveur et lancez-le :
```bash
cd /Users/wassim/Documents/Etudes/BTS/Projet/RDVGestion/gestion-rdv-mobile/server
node index.js
```
Le serveur est maintenant en écoute sur le port 3000. Laissez ce terminal ouvert.

### Étape 3 : Configurer l'IP dans le Code

Ouvrez le fichier `gestion-rdv-mobile/src/api/client.js`. Assurez-vous que la variable `API_BASE_URL` contient la bonne IP :

```javascript
// Doit correspondre à l'IP du Mac Mini sur le réseau local/VPN
export const API_BASE_URL = 'http://192.168.1.150:3000';
```
*(Cette étape est déjà faite, mais à vérifier si l'IP du Mac Mini change.)*

### Étape 4 : Lancer l'Application Mobile

Dans un **second terminal**, allez à la racine du projet mobile et lancez Expo :
```bash
cd /Users/wassim/Documents/Etudes/BTS/Projet/RDVGestion/gestion-rdv-mobile
npx expo start
```
Un QR code va s'afficher. Scannez-le avec l'application Expo Go sur votre téléphone. L'application se lancera et se connectera au serveur.

---

## Dépannage

- **"Network request failed" :**
    1.  Vérifiez que le téléphone et le Mac Mini sont bien sur le même réseau (Wi-Fi ou VPN).
    2.  Assurez-vous que l'IP dans `client.js` est correcte.
    3.  Vérifiez que le serveur Node (`node index.js`) est bien démarré.
    4.  Assurez-vous qu'aucun pare-feu sur le Mac Mini ne bloque le port `3000`.

- **L'application ne se charge pas après le scan du QR code :**
    *   Cela indique un problème de connexion entre le téléphone et le "Metro Bundler" d'Expo. La cause est la même : les appareils ne sont pas sur le même réseau ou un pare-feu bloque la connexion (sur le port `8081` cette fois).
