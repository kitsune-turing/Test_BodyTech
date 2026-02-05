import { Link, useNavigate } from 'react-router-dom';
import { useDispatch, useSelector } from 'react-redux';
import { logoutUser } from '../store/authSlice';
import { Button } from './Button';
import { useState } from 'react';

export const Header = () => {
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const { user, isAuthenticated } = useSelector((state) => state.auth);
  const [isMenuOpen, setIsMenuOpen] = useState(false);

  const handleLogout = async () => {
    await dispatch(logoutUser());
    navigate('/login');
    setIsMenuOpen(false);
  };

  return (
    <header className="bg-gradient-to-r from-primary via-primary to-primary-light text-white shadow-xl sticky top-0 z-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 sm:py-4 flex justify-between items-center">
        <Link to="/" className="flex items-center gap-3 group">
          <span className="text-xl sm:text-2xl font-bold hidden sm:inline bg-gradient-to-r from-white to-gray-200 bg-clip-text text-transparent">TaskApp</span>
          <span className="text-lg sm:text-2xl font-bold sm:hidden">TA</span>
        </Link>

        <div className="hidden sm:flex items-center gap-4">
          {isAuthenticated ? (
            <>
              <Link to="/profile" className="text-sm opacity-90 hover:opacity-100 transition truncate">
                {user?.email || 'Usuario'}
              </Link>
              <Button 
                variant="outline" 
                size="sm" 
                onClick={handleLogout}
                className="border-white text-white hover:bg-primary-dark hover:border-white"
              >
                Cerrar Sesión
              </Button>
            </>
          ) : (
            <>
              <Link to="/login">
                <Button variant="outline" size="sm" className="border-white text-white hover:bg-primary-dark hover:border-white">
                  Iniciar Sesión
                </Button>
              </Link>
              <Link to="/register">
                <Button variant="secondary" size="sm">
                  Registrarse
                </Button>
              </Link>
            </>
          )}
        </div>

        {/* Mobile menu button */}
        <button 
          onClick={() => setIsMenuOpen(!isMenuOpen)}
          className="sm:hidden p-2 rounded-lg hover:bg-primary-light transition"
        >
          <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            {isMenuOpen ? (
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            ) : (
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
            )}
          </svg>
        </button>
      </div>

      {/* Mobile menu */}
      {isMenuOpen && (
        <div className="sm:hidden bg-primary-light border-t border-primary-dark">
          <div className="px-4 py-4 space-y-3">
            {isAuthenticated ? (
              <>
                <div className="text-sm opacity-90 px-4 py-2">
                  {user?.email || 'Usuario'}
                </div>
                <Link to="/profile" onClick={() => setIsMenuOpen(false)} className="block">
                  <Button variant="outline" size="sm" fullWidth className="border-white text-white hover:bg-primary-dark hover:border-white justify-center">
                    Ver Perfil
                  </Button>
                </Link>
                <Button 
                  variant="secondary" 
                  size="sm"
                  fullWidth
                  onClick={handleLogout}
                  className="justify-center"
                >
                  Cerrar Sesión
                </Button>
              </>
            ) : (
              <>
                <Link to="/login" onClick={() => setIsMenuOpen(false)} className="block">
                  <Button variant="outline" size="sm" fullWidth className="border-white text-white hover:bg-primary-dark hover:border-white justify-center">
                    Iniciar Sesión
                  </Button>
                </Link>
                <Link to="/register" onClick={() => setIsMenuOpen(false)} className="block">
                  <Button variant="secondary" size="sm" fullWidth className="justify-center">
                    Registrarse
                  </Button>
                </Link>
              </>
            )}
          </div>
        </div>
      )}
    </header>
  );
};
