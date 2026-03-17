import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  Alert,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import * as Haptics from 'expo-haptics';
import apiClient from '../api/client';
import { useTheme } from '../theme';
import EmptyState from '../components/ui/EmptyState';
import Button from '../components/ui/Button';
import { RdvCardSkeleton, SkeletonBox } from '../components/ui/SkeletonLoader';
import { showToast } from '../components/ui/Toast';

// ─── Indicateur d'étape ───────────────────────────────────────────────────────
function StepIndicator({ step, colors, typography, spacing }) {
  return (
    <View style={[styles.stepBar, { backgroundColor: colors.surface, borderBottomColor: colors.border }]}>
      <View style={styles.stepRow}>
        <StepDot
          num={1}
          active={step >= 1}
          done={step > 1}
          label="Médecin"
          colors={colors}
          typography={typography}
        />
        <View style={[styles.stepLine, { backgroundColor: step > 1 ? colors.primary : colors.border }]} />
        <StepDot
          num={2}
          active={step >= 2}
          done={false}
          label="Créneau"
          colors={colors}
          typography={typography}
        />
      </View>
      <Text style={[typography.caption, { color: colors.text.disabled, textAlign: 'center', marginTop: spacing.xs }]}>
        Étape {step}/2 — {step === 1 ? 'Choisissez un médecin' : 'Choisissez un créneau'}
      </Text>
    </View>
  );
}

function StepDot({ num, active, done, label, colors, typography }) {
  const bg = active ? colors.primary : colors.border;
  return (
    <View style={styles.stepDotWrapper}>
      <View style={[styles.stepDot, { backgroundColor: bg }]}>
        {done
          ? <Ionicons name="checkmark" size={12} color="#fff" />
          : <Text style={[typography.caption, { color: active ? '#fff' : colors.text.disabled, fontWeight: '700' }]}>{num}</Text>
        }
      </View>
      <Text style={[typography.small, { color: active ? colors.primary : colors.text.disabled, marginTop: 4 }]}>
        {label}
      </Text>
    </View>
  );
}

// ─── Écran principal ──────────────────────────────────────────────────────────
export default function NouveauRdvScreen({ navigation }) {
  const { colors, typography, spacing, shadows } = useTheme();

  const [step, setStep]                 = useState(1);
  const [medecins, setMedecins]         = useState([]);
  const [creneaux, setCreneaux]         = useState([]);
  const [medecinChoisi, setMedecinChoisi] = useState(null);
  const [loading, setLoading]           = useState(true);
  const [submitting, setSubmitting]     = useState(false);
  const [error, setError]               = useState(null);

  useEffect(() => {
    apiClient
      .get('/api/medecins')
      .then((r) => setMedecins(r.data))
      .catch(() => setError('Impossible de charger la liste des médecins.'))
      .finally(() => setLoading(false));
  }, []);

  const choisirMedecin = async (medecin) => {
    try { await Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light); } catch {}
    setMedecinChoisi(medecin);
    setStep(2);
    setLoading(true);
    setError(null);
    try {
      const r = await apiClient.get(`/api/medecins/${medecin.id}/creneaux`);
      setCreneaux(r.data);
    } catch {
      setError('Impossible de charger les créneaux de ce médecin.');
    } finally {
      setLoading(false);
    }
  };

  const retour = () => {
    setStep(1);
    setError(null);
  };

  const confirmerRdv = async (creneau) => {
    try { await Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Medium); } catch {}

    Alert.alert(
      'Confirmer le rendez-vous',
      `Dr ${medecinChoisi.prenom} ${medecinChoisi.nom}\n${creneau.label}`,
      [
        { text: 'Annuler', style: 'cancel' },
        {
          text: 'Confirmer',
          onPress: async () => {
            setSubmitting(true);
            try {
              await apiClient.post('/api/rendez-vous/nouveau', {
                medecin_id: medecinChoisi.id,
                datetime_debut: creneau.datetime,
              });
              showToast.success('Rendez-vous demandé !', 'Votre demande a bien été envoyée.');
              setTimeout(() => navigation.navigate('MesRdv'), 800);
            } catch (e) {
              const msg = e.response?.data?.error ?? 'Impossible de créer ce rendez-vous.';
              Alert.alert('Erreur', msg);
            } finally {
              setSubmitting(false);
            }
          },
        },
      ]
    );
  };

  // ── Rendu des listes ───────────────────────────────────────────────────────

  const renderMedecin = ({ item }) => (
    <TouchableOpacity
      style={[styles.row, { backgroundColor: colors.surface, borderLeftColor: colors.primary }, shadows.sm]}
      onPress={() => choisirMedecin(item)}
      activeOpacity={0.78}
      accessibilityRole="button"
      accessibilityLabel={`Choisir le Dr ${item.prenom} ${item.nom}`}
    >
      <View style={[styles.avatar, { backgroundColor: colors.primaryLight }]}>
        <Text style={[typography.bodyMedium, { color: colors.primary, fontWeight: '700' }]}>
          {item.prenom[0]}{item.nom[0]}
        </Text>
      </View>
      <Text style={[typography.bodyMedium, { flex: 1, color: colors.text.primary }]}>
        Dr {item.prenom} {item.nom}
      </Text>
      <Ionicons name="chevron-forward" size={18} color={colors.text.disabled} accessibilityElementsHidden />
    </TouchableOpacity>
  );

  const renderCreneau = ({ item }) => (
    <TouchableOpacity
      style={[styles.row, { backgroundColor: colors.surface }, shadows.sm]}
      onPress={() => confirmerRdv(item)}
      activeOpacity={0.78}
      accessibilityRole="button"
      accessibilityLabel={`Choisir le créneau ${item.label}`}
    >
      <Ionicons name="time-outline" size={18} color={colors.primary} style={{ marginRight: spacing.sm }} accessibilityElementsHidden />
      <Text style={[typography.bodyMedium, { flex: 1, color: colors.text.primary }]}>
        {item.label}
      </Text>
      <Text style={[typography.caption, { color: colors.text.secondary, marginRight: 4 }]}>
        {item.duree} min
      </Text>
      <Ionicons name="chevron-forward" size={16} color={colors.text.disabled} accessibilityElementsHidden />
    </TouchableOpacity>
  );

  const SkeletonList = () => (
    <View style={{ padding: spacing.md }}>
      {[1, 2, 3].map((k) => (
        <View key={k} style={[styles.row, { backgroundColor: colors.surface, marginBottom: spacing.sm }]}>
          <SkeletonBox width={42} height={42} borderRadius={21} style={{ marginRight: spacing.sm }} />
          <View style={{ flex: 1 }}>
            <SkeletonBox width="55%" height={13} borderRadius={6} style={{ marginBottom: 8 }} />
            <SkeletonBox width="35%" height={11} borderRadius={6} />
          </View>
        </View>
      ))}
    </View>
  );

  return (
    <View style={[styles.container, { backgroundColor: colors.background }]}>
      <StepIndicator step={step} colors={colors} typography={typography} spacing={spacing} />

      {/* Bouton retour à l'étape 2 */}
      {step === 2 && (
        <TouchableOpacity
          style={[styles.backBtn, { paddingHorizontal: spacing.md, paddingTop: spacing.sm }]}
          onPress={retour}
          accessibilityRole="button"
          accessibilityLabel="Retour à la liste des médecins"
        >
          <Ionicons name="arrow-back" size={16} color={colors.primary} />
          <Text style={[typography.bodyMedium, { color: colors.primary, marginLeft: 4 }]}>Retour</Text>
        </TouchableOpacity>
      )}

      {/* En-tête étape 2 */}
      {step === 2 && medecinChoisi && (
        <View style={{ paddingHorizontal: spacing.md, paddingBottom: spacing.sm }}>
          <Text style={[typography.h3, { color: colors.text.primary }]}>
            Dr {medecinChoisi.prenom} {medecinChoisi.nom}
          </Text>
          <Text style={[typography.caption, { color: colors.text.secondary }]}>
            Créneaux disponibles
          </Text>
        </View>
      )}

      {/* Overlay submitting */}
      {submitting ? (
        <View style={styles.centered}>
          <RdvCardSkeleton />
          <Text style={[typography.body, { color: colors.text.secondary, marginTop: spacing.md }]}>
            Envoi en cours...
          </Text>
        </View>
      ) : loading ? (
        <SkeletonList />
      ) : error ? (
        <EmptyState
          icon="cloud-offline-outline"
          title="Erreur de chargement"
          description={error}
          actionLabel={step === 2 ? 'Retour' : 'Réessayer'}
          onAction={step === 2 ? retour : undefined}
        />
      ) : step === 1 && medecins.length === 0 ? (
        <EmptyState
          icon="medical-outline"
          title="Aucun médecin disponible"
          description="Il n'y a pas de médecin disponible pour le moment."
        />
      ) : step === 2 && creneaux.length === 0 ? (
        <EmptyState
          icon="calendar-clear-outline"
          title="Aucun créneau disponible"
          description="Ce médecin n'a pas de créneau disponible dans les 30 prochains jours."
          actionLabel="Choisir un autre médecin"
          onAction={retour}
        />
      ) : (
        <FlatList
          data={step === 1 ? medecins : creneaux}
          keyExtractor={(item) => step === 1 ? String(item.id) : item.datetime}
          renderItem={step === 1 ? renderMedecin : renderCreneau}
          contentContainerStyle={{ padding: spacing.md }}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container:  { flex: 1 },
  centered:   { flex: 1, alignItems: 'center', justifyContent: 'center', padding: 24 },
  stepBar: {
    paddingTop: 12,
    paddingBottom: 10,
    borderBottomWidth: 1,
  },
  stepRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 40,
  },
  stepLine: { flex: 1, height: 2, marginHorizontal: 8 },
  stepDotWrapper: { alignItems: 'center' },
  stepDot: {
    width: 28,
    height: 28,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 10,
    padding: 14,
    marginBottom: 10,
    borderLeftWidth: 3,
    borderLeftColor: 'transparent',
  },
  avatar: {
    width: 42,
    height: 42,
    borderRadius: 21,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  backBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingBottom: 4,
  },
});
