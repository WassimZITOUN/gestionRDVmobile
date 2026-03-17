import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  Alert,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import apiClient from '../api/client';
import { useTheme } from '../theme';
import { ETAT_STATUS_MAP } from '../theme/colors';
import { formatDateComplete, formatHeure } from '../utils/date';
import EtatBadge from '../components/EtatBadge';
import Button from '../components/ui/Button';
import { RdvCardSkeleton } from '../components/ui/SkeletonLoader';
import EmptyState from '../components/ui/EmptyState';
import { showToast } from '../components/ui/Toast';

const ETATS_ANNULABLES = [1, 2];

const INFO_ICONS = {
  heure:   'time-outline',
  medecin: 'medical-outline',
  patient: 'person-outline',
};

export default function DetailRdvScreen({ route, navigation }) {
  const { rdvId } = route.params;
  const { colors, typography, spacing, shadows } = useTheme();

  const [rdv, setRdv]           = useState(null);
  const [loading, setLoading]   = useState(true);
  const [cancelling, setCancelling] = useState(false);
  const [error, setError]       = useState(null);

  useEffect(() => {
    apiClient
      .get(`/api/mes-rendez-vous/${rdvId}`)
      .then((r) => setRdv(r.data))
      .catch(() => setError('Impossible de charger ce rendez-vous.'))
      .finally(() => setLoading(false));
  }, [rdvId]);

  const handleCancel = () => {
    Alert.alert(
      'Annuler le rendez-vous',
      'Êtes-vous sûr de vouloir annuler ce rendez-vous ?',
      [
        { text: 'Non', style: 'cancel' },
        {
          text: 'Oui, annuler',
          style: 'destructive',
          onPress: async () => {
            setCancelling(true);
            try {
              await apiClient.post(`/api/mes-rendez-vous/${rdvId}/cancel`);
              showToast.success('Rendez-vous annulé', 'Votre rendez-vous a bien été annulé.');
              setTimeout(() => navigation.goBack(), 800);
            } catch (e) {
              Alert.alert(
                'Erreur',
                e.response?.data?.error ?? "Impossible d'annuler ce rendez-vous."
              );
            } finally {
              setCancelling(false);
            }
          },
        },
      ]
    );
  };

  if (loading) {
    return (
      <View style={[styles.container, { backgroundColor: colors.background }]}>
        <View style={{ padding: spacing.md }}>
          <RdvCardSkeleton />
          <RdvCardSkeleton />
        </View>
      </View>
    );
  }

  if (error || !rdv) {
    return (
      <View style={[styles.container, { backgroundColor: colors.background }]}>
        <EmptyState
          icon="alert-circle-outline"
          title="Rendez-vous introuvable"
          description={error ?? 'Ce rendez-vous est introuvable.'}
          actionLabel="Retour"
          onAction={() => navigation.goBack()}
        />
      </View>
    );
  }

  const canCancel    = ETATS_ANNULABLES.includes(rdv.etat.id);
  const statusKey    = ETAT_STATUS_MAP[rdv.etat.id];
  const accentColor  = statusKey ? colors.status[statusKey]?.dot : colors.border;

  return (
    <ScrollView
      style={[styles.container, { backgroundColor: colors.background }]}
      contentContainerStyle={{ padding: spacing.md }}
    >
      {/* En-tête */}
      <View
        style={[
          styles.card,
          { backgroundColor: colors.surface, borderTopColor: accentColor },
          shadows.sm,
        ]}
      >
        <Text
          style={[typography.h3, { color: colors.text.primary, textTransform: 'capitalize', marginBottom: spacing.sm }]}
          accessibilityRole="header"
        >
          {formatDateComplete(rdv.debut)}
        </Text>
        <EtatBadge etat={rdv.etat} />
      </View>

      {/* Informations */}
      <View style={[styles.card, { backgroundColor: colors.surface, marginTop: spacing.md }, shadows.sm]}>
        <InfoRow
          icon={INFO_ICONS.heure}
          label="Heure"
          value={`${formatHeure(rdv.debut)} – ${formatHeure(rdv.fin)}`}
          colors={colors}
          typography={typography}
          spacing={spacing}
        />
        <View style={[styles.divider, { backgroundColor: colors.border }]} />
        <InfoRow
          icon={INFO_ICONS.medecin}
          label="Médecin"
          value={`Dr ${rdv.medecin.prenom} ${rdv.medecin.nom}`}
          colors={colors}
          typography={typography}
          spacing={spacing}
        />
        <View style={[styles.divider, { backgroundColor: colors.border }]} />
        <InfoRow
          icon={INFO_ICONS.patient}
          label="Patient"
          value={`${rdv.patient.prenom} ${rdv.patient.nom}`}
          colors={colors}
          typography={typography}
          spacing={spacing}
        />
      </View>

      {/* Bouton annulation */}
      {canCancel && (
        <Button
          variant="danger"
          size="lg"
          loading={cancelling}
          onPress={handleCancel}
          style={{ marginTop: spacing.lg, borderRadius: 10 }}
        >
          Annuler ce rendez-vous
        </Button>
      )}
    </ScrollView>
  );
}

function InfoRow({ icon, label, value, colors, typography, spacing }) {
  return (
    <View style={[styles.infoRow, { paddingVertical: spacing.sm + 2 }]}>
      <Ionicons
        name={icon}
        size={18}
        color={colors.primary}
        style={{ marginRight: spacing.sm + 2 }}
        accessibilityElementsHidden
      />
      <Text style={[typography.caption, { color: colors.text.secondary, width: 64 }]}>
        {label}
      </Text>
      <Text
        style={[typography.bodyMedium, { color: colors.text.primary, flex: 1, textAlign: 'right' }]}
        accessibilityLabel={`${label}: ${value}`}
      >
        {value}
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  card: {
    borderRadius: 12,
    padding: 18,
    borderTopWidth: 3,
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  divider: {
    height: 1,
    marginVertical: 2,
    marginHorizontal: -18,
  },
});
