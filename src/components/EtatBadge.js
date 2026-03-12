import React from 'react';
import { View, Text, StyleSheet } from 'react-native';

const ETAT_COLORS = {
  1: { bg: '#fff3cd', text: '#856404', label: 'Demandé' },    // demandé
  2: { bg: '#d1e7dd', text: '#0f5132', label: 'Confirmé' },   // confirmé
  3: { bg: '#f8d7da', text: '#842029', label: 'Annulé' },     // annulé
  4: { bg: '#f8d7da', text: '#842029', label: 'Refusé' },     // refusé
  5: { bg: '#cff4fc', text: '#055160', label: 'Réalisé' },    // réalisé
};

export default function EtatBadge({ etat }) {
  const config = ETAT_COLORS[etat?.id] ?? { bg: '#e9ecef', text: '#495057', label: etat?.libelle ?? '?' };

  return (
    <View style={[styles.badge, { backgroundColor: config.bg }]}>
      <Text style={[styles.text, { color: config.text }]}>{config.label}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  badge: {
    paddingHorizontal: 10,
    paddingVertical: 3,
    borderRadius: 12,
    alignSelf: 'flex-start',
  },
  text: {
    fontSize: 12,
    fontWeight: '600',
  },
});
