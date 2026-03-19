import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useTheme } from '../theme';
import { getDay, getMonthShort, formatHeure, formatDate } from '../utils/date';
import EtatBadge from './EtatBadge';

export default function RdvCard({ rdv, onPress }) {
  const { colors, shadows, typography, spacing } = useTheme();

  const doctorName = `Dr ${rdv.medecin.prenom} ${rdv.medecin.nom}`;
  const heures     = `${formatHeure(rdv.debut)} – ${formatHeure(rdv.fin)}`;

  return (
    <TouchableOpacity
      style={[styles.card, { backgroundColor: colors.surface }, shadows.sm]}
      onPress={onPress}
      activeOpacity={0.78}
      accessibilityRole="button"
      accessibilityLabel={`Rendez-vous avec ${doctorName} le ${formatDate(rdv.debut)} à ${formatHeure(rdv.debut)}, statut ${rdv.etat?.libelle ?? ''}`}
    >
      {/* Date badge */}
      <View style={[styles.dateBadge, { backgroundColor: colors.primary }]}>
        <Text style={[typography.h3, { color: '#fff', lineHeight: 24 }]}>
          {getDay(rdv.debut)}
        </Text>
        <Text style={[typography.small, { color: '#BBDEFB', letterSpacing: 0.5 }]}>
          {getMonthShort(rdv.debut)}
        </Text>
      </View>

      {/* Infos */}
      <View style={styles.info}>
        <Text style={[typography.bodyMedium, { color: colors.text.primary, fontWeight: '700' }]}>
          {heures}
        </Text>
        <Text style={[typography.body, { color: colors.text.secondary, marginTop: 2 }]}>
          {doctorName}
        </Text>
        <Text style={[typography.caption, { color: colors.text.disabled, marginTop: 2, marginBottom: 6 }]}>
          {formatDate(rdv.debut)}
        </Text>
        <EtatBadge etat={rdv.etat} />
      </View>

      {/* Chevron */}
      <Ionicons
        name="chevron-forward"
        size={18}
        color={colors.text.disabled}
        accessibilityElementsHidden
      />
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  card: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 12,
    marginHorizontal: 16,
    marginVertical: 6,
    padding: 14,
  },
  dateBadge: {
    width: 52,
    height: 52,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 14,
    flexShrink: 0,
  },
  info: {
    flex: 1,
  },
});
