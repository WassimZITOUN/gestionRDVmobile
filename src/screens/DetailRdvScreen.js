import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ActivityIndicator,
  ScrollView,
  TouchableOpacity,
  Alert,
} from 'react-native';
import apiClient from '../api/client';
import EtatBadge from '../components/EtatBadge';

function formatDateComplete(isoString) {
  const date = new Date(isoString);
  return date.toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });
}

function formatHeure(isoString) {
  const date = new Date(isoString);
  return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}

const ETATS_ANNULABLES = [1, 2]; // demandé, confirmé

export default function DetailRdvScreen({ route, navigation }) {
  const { rdvId } = route.params;
  const [rdv, setRdv] = useState(null);
  const [loading, setLoading] = useState(true);
  const [cancelling, setCancelling] = useState(false);
  const [error, setError] = useState(null);

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
              Alert.alert('Succès', 'Le rendez-vous a bien été annulé.', [
                { text: 'OK', onPress: () => navigation.goBack() },
              ]);
            } catch (e) {
              Alert.alert(
                'Erreur',
                e.response?.data?.error ?? 'Impossible d\'annuler ce rendez-vous.'
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
    return <ActivityIndicator size="large" color="#1a73e8" style={{ flex: 1, marginTop: 60 }} />;
  }

  if (error || !rdv) {
    return (
      <View style={styles.centered}>
        <Text style={styles.errorText}>{error ?? 'Rendez-vous introuvable.'}</Text>
      </View>
    );
  }

  const canCancel = ETATS_ANNULABLES.includes(rdv.etat.id);

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>
      {/* En-tête */}
      <View style={styles.header}>
        <Text style={styles.dateText}>{formatDateComplete(rdv.debut)}</Text>
        <EtatBadge etat={rdv.etat} />
      </View>

      {/* Informations */}
      <View style={styles.section}>
        <InfoRow label="Heure" value={`${formatHeure(rdv.debut)} – ${formatHeure(rdv.fin)}`} />
        <InfoRow label="Médecin" value={`Dr ${rdv.medecin.prenom} ${rdv.medecin.nom}`} />
        <InfoRow label="Patient" value={`${rdv.patient.prenom} ${rdv.patient.nom}`} />
      </View>

      {/* Bouton annulation */}
      {canCancel && (
        <TouchableOpacity
          style={[styles.cancelBtn, cancelling && styles.cancelBtnDisabled]}
          onPress={handleCancel}
          disabled={cancelling}
        >
          {cancelling ? (
            <ActivityIndicator color="#fff" />
          ) : (
            <Text style={styles.cancelBtnText}>Annuler ce rendez-vous</Text>
          )}
        </TouchableOpacity>
      )}
    </ScrollView>
  );
}

function InfoRow({ label, value }) {
  return (
    <View style={styles.infoRow}>
      <Text style={styles.infoLabel}>{label}</Text>
      <Text style={styles.infoValue}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f0f4f8',
  },
  content: {
    padding: 20,
  },
  centered: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 24,
  },
  header: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 20,
    marginBottom: 16,
    shadowColor: '#000',
    shadowOpacity: 0.07,
    shadowRadius: 6,
    elevation: 3,
    gap: 10,
  },
  dateText: {
    fontSize: 18,
    fontWeight: '700',
    color: '#1a1a1a',
    textTransform: 'capitalize',
  },
  section: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    marginBottom: 20,
    shadowColor: '#000',
    shadowOpacity: 0.07,
    shadowRadius: 6,
    elevation: 3,
    gap: 12,
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  infoLabel: {
    fontSize: 14,
    color: '#888',
    fontWeight: '500',
  },
  infoValue: {
    fontSize: 14,
    color: '#1a1a1a',
    fontWeight: '600',
    flex: 1,
    textAlign: 'right',
  },
  cancelBtn: {
    backgroundColor: '#dc3545',
    borderRadius: 10,
    paddingVertical: 14,
    alignItems: 'center',
  },
  cancelBtnDisabled: {
    opacity: 0.6,
  },
  cancelBtnText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  errorText: {
    color: '#c0392b',
    fontSize: 15,
  },
});
