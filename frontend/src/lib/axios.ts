import axios, { AxiosError } from 'axios'; // Import axios and AxiosError
import { useAuthStore } from '@/stores/auth'; // Assuming auth store is needed for token

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'; // Set base URL to host:port only

const apiClient = axios.create({
  baseURL: API_BASE_URL, // Base URL is now http://localhost:8000
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

apiClient.interceptors.request.use(
  (config) => {
    const authStore = useAuthStore();
    const token = authStore.token; 
    
    // Add Authorization header if token exists and it's not a public route (login/refresh)
    // Routes like /api/auth/login should not have the auth header.
    // The check for specific URLs might be better handled by router guards or explicit service functions.
    // For now, checking if the URL path starts with /api/auth or is explicitly public route if meta is set.
    const isPublicRoute = config.url?.startsWith('/api/auth/');

    if (token && !isPublicRoute) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    
    // Ensure API prefix is correctly handled. 
    // If baseURL is http://localhost:8000, requests like /api/clientes are correct.
    // If baseURL already has /api, requests should be /clientes.
    // Current setup: baseURL = http://localhost:8000. Request path = /api/clientes. Full URL = http://localhost:8000/api/clientes. This is correct.
    
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// You might also want to add a response interceptor to handle 401 Unauthorized errors
// apiClient.interceptors.response.use(
//   (response) => response,
//   (error) => {
//     if (axios.isAxiosError(error) && error.response && error.response.status === 401) {
//       // Handle unauthorized: redirect to login, clear token, etc.
//       // Example: router.push('/login'); or use authStore.logout()
//     }
//     return Promise.reject(error);
//   }
// );

export default apiClient;
