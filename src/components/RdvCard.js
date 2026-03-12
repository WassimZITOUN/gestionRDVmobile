import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';
import EtatBadge from './EtatBadge';

function formatDate(isoString) {
  const date = new Date(isoString);
  return date.toLocaleDateString('fr-FR', {
    weekday: 'short',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });
}

function formatHeure(isoString) {
  const date = new Date(isoString);
  return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}

export default function RdvCard({ rdv, onPress }) {
  return (
    <TouchableOpacity style={styles.card} onPress={onPress} activeOpacity={0.8}>
      <View style={styles.dateBadge}>
        <Text style={styles.dateDay}>{new Date(rdv.debut).getDate()}</Text>
        <Text style={styles.dateMonth}>
          {new Date(rdv.debut).toLocaleDateString('fr-FR', { month: 'short' })}
        </Text>
      </View>

      <View style={styles.info}>
        <Text style={styles.heure}>
          {formatHeure(rdv.debut)} – {formatHeure(rdv.fin)}
        </Text>
        <Text style={styles.medecin}>
          Dr {rdv.medecin.prenom} {rdv.medecin.nom}
        </Text>
        <Text style={styles.dateText}>{formatDate(rdv.debut)}</Text>
        <EtatBadge etat={rdv.etat} />
      </View>
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  card: {
    flexDirection: 'row',
    backgroundColor: '#fff',
    borderRadius: 12,
    marginHorizontal: 16,
    marginVertical: 6,
    padding: 14,
    shadowColor: '#000',
    shadowOpacity: 0.07,
    shadowRadius: 6,
    elevation: 3,
  },
  dateBadge: {
    width: 52,
    height: 52,
    borderRadius: 10,
    backgroundColor: '#1a73e8',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 14,
  },
  dateDay: {
    color: '#fff',
    fontSize: 20,
    fontWeight: 'bold',
    lineHeight: 22,
  },
  dateMonth: {
    color: '#cce0ff',
    fontSize: 11,
    textTransform: 'uppercase',
  },
  info: {
    flex: 1,
    gap: 3,
  },
  heure: {
    fontSize: 15,
    fontWeight: '700',
    color: '#1a1a1a',
  },
  medecin: {
    fontSize: 14,
    color: '#444',
  },
  dateText: {
    fontSize: 12,
    color: '#888',
    marginBottom: 4,
  },
});
