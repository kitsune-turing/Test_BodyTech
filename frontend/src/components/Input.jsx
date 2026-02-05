export const Input = ({
  label,
  type = 'text',
  placeholder,
  value,
  onChange,
  error,
  required = false,
  className = '',
  name = '',
}) => {
  const handleChange = (e) => {
    if (onChange) {
      onChange(e);
    }
  };

  return (
    <div className="w-full">
      {label && (
        <label className="block text-sm font-semibold text-primary mb-2.5">
          {label}
          {required && <span className="text-red-500 ml-1">*</span>}
        </label>
      )}
      <input
        type={type}
        name={name}
        placeholder={placeholder}
        value={value || ''}
        onChange={handleChange}
        required={required}
        autoComplete="off"
        className={`
          w-full px-4 py-3 border-2 rounded-xl
          border-gray-200 focus:border-secondary focus:outline-none
          bg-white text-gray-800 placeholder-gray-400
          transition duration-300
          focus:shadow-lg focus:shadow-orange-500/10
          ${error ? 'border-red-500' : ''}
          ${className}
        `}
      />
      {error && (
        <p className="text-red-500 text-sm mt-2">{error}</p>
      )}
    </div>
  );
};
