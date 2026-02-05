const API_BASE_URL = import.meta.env.VITE_API_AUTH_URL || 'http://localhost:8001/v1/api';

export const authService = {
  register: async (userData) => {
    try {
      const response = await fetch(`${API_BASE_URL}/register`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(userData),
      });

      let data;
      try {
        data = await response.json();
      } catch (parseError) {
        throw new Error('Error del servidor. Por favor intenta nuevamente más tarde.');
      }

      if (!response.ok) {
        if (response.status === 409) {
          throw new Error('Este email ya está registrado. Por favor inicia sesión o usa otro email.');
        }
        throw new Error(data.message || 'Error al registrar el usuario. Intenta nuevamente.');
      }

      return {
        user: data,
        email: data.email,
        id: data.id,
      };
    } catch (error) {
      throw error;
    }
  },

  login: async (email, password) => {
    try {
      const response = await fetch(`${API_BASE_URL}/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email, password }),
      });

      let data;
      try {
        data = await response.json();
      } catch (parseError) {
        throw new Error('Error del servidor. Por favor intenta nuevamente más tarde.');
      }

      if (!response.ok) {
        if (response.status === 401) {
          throw new Error('Email o contraseña incorrectos. Verifica tus datos e intenta nuevamente.');
        }
        throw new Error(data.message || 'Error al iniciar sesión. Intenta nuevamente.');
      }

      if (data.token) {
        localStorage.setItem('authToken', data.token);
        localStorage.setItem('user', JSON.stringify(data.user || { email }));
      }

      return data;
    } catch (error) {
      throw error;
    }
  },

  logout: async (token) => {
    try {
      await fetch(`${API_BASE_URL}/logout`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
    } catch (error) {
    }

    localStorage.removeItem('authToken');
    localStorage.removeItem('user');
  },

  getStoredToken: () => localStorage.getItem('authToken'),
  
  clearStoredToken: () => {
    localStorage.removeItem('authToken');
    localStorage.removeItem('user');
  },
  
  getStoredUser: () => {
    const user = localStorage.getItem('user');
    return user ? JSON.parse(user) : null;
  },

  isAuthenticated: () => !!localStorage.getItem('authToken'),
};
