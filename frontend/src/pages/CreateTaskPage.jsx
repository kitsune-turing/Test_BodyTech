import { TaskForm } from '../components/TaskForm';

export const CreateTaskPage = () => {
  const handleSuccess = () => {
    // Recarga completa de la página para reflejar datos actualizados
    window.location.href = '/';
  };

  return (
    <div className="min-h-[calc(100vh-100px)] bg-gradient-to-br from-white via-gray-50 to-gray-100">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
        <TaskForm onSuccess={handleSuccess} />
      </div>
    </div>
  );
};
