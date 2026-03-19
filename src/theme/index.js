import React, { createContext, useContext, useMemo } from 'react';
import { useColorScheme } from 'react-native';
import { lightColors, darkColors } from './colors';
import spacing from './spacing';
import typography from './typography';
import shadows from './shadows';

const ThemeContext = createContext(null);

export function ThemeProvider({ children }) {
  const scheme = useColorScheme();
  const isDark = scheme === 'dark';

  const theme = useMemo(() => ({
    isDark,
    colors: isDark ? darkColors : lightColors,
    spacing,
    typography,
    shadows,
  }), [isDark]);

  return (
    <ThemeContext.Provider value={theme}>
      {children}
    </ThemeContext.Provider>
  );
}

export function useTheme() {
  const theme = useContext(ThemeContext);
  if (!theme) throw new Error('useTheme must be used within ThemeProvider');
  return theme;
}

export { spacing, typography, shadows };
export { lightColors, darkColors } from './colors';
