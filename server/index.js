require('dotenv').config();
const express = require('express');
const cors = require('cors');

const loginRouter = require('./routes/login');
const patientRouter = require('./routes/patient');
const creneauxRouter = require('./routes/creneaux');

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware globaux
app.use(cors());
app.use(express.json());

// Routes (login AVANT les routers protégés)
app.use('/api/login', loginRouter);
app.use('/api', patientRouter);
app.use('/api', creneauxRouter);

// Route de santé
app.get('/api/health', (req, res) => {
  res.json({ status: 'ok', timestamp: new Date().toISOString() });
});

// Démarrage
app.listen(PORT, '0.0.0.0', () => {
  console.log(`✅ Serveur RDVGestion démarré sur le port ${PORT}`);
  console.log(`   → http://localhost:${PORT}/api/health`);
});
