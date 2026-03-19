import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useTheme } from '../theme';
import { ETAT_STATUS_MAP, ETAT_LABELS } from '../theme/colors';

const ETAT_ICONS = {
  pending:   'time-outline',
  confirmed: 'checkmark-circle-outline',
  cancelled: 'close-circle-outline',
  refused:   'ban-outline',
  completed: 'checkmark-done-outline',
};

export default function EtatBadge({ etat }) {
  const { colors, typography } = useTheme();

  const key    = ETAT_STATUS_MAP[etat?.id] ?? null;
  const config = key ? colors.status[key] : { bg: colors.surfaceVariant, text: colors.text.secondary, dot: colors.text.disabled };
  const label  = ETAT_LABELS[etat?.id] ?? etat?.libelle ?? '?';
  const icon   = ETAT_ICONS[key] ?? 'ellipse-outline';

  return (
    <View style={[styles.badge, { backgroundColor: config.bg }]}>
      <Ionicons
        name={icon}
        size={11}
        color={config.text}
        style={{ marginRight: 4 }}
        accessibilityElementsHidden
      />
      <Text style={[typography.caption, { color: config.text }]}>{label}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  badge: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
    alignSelf: 'flex-start',
  },
});
