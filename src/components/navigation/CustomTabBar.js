import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet, Platform } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import * as Haptics from 'expo-haptics';
import { useTheme } from '../../theme';

const TAB_ICONS = {
  MesRdvTab:   { active: 'calendar',      inactive: 'calendar-outline' },
  NouveauRdv:  { active: 'add-circle',    inactive: 'add-circle-outline' },
};

export default function CustomTabBar({ state, descriptors, navigation }) {
  const { colors, typography, shadows } = useTheme();

  return (
    <View
      style={[
        styles.bar,
        {
          backgroundColor: colors.surface,
          borderTopColor: colors.border,
        },
        shadows.lg,
      ]}
    >
      {state.routes.map((route, index) => {
        const { options } = descriptors[route.key];
        const label = options.tabBarLabel ?? options.title ?? route.name;
        const isFocused = state.index === index;
        const icons = TAB_ICONS[route.name] ?? { active: 'ellipse', inactive: 'ellipse-outline' };

        const onPress = async () => {
          const event = navigation.emit({ type: 'tabPress', target: route.key, canPreventDefault: true });
          if (!isFocused && !event.defaultPrevented) {
            try { await Haptics.selectionAsync(); } catch {}
            navigation.navigate(route.name);
          }
        };

        return (
          <TouchableOpacity
            key={route.key}
            onPress={onPress}
            activeOpacity={0.8}
            accessibilityRole="tab"
            accessibilityState={{ selected: isFocused }}
            accessibilityLabel={label}
            style={styles.tab}
          >
            <Ionicons
              name={isFocused ? icons.active : icons.inactive}
              size={26}
              color={isFocused ? colors.primary : colors.text.disabled}
            />
            <Text
              style={[
                typography.small,
                {
                  color: isFocused ? colors.primary : colors.text.disabled,
                  marginTop: 3,
                  fontWeight: isFocused ? '700' : '500',
                },
              ]}
            >
              {label}
            </Text>
          </TouchableOpacity>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  bar: {
    flexDirection: 'row',
    borderTopWidth: 1,
    paddingBottom: Platform.OS === 'ios' ? 20 : 8,
    paddingTop: 10,
    borderTopLeftRadius: 16,
    borderTopRightRadius: 16,
  },
  tab: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
