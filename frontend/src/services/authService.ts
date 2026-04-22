import axios, { AxiosError } from 'axios'; // Import axios and AxiosError
import apiClient from '@/lib/axios'; // Import the configured axios instance

const API_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'; // Base URL is host:port, API prefix handled in requests

interface AuthResponse {
  access_token: string;
  token_type: string;
  expires_in: number;
}

// Define a general interface for API responses with a payload potentially nested under 'data'
interface ApiResponse<T> {
  success: boolean;
  message: string;
  data: T | { data: T } | null; // Allows for direct payload or payload nested under 'data'
}

export const login = async (username: string, password: string): Promise<void> => {
  try {
    // Use a flexible type assertion for response, assuming common API structures
    const response = await apiClient.post<ApiResponse<AuthResponse>>(
      `${API_URL}/api/auth/login`, { // Request path includes '/api' prefix
      usuario: username,
      senha: password,
    });

    let token: string | null = null;
    
    // Prioritize checking the nested structure: response.data.data.access_token
    if (response.data?.data && typeof response.data.data === 'object' && response.data.data.access_token) {
      token = response.data.data.access_token;
    } 
    // Fallback check: if nested structure fails, try direct access_token
    else if (response.data?.access_token) { 
      token = response.data.access_token;
    }
    
    // Check if token is valid (truthy and not an empty string)
    if (token && token.length > 0) { 
      localStorage.setItem('token', token);
      // console.log('Token stored successfully:', token); // For debugging
    } else {
      // If token is still missing, null, undefined, or an empty string after all checks
      let errorMsg = 'Login bem-sucedido, mas o token de acesso é inválido ou ausente.';
      if (!response.data) {
          errorMsg = 'Resposta da API inválida: objeto `data` ausente.';
      } else if (response.data.access_token === "" || response.data.access_token === null || response.data.access_token === undefined) {
          // Specific check for direct access_token being empty/null/undefined
          errorMsg = 'Login bem-sucedido, mas o token de acesso retornado está vazio ou ausente.';
      } else if (response.data.data && typeof response.data.data === 'object' && (!response.data.data.access_token || response.data.data.access_token === "")) {
          // Specific check for nested access_token being empty/null/undefined
          errorMsg = 'Login bem-sucedido, mas o token aninhado retornado está vazio ou ausente.';
      } else if (!response.data.access_token && !response.data.data) {
          // If neither direct nor nested token structure is found at all
          errorMsg = 'Login bem-sucedido, mas o token não foi encontrado na estrutura esperada da resposta.';
      }
      throw new Error(errorMsg);
    }
  } catch (error: any) {
    // Handle API errors (e.g., invalid credentials)
    if (axios.isAxiosError(error) && error.response) {
      // Try to get error message from response.data.message or response.data.error
      const message = error.response.data?.message || error.response.data?.error || 'Credenciais inválidas ou erro no servidor.';
      throw new Error(message);
    }
    // Handle network errors or other issues
    throw new Error(error.message || 'Erro de conexão com o servidor.');
  }
};

export const logout = (): void => {
  localStorage.removeItem('token');
  // In a real application, you might also want to call a logout endpoint on the backend
  // await apiClient.post(`${API_URL}/api/auth/logout`); // Example if logout endpoint exists
}

export const getToken = (): string | null => {
  // Attempt to get token from localStorage
  const token = localStorage.getItem('token');
  // Return token if it exists and is not an empty string, otherwise null
  return token ? token : null;
}

export const isAuthenticated = (): boolean => {
  // Check if token exists and is not an empty string
  return !!getToken();
}
