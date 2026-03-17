export const lightColors = {
  primary: '#1565C0',
  primaryLight: '#E3F2FD',
  primaryDark: '#003c8f',
  secondary: '#00897B',
  secondaryLight: '#E0F2F1',

  background: '#F5F7FA',
  surface: '#FFFFFF',
  surfaceVariant: '#F0F4F8',
  border: '#E2E8F0',

  text: {
    primary: '#1A1A2E',
    secondary: '#636E7B',
    disabled: '#B0B8C1',
    inverse: '#FFFFFF',
  },

  status: {
    pending:   { bg: '#FFF8E1', text: '#F57F17', dot: '#F9A825' },   // Demandé
    confirmed: { bg: '#E8F5E9', text: '#2E7D32', dot: '#43A047' },   // Confirmé
    cancelled: { bg: '#FFEBEE', text: '#C62828', dot: '#E53935' },   // Annulé
    refused:   { bg: '#FCE4EC', text: '#AD1457', dot: '#E91E63' },   // Refusé
    completed: { bg: '#E3F2FD', text: '#1565C0', dot: '#1E88E5' },   // Réalisé
  },

  error: '#D32F2F',
  errorLight: '#FFEBEE',
  success: '#2E7D32',
  successLight: '#E8F5E9',
  warning: '#E65100',
  warningLight: '#FFF3E0',

  shadow: '#000000',
};

export const darkColors = {
  primary: '#90CAF9',
  primaryLight: '#1A2A3A',
  primaryDark: '#BBDEFB',
  secondary: '#80CBC4',
  secondaryLight: '#1A2B2A',

  background: '#0F1117',
  surface: '#1A1D24',
  surfaceVariant: '#232830',
  border: '#2A3040',

  text: {
    primary: '#E8EAF0',
    secondary: '#9BA3AF',
    disabled: '#4A5568',
    inverse: '#1A1A2E',
  },

  status: {
    pending:   { bg: '#2C2400', text: '#FFD54F', dot: '#FFC107' },
    confirmed: { bg: '#002200', text: '#81C784', dot: '#4CAF50' },
    cancelled: { bg: '#2C0000', text: '#EF9A9A', dot: '#F44336' },
    refused:   { bg: '#2C001A', text: '#F48FB1', dot: '#E91E63' },
    completed: { bg: '#001A2C', text: '#90CAF9', dot: '#42A5F5' },
  },

  error: '#EF9A9A',
  errorLight: '#2C0000',
  success: '#81C784',
  successLight: '#002200',
  warning: '#FFCC80',
  warningLight: '#2C1500',

  shadow: '#000000',
};

// Map etat.id → status key
export const ETAT_STATUS_MAP = {
  1: 'pending',
  2: 'confirmed',
  3: 'cancelled',
  4: 'refused',
  5: 'completed',
};

export const ETAT_LABELS = {
  1: 'Demandé',
  2: 'Confirmé',
  3: 'Annulé',
  4: 'Refusé',
  5: 'Réalisé',
};
