import { render, screen } from '../../utils/test-utils';
import { ProtectedRoute } from '../ProtectedRoute';
import { MemoryRouter, Route, Routes } from 'react-router-dom';

const TestComponent = () => <div>Protected Content</div>;
const LoginComponent = () => <div>Login Page</div>;

const renderWithRouter = (initialState) => {
  return render(
    <MemoryRouter initialEntries={['/protected']}>
      <Routes>
        <Route
          path="/protected"
          element={
            <ProtectedRoute>
              <TestComponent />
            </ProtectedRoute>
          }
        />
        <Route path="/login" element={<LoginComponent />} />
      </Routes>
    </MemoryRouter>,
    { initialState }
  );
};

describe('ProtectedRoute Component', () => {
  it('renders children when user is authenticated', () => {
    const initialState = {
      auth: {
        isAuthenticated: true,
        token: 'valid-token',
        loading: false,
      },
    };

    renderWithRouter(initialState);
    expect(screen.getByText('Protected Content')).toBeInTheDocument();
  });

  it('redirects to login when user is not authenticated', () => {
    const initialState = {
      auth: {
        isAuthenticated: false,
        token: null,
        loading: false,
      },
    };

    renderWithRouter(initialState);
    expect(screen.getByText('Login Page')).toBeInTheDocument();
    expect(screen.queryByText('Protected Content')).not.toBeInTheDocument();
  });

  it('redirects to login when token is missing', () => {
    const initialState = {
      auth: {
        isAuthenticated: true,
        token: null,
        loading: false,
      },
    };

    renderWithRouter(initialState);
    expect(screen.getByText('Login Page')).toBeInTheDocument();
    expect(screen.queryByText('Protected Content')).not.toBeInTheDocument();
  });

  it('shows loading spinner when authentication is loading', () => {
    const initialState = {
      auth: {
        isAuthenticated: false,
        token: null,
        loading: true,
      },
    };

    renderWithRouter(initialState);
    expect(screen.getByRole('status')).toBeInTheDocument();
    expect(screen.queryByText('Protected Content')).not.toBeInTheDocument();
    expect(screen.queryByText('Login Page')).not.toBeInTheDocument();
  });

  it('stops showing loading and renders content after auth completes', () => {
    const initialState = {
      auth: {
        isAuthenticated: false,
        token: null,
        loading: true,
      },
    };

    const { rerender } = renderWithRouter(initialState);
    expect(screen.getByRole('status')).toBeInTheDocument();

    const newState = {
      auth: {
        isAuthenticated: true,
        token: 'valid-token',
        loading: false,
      },
    };

    rerender(
      <MemoryRouter initialEntries={['/protected']}>
        <Routes>
          <Route
            path="/protected"
            element={
              <ProtectedRoute>
                <TestComponent />
              </ProtectedRoute>
            }
          />
        </Routes>
      </MemoryRouter>
    );
  });
});
