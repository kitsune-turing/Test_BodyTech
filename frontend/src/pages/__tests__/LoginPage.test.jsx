import { render, screen, waitFor } from '../../utils/test-utils';
import { LoginPage } from '../LoginPage';
import userEvent from '@testing-library/user-event';
import * as authSlice from '../../store/authSlice';
import { MemoryRouter } from 'react-router-dom';

const mockNavigate = jest.fn();

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useNavigate: () => mockNavigate,
}));

jest.mock('../../store/authSlice', () => ({
  ...jest.requireActual('../../store/authSlice'),
  login: jest.fn(),
}));

const renderLoginPage = (initialState = {}) => {
  const defaultState = {
    auth: {
      loading: false,
      error: null,
      isAuthenticated: false,
      token: null,
      user: null,
    },
    ...initialState,
  };

  return render(
    <MemoryRouter>
      <LoginPage />
    </MemoryRouter>,
    { initialState: defaultState }
  );
};

describe('LoginPage', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders login form', () => {
    renderLoginPage();

    expect(screen.getByLabelText(/email/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/contraseña/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /iniciar sesión/i })).toBeInTheDocument();
  });

  it('renders TaskApp title', () => {
    renderLoginPage();
    expect(screen.getByText('TaskApp')).toBeInTheDocument();
  });

  it('renders register link', () => {
    renderLoginPage();
    expect(screen.getByText(/¿no tienes cuenta\?/i)).toBeInTheDocument();
    expect(screen.getByRole('link', { name: /regístrate aquí/i })).toHaveAttribute('href', '/register');
  });

  it('shows validation error for empty email', async () => {
    const user = userEvent.setup();
    renderLoginPage();

    const submitButton = screen.getByRole('button', { name: /iniciar sesión/i });
    await user.click(submitButton);

    expect(screen.getByText('El email es obligatorio')).toBeInTheDocument();
  });

  it('shows validation error for invalid email format', async () => {
    const user = userEvent.setup();
    renderLoginPage();

    const emailInput = screen.getByLabelText(/email/i);
    await user.type(emailInput, 'invalid-email');

    const submitButton = screen.getByRole('button', { name: /iniciar sesión/i });
    await user.click(submitButton);

    expect(screen.getByText('Ingresa un email válido')).toBeInTheDocument();
  });

  it('shows validation error for empty password', async () => {
    const user = userEvent.setup();
    renderLoginPage();

    const emailInput = screen.getByLabelText(/email/i);
    await user.type(emailInput, 'test@example.com');

    const submitButton = screen.getByRole('button', { name: /iniciar sesión/i });
    await user.click(submitButton);

    expect(screen.getByText('La contraseña es obligatoria')).toBeInTheDocument();
  });

  it('clears validation errors when user types', async () => {
    const user = userEvent.setup();
    renderLoginPage();

    const submitButton = screen.getByRole('button', { name: /iniciar sesión/i });
    await user.click(submitButton);

    expect(screen.getByText('El email es obligatorio')).toBeInTheDocument();

    const emailInput = screen.getByLabelText(/email/i);
    await user.type(emailInput, 'test@example.com');

    expect(screen.queryByText('El email es obligatorio')).not.toBeInTheDocument();
  });

  it('submits form with valid credentials', async () => {
    const user = userEvent.setup();
    const mockLogin = jest.fn().mockResolvedValue({ success: true });
    authSlice.login.mockReturnValue(mockLogin);

    renderLoginPage();

    const emailInput = screen.getByLabelText(/email/i);
    const passwordInput = screen.getByLabelText(/contraseña/i);

    await user.type(emailInput, 'test@example.com');
    await user.type(passwordInput, 'password123');

    const submitButton = screen.getByRole('button', { name: /iniciar sesión/i });
    await user.click(submitButton);

    await waitFor(() => {
      expect(mockLogin).toHaveBeenCalled();
    });
  });

  it('navigates to dashboard on successful login', async () => {
    const user = userEvent.setup();
    const mockLogin = jest.fn().mockResolvedValue({ success: true });
    authSlice.login.mockReturnValue(mockLogin);

    renderLoginPage();

    const emailInput = screen.getByLabelText(/email/i);
    const passwordInput = screen.getByLabelText(/contraseña/i);

    await user.type(emailInput, 'test@example.com');
    await user.type(passwordInput, 'password123');

    const submitButton = screen.getByRole('button', { name: /iniciar sesión/i });
    await user.click(submitButton);

    await waitFor(() => {
      expect(mockNavigate).toHaveBeenCalledWith('/');
    });
  });

  it('displays error message on failed login', () => {
    renderLoginPage({
      auth: {
        loading: false,
        error: 'Credenciales inválidas',
        isAuthenticated: false,
        token: null,
        user: null,
      },
    });

    expect(screen.getByText('Credenciales inválidas')).toBeInTheDocument();
  });

  it('shows loading spinner when loading', () => {
    renderLoginPage({
      auth: {
        loading: true,
        error: null,
        isAuthenticated: false,
        token: null,
        user: null,
      },
    });

    expect(screen.getByRole('status')).toBeInTheDocument();
  });

  it('disables submit button when loading', () => {
    renderLoginPage({
      auth: {
        loading: true,
        error: null,
        isAuthenticated: false,
        token: null,
        user: null,
      },
    });

    const submitButton = screen.getByRole('button', { name: /iniciando sesión.../i });
    expect(submitButton).toBeDisabled();
  });
});
