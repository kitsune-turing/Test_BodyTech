import { createSlice } from '@reduxjs/toolkit';
import { authService } from '../services/authService';

const initialState = {
  user: null,
  token: null,
  isAuthenticated: false,
  loading: false,
  error: null,
};

const authSlice = createSlice({
  name: 'auth',
  initialState,
  reducers: {
    setLoading: (state, action) => {
      state.loading = action.payload;
    },
    setError: (state, action) => {
      state.error = action.payload;
    },
    loginSuccess: (state, action) => {
      state.user = action.payload.user || { email: action.payload.email };
      state.token = action.payload.token;
      state.isAuthenticated = true;
      state.error = null;
      state.loading = false;
    },
    registerSuccess: (state, action) => {
      state.user = action.payload.user || { email: action.payload.email };
      state.token = action.payload.token;
      state.isAuthenticated = true;
      state.error = null;
      state.loading = false;
    },
    logout: (state) => {
      state.user = null;
      state.token = null;
      state.isAuthenticated = false;
      state.error = null;
      state.loading = false;
    },
    clearError: (state) => {
      state.error = null;
    },
    initializeAuth: (state, action) => {
      if (action.payload.token && action.payload.user) {
        state.token = action.payload.token;
        state.user = action.payload.user;
        state.isAuthenticated = true;
      }
    },
  },
});

export const { setLoading, setError, loginSuccess, registerSuccess, logout, clearError, initializeAuth } = authSlice.actions;

export const login = (email, password) => async (dispatch) => {
  dispatch(setLoading(true));
  dispatch(clearError());
  
  try {
    const result = await authService.login(email, password);
    dispatch(loginSuccess(result));
    dispatch(setLoading(false));
    return { success: true };
  } catch (error) {
    dispatch(setError(error.message));
    dispatch(setLoading(false));
    return { success: false, error: error.message };
  }
};

export const register = (userData) => async (dispatch) => {
  dispatch(setLoading(true));
  dispatch(clearError());
  
  try {
    const result = await authService.register(userData);
    dispatch(setLoading(false));
    return { success: true, data: result };
  } catch (error) {
    dispatch(setError(error.message));
    dispatch(setLoading(false));
    return { success: false, error: error.message };
  }
};

export const logoutUser = () => async (dispatch, getState) => {
  const state = getState();
  const token = state.auth.token;

  try {
    await authService.logout(token);
  } catch (error) {
  }

  dispatch(logout());
};

export default authSlice.reducer;
