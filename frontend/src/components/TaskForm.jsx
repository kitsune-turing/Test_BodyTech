import { useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { createTask, fetchTasks } from '../store/tasksSlice';
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

export const TaskForm = ({ onSuccess, isLoading = false }) => {
  const dispatch = useDispatch();
  const [formData, setFormData] = useState({
    title: '',
    description: '',
    status: 'pending',
  });
  const [errors, setErrors] = useState({});
  const [errorMessage, setErrorMessage] = useState('');

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
    setSuccessMessage('');

    const validationErrors = validateForm(formData);
    if (Object.keys(validationErrors).length > 0) {
      setErrors(validationErrors);
      return;
    }

    const result = await dispatch(createTask(formData));

    if (result.success) {
      setFormData({ title: '', description: '', status: 'pending' });
      setErrors({});
      await dispatch(fetchTasks());
      onSuccess?.();
    } else {
      setErrorMessage(result.error || 'Error al crear la tarea');
    }
  };

  return (
    <Card className="w-full max-w-2xl mx-auto shadow-xl">
      <div className="flex items-center gap-3 mb-6">
        <h2 className="text-2xl sm:text-3xl font-bold text-primary">Nueva Tarea</h2>
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
          disabled={isLoading}
        />

        <Textarea
          label="Descripción"
          name="description"
          placeholder="Ingresa una descripción (opcional)"
          value={formData.description}
          onChange={handleChange}
          rows={4}
          disabled={isLoading}
        />

        <Select
          label="Estado"
          name="status"
          value={formData.status}
          onChange={handleChange}
          error={errors.status}
          required
          disabled={isLoading}
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
            disabled={isLoading}
          >
            {isLoading ? 'Creando...' : 'Crear Tarea'}
          </Button>
        </div>
      </form>
    </Card>
  );
};
