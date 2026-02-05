import { render, screen } from '../../utils/test-utils';
import { Button } from '../Button';
import userEvent from '@testing-library/user-event';

describe('Button Component', () => {
  it('renders button with children text', () => {
    render(<Button>Click me</Button>);
    expect(screen.getByRole('button', { name: /click me/i })).toBeInTheDocument();
  });

  it('applies primary variant styles by default', () => {
    render(<Button>Primary Button</Button>);
    const button = screen.getByRole('button');
    expect(button).toHaveClass('bg-secondary');
  });

  it('applies secondary variant styles', () => {
    render(<Button variant="secondary">Secondary Button</Button>);
    const button = screen.getByRole('button');
    expect(button).toHaveClass('bg-primary');
  });

  it('applies outline variant styles', () => {
    render(<Button variant="outline">Outline Button</Button>);
    const button = screen.getByRole('button');
    expect(button).toHaveClass('border-2', 'border-secondary');
  });

  it('applies danger variant styles', () => {
    render(<Button variant="danger">Danger Button</Button>);
    const button = screen.getByRole('button');
    expect(button).toHaveClass('bg-red-500');
  });

  it('applies small size styles', () => {
    render(<Button size="sm">Small Button</Button>);
    const button = screen.getByRole('button');
    expect(button).toHaveClass('px-3', 'py-1.5', 'text-sm');
  });

  it('applies medium size styles by default', () => {
    render(<Button>Medium Button</Button>);
    const button = screen.getByRole('button');
    expect(button).toHaveClass('px-5', 'py-2.5', 'text-base');
  });

  it('applies large size styles', () => {
    render(<Button size="lg">Large Button</Button>);
    const button = screen.getByRole('button');
    expect(button).toHaveClass('px-7', 'py-3.5', 'text-lg');
  });

  it('applies full width class when fullWidth is true', () => {
    render(<Button fullWidth>Full Width Button</Button>);
    const button = screen.getByRole('button');
    expect(button).toHaveClass('w-full');
  });

  it('handles click events', async () => {
    const user = userEvent.setup();
    const handleClick = jest.fn();
    render(<Button onClick={handleClick}>Click Handler</Button>);

    const button = screen.getByRole('button');
    await user.click(button);

    expect(handleClick).toHaveBeenCalledTimes(1);
  });

  it('disables button when disabled prop is true', () => {
    render(<Button disabled>Disabled Button</Button>);
    const button = screen.getByRole('button');
    expect(button).toBeDisabled();
  });

  it('does not trigger onClick when disabled', async () => {
    const user = userEvent.setup();
    const handleClick = jest.fn();
    render(<Button disabled onClick={handleClick}>Disabled Button</Button>);

    const button = screen.getByRole('button');
    await user.click(button);

    expect(handleClick).not.toHaveBeenCalled();
  });

  it('renders with custom className', () => {
    render(<Button className="custom-class">Custom Class</Button>);
    const button = screen.getByRole('button');
    expect(button).toHaveClass('custom-class');
  });

  it('renders as submit type when specified', () => {
    render(<Button type="submit">Submit</Button>);
    const button = screen.getByRole('button');
    expect(button).toHaveAttribute('type', 'submit');
  });

  it('renders as button type by default', () => {
    render(<Button>Default Type</Button>);
    const button = screen.getByRole('button');
    expect(button).toHaveAttribute('type', 'button');
  });
});
