const express = require('express');
const db = require('../db');
const auth = require('../middleware/auth');
const { getProchainsCréneaux, isCreneauDisponible } = require('../services/creneaux');

const router = express.Router();

router.use(auth);

/**
 * GET /api/medecins
 * Liste de tous les médecins
 */
router.get('/medecins', async (req, res) => {
  try {
    const [rows] = await db.query(
      `SELECT m.id, u.nom, u.prenom
       FROM medecin m
       JOIN user u ON u.id = m.id
       ORDER BY u.nom ASC, u.prenom ASC`
    );
    return res.json(rows);
  } catch (err) {
    console.error('[GET /api/medecins]', err);
    return res.status(500).json({ error: 'Erreur serveur.' });
  }
});

/**
 * GET /api/medecins/:id/creneaux
 * Créneaux disponibles d'un médecin
 * Query params: ?jours=30&max=50
 */
router.get('/medecins/:id/creneaux', async (req, res) => {
  const medecinId = parseInt(req.params.id);
  const nombreJours = parseInt(req.query.jours ?? 30);
  const maxCreneaux = parseInt(req.query.max ?? 50);

  try {
    // Vérifier que le médecin existe
    const [rows] = await db.query(
      'SELECT m.id FROM medecin m JOIN user u ON u.id = m.id WHERE m.id = ?',
      [medecinId]
    );

    if (rows.length === 0) {
      return res.status(404).json({ error: 'Médecin introuvable.' });
    }

    const creneaux = await getProchainsCréneaux(db, medecinId, nombreJours, maxCreneaux);
    return res.json(creneaux);
  } catch (err) {
    console.error('[GET /api/medecins/:id/creneaux]', err);
    return res.status(500).json({ error: 'Erreur serveur.' });
  }
});

/**
 * POST /api/rendez-vous/nouveau
 * Demande de rendez-vous par le patient connecté
 * Body JSON: { medecin_id, datetime_debut }
 */
router.post('/rendez-vous/nouveau', async (req, res) => {
  const patientId = req.user.id;
  const { medecin_id, datetime_debut } = req.body;

  if (!medecin_id || !datetime_debut) {
    return res.status(400).json({ error: 'Champs requis : medecin_id, datetime_debut.' });
  }

  const medecinId = parseInt(medecin_id);

  // Vérifier que le médecin existe
  const [medecinRows] = await db.query(
    'SELECT m.id FROM medecin m WHERE m.id = ?',
    [medecinId]
  );

  if (medecinRows.length === 0) {
    return res.status(404).json({ error: 'Médecin introuvable.' });
  }

  // Parser la date de début
  const debut = new Date(datetime_debut);
  if (isNaN(debut.getTime())) {
    return res.status(400).json({ error: 'Format de date invalide. Utiliser ISO 8601 : Y-m-d\\TH:i:s' });
  }

  try {
    // Récupère la durée du créneau depuis les disponibilités
    const jourSemaine = ((debut.getDay() + 6) % 7) + 1;
    const heureDebutStr = `${String(debut.getHours()).padStart(2, '0')}:${String(debut.getMinutes()).padStart(2, '0')}:00`;

    const [dispoRows] = await db.query(
      `SELECT duree_rdv_minutes FROM disponibilite_recurrente
       WHERE medecin_id = ? AND jour_semaine = ? AND actif = 1
         AND heure_debut <= ? AND heure_fin > ?`,
      [medecinId, jourSemaine, heureDebutStr, heureDebutStr]
    );

    const dureeMin = dispoRows.length > 0 ? dispoRows[0].duree_rdv_minutes : 60;
    const fin = new Date(debut);
    fin.setMinutes(fin.getMinutes() + dureeMin);

    // Vérifier la disponibilité du créneau
    const disponible = await isCreneauDisponible(db, medecinId, debut, fin);
    if (!disponible) {
      return res.status(409).json({ error: 'Ce créneau n\'est plus disponible.' });
    }

    // Formater pour MySQL DATETIME
    const toMysqlDatetime = (d) =>
      d.toISOString().slice(0, 19).replace('T', ' ');

    // Créer le RDV (etat_id = 1 = demandé)
    const [result] = await db.query(
      `INSERT INTO rendez_vous (patient_id, medecin_id, debut, fin, etat_id)
       VALUES (?, ?, ?, ?, 1)`,
      [patientId, medecinId, toMysqlDatetime(debut), toMysqlDatetime(fin)]
    );

    return res.status(201).json({
      message: 'Votre demande de rendez-vous a été envoyée avec succès.',
      rdv: {
        id: result.insertId,
        debut: debut.toISOString().slice(0, 19),
        fin: fin.toISOString().slice(0, 19),
        etat: { id: 1, libelle: 'demandé' },
      },
    });
  } catch (err) {
    console.error('[POST /api/rendez-vous/nouveau]', err);
    return res.status(500).json({ error: 'Erreur serveur.' });
  }
});

module.exports = router;
