import { useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useDispatch, useSelector } from 'react-redux';
import { EditTaskForm } from '../components/EditTaskForm';
import { LoadingSpinner } from '../components';
import { fetchTasks } from '../store/tasksSlice';

export const EditTaskPage = () => {
  const navigate = useNavigate();
  const dispatch = useDispatch();
  const { taskId } = useParams();
  const { tasks, loading: tasksLoading } = useSelector((state) => state.tasks);

  useEffect(() => {
    if (tasks.length === 0) {
      dispatch(fetchTasks());
    }
  }, []); // Only run once on mount

  // Calcula la tarea directamente desde props para evitar renders innecesarios
  const task = !tasksLoading && tasks.length > 0
    ? tasks.find((t) => t && String(t.id) === String(taskId)) || null
    : null;

  const handleSuccess = () => {
    window.location.href = '/';
  };

  if (tasksLoading) {
    return (
      <div className="flex justify-center items-center min-h-[calc(100vh-100px)] bg-gradient-to-br from-white via-gray-50 to-gray-100">
        <LoadingSpinner />
      </div>
    );
  }

  if (!task) {
    return (
      <div className="flex justify-center items-center min-h-[calc(100vh-100px)] bg-gradient-to-br from-white via-gray-50 to-gray-100">
        <div className="text-center">
          <p className="text-gray-600 text-lg mb-4">Tarea no encontrada</p>
          <button 
            onClick={() => navigate('/')}
            className="text-secondary font-semibold hover:text-orange-600 transition"
          >
            Volver al inicio
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-[calc(100vh-100px)] bg-gradient-to-br from-white via-gray-50 to-gray-100">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
        <EditTaskForm task={task} onSuccess={handleSuccess} />
      </div>
    </div>
  );
};
