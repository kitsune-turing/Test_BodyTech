import { render, screen, waitFor } from '../../utils/test-utils';
import { DashboardPage } from '../DashboardPage';
import { MemoryRouter } from 'react-router-dom';
import * as tasksSlice from '../../store/tasksSlice';

jest.mock('../../store/tasksSlice', () => ({
  ...jest.requireActual('../../store/tasksSlice'),
  fetchTasks: jest.fn(),
}));

jest.mock('../../components/TaskList', () => ({
  TaskList: () => <div data-testid="task-list">Task List Component</div>,
}));

const renderDashboardPage = (initialState = {}) => {
  const defaultState = {
    auth: {
      user: {
        name: 'Test User',
        email: 'test@example.com',
      },
      isAuthenticated: true,
      token: 'valid-token',
      loading: false,
    },
    tasks: {
      items: [],
      loading: false,
      error: null,
      filters: {
        status: 'all',
      },
    },
    ...initialState,
  };

  return render(
    <MemoryRouter>
      <DashboardPage />
    </MemoryRouter>,
    { initialState: defaultState }
  );
};

describe('DashboardPage', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders dashboard with welcome message', () => {
    renderDashboardPage();

    expect(screen.getByText(/¡bienvenido, test user!/i)).toBeInTheDocument();
  });

  it('displays user name from auth state', () => {
    renderDashboardPage({
      auth: {
        user: {
          name: 'John Doe',
          email: 'john@example.com',
        },
        isAuthenticated: true,
        token: 'valid-token',
        loading: false,
      },
    });

    expect(screen.getByText(/¡bienvenido, john doe!/i)).toBeInTheDocument();
  });

  it('displays email username when name is not available', () => {
    renderDashboardPage({
      auth: {
        user: {
          email: 'testuser@example.com',
        },
        isAuthenticated: true,
        token: 'valid-token',
        loading: false,
      },
    });

    expect(screen.getByText(/¡bienvenido, testuser!/i)).toBeInTheDocument();
  });

  it('displays default username when user data is not available', () => {
    renderDashboardPage({
      auth: {
        user: null,
        isAuthenticated: true,
        token: 'valid-token',
        loading: false,
      },
    });

    expect(screen.getByText(/¡bienvenido, usuario!/i)).toBeInTheDocument();
  });

  it('renders TaskList component', () => {
    renderDashboardPage();

    expect(screen.getByTestId('task-list')).toBeInTheDocument();
  });

  it('renders profile button', () => {
    renderDashboardPage();

    expect(screen.getByRole('button', { name: /ver perfil/i })).toBeInTheDocument();
  });

  it('profile button links to profile page', () => {
    renderDashboardPage();

    const profileLink = screen.getByRole('link');
    expect(profileLink).toHaveAttribute('href', '/profile');
  });

  it('dispatches fetchTasks on mount', () => {
    const mockFetchTasks = jest.fn();
    tasksSlice.fetchTasks.mockReturnValue(mockFetchTasks);

    renderDashboardPage();

    expect(mockFetchTasks).toHaveBeenCalled();
  });

  it('renders subtitle text', () => {
    renderDashboardPage();

    expect(screen.getByText(/organiza y gestiona tus tareas diarias/i)).toBeInTheDocument();
  });

  it('re-fetches tasks when component remounts', () => {
    const mockFetchTasks = jest.fn();
    tasksSlice.fetchTasks.mockReturnValue(mockFetchTasks);

    const { unmount } = renderDashboardPage();
    unmount();

    renderDashboardPage();

    expect(mockFetchTasks).toHaveBeenCalledTimes(2);
  });
});
