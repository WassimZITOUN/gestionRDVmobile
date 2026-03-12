import { ActivityIndicator, View, TouchableOpacity, Text, StyleSheet } from 'react-native';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';

import { AuthProvider, useAuth } from './src/context/AuthContext';
import LoginScreen from './src/screens/LoginScreen';
import MesRdvScreen from './src/screens/MesRdvScreen';
import DetailRdvScreen from './src/screens/DetailRdvScreen';
import NouveauRdvScreen from './src/screens/NouveauRdvScreen';

const Stack = createNativeStackNavigator();
const Tab = createBottomTabNavigator();

// Stack de l'onglet "Mes RDV"
function MesRdvStack() {
  const { logout } = useAuth();
  return (
    <Stack.Navigator
      screenOptions={{
        headerStyle: { backgroundColor: '#1a73e8' },
        headerTintColor: '#fff',
        headerTitleStyle: { fontWeight: '700' },
      }}
    >
      <Stack.Screen
        name="MesRdv"
        component={MesRdvScreen}
        options={{
          title: 'Mes rendez-vous',
          headerRight: () => (
            <TouchableOpacity onPress={logout} style={styles.logoutBtn}>
              <Text style={styles.logoutText}>Déconnexion</Text>
            </TouchableOpacity>
          ),
        }}
      />
      <Stack.Screen
        name="DetailRdv"
        component={DetailRdvScreen}
        options={{ title: 'Détail du rendez-vous' }}
      />
    </Stack.Navigator>
  );
}

// Navigation principale (connecté)
function AppTabs() {
  return (
    <Tab.Navigator
      screenOptions={{
        headerStyle: { backgroundColor: '#1a73e8' },
        headerTintColor: '#fff',
        headerTitleStyle: { fontWeight: '700' },
        tabBarActiveTintColor: '#1a73e8',
        tabBarInactiveTintColor: '#999',
      }}
    >
      <Tab.Screen
        name="MesRdvTab"
        component={MesRdvStack}
        options={{ title: 'Mes RDV', headerShown: false }}
      />
      <Tab.Screen
        name="NouveauRdv"
        component={NouveauRdvScreen}
        options={{ title: 'Nouveau RDV' }}
      />
    </Tab.Navigator>
  );
}

// Racine : redirige selon authentification
function RootNavigator() {
  const { token, loading } = useAuth();

  if (loading) {
    return (
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
        <ActivityIndicator size="large" color="#1a73e8" />
      </View>
    );
  }

  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      {token ? (
        <Stack.Screen name="App" component={AppTabs} />
      ) : (
        <Stack.Screen name="Login" component={LoginScreen} />
      )}
    </Stack.Navigator>
  );
}

const styles = StyleSheet.create({
  logoutBtn: {
    marginRight: 4,
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.7)',
  },
  logoutText: {
    color: '#fff',
    fontSize: 13,
    fontWeight: '600',
  },
});

export default function App() {
  return (
    <SafeAreaProvider>
      <AuthProvider>
        <NavigationContainer>
          <RootNavigator />
        </NavigationContainer>
        <StatusBar style="light" />
      </AuthProvider>
    </SafeAreaProvider>
  );
}
