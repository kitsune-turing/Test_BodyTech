import tasksReducer, {
  setLoading,
  setError,
  setTasks,
  addTask,
  updateTaskInState,
  deleteTaskFromState,
  setFilter,
  clearError,
  fetchTasks,
  createTask,
  updateTask,
  deleteTask,
} from '../tasksSlice';
import { taskService } from '../../services/taskService';
import { configureStore } from '@reduxjs/toolkit';
import authReducer from '../authSlice';

jest.mock('../../services/taskService');

describe('tasksSlice', () => {
  const initialState = {
    tasks: [],
    loading: false,
    error: null,
    filter: 'all',
  };

  const mockTask = {
    id: 1,
    title: 'Test Task',
    description: 'Test Description',
    status: 'pending',
  };

  describe('reducers', () => {
    it('should return the initial state', () => {
      expect(tasksReducer(undefined, { type: 'unknown' })).toEqual(initialState);
    });

    it('should handle setLoading', () => {
      const actual = tasksReducer(initialState, setLoading(true));
      expect(actual.loading).toBe(true);
    });

    it('should handle setError', () => {
      const error = 'Test error';
      const actual = tasksReducer(initialState, setError(error));
      expect(actual.error).toBe(error);
    });

    it('should handle clearError', () => {
      const stateWithError = { ...initialState, error: 'Some error' };
      const actual = tasksReducer(stateWithError, clearError());
      expect(actual.error).toBe(null);
    });

    it('should handle setTasks', () => {
      const tasks = [mockTask, { ...mockTask, id: 2 }];
      const actual = tasksReducer(initialState, setTasks(tasks));

      expect(actual.tasks).toEqual(tasks);
      expect(actual.error).toBe(null);
      expect(actual.loading).toBe(false);
    });

    it('should handle addTask', () => {
      const actual = tasksReducer(initialState, addTask(mockTask));

      expect(actual.tasks).toHaveLength(1);
      expect(actual.tasks[0]).toEqual(mockTask);
      expect(actual.error).toBe(null);
    });

    it('should add task to beginning of array', () => {
      const stateWithTask = { ...initialState, tasks: [{ id: 2, title: 'Existing Task' }] };
      const actual = tasksReducer(stateWithTask, addTask(mockTask));

      expect(actual.tasks).toHaveLength(2);
      expect(actual.tasks[0]).toEqual(mockTask);
      expect(actual.tasks[1].id).toBe(2);
    });

    it('should handle updateTaskInState', () => {
      const stateWithTask = { ...initialState, tasks: [mockTask] };
      const updatedTask = { ...mockTask, title: 'Updated Title' };
      const actual = tasksReducer(stateWithTask, updateTaskInState(updatedTask));

      expect(actual.tasks[0].title).toBe('Updated Title');
      expect(actual.error).toBe(null);
    });

    it('should handle updateTaskInState with string id', () => {
      const stateWithTask = { ...initialState, tasks: [{ ...mockTask, id: '1' }] };
      const updatedTask = { ...mockTask, id: 1, title: 'Updated Title' };
      const actual = tasksReducer(stateWithTask, updateTaskInState(updatedTask));

      expect(actual.tasks[0].title).toBe('Updated Title');
    });

    it('should not update if task id not found', () => {
      const stateWithTask = { ...initialState, tasks: [mockTask] };
      const updatedTask = { ...mockTask, id: 999, title: 'Updated Title' };
      const actual = tasksReducer(stateWithTask, updateTaskInState(updatedTask));

      expect(actual.tasks[0].title).toBe('Test Task');
    });

    it('should handle deleteTaskFromState', () => {
      const stateWithTasks = {
        ...initialState,
        tasks: [mockTask, { ...mockTask, id: 2 }],
      };
      const actual = tasksReducer(stateWithTasks, deleteTaskFromState(1));

      expect(actual.tasks).toHaveLength(1);
      expect(actual.tasks[0].id).toBe(2);
      expect(actual.error).toBe(null);
    });

    it('should handle setFilter', () => {
      const actual = tasksReducer(initialState, setFilter('pending'));
      expect(actual.filter).toBe('pending');
    });
  });

  describe('async thunks', () => {
    let store;

    beforeEach(() => {
      store = configureStore({
        reducer: {
          auth: authReducer,
          tasks: tasksReducer,
        },
        preloadedState: {
          auth: {
            token: 'test-token',
            user: { id: 1 },
            isAuthenticated: true,
          },
        },
      });
      jest.clearAllMocks();
    });

    describe('fetchTasks', () => {
      it('fetches tasks successfully', async () => {
        const mockTasks = [mockTask, { ...mockTask, id: 2 }];
        taskService.getTasks.mockResolvedValue(mockTasks);

        await store.dispatch(fetchTasks());

        expect(taskService.getTasks).toHaveBeenCalledWith('test-token');

        const state = store.getState().tasks;
        expect(state.tasks).toEqual(mockTasks);
        expect(state.loading).toBe(false);
        expect(state.error).toBe(null);
      });

      it('handles fetch error', async () => {
        const errorMessage = 'Network error';
        taskService.getTasks.mockRejectedValue(new Error(errorMessage));

        await store.dispatch(fetchTasks());

        const state = store.getState().tasks;
        expect(state.error).toBe(errorMessage);
        expect(state.loading).toBe(false);
      });
    });

    describe('createTask', () => {
      it('creates task successfully', async () => {
        taskService.createTask.mockResolvedValue(mockTask);

        const result = await store.dispatch(
          createTask({ title: 'New Task', status: 'pending' })
        );

        expect(result.success).toBe(true);
        expect(result.task).toEqual(mockTask);
        expect(taskService.createTask).toHaveBeenCalledWith(
          'test-token',
          { title: 'New Task', status: 'pending' }
        );

        const state = store.getState().tasks;
        expect(state.tasks).toHaveLength(1);
        expect(state.tasks[0]).toEqual(mockTask);
        expect(state.loading).toBe(false);
      });

      it('handles create error', async () => {
        const errorMessage = 'Failed to create';
        taskService.createTask.mockRejectedValue(new Error(errorMessage));

        const result = await store.dispatch(
          createTask({ title: 'New Task', status: 'pending' })
        );

        expect(result.success).toBe(false);
        expect(result.error).toBe(errorMessage);

        const state = store.getState().tasks;
        expect(state.error).toBe(errorMessage);
        expect(state.loading).toBe(false);
      });
    });

    describe('updateTask', () => {
      it('updates task successfully', async () => {
        const updatedTask = { ...mockTask, title: 'Updated Task' };
        taskService.updateTask.mockResolvedValue(updatedTask);

        store = configureStore({
          reducer: {
            auth: authReducer,
            tasks: tasksReducer,
          },
          preloadedState: {
            auth: {
              token: 'test-token',
              user: { id: 1 },
              isAuthenticated: true,
            },
            tasks: {
              tasks: [mockTask],
              loading: false,
              error: null,
              filter: 'all',
            },
          },
        });

        const result = await store.dispatch(
          updateTask(1, { title: 'Updated Task' })
        );

        expect(result.success).toBe(true);
        expect(result.task).toEqual(updatedTask);
        expect(taskService.updateTask).toHaveBeenCalledWith(
          'test-token',
          1,
          { title: 'Updated Task' }
        );

        const state = store.getState().tasks;
        expect(state.tasks[0].title).toBe('Updated Task');
        expect(state.loading).toBe(false);
      });

      it('handles update error', async () => {
        const errorMessage = 'Failed to update';
        taskService.updateTask.mockRejectedValue(new Error(errorMessage));

        const result = await store.dispatch(
          updateTask(1, { title: 'Updated Task' })
        );

        expect(result.success).toBe(false);
        expect(result.error).toBe(errorMessage);

        const state = store.getState().tasks;
        expect(state.error).toBe(errorMessage);
        expect(state.loading).toBe(false);
      });
    });

    describe('deleteTask', () => {
      it('deletes task successfully', async () => {
        taskService.deleteTask.mockResolvedValue();

        store = configureStore({
          reducer: {
            auth: authReducer,
            tasks: tasksReducer,
          },
          preloadedState: {
            auth: {
              token: 'test-token',
              user: { id: 1 },
              isAuthenticated: true,
            },
            tasks: {
              tasks: [mockTask, { ...mockTask, id: 2 }],
              loading: false,
              error: null,
              filter: 'all',
            },
          },
        });

        const result = await store.dispatch(deleteTask(1));

        expect(result.success).toBe(true);
        expect(taskService.deleteTask).toHaveBeenCalledWith('test-token', 1);

        const state = store.getState().tasks;
        expect(state.tasks).toHaveLength(1);
        expect(state.tasks[0].id).toBe(2);
        expect(state.loading).toBe(false);
      });

      it('handles delete error', async () => {
        const errorMessage = 'Failed to delete';
        taskService.deleteTask.mockRejectedValue(new Error(errorMessage));

        const result = await store.dispatch(deleteTask(1));

        expect(result.success).toBe(false);
        expect(result.error).toBe(errorMessage);

        const state = store.getState().tasks;
        expect(state.error).toBe(errorMessage);
        expect(state.loading).toBe(false);
      });
    });
  });
});
