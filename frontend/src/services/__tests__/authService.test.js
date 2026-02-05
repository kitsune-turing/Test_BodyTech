import { authService } from '../authService';

global.fetch = jest.fn();

describe('authService', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    localStorage.clear();
  });

  describe('register', () => {
    it('registers user successfully', async () => {
      const mockUser = {
        id: 1,
        email: 'test@example.com',
        name: 'Test User',
      };

      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockUser,
      });

      const result = await authService.register({
        email: 'test@example.com',
        password: 'password123',
        name: 'Test User',
      });

      expect(result.user).toEqual(mockUser);
      expect(result.email).toBe(mockUser.email);
      expect(result.id).toBe(mockUser.id);
      expect(fetch).toHaveBeenCalledWith(
        'http://localhost:8001/v1/api/register',
        expect.objectContaining({
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
        })
      );
    });

    it('throws error on duplicate email (409)', async () => {
      global.fetch.mockResolvedValueOnce({
        ok: false,
        status: 409,
        json: async () => ({ message: 'Email already exists' }),
      });

      await expect(
        authService.register({
          email: 'existing@example.com',
          password: 'password123',
        })
      ).rejects.toThrow(/este email ya está registrado/i);
    });

    it('throws error on server error', async () => {
      global.fetch.mockResolvedValueOnce({
        ok: false,
        status: 500,
        json: async () => ({ message: 'Server error' }),
      });

      await expect(
        authService.register({
          email: 'test@example.com',
          password: 'password123',
        })
      ).rejects.toThrow();
    });

    it('throws error on invalid JSON response', async () => {
      global.fetch.mockResolvedValueOnce({
        ok: false,
        status: 500,
        json: async () => {
          throw new Error('Invalid JSON');
        },
      });

      await expect(
        authService.register({
          email: 'test@example.com',
          password: 'password123',
        })
      ).rejects.toThrow(/error del servidor/i);
    });
  });

  describe('login', () => {
    it('logs in user successfully and stores token', async () => {
      const mockResponse = {
        token: 'test-token',
        user: { id: 1, email: 'test@example.com' },
      };

      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockResponse,
      });

      const result = await authService.login('test@example.com', 'password123');

      expect(result.token).toBe('test-token');
      expect(result.user).toEqual(mockResponse.user);
      expect(localStorage.getItem('authToken')).toBe('test-token');
      expect(localStorage.getItem('user')).toBe(JSON.stringify(mockResponse.user));
    });

    it('stores email if user object not provided', async () => {
      const mockResponse = {
        token: 'test-token',
      };

      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockResponse,
      });

      await authService.login('test@example.com', 'password123');

      const storedUser = JSON.parse(localStorage.getItem('user'));
      expect(storedUser.email).toBe('test@example.com');
    });

    it('throws error on invalid credentials (401)', async () => {
      global.fetch.mockResolvedValueOnce({
        ok: false,
        status: 401,
        json: async () => ({ message: 'Invalid credentials' }),
      });

      await expect(
        authService.login('test@example.com', 'wrongpassword')
      ).rejects.toThrow(/email o contraseña incorrectos/i);
    });

    it('throws error on server error', async () => {
      global.fetch.mockResolvedValueOnce({
        ok: false,
        status: 500,
        json: async () => ({ message: 'Server error' }),
      });

      await expect(
        authService.login('test@example.com', 'password123')
      ).rejects.toThrow();
    });

    it('throws error on invalid JSON response', async () => {
      global.fetch.mockResolvedValueOnce({
        ok: false,
        status: 500,
        json: async () => {
          throw new Error('Invalid JSON');
        },
      });

      await expect(
        authService.login('test@example.com', 'password123')
      ).rejects.toThrow(/error del servidor/i);
    });
  });

  describe('logout', () => {
    it('calls logout endpoint and clears storage', async () => {
      localStorage.setItem('authToken', 'test-token');
      localStorage.setItem('user', JSON.stringify({ email: 'test@example.com' }));

      global.fetch.mockResolvedValueOnce({
        ok: true,
      });

      await authService.logout('test-token');

      expect(fetch).toHaveBeenCalledWith(
        'http://localhost:8001/v1/api/logout',
        expect.objectContaining({
          method: 'POST',
          headers: expect.objectContaining({
            Authorization: 'Bearer test-token',
          }),
        })
      );

      expect(localStorage.getItem('authToken')).toBeNull();
      expect(localStorage.getItem('user')).toBeNull();
    });

    it('clears storage even if API call fails', async () => {
      localStorage.setItem('authToken', 'test-token');
      localStorage.setItem('user', JSON.stringify({ email: 'test@example.com' }));

      global.fetch.mockRejectedValueOnce(new Error('Network error'));

      await authService.logout('test-token');

      expect(localStorage.getItem('authToken')).toBeNull();
      expect(localStorage.getItem('user')).toBeNull();
    });
  });

  describe('utility methods', () => {
    it('getStoredToken returns token from localStorage', () => {
      localStorage.setItem('authToken', 'test-token');
      expect(authService.getStoredToken()).toBe('test-token');
    });

    it('getStoredToken returns null if no token', () => {
      expect(authService.getStoredToken()).toBeNull();
    });

    it('getStoredUser returns parsed user from localStorage', () => {
      const user = { id: 1, email: 'test@example.com' };
      localStorage.setItem('user', JSON.stringify(user));
      expect(authService.getStoredUser()).toEqual(user);
    });

    it('getStoredUser returns null if no user', () => {
      expect(authService.getStoredUser()).toBeNull();
    });

    it('isAuthenticated returns true if token exists', () => {
      localStorage.setItem('authToken', 'test-token');
      expect(authService.isAuthenticated()).toBe(true);
    });

    it('isAuthenticated returns false if no token', () => {
      expect(authService.isAuthenticated()).toBe(false);
    });
  });
});
