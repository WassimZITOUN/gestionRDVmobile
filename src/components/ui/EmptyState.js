import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useTheme } from '../../theme';
import Button from './Button';

/**
 * Empty state avec icône, titre, description et action optionnelle
 */
export default function EmptyState({ icon, title, description, actionLabel, onAction }) {
  const { colors, typography, spacing } = useTheme();

  return (
    <View style={[styles.container, { padding: spacing.xl }]}>
      {icon && (
        <Ionicons
          name={icon}
          size={64}
          color={colors.text.disabled}
          style={{ marginBottom: spacing.md }}
          accessibilityElementsHidden
        />
      )}
      <Text
        style={[
          typography.h3,
          { color: colors.text.secondary, textAlign: 'center', marginBottom: spacing.sm },
        ]}
      >
        {title}
      </Text>
      {description && (
        <Text
          style={[
            typography.body,
            { color: colors.text.disabled, textAlign: 'center', marginBottom: spacing.lg },
          ]}
        >
          {description}
        </Text>
      )}
      {actionLabel && onAction && (
        <Button variant="ghost" size="sm" onPress={onAction} style={{ minWidth: 160 }}>
          {actionLabel}
        </Button>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
