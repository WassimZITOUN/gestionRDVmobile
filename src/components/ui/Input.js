import React, { useState } from 'react';
import {
  View,
  TextInput,
  Text,
  TouchableOpacity,
  StyleSheet,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useTheme } from '../../theme';

/**
 * Input avec label, icône, erreur inline et toggle mot de passe
 */
export default function Input({
  label,
  error,
  icon,
  secureTextEntry,
  style,
  containerStyle,
  ...props
}) {
  const { colors, typography } = useTheme();
  const [focused, setFocused] = useState(false);
  const [visible, setVisible] = useState(false);

  const isPassword = secureTextEntry;
  const hideText = isPassword && !visible;

  const borderColor = error
    ? colors.error
    : focused
    ? colors.primary
    : colors.border;

  return (
    <View style={[styles.container, containerStyle]}>
      {label && (
        <Text style={[typography.caption, { color: colors.text.secondary, marginBottom: 6 }]}>
          {label}
        </Text>
      )}

      <View
        style={[
          styles.inputRow,
          {
            borderColor,
            backgroundColor: colors.surface,
            borderWidth: focused ? 1.5 : 1,
          },
        ]}
      >
        {icon && (
          <Ionicons
            name={icon}
            size={18}
            color={focused ? colors.primary : colors.text.disabled}
            style={styles.icon}
          />
        )}

        <TextInput
          {...props}
          secureTextEntry={hideText}
          onFocus={() => setFocused(true)}
          onBlur={() => setFocused(false)}
          style={[
            styles.input,
            { color: colors.text.primary },
            typography.body,
            style,
          ]}
          placeholderTextColor={colors.text.disabled}
          accessibilityLabel={label}
          accessibilityHint={error}
        />

        {isPassword && (
          <TouchableOpacity
            onPress={() => setVisible((v) => !v)}
            style={styles.eyeBtn}
            accessibilityLabel={visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe'}
          >
            <Ionicons
              name={visible ? 'eye-off-outline' : 'eye-outline'}
              size={18}
              color={colors.text.disabled}
            />
          </TouchableOpacity>
        )}
      </View>

      {error && (
        <Text style={[styles.error, { color: colors.error }, typography.caption]}>
          {error}
        </Text>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    marginBottom: 14,
  },
  inputRow: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 10,
    overflow: 'hidden',
    minHeight: 48,
  },
  icon: {
    marginLeft: 14,
    marginRight: 4,
  },
  input: {
    flex: 1,
    paddingHorizontal: 12,
    paddingVertical: 12,
  },
  eyeBtn: {
    paddingHorizontal: 14,
    paddingVertical: 12,
    minWidth: 44,
    alignItems: 'center',
  },
  error: {
    marginTop: 5,
    marginLeft: 2,
  },
});
