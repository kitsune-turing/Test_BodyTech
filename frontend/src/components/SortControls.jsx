export const SortControls = ({ sortBy, sortOrder, onSortChange }) => {
  const sortOptions = [
    { value: 'created_at', label: 'Fecha de Creación' },
    { value: 'updated_at', label: 'Última Actualización' },
    { value: 'title', label: 'Título' },
    { value: 'status', label: 'Estado' },
  ];

  const handleSortByChange = (e) => {
    onSortChange(e.target.value, sortOrder);
  };

  const handleSortOrderToggle = () => {
    const newOrder = sortOrder === 'ASC' ? 'DESC' : 'ASC';
    onSortChange(sortBy, newOrder);
  };

  return (
    <div className="flex items-center gap-3 flex-wrap">
      <span className="text-sm font-semibold text-gray-700">Ordenar por:</span>

      {/* Sort field selector */}
      <select
        value={sortBy}
        onChange={handleSortByChange}
        className="px-4 py-2 border-2 border-gray-200 rounded-lg focus:border-secondary focus:outline-none text-sm font-medium bg-white hover:border-secondary transition"
      >
        {sortOptions.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>

      {/* Sort order toggle button */}
      <button
        onClick={handleSortOrderToggle}
        className="p-2 border-2 border-gray-200 rounded-lg hover:border-secondary hover:bg-secondary hover:text-white transition group"
        title={sortOrder === 'ASC' ? 'Orden Ascendente' : 'Orden Descendente'}
      >
        {sortOrder === 'ASC' ? (
          <svg
            className="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"
            />
          </svg>
        ) : (
          <svg
            className="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"
            />
          </svg>
        )}
      </button>

      {/* Visual indicator */}
      <span className="text-xs text-gray-500">
        {sortOrder === 'ASC' ? '↑ Ascendente' : '↓ Descendente'}
      </span>
    </div>
  );
};
