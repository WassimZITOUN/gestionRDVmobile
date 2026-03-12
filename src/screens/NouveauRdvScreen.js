import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ActivityIndicator,
  FlatList,
  TouchableOpacity,
  Alert,
} from 'react-native';
import apiClient from '../api/client';

export default function NouveauRdvScreen({ navigation }) {
  // Étape 1 : choix médecin | Étape 2 : choix créneau
  const [step, setStep] = useState(1);
  const [medecins, setMedecins] = useState([]);
  const [creneaux, setCreneaux] = useState([]);
  const [medecinChoisi, setMedecinChoisi] = useState(null);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState(null);

  // Charger les médecins au démarrage
  useEffect(() => {
    apiClient
      .get('/api/medecins')
      .then((r) => setMedecins(r.data))
      .catch(() => setError('Impossible de charger la liste des médecins.'))
      .finally(() => setLoading(false));
  }, []);

  // Charger les créneaux quand un médecin est sélectionné
  const choisirMedecin = async (medecin) => {
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

  const confirmerRdv = async (creneau) => {
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
              Alert.alert('Succès', 'Votre demande de rendez-vous a été envoyée.', [
                {
                  text: 'OK',
                  onPress: () => navigation.navigate('MesRdv'),
                },
              ]);
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

  if (submitting) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color="#1a73e8" />
        <Text style={styles.loadingText}>Envoi en cours...</Text>
      </View>
    );
  }

  // Étape 1 : liste des médecins
  if (step === 1) {
    return (
      <View style={styles.container}>
        <Text style={styles.stepTitle}>Choisissez un médecin</Text>

        {loading ? (
          <ActivityIndicator size="large" color="#1a73e8" style={{ marginTop: 40 }} />
        ) : error ? (
          <View style={styles.centered}>
            <Text style={styles.errorText}>{error}</Text>
          </View>
        ) : medecins.length === 0 ? (
          <View style={styles.centered}>
            <Text style={styles.emptyText}>Aucun médecin disponible.</Text>
          </View>
        ) : (
          <FlatList
            data={medecins}
            keyExtractor={(item) => String(item.id)}
            contentContainerStyle={{ padding: 16 }}
            renderItem={({ item }) => (
              <TouchableOpacity
                style={styles.medecinCard}
                onPress={() => choisirMedecin(item)}
                activeOpacity={0.8}
              >
                <View style={styles.avatar}>
                  <Text style={styles.avatarText}>
                    {item.prenom[0]}{item.nom[0]}
                  </Text>
                </View>
                <Text style={styles.medecinName}>
                  Dr {item.prenom} {item.nom}
                </Text>
                <Text style={styles.chevron}>›</Text>
              </TouchableOpacity>
            )}
          />
        )}
      </View>
    );
  }

  // Étape 2 : liste des créneaux
  return (
    <View style={styles.container}>
      <View style={styles.stepHeader}>
        <TouchableOpacity onPress={() => setStep(1)} style={styles.backBtn}>
          <Text style={styles.backBtnText}>‹ Retour</Text>
        </TouchableOpacity>
        <Text style={styles.stepTitle}>
          Dr {medecinChoisi.prenom} {medecinChoisi.nom}
        </Text>
      </View>
      <Text style={styles.stepSub}>Choisissez un créneau disponible</Text>

      {loading ? (
        <ActivityIndicator size="large" color="#1a73e8" style={{ marginTop: 40 }} />
      ) : error ? (
        <View style={styles.centered}>
          <Text style={styles.errorText}>{error}</Text>
        </View>
      ) : creneaux.length === 0 ? (
        <View style={styles.centered}>
          <Text style={styles.emptyText}>Aucun créneau disponible dans les 30 prochains jours.</Text>
        </View>
      ) : (
        <FlatList
          data={creneaux}
          keyExtractor={(item) => item.datetime}
          contentContainerStyle={{ padding: 16 }}
          renderItem={({ item }) => (
            <TouchableOpacity
              style={styles.creneauCard}
              onPress={() => confirmerRdv(item)}
              activeOpacity={0.8}
            >
              <Text style={styles.creneauLabel}>{item.label}</Text>
              <Text style={styles.creneauDuree}>{item.duree} min</Text>
            </TouchableOpacity>
          )}
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
  centered: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 24,
  },
  loadingText: {
    marginTop: 12,
    color: '#555',
    fontSize: 15,
  },
  stepTitle: {
    fontSize: 17,
    fontWeight: '700',
    color: '#1a1a1a',
    paddingHorizontal: 16,
    paddingTop: 16,
    paddingBottom: 4,
  },
  stepSub: {
    fontSize: 13,
    color: '#888',
    paddingHorizontal: 16,
    paddingBottom: 8,
  },
  stepHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingTop: 12,
    paddingHorizontal: 16,
    gap: 8,
  },
  backBtn: {
    paddingVertical: 4,
  },
  backBtnText: {
    color: '#1a73e8',
    fontSize: 16,
  },
  medecinCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fff',
    borderRadius: 10,
    padding: 14,
    marginBottom: 10,
    shadowColor: '#000',
    shadowOpacity: 0.06,
    shadowRadius: 5,
    elevation: 2,
  },
  avatar: {
    width: 42,
    height: 42,
    borderRadius: 21,
    backgroundColor: '#1a73e8',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  avatarText: {
    color: '#fff',
    fontWeight: 'bold',
    fontSize: 15,
  },
  medecinName: {
    flex: 1,
    fontSize: 15,
    fontWeight: '600',
    color: '#1a1a1a',
  },
  chevron: {
    fontSize: 22,
    color: '#aaa',
  },
  creneauCard: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: '#fff',
    borderRadius: 10,
    paddingHorizontal: 16,
    paddingVertical: 14,
    marginBottom: 8,
    shadowColor: '#000',
    shadowOpacity: 0.06,
    shadowRadius: 5,
    elevation: 2,
  },
  creneauLabel: {
    fontSize: 14,
    color: '#1a1a1a',
    fontWeight: '500',
    flex: 1,
  },
  creneauDuree: {
    fontSize: 12,
    color: '#888',
    marginLeft: 8,
  },
  errorText: {
    color: '#c0392b',
    fontSize: 15,
    textAlign: 'center',
  },
  emptyText: {
    color: '#888',
    fontSize: 15,
    textAlign: 'center',
  },
});
