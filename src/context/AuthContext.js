import React, { createContext, useContext, useState, useEffect } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import apiClient from '../api/client';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [token, setToken] = useState(null);
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  // Au démarrage, charger le token stocké
  useEffect(() => {
    (async () => {
      try {
        const storedToken = await AsyncStorage.getItem('jwt_token');
        if (storedToken) {
          setToken(storedToken);
          const response = await apiClient.get('/api/me');
          setUser(response.data);
        }
      } catch {
        // Token invalide ou expiré
        await AsyncStorage.removeItem('jwt_token');
        setToken(null);
        setUser(null);
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  const login = async (email, password) => {
    const response = await apiClient.post('/api/login', { email, password });
    const { token: jwt } = response.data;

    await AsyncStorage.setItem('jwt_token', jwt);
    setToken(jwt);

    const meResponse = await apiClient.get('/api/me');
    setUser(meResponse.data);
  };

  const logout = async () => {
    await AsyncStorage.removeItem('jwt_token');
    setToken(null);
    setUser(null);
  };

  return (
    <AuthContext.Provider value={{ token, user, loading, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  return useContext(AuthContext);
}
