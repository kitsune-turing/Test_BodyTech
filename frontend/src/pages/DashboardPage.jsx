import { useEffect, memo, useMemo } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { Link } from 'react-router-dom';
import { fetchTasks } from '../store/tasksSlice';
import { TaskList } from '../components/TaskList';
import { LoadingSpinner, Button } from '../components';

const DashboardPageComponent = () => {
  const dispatch = useDispatch();
  const { user } = useSelector((state) => state.auth);

  useEffect(() => {
    // Recarga las tareas al montar el componente para garantizar datos frescos.
    // Asegura que se muestren los datos más recientes del servidor.
    dispatch(fetchTasks());
  }, [dispatch]);

  const userName = useMemo(() => {
    return user?.name || user?.email?.split('@')[0] || 'Usuario';
  }, [user?.name, user?.email]);

  return (
    <div className="min-h-[calc(100vh-100px)] bg-gradient-to-br from-white via-gray-50 to-gray-100">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
        <div className="mb-8 sm:mb-10 flex flex-col sm:flex-row justify-between sm:items-start gap-6">
          <div>
            <div className="flex items-center gap-3 mb-3">
              <div>
                <h1 className="text-2xl sm:text-3xl lg:text-4xl font-bold text-primary">¡Bienvenido, {userName}!</h1>
              </div>
            </div>
            <p className="text-sm sm:text-base text-gray-600 font-medium ml-15">Organiza y gestiona tus tareas diarias</p>
          </div>
          <Link to="/profile" className="w-full sm:w-auto">
            <Button variant="secondary" fullWidth className="sm:w-auto">
              Ver Perfil
            </Button>
          </Link>
        </div>
        <TaskList />
      </div>
    </div>
  );
};

export const DashboardPage = memo(DashboardPageComponent);
