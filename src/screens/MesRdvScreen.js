import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  FlatList,
  ScrollView,
  RefreshControl,
  StyleSheet,
} from 'react-native';
import apiClient from '../api/client';
import { useTheme } from '../theme';
import RdvCard from '../components/RdvCard';
import FilterChip from '../components/ui/FilterChip';
import EmptyState from '../components/ui/EmptyState';
import { RdvCardSkeleton } from '../components/ui/SkeletonLoader';

const ETATS = [
  { id: null,  libelle: 'Tous',     icon: 'list-outline' },
  { id: 1,     libelle: 'Demandé',  icon: 'time-outline' },
  { id: 2,     libelle: 'Confirmé', icon: 'checkmark-circle-outline' },
  { id: 3,     libelle: 'Annulé',   icon: 'close-circle-outline' },
  { id: 4,     libelle: 'Refusé',   icon: 'ban-outline' },
  { id: 5,     libelle: 'Réalisé',  icon: 'checkmark-done-outline' },
];

export default function MesRdvScreen({ navigation }) {
  const { colors, spacing } = useTheme();
  const [rdvs, setRdvs]             = useState([]);
  const [loading, setLoading]       = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [etatFiltre, setEtatFiltre] = useState(null);
  const [error, setError]           = useState(null);

  const fetchRdvs = useCallback(async () => {
    setError(null);
    try {
      const params = etatFiltre !== null ? { etat: etatFiltre } : {};
      const response = await apiClient.get('/api/mes-rendez-vous', { params });
      setRdvs(response.data);
    } catch {
      setError('Impossible de charger les rendez-vous.');
    }
  }, [etatFiltre]);

  useEffect(() => {
    setLoading(true);
    fetchRdvs().finally(() => setLoading(false));
  }, [fetchRdvs]);

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchRdvs();
    setRefreshing(false);
  };

  return (
    <View style={[styles.container, { backgroundColor: colors.background }]}>
      {/* Filtres */}
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        style={[styles.filterRow, { backgroundColor: colors.surface, borderBottomColor: colors.border }]}
        contentContainerStyle={[styles.filterContent, { paddingHorizontal: spacing.md, paddingVertical: spacing.sm + 2 }]}
      >
        {ETATS.map((e) => (
          <FilterChip
            key={String(e.id)}
            label={e.libelle}
            icon={e.icon}
            active={etatFiltre === e.id}
            onPress={() => setEtatFiltre(e.id)}
          />
        ))}
      </ScrollView>

      {loading ? (
        <View style={{ paddingTop: spacing.sm }}>
          {[1, 2, 3, 4].map((k) => <RdvCardSkeleton key={k} />)}
        </View>
      ) : error ? (
        <EmptyState
          icon="cloud-offline-outline"
          title="Erreur de connexion"
          description={error}
          actionLabel="Réessayer"
          onAction={fetchRdvs}
        />
      ) : rdvs.length === 0 ? (
        <EmptyState
          icon="calendar-outline"
          title="Aucun rendez-vous"
          description="Vous n'avez pas encore de rendez-vous dans cette catégorie."
          actionLabel="Prendre un RDV"
          onAction={() => navigation.navigate('NouveauRdv')}
        />
      ) : (
        <FlatList
          data={rdvs}
          keyExtractor={(item) => String(item.id)}
          renderItem={({ item }) => (
            <RdvCard
              rdv={item}
              onPress={() => navigation.navigate('DetailRdv', { rdvId: item.id })}
            />
          )}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={onRefresh}
              tintColor={colors.primary}
              colors={[colors.primary]}
            />
          }
          contentContainerStyle={{ paddingVertical: spacing.sm }}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  filterRow: {
    maxHeight: 52,
    borderBottomWidth: 1,
  },
  filterContent: {
    alignItems: 'center',
  },
});
