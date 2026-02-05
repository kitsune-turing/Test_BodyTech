import { createSlice } from '@reduxjs/toolkit';
import { taskService } from '../services/taskService';
import { authService } from '../services/authService';

const initialState = {
  tasks: [],
  loading: false,
  error: null,
  filter: 'all', // Valores permitidos: all, pending, in_progress, done.
  pagination: {
    page: 1,
    limit: 20,
    total: 0,
    pages: 0,
  },
  search: '',
  sortBy: 'created_at',
  sortOrder: 'DESC',
};

const tasksSlice = createSlice({
  name: 'tasks',
  initialState,
  reducers: {
    setLoading: (state, action) => {
      state.loading = action.payload;
    },
    setError: (state, action) => {
      state.error = action.payload;
    },
    setTasks: (state, action) => {
      if (action.payload.items) {
        state.tasks = action.payload.items;
        state.pagination = action.payload.pagination || state.pagination;
      } else {
        state.tasks = action.payload;
      }
      state.error = null;
      state.loading = false;
    },
    addTask: (state, action) => {
      state.tasks.unshift(action.payload);
      state.error = null;
    },
    updateTaskInState: (state, action) => {
      const index = state.tasks.findIndex(task => String(task.id) === String(action.payload.id));
      if (index !== -1) {
        state.tasks[index] = action.payload;
      }
      state.error = null;
    },
    deleteTaskFromState: (state, action) => {
      state.tasks = state.tasks.filter(task => task.id !== action.payload);
      state.error = null;
    },
    setFilter: (state, action) => {
      state.filter = action.payload;
      state.pagination.page = 1; // Reinicia a la primera página al cambiar filtros.
    },
    clearError: (state) => {
      state.error = null;
    },
    setPage: (state, action) => {
      state.pagination.page = action.payload;
    },
    setLimit: (state, action) => {
      state.pagination.limit = action.payload;
      state.pagination.page = 1; // Reinicia a la primera página al cambiar el límite.
    },
    setSearch: (state, action) => {
      state.search = action.payload;
      state.pagination.page = 1; // Reinicia a la primera página al cambiar la búsqueda.
    },
    setSort: (state, action) => {
      state.sortBy = action.payload.sortBy;
      state.sortOrder = action.payload.sortOrder;
    },
    taskCreatedViaWebSocket: (state, action) => {
      const exists = state.tasks.find(task => task.id === action.payload.id);
      if (!exists) {
        state.tasks.unshift(action.payload);
        if (state.pagination.total) {
          state.pagination.total += 1;
        }
      }
    },
    taskUpdatedViaWebSocket: (state, action) => {
      const index = state.tasks.findIndex(task => String(task.id) === String(action.payload.id));
      if (index !== -1) {
        state.tasks[index] = action.payload;
      }
    },
    taskDeletedViaWebSocket: (state, action) => {
      state.tasks = state.tasks.filter(task => String(task.id) !== String(action.payload));
      if (state.pagination.total > 0) {
        state.pagination.total -= 1;
      }
    },
  },
});

export const {
  setLoading,
  setError,
  setTasks,
  addTask,
  updateTaskInState,
  deleteTaskFromState,
  setFilter,
  clearError,
  setPage,
  setLimit,
  setSearch,
  setSort,
} = tasksSlice.actions;

export const fetchTasks = () => async (dispatch, getState) => {
  dispatch(setLoading(true));
  dispatch(clearError());

  try {
    const state = getState();
    let token = state.auth.token;
    if (!token) {
      token = authService.getStoredToken();
    }

    if (!token) {
      throw new Error('No authentication token available');
    }

    const { pagination, filter, search, sortBy, sortOrder } = state.tasks;

    const options = {
      page: pagination.page,
      limit: pagination.limit,
    };

    if (filter && filter !== 'all') {
      options.status = filter === 'pending' ? 'pending' : filter === 'in_progress' ? 'in_progress' : 'done';
    }

    if (search) {
      options.search = search;
    }

    if (sortBy) {
      options.sort = sortBy;
    }
    if (sortOrder) {
      options.order = sortOrder;
    }

    const result = await taskService.getTasks(token, options);
    dispatch(setTasks(result));
    return { success: true, data: result };
  } catch (error) {
    dispatch(setError(error.message));
    dispatch(setLoading(false));
    return { success: false, error: error.message };
  }
};

export const createTask = (taskData) => async (dispatch, getState) => {
  dispatch(setLoading(true));
  dispatch(clearError());
  
  try {
    const state = getState();
    let token = state.auth.token;
    
    if (!token) {
      token = authService.getStoredToken();
    }
    
    const newTask = await taskService.createTask(token, taskData);
    dispatch(addTask(newTask));
    dispatch(setLoading(false));
    return { success: true, task: newTask };
  } catch (error) {
    dispatch(setError(error.message));
    dispatch(setLoading(false));
    return { success: false, error: error.message };
  }
};

export const updateTask = (taskId, taskData) => async (dispatch, getState) => {
  dispatch(setLoading(true));
  dispatch(clearError());
  
  try {
    const state = getState();
    let token = state.auth.token;
    
    if (!token) {
      token = authService.getStoredToken();
    }
    
    const updatedTask = await taskService.updateTask(token, taskId, taskData);
    dispatch(updateTaskInState(updatedTask));
    dispatch(setLoading(false));
    return { success: true, task: updatedTask };
  } catch (error) {
    dispatch(setError(error.message));
    dispatch(setLoading(false));
    return { success: false, error: error.message };
  }
};

export const deleteTask = (taskId) => async (dispatch, getState) => {
  dispatch(setLoading(true));
  dispatch(clearError());
  
  try {
    const state = getState();
    let token = state.auth.token;
    
    if (!token) {
      token = authService.getStoredToken();
    }
    
    await taskService.deleteTask(token, taskId);
    dispatch(deleteTaskFromState(taskId));
    dispatch(setLoading(false));
    return { success: true };
  } catch (error) {
    dispatch(setError(error.message));
    dispatch(setLoading(false));
    return { success: false, error: error.message };
  }
};

export default tasksSlice.reducer;
