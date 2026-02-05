import { useState, useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { updateTask, fetchTasks } from '../store/tasksSlice';
import { Input, Textarea, Select, Button, Alert } from './index';
import { Card } from './Card';

const validateForm = (formData) => {
  const errors = {};
  
  if (!formData.title || formData.title.trim() === '') {
    errors.title = 'El título es obligatorio';
  }
  
  if (!formData.status || formData.status === '') {
    errors.status = 'El estado es obligatorio';
  }

  return errors;
};

export const EditTaskForm = ({ task, onSuccess }) => {
  const dispatch = useDispatch();
  const { loading } = useSelector((state) => state.tasks);
  const [formData, setFormData] = useState({
    title: '',
    description: '',
    status: 'pending',
  });
  const [errors, setErrors] = useState({});
  const [errorMessage, setErrorMessage] = useState('');

  useEffect(() => {
    if (task) {
      setFormData({
        title: task.title || '',
        description: task.description || '',
        status: task.status || 'pending',
      });
    }
  }, [task]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: value,
    }));
    if (errors[name]) {
      setErrors((prev) => ({
        ...prev,
        [name]: '',
      }));
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrorMessage('');

    if (!task) {
      setErrorMessage('Tarea no disponible');
      return;
    }

    const validationErrors = validateForm(formData);
    if (Object.keys(validationErrors).length > 0) {
      setErrors(validationErrors);
      return;
    }

    try {
      const result = await dispatch(updateTask(task.id, formData));

      if (result && result.success) {
        setErrors({});
        await dispatch(fetchTasks());
        if (onSuccess) {
          onSuccess();
        }
      } else {
        const errorMsg = (result && result.error) || 'Ocurrió un error al actualizar la tarea. Por favor intenta nuevamente.';
        setErrorMessage(errorMsg);
      }
    } catch (err) {
      setErrorMessage(err.message || 'Ocurrió un error al actualizar la tarea. Por favor intenta nuevamente.');
    }
  };

  return (
    <Card className="w-full max-w-2xl mx-auto shadow-xl">
      <div className="flex items-center gap-3 mb-6">
        <div className="w-12 h-12 bg-gradient-to-br from-secondary to-orange-600 rounded-xl flex items-center justify-center text-white text-xl">
          ✎
        </div>
        <h2 className="text-2xl sm:text-3xl font-bold text-primary">Editar Tarea</h2>
      </div>

      {errorMessage && (
        <Alert 
          type="error" 
          message={errorMessage} 
          onClose={() => setErrorMessage('')}
          className="mb-4"
        />
      )}

      <form onSubmit={handleSubmit} className="space-y-5">
        <Input
          label="Título"
          name="title"
          placeholder="Ingresa el título de la tarea"
          value={formData.title}
          onChange={handleChange}
          error={errors.title}
          required
          disabled={loading}
        />

        <Textarea
          label="Descripción"
          name="description"
          placeholder="Ingresa una descripción (opcional)"
          value={formData.description}
          onChange={handleChange}
          rows={4}
          disabled={loading}
        />

        <Select
          label="Estado"
          name="status"
          value={formData.status}
          onChange={handleChange}
          error={errors.status}
          required
          disabled={loading}
          options={[
            { value: 'pending', label: 'Pendiente' },
            { value: 'in_progress', label: 'En Progreso' },
            { value: 'done', label: 'Completado' },
          ]}
        />

        <div className="flex gap-3 pt-6">
          <Button 
            type="submit" 
            variant="primary" 
            fullWidth
            disabled={loading}
          >
            {loading ? 'Guardando...' : 'Guardar Cambios'}
          </Button>
        </div>
      </form>
    </Card>
  );
};
