export const Alert = ({ type = 'info', message, onClose }) => {
  const baseStyles = 'p-4 rounded-xl flex items-start gap-3 animate-fade-in shadow-lg';
  
  const types = {
    success: 'bg-gradient-to-r from-green-50 to-green-100 border-l-4 border-green-500',
    error: 'bg-gradient-to-r from-red-50 to-red-100 border-l-4 border-red-500',
    warning: 'bg-gradient-to-r from-yellow-50 to-yellow-100 border-l-4 border-yellow-500',
    info: 'bg-gradient-to-r from-orange-50 to-orange-100 border-l-4 border-secondary',
  };

  const icons = {
    success: '✓',
    error: '✕',
    warning: '⚠',
    info: 'ℹ',
  };

  const textColors = {
    success: 'text-green-700',
    error: 'text-red-700',
    warning: 'text-yellow-700',
    info: 'text-orange-700',
  };

  const iconBgColors = {
    success: 'bg-green-200',
    error: 'bg-red-200',
    warning: 'bg-yellow-200',
    info: 'bg-orange-200',
  };

  return (
    <div className={`${baseStyles} ${types[type]}`}>
      <span className={`text-lg font-bold rounded-lg w-7 h-7 flex items-center justify-center flex-shrink-0 ${iconBgColors[type]} ${textColors[type]}`}>
        {icons[type]}
      </span>
      <div className="flex-1">
        <p className={`${textColors[type]} font-semibold text-sm`}>{message}</p>
      </div>
      {onClose && (
        <button
          onClick={onClose}
          className="text-gray-400 hover:text-gray-600 text-xl flex-shrink-0 transition duration-200"
        >
          ×
        </button>
      )}
    </div>
  );
};
