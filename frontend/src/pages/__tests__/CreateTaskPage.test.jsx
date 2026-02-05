import { render, screen } from '../../utils/test-utils';
import { CreateTaskPage } from '../CreateTaskPage';
import { MemoryRouter } from 'react-router-dom';

const mockNavigate = jest.fn();

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useNavigate: () => mockNavigate,
}));

jest.mock('../../components/TaskForm', () => ({
  TaskForm: ({ onSuccess }) => (
    <div data-testid="task-form">
      <button onClick={onSuccess}>Submit Form</button>
    </div>
  ),
}));

describe('CreateTaskPage', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders TaskForm component', () => {
    render(
      <MemoryRouter>
        <CreateTaskPage />
      </MemoryRouter>
    );

    expect(screen.getByTestId('task-form')).toBeInTheDocument();
  });

  it('navigates to dashboard on successful task creation', async () => {
    render(
      <MemoryRouter>
        <CreateTaskPage />
      </MemoryRouter>
    );

    const submitButton = screen.getByText('Submit Form');
    submitButton.click();

    expect(mockNavigate).toHaveBeenCalledWith('/');
  });

  it('renders with correct page layout', () => {
    const { container } = render(
      <MemoryRouter>
        <CreateTaskPage />
      </MemoryRouter>
    );

    const pageWrapper = container.querySelector('.min-h-\\[calc\\(100vh-100px\\)\\]');
    expect(pageWrapper).toBeInTheDocument();
  });
});
