import React, { useEffect, useRef } from 'react';
import { View, Animated, Easing, StyleSheet } from 'react-native';
import { useTheme } from '../../theme';

/**
 * Placeholder animé façon shimmer (React Native Animated — compatible Expo Go)
 */
export function SkeletonBox({ width = '100%', height = 16, borderRadius = 8, style }) {
  const { colors, isDark } = useTheme();
  const opacity = useRef(new Animated.Value(0.4)).current;

  useEffect(() => {
    const anim = Animated.loop(
      Animated.sequence([
        Animated.timing(opacity, {
          toValue: 1,
          duration: 800,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: true,
        }),
        Animated.timing(opacity, {
          toValue: 0.4,
          duration: 800,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: true,
        }),
      ])
    );
    anim.start();
    return () => anim.stop();
  }, [opacity]);

  const baseColor = isDark ? colors.surfaceVariant : '#E2E8F0';

  return (
    <Animated.View
      style={[
        { width, height, borderRadius, backgroundColor: baseColor, opacity },
        style,
      ]}
    />
  );
}

/**
 * Skeleton d'une RdvCard
 */
export function RdvCardSkeleton() {
  const { colors, shadows } = useTheme();
  return (
    <View
      style={[
        styles.card,
        { backgroundColor: colors.surface, marginHorizontal: 16, marginVertical: 6 },
        shadows.sm,
      ]}
    >
      <SkeletonBox width={52} height={52} borderRadius={10} style={{ marginRight: 14 }} />
      <View style={styles.info}>
        <SkeletonBox width="60%" height={14} borderRadius={6} style={{ marginBottom: 8 }} />
        <SkeletonBox width="40%" height={12} borderRadius={6} style={{ marginBottom: 8 }} />
        <SkeletonBox width="30%" height={20} borderRadius={10} />
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    flexDirection: 'row',
    borderRadius: 12,
    padding: 14,
    alignItems: 'center',
  },
  info: { flex: 1 },
});
