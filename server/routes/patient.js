const express = require('express');
const db = require('../db');
const auth = require('../middleware/auth');

const router = express.Router();

// Toutes les routes nécessitent un JWT valide
router.use(auth);

/**
 * GET /api/me
 * Retourne les informations du patient connecté
 */
router.get('/me', async (req, res) => {
  try {
    const [rows] = await db.query(
      'SELECT id, email, nom, prenom FROM user WHERE id = ?',
      [req.user.id]
    );

    if (rows.length === 0) {
      return res.status(404).json({ error: 'Utilisateur introuvable.' });
    }

    return res.json(rows[0]);
  } catch (err) {
    console.error('[GET /api/me]', err);
    return res.status(500).json({ error: 'Erreur serveur.' });
  }
});

/**
 * GET /api/mes-rendez-vous
 * Liste des RDV du patient, filtre optionnel par état (?etat=1)
 */
router.get('/mes-rendez-vous', async (req, res) => {
  const patientId = req.user.id;
  const etatId = req.query.etat ? parseInt(req.query.etat) : null;

  try {
    // Met à jour automatiquement les RDV confirmés passés → réalisé (id=5)
    await db.query(
      `UPDATE rendez_vous SET etat_id = 5
       WHERE etat_id = 2 AND fin < NOW()`,
      []
    );

    let sql = `
      SELECT
        rv.id, rv.debut, rv.fin,
        e.id   AS etat_id,   e.libelle  AS etat_libelle,
        m.id   AS medecin_id,
        um.nom AS medecin_nom, um.prenom AS medecin_prenom,
        p.id   AS patient_id,
        up.nom AS patient_nom, up.prenom AS patient_prenom
      FROM rendez_vous rv
      JOIN etat e      ON e.id = rv.etat_id
      JOIN medecin m   ON m.id = rv.medecin_id
      JOIN user um     ON um.id = m.id
      JOIN patient p   ON p.id = rv.patient_id
      JOIN user up     ON up.id = p.id
      WHERE rv.patient_id = ?
    `;
    const params = [patientId];

    if (etatId) {
      sql += ' AND rv.etat_id = ?';
      params.push(etatId);
    }

    sql += ' ORDER BY rv.debut ASC';

    const [rows] = await db.query(sql, params);
    return res.json(rows.map(serializeRdv));
  } catch (err) {
    console.error('[GET /api/mes-rendez-vous]', err);
    return res.status(500).json({ error: 'Erreur serveur.' });
  }
});

/**
 * GET /api/mes-rendez-vous/:id
 * Détail d'un RDV (vérification que le RDV appartient au patient)
 */
router.get('/mes-rendez-vous/:id', async (req, res) => {
  const patientId = req.user.id;
  const rdvId = parseInt(req.params.id);

  try {
    const [rows] = await db.query(
      `SELECT
         rv.id, rv.debut, rv.fin,
         e.id   AS etat_id,   e.libelle  AS etat_libelle,
         m.id   AS medecin_id,
         um.nom AS medecin_nom, um.prenom AS medecin_prenom,
         p.id   AS patient_id,
         up.nom AS patient_nom, up.prenom AS patient_prenom
       FROM rendez_vous rv
       JOIN etat e      ON e.id = rv.etat_id
       JOIN medecin m   ON m.id = rv.medecin_id
       JOIN user um     ON um.id = m.id
       JOIN patient p   ON p.id = rv.patient_id
       JOIN user up     ON up.id = p.id
       WHERE rv.id = ? AND rv.patient_id = ?`,
      [rdvId, patientId]
    );

    if (rows.length === 0) {
      return res.status(404).json({ error: 'Rendez-vous introuvable.' });
    }

    return res.json(serializeRdv(rows[0]));
  } catch (err) {
    console.error('[GET /api/mes-rendez-vous/:id]', err);
    return res.status(500).json({ error: 'Erreur serveur.' });
  }
});

/**
 * POST /api/mes-rendez-vous/:id/cancel
 * Annule un RDV du patient
 */
router.post('/mes-rendez-vous/:id/cancel', async (req, res) => {
  const patientId = req.user.id;
  const rdvId = parseInt(req.params.id);

  try {
    // Vérification existence et propriété
    const [rows] = await db.query(
      'SELECT id, etat_id, patient_id FROM rendez_vous WHERE id = ? AND patient_id = ?',
      [rdvId, patientId]
    );

    if (rows.length === 0) {
      return res.status(404).json({ error: 'Rendez-vous introuvable.' });
    }

    const rdv = rows[0];

    // États non annulables : 3=annulé, 4=refusé, 5=réalisé
    if ([3, 4, 5].includes(rdv.etat_id)) {
      return res.status(400).json({ error: 'Ce rendez-vous ne peut plus être annulé.' });
    }

    await db.query(
      'UPDATE rendez_vous SET etat_id = 3 WHERE id = ?',
      [rdvId]
    );

    return res.json({ message: 'Le rendez-vous a bien été annulé.' });
  } catch (err) {
    console.error('[POST /api/mes-rendez-vous/:id/cancel]', err);
    return res.status(500).json({ error: 'Erreur serveur.' });
  }
});

/**
 * Sérialise une ligne de BDD en objet JSON structuré
 */
function serializeRdv(row) {
  return {
    id: row.id,
    debut: formatDatetime(row.debut),
    fin: formatDatetime(row.fin),
    etat: { id: row.etat_id, libelle: row.etat_libelle },
    medecin: { id: row.medecin_id, nom: row.medecin_nom, prenom: row.medecin_prenom },
    patient: { id: row.patient_id, nom: row.patient_nom, prenom: row.patient_prenom },
  };
}

function formatDatetime(date) {
  if (!date) return null;
  const d = new Date(date);
  return d.toISOString().slice(0, 19);
}

module.exports = router;
