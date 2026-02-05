import { taskService } from '../taskService';
import { cacheManager } from '../../utils/cacheManager';

global.fetch = jest.fn();

jest.mock('../../utils/cacheManager', () => ({
  cacheManager: {
    get: jest.fn(),
    set: jest.fn(),
    clear: jest.fn(),
  },
}));

describe('taskService', () => {
  const mockToken = 'test-token-123456';
  const mockTask = {
    id: 1,
    title: 'Test Task',
    description: 'Test Description',
    status: 'pending',
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('getTasks', () => {
    it('returns cached tasks if available', async () => {
      const cachedTasks = [mockTask];
      cacheManager.get.mockReturnValue(cachedTasks);

      const result = await taskService.getTasks(mockToken);

      expect(result).toEqual(cachedTasks);
      expect(cacheManager.get).toHaveBeenCalledWith('tasks_test-token');
      expect(fetch).not.toHaveBeenCalled();
    });

    it('fetches tasks from API when cache is empty', async () => {
      cacheManager.get.mockReturnValue(null);
      const mockTasks = [mockTask, { ...mockTask, id: 2 }];

      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockTasks,
      });

      const result = await taskService.getTasks(mockToken);

      expect(result).toEqual(mockTasks);
      expect(fetch).toHaveBeenCalledWith(
        'http://localhost:8002/v1/api/tasks',
        expect.objectContaining({
          method: 'GET',
          headers: expect.objectContaining({
            Authorization: `Bearer ${mockToken}`,
          }),
        })
      );
      expect(cacheManager.set).toHaveBeenCalledWith(
        'tasks_test-token',
        mockTasks,
        120000
      );
    });

    it('handles tasks array response', async () => {
      cacheManager.get.mockReturnValue(null);
      const mockTasks = [mockTask];

      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockTasks,
      });

      const result = await taskService.getTasks(mockToken);
      expect(result).toEqual(mockTasks);
    });

    it('handles tasks object response with tasks property', async () => {
      cacheManager.get.mockReturnValue(null);
      const mockTasks = [mockTask];

      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ tasks: mockTasks }),
      });

      const result = await taskService.getTasks(mockToken);
      expect(result).toEqual(mockTasks);
    });

    it('handles tasks object response with data property', async () => {
      cacheManager.get.mockReturnValue(null);
      const mockTasks = [mockTask];

      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: mockTasks }),
      });

      const result = await taskService.getTasks(mockToken);
      expect(result).toEqual(mockTasks);
    });

    it('throws error on failed API call', async () => {
      cacheManager.get.mockReturnValue(null);

      global.fetch.mockResolvedValueOnce({
        ok: false,
        json: async () => ({ message: 'Unauthorized' }),
      });

      await expect(taskService.getTasks(mockToken)).rejects.toThrow('Unauthorized');
    });
  });

  describe('getTaskById', () => {
    it('fetches single task successfully', async () => {
      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ task: mockTask }),
      });

      const result = await taskService.getTaskById(mockToken, 1);

      expect(result).toEqual(mockTask);
      expect(fetch).toHaveBeenCalledWith(
        'http://localhost:8002/v1/api/tasks/1',
        expect.objectContaining({
          method: 'GET',
        })
      );
    });

    it('handles data property in response', async () => {
      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: mockTask }),
      });

      const result = await taskService.getTaskById(mockToken, 1);
      expect(result).toEqual(mockTask);
    });

    it('throws error when task not found', async () => {
      global.fetch.mockResolvedValueOnce({
        ok: false,
        json: async () => ({ message: 'Task not found' }),
      });

      await expect(taskService.getTaskById(mockToken, 999)).rejects.toThrow(
        'Task not found'
      );
    });
  });

  describe('createTask', () => {
    it('creates task successfully and invalidates cache', async () => {
      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ task: mockTask }),
      });

      const result = await taskService.createTask(mockToken, {
        title: 'Test Task',
        status: 'pending',
      });

      expect(result).toEqual(mockTask);
      expect(fetch).toHaveBeenCalledWith(
        'http://localhost:8002/v1/api/tasks',
        expect.objectContaining({
          method: 'POST',
          body: JSON.stringify({ title: 'Test Task', status: 'pending' }),
        })
      );
      expect(cacheManager.clear).toHaveBeenCalledWith('tasks_test-token');
    });

    it('handles data property in response', async () => {
      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: mockTask }),
      });

      const result = await taskService.createTask(mockToken, {
        title: 'Test Task',
      });
      expect(result).toEqual(mockTask);
    });

    it('throws error on failed creation', async () => {
      global.fetch.mockResolvedValueOnce({
        ok: false,
        json: async () => ({ message: 'Validation error' }),
      });

      await expect(
        taskService.createTask(mockToken, { title: '' })
      ).rejects.toThrow('Validation error');
    });
  });

  describe('updateTask', () => {
    it('updates task successfully and invalidates cache', async () => {
      const updatedTask = { ...mockTask, title: 'Updated Task' };
      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: updatedTask }),
      });

      const result = await taskService.updateTask(mockToken, 1, {
        title: 'Updated Task',
      });

      expect(result).toEqual(updatedTask);
      expect(fetch).toHaveBeenCalledWith(
        'http://localhost:8002/v1/api/tasks/1',
        expect.objectContaining({
          method: 'PUT',
        })
      );
      expect(cacheManager.clear).toHaveBeenCalledWith('tasks_test-token');
    });

    it('throws error on permission denied (403)', async () => {
      global.fetch.mockResolvedValueOnce({
        ok: false,
        status: 403,
        json: async () => ({ message: 'Forbidden' }),
      });

      await expect(
        taskService.updateTask(mockToken, 1, { title: 'Updated' })
      ).rejects.toThrow(/no tienes permisos/i);
    });

    it('throws error on invalid response', async () => {
      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({}),
      });

      await expect(
        taskService.updateTask(mockToken, 1, { title: 'Updated' })
      ).rejects.toThrow(/respuesta del servidor inválida/i);
    });

    it('throws error on invalid JSON', async () => {
      global.fetch.mockResolvedValueOnce({
        ok: false,
        json: async () => {
          throw new Error('Invalid JSON');
        },
      });

      await expect(
        taskService.updateTask(mockToken, 1, { title: 'Updated' })
      ).rejects.toThrow(/error del servidor/i);
    });
  });

  describe('deleteTask', () => {
    it('deletes task successfully with 204 status', async () => {
      global.fetch.mockResolvedValueOnce({
        ok: true,
        status: 204,
      });

      const result = await taskService.deleteTask(mockToken, 1);

      expect(result.success).toBe(true);
      expect(fetch).toHaveBeenCalledWith(
        'http://localhost:8002/v1/api/tasks/1',
        expect.objectContaining({
          method: 'DELETE',
        })
      );
      expect(cacheManager.clear).toHaveBeenCalledWith('tasks_test-token');
    });

    it('throws error on failed deletion', async () => {
      global.fetch.mockResolvedValueOnce({
        ok: false,
        status: 404,
        json: async () => ({ message: 'Task not found' }),
      });

      await expect(taskService.deleteTask(mockToken, 999)).rejects.toThrow(
        'Task not found'
      );
    });

    it('handles non-JSON error response', async () => {
      global.fetch.mockResolvedValueOnce({
        ok: false,
        status: 500,
        json: async () => {
          throw new Error('Not JSON');
        },
      });

      await expect(taskService.deleteTask(mockToken, 1)).rejects.toThrow(
        'Failed to delete task'
      );
    });
  });
});
