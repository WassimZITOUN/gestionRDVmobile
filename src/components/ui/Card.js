import React from 'react';
import { View, TouchableOpacity, StyleSheet } from 'react-native';
import { useTheme } from '../../theme';

/**
 * Card
 * @param {string}   variant  - 'elevated' | 'outlined'
 * @param {Function} onPress  - Si fourni, la carte est pressable
 */
export default function Card({ variant = 'elevated', onPress, children, style }) {
  const { colors, shadows } = useTheme();

  const cardStyle = [
    styles.base,
    { backgroundColor: colors.surface, borderColor: colors.border },
    variant === 'elevated' && shadows.md,
    variant === 'outlined' && styles.outlined,
    style,
  ];

  if (onPress) {
    return (
      <TouchableOpacity
        activeOpacity={0.78}
        onPress={onPress}
        style={cardStyle}
        accessibilityRole="button"
      >
        {children}
      </TouchableOpacity>
    );
  }

  return <View style={cardStyle}>{children}</View>;
}

const styles = StyleSheet.create({
  base: {
    borderRadius: 12,
    overflow: 'hidden',
  },
  outlined: {
    borderWidth: 1,
  },
});
