import { Link } from 'react-router-dom';
import { useDispatch, useSelector, shallowEqual } from 'react-redux';
import { memo, useMemo, useCallback, useState, useEffect } from 'react';
import { setFilter, fetchTasks, setPage, setLimit, setSearch, setSort } from '../store/tasksSlice';
import { Button, LoadingSpinner, Alert } from './index';
import { TaskCard } from './TaskCard';
import { Pagination } from './Pagination';
import { SortControls } from './SortControls';
import { ExportButtons } from './ExportButtons';

const TaskListComponent = () => {
  const dispatch = useDispatch();
  const { tasks = [], loading, error, filter, pagination, search, sortBy, sortOrder } = useSelector((state) => state.tasks, shallowEqual);
  const [searchInput, setSearchInput] = useState(search || '');

  useEffect(() => {
    setSearchInput(search || '');
  }, [search]);

  useEffect(() => {
    const timer = setTimeout(() => {
      if (searchInput !== search) {
        dispatch(setSearch(searchInput));
      }
    }, 300);

    return () => clearTimeout(timer);
  }, [searchInput, search, dispatch]);

  useEffect(() => {
    dispatch(fetchTasks());
  }, [dispatch, filter, pagination.page, pagination.limit, search, sortBy, sortOrder]);

  const handleFilterChange = useCallback((newFilter) => {
    dispatch(setFilter(newFilter));
    dispatch(setPage(1)); // Reset to first page when filter changes
  }, [dispatch]);

  const handleRefresh = useCallback(async () => {
    await dispatch(fetchTasks());
  }, [dispatch]);

  const handlePageChange = useCallback((newPage) => {
    dispatch(setPage(newPage));
  }, [dispatch]);

  const handleLimitChange = useCallback((newLimit) => {
    dispatch(setLimit(newLimit));
  }, [dispatch]);

  const handleSortChange = useCallback((newSortBy, newSortOrder) => {
    dispatch(setSort({ sortBy: newSortBy, sortOrder: newSortOrder }));
  }, [dispatch]);

  const handleSearchChange = useCallback((e) => {
    setSearchInput(e.target.value);
  }, []);

  const handleSearchClear = useCallback(() => {
    setSearchInput('');
  }, []);

  if (loading) {
    return (
      <div className="flex justify-center items-center py-12">
        <LoadingSpinner />
      </div>
    );
  }

  return (
    <div className="space-y-5 sm:space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 sm:gap-4">
        <div className="flex items-center gap-3">
          <h2 className="text-2xl sm:text-3xl font-bold text-primary">Mis Tareas</h2>
        </div>
        <div className="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
          <ExportButtons />
          <Link to="/tasks/new" className="w-full sm:w-auto">
            <Button variant="primary" fullWidth className="sm:w-auto">
              + Nueva Tarea
            </Button>
          </Link>
        </div>
      </div>

      {error && (
        <Alert 
          type="error" 
          message={error}
          onClose={handleRefresh}
        />
      )}

      {/* Search Bar */}
      <div className="relative">
        <input
          type="text"
          placeholder="Buscar tareas..."
          value={searchInput}
          onChange={handleSearchChange}
          className="w-full px-4 py-3 pl-10 border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none bg-white"
        />
        <svg
          className="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
          />
        </svg>
        {searchInput && (
          <button
            onClick={handleSearchClear}
            className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
          >
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        )}
      </div>

      {/* Filter and Sort Controls */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div className="flex flex-wrap gap-2">
          {['all', 'pending', 'in_progress', 'done'].map((filterOption) => {
            const isActive = filter === filterOption;
            const labels = {
              all: 'Todas',
              pending: 'Pendientes',
              in_progress: 'En Progreso',
              done: 'Completadas'
            };

            return (
              <Button
                key={filterOption}
                variant={isActive ? 'primary' : 'outline'}
                size="sm"
                onClick={() => handleFilterChange(filterOption)}
                className={`text-xs sm:text-sm transition-all ${
                  isActive
                    ? 'shadow-lg shadow-orange-500/30'
                    : ''
                }`}
              >
                {labels[filterOption]}
              </Button>
            );
          })}
        </div>

        <SortControls
          sortBy={sortBy}
          sortOrder={sortOrder}
          onSortChange={handleSortChange}
        />
      </div>

      {tasks.length === 0 ? (
        <div className="text-center py-12 sm:py-16 bg-gradient-to-r from-gray-50 to-gray-100 rounded-2xl px-4 shadow-md">
          <p className="text-gray-700 text-sm sm:text-base lg:text-lg font-medium">
            {searchInput
              ? `No se encontraron tareas con "${searchInput}"`
              : filter === 'all'
              ? 'No hay tareas aún. ¡Crea una nueva!'
              : `No hay tareas ${
                  filter === 'pending' ? 'pendientes' :
                  filter === 'in_progress' ? 'en progreso' :
                  'completadas'
                }`}
          </p>
          {(filter !== 'all' || searchInput) && (
            <div className="flex gap-2 justify-center mt-4">
              {searchInput && (
                <Button
                  variant="outline"
                  size="sm"
                  onClick={handleSearchClear}
                >
                  Limpiar búsqueda
                </Button>
              )}
              {filter !== 'all' && (
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => handleFilterChange('all')}
                >
                  Ver todas las tareas
                </Button>
              )}
            </div>
          )}
        </div>
      ) : (
        <>
          <div className="grid gap-4">
            {tasks.filter(task => task && task.id).map((task) => (
              <TaskCard key={task.id} task={task} />
            ))}
          </div>

          {/* Pagination */}
          {pagination && pagination.pages > 1 && (
            <Pagination
              currentPage={pagination.page}
              totalPages={pagination.pages}
              onPageChange={handlePageChange}
              itemsPerPage={pagination.limit}
              onItemsPerPageChange={handleLimitChange}
            />
          )}
        </>
      )}
    </div>
  );
};

export const TaskList = memo(TaskListComponent);
