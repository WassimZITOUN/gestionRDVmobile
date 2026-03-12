import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  FlatList,
  StyleSheet,
  ActivityIndicator,
  TouchableOpacity,
  ScrollView,
  RefreshControl,
} from 'react-native';
import apiClient from '../api/client';
import RdvCard from '../components/RdvCard';

const ETATS = [
  { id: null, libelle: 'Tous' },
  { id: 1, libelle: 'Demandé' },
  { id: 2, libelle: 'Confirmé' },
  { id: 3, libelle: 'Annulé' },
  { id: 4, libelle: 'Refusé' },
  { id: 5, libelle: 'Réalisé' },
];

export default function MesRdvScreen({ navigation }) {
  const [rdvs, setRdvs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [etatFiltre, setEtatFiltre] = useState(null);
  const [error, setError] = useState(null);

  const fetchRdvs = useCallback(async () => {
    setError(null);
    try {
      const params = etatFiltre !== null ? { etat: etatFiltre } : {};
      const response = await apiClient.get('/api/mes-rendez-vous', { params });
      setRdvs(response.data);
    } catch (e) {
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
    <View style={styles.container}>
      {/* Filtre par état */}
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        style={styles.filterRow}
        contentContainerStyle={styles.filterContent}
      >
        {ETATS.map((e) => (
          <TouchableOpacity
            key={String(e.id)}
            style={[styles.filterBtn, etatFiltre === e.id && styles.filterBtnActive]}
            onPress={() => setEtatFiltre(e.id)}
          >
            <Text style={[styles.filterText, etatFiltre === e.id && styles.filterTextActive]}>
              {e.libelle}
            </Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      {loading ? (
        <ActivityIndicator size="large" color="#1a73e8" style={{ marginTop: 40 }} />
      ) : error ? (
        <View style={styles.centered}>
          <Text style={styles.errorText}>{error}</Text>
          <TouchableOpacity onPress={fetchRdvs}>
            <Text style={styles.retryText}>Réessayer</Text>
          </TouchableOpacity>
        </View>
      ) : rdvs.length === 0 ? (
        <View style={styles.centered}>
          <Text style={styles.emptyText}>Aucun rendez-vous trouvé.</Text>
        </View>
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
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
          contentContainerStyle={{ paddingVertical: 8 }}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f0f4f8',
  },
  filterRow: {
    maxHeight: 52,
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderBottomColor: '#eee',
  },
  filterContent: {
    paddingHorizontal: 12,
    paddingVertical: 10,
    gap: 8,
  },
  filterBtn: {
    paddingHorizontal: 14,
    paddingVertical: 6,
    borderRadius: 20,
    backgroundColor: '#f0f4f8',
    marginRight: 6,
  },
  filterBtnActive: {
    backgroundColor: '#1a73e8',
  },
  filterText: {
    fontSize: 13,
    color: '#555',
    fontWeight: '500',
  },
  filterTextActive: {
    color: '#fff',
  },
  centered: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 24,
  },
  emptyText: {
    color: '#888',
    fontSize: 15,
  },
  errorText: {
    color: '#c0392b',
    fontSize: 15,
    marginBottom: 12,
  },
  retryText: {
    color: '#1a73e8',
    fontWeight: '600',
  },
});
