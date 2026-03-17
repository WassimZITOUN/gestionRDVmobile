import { ActivityIndicator, View, TouchableOpacity } from 'react-native';
import { NavigationContainer, DarkTheme, DefaultTheme } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { Ionicons } from '@expo/vector-icons';
import Toast from 'react-native-toast-message';

import { AuthProvider, useAuth } from './src/context/AuthContext';
import { ThemeProvider, useTheme } from './src/theme';
import { toastConfig } from './src/components/ui/Toast';
import CustomTabBar from './src/components/navigation/CustomTabBar';
import LoginScreen from './src/screens/LoginScreen';
import MesRdvScreen from './src/screens/MesRdvScreen';
import DetailRdvScreen from './src/screens/DetailRdvScreen';
import NouveauRdvScreen from './src/screens/NouveauRdvScreen';

const Stack = createNativeStackNavigator();
const Tab   = createBottomTabNavigator();

function MesRdvStack() {
  const { logout }  = useAuth();
  const { colors }  = useTheme();

  return (
    <Stack.Navigator
      screenOptions={{
        headerStyle: { backgroundColor: colors.primary },
        headerTintColor: '#fff',
        headerTitleStyle: { fontWeight: '700' },
        animation: 'slide_from_right',
      }}
    >
      <Stack.Screen
        name="MesRdv"
        component={MesRdvScreen}
        options={{
          title: 'Mes rendez-vous',
          headerRight: () => (
            <TouchableOpacity
              onPress={logout}
              style={{ paddingHorizontal: 6, paddingVertical: 4 }}
              accessibilityRole="button"
              accessibilityLabel="Se déconnecter"
            >
              <Ionicons name="log-out-outline" size={22} color="#fff" />
            </TouchableOpacity>
          ),
        }}
      />
      <Stack.Screen
        name="DetailRdv"
        component={DetailRdvScreen}
        options={{ title: 'Détail du rendez-vous', animation: 'slide_from_right' }}
      />
    </Stack.Navigator>
  );
}

function AppTabs() {
  const { colors } = useTheme();
  return (
    <Tab.Navigator
      tabBar={(props) => <CustomTabBar {...props} />}
      screenOptions={{
        headerStyle: { backgroundColor: colors.primary },
        headerTintColor: '#fff',
        headerTitleStyle: { fontWeight: '700' },
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

function RootNavigator() {
  const { token, loading } = useAuth();
  const { colors } = useTheme();

  if (loading) {
    return (
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: colors.background }}>
        <ActivityIndicator size="large" color={colors.primary} />
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

function AppWithTheme() {
  const { isDark, colors } = useTheme();

  const navTheme = {
    ...(isDark ? DarkTheme : DefaultTheme),
    colors: {
      ...(isDark ? DarkTheme.colors : DefaultTheme.colors),
      primary:    colors.primary,
      background: colors.background,
      card:       colors.surface,
      text:       colors.text.primary,
      border:     colors.border,
    },
  };

  return (
    <NavigationContainer theme={navTheme}>
      <RootNavigator />
      <StatusBar style={isDark ? 'light' : 'dark'} />
    </NavigationContainer>
  );
}

export default function App() {
  return (
    <SafeAreaProvider>
      <ThemeProvider>
        <AuthProvider>
          <AppWithTheme />
        </AuthProvider>
        <Toast config={toastConfig} />
      </ThemeProvider>
    </SafeAreaProvider>
  );
}
