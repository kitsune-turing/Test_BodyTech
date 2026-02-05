import { cacheManager } from '../utils/cacheManager';
import { authService } from './authService';

const API_BASE_URL = import.meta.env.VITE_API_TASK_URL || 'http://localhost:8002/v1/api';
const CACHE_TTL = 2 * 60 * 1000; // TTL de caché: 2 minutos.

// Maneja respuestas 401 y dispara el flujo de desautenticación.
const handleUnauthorized = (response) => {
  if (response.status === 401) {
    authService.clearStoredToken();
    window.dispatchEvent(new CustomEvent('auth:unauthorized'));
    throw new Error('Authentication expired. Please login again.');
  }
};

export const taskService = {
  getTasks: async (token, options = {}) => {
    if (!token) {
      token = authService.getStoredToken();
    }

    if (!token) {
      throw new Error('No authentication token available');
    }

    const {
      page = 1,
      limit = 20,
      status = null,
      search = null,
      sort = null,
      order = null,
    } = options;

    const params = new URLSearchParams();
    params.append('page', page.toString());
    params.append('limit', limit.toString());
    if (status) params.append('status', status);
    if (search) params.append('search', search);
    if (sort) params.append('sort', sort);
    if (order) params.append('order', order);

    const cacheKey = `tasks_${token.substring(0, 10)}_${params.toString()}`;
    const cached = cacheManager.get(cacheKey);

    if (cached) {
      return cached;
    }

    const response = await fetch(`${API_BASE_URL}/tasks?${params.toString()}`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
    });

    const data = await response.json();

    if (!response.ok) {
      if (response.status === 401) {
        authService.clearStoredToken();
        window.dispatchEvent(new CustomEvent('auth:unauthorized'));
        throw new Error('Authentication expired. Please login again.');
      }
      throw new Error(data.message || 'Failed to fetch tasks');
    }

    const result = data.items ? data : { items: Array.isArray(data) ? data : (data.tasks || data.data || []), pagination: null };
    cacheManager.set(cacheKey, result, CACHE_TTL);

    return result;
  },

  getTaskById: async (token, taskId) => {
    if (!token) {
      token = authService.getStoredToken();
    }

    if (!token) {
      throw new Error('No authentication token available');
    }

    const response = await fetch(`${API_BASE_URL}/v1/api/tasks/${taskId}`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
    });

    const data = await response.json();

    if (!response.ok) {
      handleUnauthorized(response);
      throw new Error(data.message || 'Failed to fetch task');
    }

    return data.task || data.data;
  },

  createTask: async (token, taskData) => {
    if (!token) {
      token = authService.getStoredToken();
    }

    if (!token) {
      throw new Error('No authentication token available');
    }

    const response = await fetch(`${API_BASE_URL}/tasks`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(taskData),
    });

    const data = await response.json();

    if (!response.ok) {
      handleUnauthorized(response);
      throw new Error(data.message || 'Failed to create task');
    }

    const cacheKey = `tasks_${token.substring(0, 10)}`;
    cacheManager.clear(cacheKey);

    return data.task || data.data;
  },

  updateTask: async (token, taskId, taskData) => {
    if (!token) {
      token = authService.getStoredToken();
    }

    if (!token) {
      throw new Error('No authentication token available');
    }

    const response = await fetch(`${API_BASE_URL}/tasks/${taskId}`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(taskData),
    });

    let data;
    try {
      data = await response.json();
    } catch (parseError) {
      throw new Error('Error del servidor. Por favor intenta nuevamente más tarde.');
    }

    if (!response.ok) {
      handleUnauthorized(response);

      if (response.status === 403) {
        throw new Error('No tienes permisos para editar esta tarea.');
      }
      throw new Error(data.message || data.error?.message || 'Error al actualizar la tarea');
    }

    const cacheKey = `tasks_${token.substring(0, 10)}`;
    cacheManager.clear(cacheKey);

    const task = data.data || data.task || data;

    if (!task || !task.id) {
      throw new Error('Respuesta del servidor inválida');
    }

    return task;
  },

  deleteTask: async (token, taskId) => {
    if (!token) {
      token = authService.getStoredToken();
    }

    if (!token) {
      throw new Error('No authentication token available');
    }

    const response = await fetch(`${API_BASE_URL}/tasks/${taskId}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
    });

    // 204 No Content indica una eliminación exitosa.
    if (response.status === 204) {
      const cacheKey = `tasks_${token.substring(0, 10)}`;
      cacheManager.clear(cacheKey);
      return { success: true };
    }
    let errorMessage = 'Failed to delete task';
    try {
      const data = await response.json();
      errorMessage = data.message || data.error?.message || errorMessage;
    } catch (e) {
    }

    if (!response.ok) {
      handleUnauthorized(response);
      throw new Error(errorMessage);
    }

    return { success: true };
  },

  exportCSV: async (token, filters = {}) => {
    if (!token) {
      token = authService.getStoredToken();
    }

    if (!token) {
      throw new Error('No authentication token available');
    }

    const params = new URLSearchParams();
    if (filters.status) params.append('status', filters.status);
    if (filters.search) params.append('search', filters.search);

    const response = await fetch(`${API_BASE_URL}/tasks/export/csv?${params.toString()}`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
      },
    });

    if (!response.ok) {
      handleUnauthorized(response);
      throw new Error('Failed to export CSV');
    }

    return response.blob();
  },

  exportPDF: async (token, filters = {}) => {
    if (!token) {
      token = authService.getStoredToken();
    }

    if (!token) {
      throw new Error('No authentication token available');
    }

    const params = new URLSearchParams();
    if (filters.status) params.append('status', filters.status);
    if (filters.search) params.append('search', filters.search);

    const response = await fetch(`${API_BASE_URL}/tasks/export/pdf?${params.toString()}`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
      },
    });

    if (!response.ok) {
      handleUnauthorized(response);
      throw new Error('Failed to export PDF');
    }

    return response.blob();
  },
};
