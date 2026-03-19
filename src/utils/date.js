/**
 * Formate une date ISO en "lun. 18 mars 2026"
 */
export function formatDate(isoString) {
  const date = new Date(isoString);
  return date.toLocaleDateString('fr-FR', {
    weekday: 'short',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });
}

/**
 * Formate une date ISO en "lundi 18 mars 2026"
 */
export function formatDateComplete(isoString) {
  const date = new Date(isoString);
  return date.toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });
}

/**
 * Formate une date ISO en "14:30"
 */
export function formatHeure(isoString) {
  const date = new Date(isoString);
  return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}

/**
 * Retourne le jour du mois (ex: 18)
 */
export function getDay(isoString) {
  return new Date(isoString).getDate();
}

/**
 * Retourne le mois court en majuscules (ex: "MARS")
 */
export function getMonthShort(isoString) {
  return new Date(isoString)
    .toLocaleDateString('fr-FR', { month: 'short' })
    .toUpperCase();
}

/**
 * Retourne "dans X jours", "aujourd'hui", "hier", "il y a X jours"
 */
export function formatRelative(isoString) {
  const date = new Date(isoString);
  const now = new Date();
  const diffMs = date - now;
  const diffDays = Math.round(diffMs / (1000 * 60 * 60 * 24));

  if (diffDays === 0) return "Aujourd'hui";
  if (diffDays === 1) return 'Demain';
  if (diffDays === -1) return 'Hier';
  if (diffDays > 1) return `Dans ${diffDays} jours`;
  return `Il y a ${Math.abs(diffDays)} jours`;
}
