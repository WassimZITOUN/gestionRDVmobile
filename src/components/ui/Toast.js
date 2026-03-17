import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import ToastMessage from 'react-native-toast-message';

// Configuration des types custom pour react-native-toast-message
export const toastConfig = {
  success: ({ text1, text2 }) => (
    <View style={[styles.toast, styles.success]}>
      <Ionicons name="checkmark-circle" size={22} color="#2E7D32" style={styles.icon} />
      <View style={styles.texts}>
        {text1 && <Text style={[styles.title, { color: '#1B5E20' }]}>{text1}</Text>}
        {text2 && <Text style={[styles.subtitle, { color: '#388E3C' }]}>{text2}</Text>}
      </View>
    </View>
  ),
  error: ({ text1, text2 }) => (
    <View style={[styles.toast, styles.error]}>
      <Ionicons name="close-circle" size={22} color="#C62828" style={styles.icon} />
      <View style={styles.texts}>
        {text1 && <Text style={[styles.title, { color: '#B71C1C' }]}>{text1}</Text>}
        {text2 && <Text style={[styles.subtitle, { color: '#D32F2F' }]}>{text2}</Text>}
      </View>
    </View>
  ),
  info: ({ text1, text2 }) => (
    <View style={[styles.toast, styles.info]}>
      <Ionicons name="information-circle" size={22} color="#1565C0" style={styles.icon} />
      <View style={styles.texts}>
        {text1 && <Text style={[styles.title, { color: '#0D47A1' }]}>{text1}</Text>}
        {text2 && <Text style={[styles.subtitle, { color: '#1976D2' }]}>{text2}</Text>}
      </View>
    </View>
  ),
};

// Helper pour afficher facilement
export const showToast = {
  success: (text1, text2) =>
    ToastMessage.show({ type: 'success', text1, text2, visibilityTime: 3000, position: 'top' }),
  error: (text1, text2) =>
    ToastMessage.show({ type: 'error', text1, text2, visibilityTime: 4000, position: 'top' }),
  info: (text1, text2) =>
    ToastMessage.show({ type: 'info', text1, text2, visibilityTime: 3000, position: 'top' }),
};

const styles = StyleSheet.create({
  toast: {
    flexDirection: 'row',
    alignItems: 'center',
    marginHorizontal: 16,
    paddingHorizontal: 16,
    paddingVertical: 12,
    borderRadius: 12,
    minWidth: '80%',
    shadowColor: '#000',
    shadowOpacity: 0.12,
    shadowRadius: 8,
    shadowOffset: { width: 0, height: 3 },
    elevation: 6,
  },
  success: { backgroundColor: '#E8F5E9', borderLeftWidth: 4, borderLeftColor: '#43A047' },
  error:   { backgroundColor: '#FFEBEE', borderLeftWidth: 4, borderLeftColor: '#E53935' },
  info:    { backgroundColor: '#E3F2FD', borderLeftWidth: 4, borderLeftColor: '#1E88E5' },
  icon:    { marginRight: 10 },
  texts:   { flex: 1 },
  title:   { fontSize: 14, fontWeight: '700' },
  subtitle: { fontSize: 12, fontWeight: '400', marginTop: 2 },
});
