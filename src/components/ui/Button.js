import React from 'react';
import { ActivityIndicator, StyleSheet, Text, TouchableOpacity } from 'react-native';
import * as Haptics from 'expo-haptics';
import { useTheme } from '../../theme';

/**
 * Button
 * @param {string}   variant  - 'primary' | 'secondary' | 'danger' | 'ghost'
 * @param {string}   size     - 'sm' | 'md' | 'lg'
 * @param {boolean}  loading
 * @param {boolean}  disabled
 * @param {Function} onPress
 * @param {ReactNode} children
 */
export default function Button({
  variant = 'primary',
  size = 'md',
  loading = false,
  disabled = false,
  onPress,
  children,
  style,
}) {
  const { colors, typography } = useTheme();

  const handlePress = async () => {
    if (disabled || loading) return;
    try {
      await Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
    } catch {}
    onPress?.();
  };

  const bgColor = {
    primary:   disabled || loading ? colors.primaryLight : colors.primary,
    secondary: disabled || loading ? colors.secondaryLight : colors.secondary,
    danger:    disabled || loading ? '#FFCDD2' : '#D32F2F',
    ghost:     'transparent',
  }[variant];

  const textColor = {
    primary:   colors.text.inverse,
    secondary: colors.text.inverse,
    danger:    colors.text.inverse,
    ghost:     colors.primary,
  }[variant];

  const paddingV = { sm: 8, md: 13, lg: 16 }[size];
  const fontSize  = { sm: 13, md: 15, lg: 17 }[size];
  const borderRadius = { sm: 8, md: 10, lg: 12 }[size];

  return (
    <TouchableOpacity
      activeOpacity={0.75}
      onPress={handlePress}
      disabled={disabled || loading}
      accessibilityRole="button"
      accessibilityState={{ disabled: disabled || loading }}
      style={[
        styles.base,
        {
          backgroundColor: bgColor,
          paddingVertical: paddingV,
          borderRadius,
          borderWidth: variant === 'ghost' ? 1.5 : 0,
          borderColor: variant === 'ghost' ? colors.primary : 'transparent',
          opacity: disabled && !loading ? 0.5 : 1,
        },
        style,
      ]}
    >
      {loading ? (
        <ActivityIndicator color={textColor} size="small" />
      ) : (
        <Text style={[styles.text, { color: textColor, fontSize }, typography.button]}>
          {children}
        </Text>
      )}
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  base: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 20,
    minHeight: 44,
  },
  text: {
    textAlign: 'center',
  },
});
