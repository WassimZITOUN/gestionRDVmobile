/**
 * Réplication JavaScript de CreneauDisponibiliteService.php (Symfony)
 * et DisponibiliteRecurrente::genererCreneaux()
 */

/**
 * Convertit "HH:MM:SS" MySQL TIME en minutes depuis minuit
 */
function timeToMinutes(timeStr) {
  const [h, m] = timeStr.split(':').map(Number);
  return h * 60 + m;
}

/**
 * Convertit des minutes depuis minuit en "HH:MM"
 */
function minutesToTime(minutes) {
  const h = String(Math.floor(minutes / 60)).padStart(2, '0');
  const m = String(minutes % 60).padStart(2, '0');
  return `${h}:${m}`;
}

/**
 * Équivalent de DisponibiliteRecurrente::genererCreneaux()
 * Génère les créneaux horaires d'une disponibilité
 *
 * @param {string} heureDebut  "HH:MM:SS"
 * @param {string} heureFin    "HH:MM:SS"
 * @param {number} dureeMin    durée en minutes
 * @returns {string[]} ["09:00", "10:00", ...]
 */
function genererCreneaux(heureDebut, heureFin, dureeMin) {
  const creneaux = [];
  const debut = timeToMinutes(heureDebut);
  const fin = timeToMinutes(heureFin);
  let cursor = debut;

  while (cursor + dureeMin <= fin) {
    creneaux.push(minutesToTime(cursor));
    cursor += dureeMin;
  }

  return creneaux;
}

/**
 * Convertit le getDay() JS (0=Dim) en ISO-8601 (1=Lun..7=Dim)
 */
function jsToIsoDay(jsDay) {
  return ((jsDay + 6) % 7) + 1;
}

/**
 * Formate une date en label français : "Lundi 15 mars à 09:00"
 */
function formatLabel(date) {
  const jours = ['', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
  const mois = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
    'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
  const jourNom = jours[jsToIsoDay(date.getDay())];
  const numJour = date.getDate();
  const numMois = mois[date.getMonth() + 1];
  const heure = `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
  return `${jourNom} ${numJour} ${numMois} à ${heure}`;
}

/**
 * Équivalent de CreneauDisponibiliteService::getProchainsCréneaux()
 *
 * @param {object} db          Pool MySQL2
 * @param {number} medecinId
 * @param {number} nombreJours Nb de jours à scanner (défaut: 30)
 * @param {number} maxCreneaux Nb max de créneaux à retourner (défaut: 50)
 * @returns {Promise<Array>}
 */
async function getProchainsCréneaux(db, medecinId, nombreJours = 30, maxCreneaux = 50) {
  const résultats = [];
  const maintenant = new Date();
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  for (let i = 0; i < nombreJours && résultats.length < maxCreneaux; i++) {
    const dateCursor = new Date(today);
    dateCursor.setDate(today.getDate() + i);

    const jourSemaine = jsToIsoDay(dateCursor.getDay());

    // Récupère les disponibilités actives pour ce jour
    const [dispos] = await db.query(
      `SELECT heure_debut, heure_fin, duree_rdv_minutes
       FROM disponibilite_recurrente
       WHERE medecin_id = ? AND jour_semaine = ? AND actif = 1`,
      [medecinId, jourSemaine]
    );

    for (const dispo of dispos) {
      const creneaux = genererCreneaux(
        dispo.heure_debut,
        dispo.heure_fin,
        dispo.duree_rdv_minutes
      );

      for (const heure of creneaux) {
        if (résultats.length >= maxCreneaux) break;

        const [h, m] = heure.split(':').map(Number);
        const debutCreneau = new Date(dateCursor);
        debutCreneau.setHours(h, m, 0, 0);

        // Ignorer les créneaux passés
        if (debutCreneau <= maintenant) continue;

        const finCreneau = new Date(debutCreneau);
        finCreneau.setMinutes(finCreneau.getMinutes() + dispo.duree_rdv_minutes);

        // Vérifier absence de conflit (hors annulé=3 et refusé=4)
        const [conflicts] = await db.query(
          `SELECT id FROM rendez_vous
           WHERE medecin_id = ?
             AND debut < ? AND fin > ?
             AND etat_id NOT IN (3, 4)`,
          [medecinId, finCreneau, debutCreneau]
        );

        if (conflicts.length === 0) {
          résultats.push({
            datetime: debutCreneau.toISOString().slice(0, 19),
            date: dateCursor.toISOString().slice(0, 10),
            heure,
            label: formatLabel(debutCreneau),
            duree: dispo.duree_rdv_minutes,
          });
        }
      }
    }
  }

  return résultats;
}

/**
 * Vérifie si un créneau spécifique est disponible
 *
 * @param {object} db
 * @param {number} medecinId
 * @param {Date}   debut
 * @param {Date}   fin
 * @returns {Promise<boolean>}
 */
async function isCreneauDisponible(db, medecinId, debut, fin) {
  if (debut <= new Date()) return false;

  // Vérifier qu'il y a une disponibilité pour ce jour/heure
  const jourSemaine = jsToIsoDay(debut.getDay());
  const heureDebut = `${String(debut.getHours()).padStart(2, '0')}:${String(debut.getMinutes()).padStart(2, '0')}:00`;
  const heureFin = `${String(fin.getHours()).padStart(2, '0')}:${String(fin.getMinutes()).padStart(2, '0')}:00`;

  const [dispos] = await db.query(
    `SELECT id FROM disponibilite_recurrente
     WHERE medecin_id = ? AND jour_semaine = ? AND actif = 1
       AND heure_debut <= ? AND heure_fin >= ?`,
    [medecinId, jourSemaine, heureDebut, heureFin]
  );

  if (dispos.length === 0) return false;

  // Vérifier absence de conflit
  const [conflicts] = await db.query(
    `SELECT id FROM rendez_vous
     WHERE medecin_id = ?
       AND debut < ? AND fin > ?
       AND etat_id NOT IN (3, 4)`,
    [medecinId, fin, debut]
  );

  return conflicts.length === 0;
}

module.exports = { getProchainsCréneaux, isCreneauDisponible };
