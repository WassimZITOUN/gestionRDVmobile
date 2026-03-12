require('dotenv').config();
const express = require('express');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const db = require('../db');

const router = express.Router();

/**
 * POST /api/login
 * Body: { email, password }
 * Retourne un JWT si les identifiants sont valides et que l'utilisateur est un patient
 */
router.post('/', async (req, res) => {
  const { email, password } = req.body;

  if (!email || !password) {
    return res.status(400).json({ error: 'Email et mot de passe requis.' });
  }

  try {
    // Récupère l'utilisateur patient (type = 'patient')
    const [rows] = await db.query(
      `SELECT u.id, u.email, u.password, u.nom, u.prenom, u.type
       FROM user u
       INNER JOIN patient p ON p.id = u.id
       WHERE u.email = ? AND u.type = 'patient'`,
      [email]
    );

    if (rows.length === 0) {
      return res.status(401).json({ error: 'Identifiants invalides.' });
    }

    const user = rows[0];
    // PHP génère des hash $2y$, bcryptjs attend $2b$ — fonctionnellement identiques
    const hash = user.password.replace(/^\$2y\$/, '$2b$');
    const passwordValid = await bcrypt.compare(password, hash);

    if (!passwordValid) {
      return res.status(401).json({ error: 'Identifiants invalides.' });
    }

    const token = jwt.sign(
      { id: user.id, email: user.email, nom: user.nom, prenom: user.prenom },
      process.env.JWT_SECRET,
      { expiresIn: '24h' }
    );

    return res.json({ token });
  } catch (err) {
    console.error('[POST /api/login]', err);
    return res.status(500).json({ error: 'Erreur serveur.' });
  }
});

module.exports = router;
