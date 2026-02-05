import { render, screen, waitFor } from '../../utils/test-utils';
import { TaskForm } from '../TaskForm';
import userEvent from '@testing-library/user-event';
import * as tasksSlice from '../../store/tasksSlice';

jest.mock('../../store/tasksSlice', () => ({
  ...jest.requireActual('../../store/tasksSlice'),
  createTask: jest.fn(),
}));

describe('TaskForm Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders form with all fields', () => {
    render(<TaskForm />);

    expect(screen.getByLabelText(/título/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/descripción/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/estado/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /crear tarea/i })).toBeInTheDocument();
  });

  it('displays form title', () => {
    render(<TaskForm />);
    expect(screen.getByText('Nueva Tarea')).toBeInTheDocument();
  });

  it('shows validation error when title is empty', async () => {
    const user = userEvent.setup();
    render(<TaskForm />);

    const submitButton = screen.getByRole('button', { name: /crear tarea/i });
    await user.click(submitButton);

    expect(screen.getByText('El título es obligatorio')).toBeInTheDocument();
  });

  it('clears validation error when user types in title field', async () => {
    const user = userEvent.setup();
    render(<TaskForm />);

    const submitButton = screen.getByRole('button', { name: /crear tarea/i });
    await user.click(submitButton);

    expect(screen.getByText('El título es obligatorio')).toBeInTheDocument();

    const titleInput = screen.getByLabelText(/título/i);
    await user.type(titleInput, 'New Task');

    expect(screen.queryByText('El título es obligatorio')).not.toBeInTheDocument();
  });

  it('handles form input changes', async () => {
    const user = userEvent.setup();
    render(<TaskForm />);

    const titleInput = screen.getByLabelText(/título/i);
    const descriptionInput = screen.getByLabelText(/descripción/i);

    await user.type(titleInput, 'Test Task');
    await user.type(descriptionInput, 'Test Description');

    expect(titleInput).toHaveValue('Test Task');
    expect(descriptionInput).toHaveValue('Test Description');
  });

  it('submits form with valid data', async () => {
    const user = userEvent.setup();
    const mockCreateTask = jest.fn().mockResolvedValue({ success: true });
    tasksSlice.createTask.mockReturnValue(mockCreateTask);

    render(<TaskForm />);

    const titleInput = screen.getByLabelText(/título/i);
    await user.type(titleInput, 'Test Task');

    const submitButton = screen.getByRole('button', { name: /crear tarea/i });
    await user.click(submitButton);

    await waitFor(() => {
      expect(mockCreateTask).toHaveBeenCalled();
    });
  });

  it('shows success message on successful submission', async () => {
    const user = userEvent.setup();
    const mockCreateTask = jest.fn().mockResolvedValue({ success: true });
    tasksSlice.createTask.mockReturnValue(mockCreateTask);

    render(<TaskForm />);

    const titleInput = screen.getByLabelText(/título/i);
    await user.type(titleInput, 'Test Task');

    const submitButton = screen.getByRole('button', { name: /crear tarea/i });
    await user.click(submitButton);

    await waitFor(() => {
      expect(screen.getByText(/tarea creada exitosamente/i)).toBeInTheDocument();
    });
  });

  it('shows error message on failed submission', async () => {
    const user = userEvent.setup();
    const mockCreateTask = jest.fn().mockResolvedValue({
      success: false,
      error: 'Server error'
    });
    tasksSlice.createTask.mockReturnValue(mockCreateTask);

    render(<TaskForm />);

    const titleInput = screen.getByLabelText(/título/i);
    await user.type(titleInput, 'Test Task');

    const submitButton = screen.getByRole('button', { name: /crear tarea/i });
    await user.click(submitButton);

    await waitFor(() => {
      expect(screen.getByText('Server error')).toBeInTheDocument();
    });
  });

  it('resets form after successful submission', async () => {
    const user = userEvent.setup();
    const mockCreateTask = jest.fn().mockResolvedValue({ success: true });
    tasksSlice.createTask.mockReturnValue(mockCreateTask);

    render(<TaskForm />);

    const titleInput = screen.getByLabelText(/título/i);
    const descriptionInput = screen.getByLabelText(/descripción/i);

    await user.type(titleInput, 'Test Task');
    await user.type(descriptionInput, 'Test Description');

    const submitButton = screen.getByRole('button', { name: /crear tarea/i });
    await user.click(submitButton);

    await waitFor(() => {
      expect(titleInput).toHaveValue('');
      expect(descriptionInput).toHaveValue('');
    });
  });

  it('calls onSuccess callback after successful submission', async () => {
    const user = userEvent.setup();
    const mockOnSuccess = jest.fn();
    const mockCreateTask = jest.fn().mockResolvedValue({ success: true });
    tasksSlice.createTask.mockReturnValue(mockCreateTask);

    render(<TaskForm onSuccess={mockOnSuccess} />);

    const titleInput = screen.getByLabelText(/título/i);
    await user.type(titleInput, 'Test Task');

    const submitButton = screen.getByRole('button', { name: /crear tarea/i });
    await user.click(submitButton);

    await waitFor(() => {
      expect(mockOnSuccess).toHaveBeenCalled();
    }, { timeout: 3000 });
  });

  it('disables form when isLoading is true', () => {
    render(<TaskForm isLoading={true} />);

    const titleInput = screen.getByLabelText(/título/i);
    const submitButton = screen.getByRole('button', { name: /creando.../i });

    expect(titleInput).toBeDisabled();
    expect(submitButton).toBeDisabled();
  });
});
