import { render, screen, waitFor } from '../../utils/test-utils';
import { TaskCard } from '../TaskCard';
import userEvent from '@testing-library/user-event';

const mockTask = {
  id: 1,
  title: 'Test Task',
  description: 'Test Description',
  status: 'pending',
};

describe('TaskCard Component', () => {
  it('renders task title', () => {
    render(<TaskCard task={mockTask} />);
    expect(screen.getByText('Test Task')).toBeInTheDocument();
  });

  it('renders task description', () => {
    render(<TaskCard task={mockTask} />);
    expect(screen.getByText('Test Description')).toBeInTheDocument();
  });

  it('does not render description when not provided', () => {
    const taskWithoutDesc = { ...mockTask, description: null };
    render(<TaskCard task={taskWithoutDesc} />);
    expect(screen.queryByText('Test Description')).not.toBeInTheDocument();
  });

  it('renders pending status badge', () => {
    render(<TaskCard task={{ ...mockTask, status: 'pending' }} />);
    expect(screen.getByText('Pendiente')).toBeInTheDocument();
  });

  it('renders in progress status badge', () => {
    render(<TaskCard task={{ ...mockTask, status: 'in_progress' }} />);
    expect(screen.getByText('En Progreso')).toBeInTheDocument();
  });

  it('renders done status badge', () => {
    render(<TaskCard task={{ ...mockTask, status: 'done' }} />);
    expect(screen.getByText('Completado')).toBeInTheDocument();
  });

  it('renders edit button', () => {
    render(<TaskCard task={mockTask} />);
    expect(screen.getByRole('button', { name: /editar/i })).toBeInTheDocument();
  });

  it('renders delete button', () => {
    render(<TaskCard task={mockTask} />);
    expect(screen.getByRole('button', { name: /eliminar/i })).toBeInTheDocument();
  });

  it('renders edit link with correct path', () => {
    render(<TaskCard task={mockTask} />);
    const editLink = screen.getByRole('link');
    expect(editLink).toHaveAttribute('href', '/tasks/1/edit');
  });

  it('shows delete confirmation modal when delete is clicked', async () => {
    const user = userEvent.setup();
    render(<TaskCard task={mockTask} />);

    const deleteButton = screen.getByRole('button', { name: /eliminar/i });
    await user.click(deleteButton);

    expect(screen.getByText('Confirmar eliminación')).toBeInTheDocument();
    expect(screen.getByText(/¿estás seguro/i)).toBeInTheDocument();
  });

  it('hides confirmation modal when cancel is clicked', async () => {
    const user = userEvent.setup();
    render(<TaskCard task={mockTask} />);

    const deleteButton = screen.getByRole('button', { name: /eliminar/i });
    await user.click(deleteButton);

    const cancelButton = screen.getByRole('button', { name: /cancelar/i });
    await user.click(cancelButton);

    await waitFor(() => {
      expect(screen.queryByText('Confirmar eliminación')).not.toBeInTheDocument();
    });
  });

  it('dispatches delete action when confirmed', async () => {
    const user = userEvent.setup();
    render(<TaskCard task={mockTask} />);

    const deleteButton = screen.getByRole('button', { name: /eliminar/i });
    await user.click(deleteButton);

    const confirmButtons = screen.getAllByRole('button', { name: /eliminar/i });
    const confirmButton = confirmButtons.find(btn => btn.textContent === 'Eliminar');
    await user.click(confirmButton);

    await waitFor(() => {
      expect(confirmButton).toHaveTextContent('Eliminando...');
    });
  });

  it('disables buttons during deletion', async () => {
    const user = userEvent.setup();
    render(<TaskCard task={mockTask} />);

    const deleteButton = screen.getByRole('button', { name: /eliminar/i });
    await user.click(deleteButton);

    const confirmButtons = screen.getAllByRole('button', { name: /eliminar/i });
    const confirmButton = confirmButtons.find(btn => btn.textContent === 'Eliminar');
    await user.click(confirmButton);

    await waitFor(() => {
      const cancelButton = screen.getByRole('button', { name: /cancelar/i });
      expect(cancelButton).toBeDisabled();
    });
  });
});
