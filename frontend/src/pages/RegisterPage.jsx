import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useDispatch, useSelector } from 'react-redux';
import { register } from '../store/authSlice';
import { Input, Button, Alert, LoadingSpinner } from '../components';
import { Card } from '../components/Card';

const validateForm = (formData) => {
  const errors = {};
  
  if (!formData.name || formData.name.trim() === '') {
    errors.name = 'El nombre es obligatorio';
  } else if (formData.name.trim().length < 3) {
    errors.name = 'El nombre debe tener al menos 3 caracteres';
  }
  
  if (!formData.email || formData.email.trim() === '') {
    errors.email = 'El email es obligatorio';
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
    errors.email = 'Ingresa un email válido';
  }
  
  if (!formData.password || formData.password === '') {
    errors.password = 'La contraseña es obligatoria';
  } else if (formData.password.length < 8) {
    errors.password = 'La contraseña debe tener al menos 8 caracteres';
  }

  if (formData.password !== formData.confirmPassword) {
    errors.confirmPassword = 'Las contraseñas no coinciden';
  }

  return errors;
};

export const RegisterPage = () => {
  const navigate = useNavigate();
  const dispatch = useDispatch();
  const { loading, error } = useSelector((state) => state.auth);
  
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    password: '',
    confirmPassword: '',
  });
  const [errors, setErrors] = useState({});
  const [successMessage, setSuccessMessage] = useState('');

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

    const validationErrors = validateForm(formData);
    if (Object.keys(validationErrors).length > 0) {
      setErrors(validationErrors);
      return;
    }

    const result = await dispatch(register({
      name: formData.name,
      email: formData.email,
      password: formData.password,
    }));
    
    if (result.success) {
      setSuccessMessage('Registro exitoso. Redirigiendo a login...');
      setTimeout(() => {
        navigate('/login');
      }, 1500);
    }
  };

  return (
    <div className="min-h-[calc(100vh-100px)] bg-gradient-to-br from-white via-gray-50 to-gray-100 flex items-center justify-center p-4 relative">
      {loading && (
        <div className="fixed inset-0 bg-white/30 backdrop-blur-sm z-50 flex items-center justify-center">
          <LoadingSpinner />
        </div>
      )}
      <Card className="w-full max-w-md shadow-2xl">
        <div className="text-center mb-8">
          <h1 className="text-2xl sm:text-3xl font-bold text-primary mb-1">TaskApp</h1>
          <p className="text-sm sm:text-base text-gray-600 font-medium">Crea tu cuenta para comenzar</p>
        </div>

        {successMessage && (
          <Alert 
            type="success" 
            message={successMessage}
            className="mb-4"
          />
        )}

        {error && (
          <Alert 
            type="error" 
            message={error}
            className="mb-4"
          />
        )}

        <form onSubmit={handleSubmit} className="space-y-5">
          <Input
            label="Nombre Completo"
            type="text"
            name="name"
            placeholder="Tu nombre"
            value={formData.name}
            onChange={handleChange}
            error={errors.name}
            required
          />

          <Input
            label="Email"
            type="email"
            name="email"
            placeholder="tu@email.com"
            value={formData.email}
            onChange={handleChange}
            error={errors.email}
            required
          />

          <Input
            label="Contraseña"
            type="password"
            name="password"
            placeholder="Mínimo 8 caracteres"
            value={formData.password}
            onChange={handleChange}
            error={errors.password}
            required
          />

          <Input
            label="Confirmar Contraseña"
            type="password"
            name="confirmPassword"
            placeholder="Confirma tu contraseña"
            value={formData.confirmPassword}
            onChange={handleChange}
            error={errors.confirmPassword}
            required
          />

          <Button 
            type="submit" 
            variant="primary" 
            fullWidth
            disabled={loading}
            className="py-3"
          >
            {loading ? 'Registrando...' : 'Registrarse'}
          </Button>
        </form>

        <p className="text-center text-gray-600 text-sm mt-6">
          ¿Ya tienes cuenta?{' '}
          <Link to="/login" className="text-secondary font-bold hover:text-orange-600 transition">
            Inicia sesión
          </Link>
        </p>
      </Card>
    </div>
  );
};
