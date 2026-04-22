import { defineStore } from 'pinia'
import { ref } from 'vue'
import { login as authServiceLogin, logout as authServiceLogout, getToken as authServiceGetToken } from '@/services/authService'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(authServiceGetToken())
  const user = ref<any | null>(null) // You can define a more specific user interface

  const isAuthenticated = ref<boolean>(!!token.value)

  const login = async (username: string, password: string) => {
    try {
      // Call authServiceLogin which handles API call and localStorage.setItem
      await authServiceLogin(username, password); 
      
      // After authServiceLogin succeeds, re-fetch token and update store state explicitly
      token.value = authServiceGetToken(); // Retrieve token from localStorage
      
      if (token.value && token.value.length > 0) { // Check if token is truthy and non-empty
        isAuthenticated.value = true; // Explicitly set to true since token was found
        // console.log('Authentication state updated in store, token:', token.value); // For debugging
        // Optionally fetch user data
        // user.value = await fetchUserProfile(); 
      } else {
        // If token is still not found or is empty after login attempt
        // Clear localStorage just in case it has a stale empty token
        authServiceLogout(); // Ensure local storage is clean
        token.value = null;
        isAuthenticated.value = false;
        throw new Error('Login bem-sucedido, mas o token de acesso não pôde ser recuperado ou está vazio.');
      }
    } catch (error: any) {
      // Clear any stale token if login fails
      authServiceLogout(); // Ensure local storage is clean
      token.value = null;
      isAuthenticated.value = false;
      // Rethrow the error for Login.vue to catch and display
      throw error;
    }
  };

  const logout = () => {
    authServiceLogout(); // authServiceLogout will remove token from localStorage
    token.value = null;
    user.value = null;
    isAuthenticated.value = false;
  };

  // Future: Function to check if token is valid/expired
  // const checkAuth = async () => { ... }

  return {
    token,
    user,
    isAuthenticated,
    login,
    logout,
  };
});
