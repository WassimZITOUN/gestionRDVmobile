import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';

// URL du serveur Node.js
// En SSH/remote : remplacer par l'URL ngrok obtenue avec `ngrok http 3000`
// Exemple : export const API_BASE_URL = 'https://xxxx-xx-xx.ngrok-free.app';
// En local (même réseau Wi-Fi) : utiliser l'IP LAN de la machine
export const API_BASE_URL = 'http://192.168.1.150:3000'; 

const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  timeout: 10000,
});

// Intercepteur de requêtes : ajoute le token JWT
apiClient.interceptors.request.use(
  async (config) => {
    const token = await AsyncStorage.getItem('jwt_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

export default apiClient;
