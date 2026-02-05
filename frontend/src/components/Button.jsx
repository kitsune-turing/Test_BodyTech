import { memo } from 'react';

const ButtonComponent = ({ 
  children, 
  variant = 'primary', 
  size = 'md', 
  fullWidth = false,
  disabled = false,
  type = 'button',
  onClick,
  className = '',
}) => {
  const baseStyles = 'font-semibold rounded-xl transition duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 active:scale-95';
  
  const variants = {
    primary: 'bg-secondary text-white hover:bg-orange-600 hover:shadow-lg hover:shadow-orange-500/30 focus:ring-secondary disabled:bg-gray-400 disabled:cursor-not-allowed',
    secondary: 'bg-primary text-secondary hover:bg-primary-light hover:shadow-lg focus:ring-primary disabled:bg-gray-400 disabled:cursor-not-allowed',
    outline: 'border-2 border-secondary text-secondary hover:bg-secondary hover:text-white hover:shadow-lg hover:shadow-orange-500/30 focus:ring-secondary disabled:opacity-50 disabled:cursor-not-allowed',
    danger: 'bg-red-500 text-white hover:bg-red-600 hover:shadow-lg hover:shadow-red-500/30 focus:ring-red-500 disabled:bg-gray-400 disabled:cursor-not-allowed',
  };

  const sizes = {
    sm: 'px-3 py-1.5 text-sm',
    md: 'px-5 py-2.5 text-base',
    lg: 'px-7 py-3.5 text-lg',
  };

  const widthClass = fullWidth ? 'w-full' : '';

  return (
    <button
      type={type}
      onClick={onClick}
      disabled={disabled}
      className={`${baseStyles} ${variants[variant]} ${sizes[size]} ${widthClass} ${className}`}
    >
      {children}
    </button>
  );
};

export const Button = memo(ButtonComponent);
