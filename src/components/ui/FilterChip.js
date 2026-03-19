import React from 'react';
import { Text, TouchableOpacity, StyleSheet, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import * as Haptics from 'expo-haptics';
import { useTheme } from '../../theme';

export default function FilterChip({ label, active, onPress, icon }) {
  const { colors, typography, spacing } = useTheme();

  const handlePress = async () => {
    try {
      await Haptics.selectionAsync();
    } catch {}
    onPress?.();
  };

  const bg    = active ? colors.primary : colors.surfaceVariant;
  const color = active ? colors.text.inverse : colors.text.secondary;

  return (
    <TouchableOpacity
      activeOpacity={0.75}
      onPress={handlePress}
      accessibilityRole="button"
      accessibilityState={{ selected: active }}
      accessibilityLabel={label}
      style={[
        styles.chip,
        {
          backgroundColor: bg,
          paddingVertical: spacing.xs + 2,
          paddingHorizontal: spacing.sm + 4,
          marginRight: spacing.sm - 2,
        },
      ]}
    >
      {icon && (
        <Ionicons
          name={icon}
          size={13}
          color={color}
          style={{ marginRight: 4 }}
          accessibilityElementsHidden
        />
      )}
      <Text style={[typography.caption, { color }]}>{label}</Text>
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  chip: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 20,
  },
});
