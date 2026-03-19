import React, { useState, useRef, useEffect } from 'react';
import {
  View,
  Text,
  KeyboardAvoidingView,
  Platform,
  Animated,
  Image,
  ScrollView,
  StyleSheet,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useAuth } from '../context/AuthContext';
import { useTheme } from '../theme';
import Button from '../components/ui/Button';
import Input from '../components/ui/Input';

export default function LoginScreen() {
  const { login } = useAuth();
  const { colors, typography, spacing, shadows } = useTheme();

  const [identifier, setIdentifier] = useState('');
  const [password, setPassword]     = useState('');
  const [loading, setLoading]       = useState(false);
  const [errors, setErrors]         = useState({});

  // Animation d'entrée
  const cardAnim = useRef(new Animated.Value(0)).current;
  useEffect(() => {
    Animated.spring(cardAnim, {
      toValue: 1,
      friction: 7,
      tension: 60,
      useNativeDriver: true,
    }).start();
  }, [cardAnim]);

  const cardStyle = {
    opacity: cardAnim,
    transform: [
      {
        translateY: cardAnim.interpolate({
          inputRange: [0, 1],
          outputRange: [40, 0],
        }),
      },
    ],
  };

  const validate = () => {
    const newErrors = {};
    if (!identifier.trim()) newErrors.identifier = 'Veuillez saisir votre identifiant.';
    if (!password.trim())   newErrors.password   = 'Veuillez saisir votre mot de passe.';
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleLogin = async () => {
    if (!validate()) return;
    setLoading(true);
    setErrors({});
    try {
      await login(identifier.trim(), password);
    } catch (error) {
      const message =
        error.response?.status === 401
          ? 'Identifiant ou mot de passe incorrect.'
          : 'Impossible de se connecter. Vérifiez votre connexion.';
      setErrors({ global: message });
    } finally {
      setLoading(false);
    }
  };

  return (
    <LinearGradient
      colors={[colors.primaryLight, colors.background]}
      style={styles.gradient}
    >
      <KeyboardAvoidingView
        style={styles.flex}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      >
        <ScrollView
          contentContainerStyle={[styles.scroll, { padding: spacing.lg }]}
          keyboardShouldPersistTaps="handled"
        >
          {/* Logo / icône */}
          <View style={styles.logoContainer}>
            <View style={[styles.logoCircle, { backgroundColor: colors.primary }, shadows.md]}>
              <Image
                source={require('../../assets/icon.png')}
                style={styles.logo}
                accessibilityElementsHidden
              />
            </View>
          </View>

          {/* Carte */}
          <Animated.View
            style={[
              styles.card,
              { backgroundColor: colors.surface },
              shadows.lg,
              cardStyle,
            ]}
          >
            <Text style={[typography.h1, { color: colors.primary, textAlign: 'center', marginBottom: 4 }]}>
              RDV Gestion
            </Text>
            <Text style={[typography.body, { color: colors.text.secondary, textAlign: 'center', marginBottom: spacing.lg + 4 }]}>
              Connexion patient
            </Text>

            {/* Erreur globale */}
            {errors.global && (
              <View
                style={[styles.globalError, { backgroundColor: colors.errorLight, borderColor: colors.error }]}
                accessibilityRole="alert"
              >
                <Text style={[typography.caption, { color: colors.error }]}>{errors.global}</Text>
              </View>
            )}

            <Input
              label="Identifiant"
              icon="person-outline"
              placeholder="email@exemple.com ou identifiant LDAP"
              autoCapitalize="none"
              autoCorrect={false}
              value={identifier}
              onChangeText={(t) => { setIdentifier(t); setErrors((e) => ({ ...e, identifier: null })); }}
              error={errors.identifier}
            />

            <Input
              label="Mot de passe"
              icon="lock-closed-outline"
              placeholder="Votre mot de passe"
              secureTextEntry
              value={password}
              onChangeText={(t) => { setPassword(t); setErrors((e) => ({ ...e, password: null })); }}
              error={errors.password}
            />

            <Button
              variant="primary"
              size="lg"
              loading={loading}
              onPress={handleLogin}
              style={{ marginTop: spacing.sm, borderRadius: 10 }}
            >
              Se connecter
            </Button>
          </Animated.View>
        </ScrollView>
      </KeyboardAvoidingView>
    </LinearGradient>
  );
}

const styles = StyleSheet.create({
  gradient: { flex: 1 },
  flex:     { flex: 1 },
  scroll: {
    flexGrow: 1,
    justifyContent: 'center',
  },
  logoContainer: {
    alignItems: 'center',
    marginBottom: 24,
  },
  logoCircle: {
    width: 80,
    height: 80,
    borderRadius: 24,
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  logo: {
    width: 80,
    height: 80,
  },
  card: {
    borderRadius: 16,
    padding: 28,
  },
  globalError: {
    borderWidth: 1,
    borderRadius: 8,
    padding: 10,
    marginBottom: 14,
  },
});
