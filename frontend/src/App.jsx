import { useEffect, Suspense, lazy } from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate, useNavigate } from 'react-router-dom';
import { Provider, useDispatch } from 'react-redux';
import store from './store';
import { Header } from './components/Header';
import { ProtectedRoute } from './components/ProtectedRoute';
import { GuestRoute } from './components/GuestRoute';
import { LoadingSpinner } from './components';
import { WebSocketStatus } from './components/WebSocketStatus';
import { initializeAuth } from './store/authSlice';
import { authService } from './services/authService';

// Carga diferida de páginas para reducir el bundle inicial.
const LoginPage = lazy(() => import('./pages/LoginPage').then(m => ({ default: m.LoginPage })));
const RegisterPage = lazy(() => import('./pages/RegisterPage').then(m => ({ default: m.RegisterPage })));
const DashboardPage = lazy(() => import('./pages/DashboardPage').then(m => ({ default: m.DashboardPage })));
const CreateTaskPage = lazy(() => import('./pages/CreateTaskPage').then(m => ({ default: m.CreateTaskPage })));
const EditTaskPage = lazy(() => import('./pages/EditTaskPage').then(m => ({ default: m.EditTaskPage })));
const ProfilePage = lazy(() => import('./pages/ProfilePage').then(m => ({ default: m.ProfilePage })));

const PageLoader = () => (
  <div className="flex justify-center items-center min-h-[calc(100vh-100px)]">
    <LoadingSpinner size="lg" />
  </div>
);

// Componente separado para usar useNavigate dentro del Router.
function AppRoutes() {
  const dispatch = useDispatch();
  const navigate = useNavigate();

  useEffect(() => {
    // Inicializa el estado de autenticación desde localStorage al cargar la app.
    const token = authService.getStoredToken();
    const user = authService.getStoredUser();
    if (token && user) {
      dispatch(initializeAuth({ token, user }));
    }
  }, [dispatch]);

  useEffect(() => {
    // Escucha eventos de no autorizado (token expirado o inválido).
    const handleUnauthorized = () => {
      // Redirige al login cuando el token deja de ser válido.
      navigate('/login', { replace: true });
    };

    window.addEventListener('auth:unauthorized', handleUnauthorized);
    
    return () => {
      window.removeEventListener('auth:unauthorized', handleUnauthorized);
    };
  }, [navigate]);

  return (
    <div className="min-h-screen bg-white">
      <Header />
      <WebSocketStatus />
      <Suspense fallback={<PageLoader />}>
        <Routes>
          <Route path="/login" element={
            <GuestRoute>
              <LoginPage />
            </GuestRoute>
          } />
          <Route path="/register" element={
            <GuestRoute>
              <RegisterPage />
            </GuestRoute>
          } />
          <Route 
            path="/" 
            element={
              <ProtectedRoute>
                <DashboardPage />
              </ProtectedRoute>
            } 
          />
          <Route 
            path="/tasks/new" 
            element={
              <ProtectedRoute>
                <CreateTaskPage />
              </ProtectedRoute>
            } 
          />
          <Route 
            path="/tasks/:taskId/edit" 
            element={
              <ProtectedRoute>
                <EditTaskPage />
              </ProtectedRoute>
            } 
          />
          <Route 
            path="/profile" 
            element={
              <ProtectedRoute>
                <ProfilePage />
              </ProtectedRoute>
            } 
          />
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </Suspense>
    </div>
  );
}

function App() {
  return (
    <Provider store={store}>
      <Router future={{
        v7_startTransition: true,
        v7_relativeSplatPath: true
      }}>
        <AppRoutes />
      </Router>
    </Provider>
  );
}

export default App;
