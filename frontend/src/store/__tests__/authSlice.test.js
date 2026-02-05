import authReducer, {
  setLoading,
  setError,
  loginSuccess,
  registerSuccess,
  logout,
  clearError,
  initializeAuth,
  login,
  logoutUser,
} from '../authSlice';
import { authService } from '../../services/authService';
import { configureStore } from '@reduxjs/toolkit';

jest.mock('../../services/authService');

describe('authSlice', () => {
  const initialState = {
    user: null,
    token: null,
    isAuthenticated: false,
    loading: false,
    error: null,
  };

  describe('reducers', () => {
    it('should return the initial state', () => {
      expect(authReducer(undefined, { type: 'unknown' })).toEqual(initialState);
    });

    it('should handle setLoading', () => {
      const actual = authReducer(initialState, setLoading(true));
      expect(actual.loading).toBe(true);
    });

    it('should handle setError', () => {
      const error = 'Test error';
      const actual = authReducer(initialState, setError(error));
      expect(actual.error).toBe(error);
    });

    it('should handle clearError', () => {
      const stateWithError = { ...initialState, error: 'Some error' };
      const actual = authReducer(stateWithError, clearError());
      expect(actual.error).toBe(null);
    });

    it('should handle loginSuccess', () => {
      const payload = {
        user: { id: 1, email: 'test@example.com', name: 'Test User' },
        token: 'test-token',
      };
      const actual = authReducer(initialState, loginSuccess(payload));

      expect(actual.user).toEqual(payload.user);
      expect(actual.token).toBe(payload.token);
      expect(actual.isAuthenticated).toBe(true);
      expect(actual.error).toBe(null);
      expect(actual.loading).toBe(false);
    });

    it('should handle loginSuccess with only email', () => {
      const payload = {
        email: 'test@example.com',
        token: 'test-token',
      };
      const actual = authReducer(initialState, loginSuccess(payload));

      expect(actual.user).toEqual({ email: payload.email });
      expect(actual.token).toBe(payload.token);
      expect(actual.isAuthenticated).toBe(true);
    });

    it('should handle registerSuccess', () => {
      const payload = {
        user: { id: 1, email: 'test@example.com', name: 'Test User' },
        token: 'test-token',
      };
      const actual = authReducer(initialState, registerSuccess(payload));

      expect(actual.user).toEqual(payload.user);
      expect(actual.token).toBe(payload.token);
      expect(actual.isAuthenticated).toBe(true);
      expect(actual.error).toBe(null);
      expect(actual.loading).toBe(false);
    });

    it('should handle logout', () => {
      const authenticatedState = {
        user: { id: 1, email: 'test@example.com' },
        token: 'test-token',
        isAuthenticated: true,
        loading: false,
        error: null,
      };
      const actual = authReducer(authenticatedState, logout());

      expect(actual).toEqual(initialState);
    });

    it('should handle initializeAuth', () => {
      const payload = {
        token: 'stored-token',
        user: { id: 1, email: 'test@example.com' },
      };
      const actual = authReducer(initialState, initializeAuth(payload));

      expect(actual.token).toBe(payload.token);
      expect(actual.user).toEqual(payload.user);
      expect(actual.isAuthenticated).toBe(true);
    });

    it('should not update state on initializeAuth without token', () => {
      const payload = {
        user: { id: 1, email: 'test@example.com' },
      };
      const actual = authReducer(initialState, initializeAuth(payload));

      expect(actual).toEqual(initialState);
    });
  });

  describe('async thunks', () => {
    let store;

    beforeEach(() => {
      store = configureStore({
        reducer: {
          auth: authReducer,
        },
      });
      jest.clearAllMocks();
    });

    describe('login', () => {
      it('dispatches loginSuccess on successful login', async () => {
        const mockResponse = {
          token: 'test-token',
          user: { id: 1, email: 'test@example.com' },
        };
        authService.login.mockResolvedValue(mockResponse);

        const result = await store.dispatch(login('test@example.com', 'password'));

        expect(result.success).toBe(true);
        expect(authService.login).toHaveBeenCalledWith('test@example.com', 'password');

        const state = store.getState().auth;
        expect(state.user).toEqual(mockResponse.user);
        expect(state.token).toBe(mockResponse.token);
        expect(state.isAuthenticated).toBe(true);
        expect(state.loading).toBe(false);
        expect(state.error).toBe(null);
      });

      it('dispatches setError on failed login', async () => {
        const errorMessage = 'Invalid credentials';
        authService.login.mockRejectedValue(new Error(errorMessage));

        const result = await store.dispatch(login('test@example.com', 'wrong-password'));

        expect(result.success).toBe(false);
        expect(result.error).toBe(errorMessage);

        const state = store.getState().auth;
        expect(state.error).toBe(errorMessage);
        expect(state.loading).toBe(false);
        expect(state.isAuthenticated).toBe(false);
      });
    });

    describe('logoutUser', () => {
      it('dispatches logout action', async () => {
        const initialAuthState = {
          user: { id: 1, email: 'test@example.com' },
          token: 'test-token',
          isAuthenticated: true,
          loading: false,
          error: null,
        };

        store = configureStore({
          reducer: {
            auth: authReducer,
          },
          preloadedState: {
            auth: initialAuthState,
          },
        });

        authService.logout.mockResolvedValue();

        await store.dispatch(logoutUser());

        expect(authService.logout).toHaveBeenCalledWith('test-token');

        const state = store.getState().auth;
        expect(state.user).toBe(null);
        expect(state.token).toBe(null);
        expect(state.isAuthenticated).toBe(false);
      });

      it('dispatches logout even if service fails', async () => {
        const initialAuthState = {
          user: { id: 1, email: 'test@example.com' },
          token: 'test-token',
          isAuthenticated: true,
          loading: false,
          error: null,
        };

        store = configureStore({
          reducer: {
            auth: authReducer,
          },
          preloadedState: {
            auth: initialAuthState,
          },
        });

        authService.logout.mockRejectedValue(new Error('Network error'));

        await store.dispatch(logoutUser());

        const state = store.getState().auth;
        expect(state.user).toBe(null);
        expect(state.token).toBe(null);
        expect(state.isAuthenticated).toBe(false);
      });
    });
  });
});
