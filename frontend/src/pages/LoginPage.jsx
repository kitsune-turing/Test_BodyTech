import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useDispatch, useSelector } from 'react-redux';
import { login } from '../store/authSlice';
import { Input, Button, Alert, LoadingSpinner } from '../components';
import { Card } from '../components/Card';

const validateForm = (formData) => {
  const errors = {};
  
  if (!formData.email || formData.email.trim() === '') {
    errors.email = 'El email es obligatorio';
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
    errors.email = 'Ingresa un email válido';
  }
  
  if (!formData.password || formData.password === '') {
    errors.password = 'La contraseña es obligatoria';
  }

  return errors;
};

export const LoginPage = () => {
  const navigate = useNavigate();
  const dispatch = useDispatch();
  const { loading, error } = useSelector((state) => state.auth);
  
  const [formData, setFormData] = useState({
    email: '',
    password: '',
  });
  const [errors, setErrors] = useState({});

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

    const result = await dispatch(login(formData.email, formData.password));
    
    if (result.success) {
      navigate('/');
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
          <p className="text-sm sm:text-base text-gray-600 font-medium">Inicia sesión en tu cuenta</p>
        </div>

        {error && (
          <Alert 
            type="error" 
            message={error}
            className="mb-4"
          />
        )}

        <form onSubmit={handleSubmit} className="space-y-5">
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
            placeholder="Tu contraseña"
            value={formData.password}
            onChange={handleChange}
            error={errors.password}
            required
          />

          <Button 
            type="submit" 
            variant="primary" 
            fullWidth
            disabled={loading}
            className="py-3"
          >
            {loading ? 'Iniciando sesión...' : 'Iniciar Sesión'}
          </Button>
        </form>

        <p className="text-center text-gray-600 text-sm mt-6">
          ¿No tienes cuenta?{' '}
          <Link to="/register" className="text-secondary font-bold hover:text-orange-600 transition">
            Regístrate aquí
          </Link>
        </p>
      </Card>
    </div>
  );
};
