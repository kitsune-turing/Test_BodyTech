import { useState, useEffect, memo, useCallback, useMemo } from 'react';
import { useSelector, useDispatch } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { logoutUser } from '../store/authSlice';
import { Card } from '../components/Card';
import { Button } from '../components';
import { AvatarUpload } from '../components/AvatarUpload';

const ProfilePageComponent = () => {
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const { user } = useSelector((state) => state.auth);
  const [avatarImage, setAvatarImage] = useState(null);

  useEffect(() => {
    if (user?.id) {
      const savedAvatar = localStorage.getItem(`userAvatar_${user.id}`);
      if (savedAvatar) {
        setAvatarImage(savedAvatar);
      }
    }
  }, [user?.id]);

  const handleLogout = useCallback(async () => {
    await dispatch(logoutUser());
    navigate('/login');
  }, [dispatch, navigate]);

  const handleImageChange = useCallback((imageData) => {
    setAvatarImage(imageData);
    if (user?.id) {
      localStorage.setItem(`userAvatar_${user.id}`, imageData);
    }
  }, [user?.id]);

  const memberSince = useMemo(() => {
    return user?.created_at 
      ? new Date(user.created_at).toLocaleDateString('es-ES', { 
          year: 'numeric', 
          month: 'long', 
          day: 'numeric' 
        })
      : 'No disponible';
  }, [user?.created_at]);

  const userEmail = useMemo(() => user?.email || 'No especificado', [user?.email]);
  const userId = useMemo(() => user?.id || 'No especificado', [user?.id]);
  const userName = useMemo(() => user?.name || 'Usuario', [user?.name]);

  return (
    <div className="min-h-[calc(100vh-100px)] bg-gradient-to-br from-white via-gray-50 to-gray-100 p-4 sm:p-6 lg:p-8">
      <div className="max-w-2xl mx-auto">
        <Card className="w-full shadow-2xl">
          <div className="text-center mb-8">
            <AvatarUpload
              userName={userName}
              onImageChange={handleImageChange}
              initialImage={avatarImage}
            />
            <h1 className="text-2xl sm:text-3xl font-bold text-primary mb-1 mt-6">{userName}</h1>
            <p className="text-sm sm:text-base text-gray-600 font-medium">Mi Perfil</p>
          </div>

          <div className="space-y-4 sm:space-y-5">
            <div className="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4 border-2 border-gray-200">
              <label className="block text-xs sm:text-sm font-bold text-primary mb-2">
                Nombre Completo
              </label>
              <div className="text-gray-800 font-semibold text-sm sm:text-base">
                {userName}
              </div>
            </div>

            <div className="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4 border-2 border-gray-200">
              <label className="block text-xs sm:text-sm font-bold text-primary mb-2">
                Email
              </label>
              <div className="text-gray-800 font-semibold text-sm sm:text-base break-all">
                {userEmail}
              </div>
            </div>

            <div className="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4 border-2 border-gray-200">
              <label className="block text-xs sm:text-sm font-bold text-primary mb-2">
                ID de Usuario
              </label>
              <div className="text-gray-700 font-mono text-xs sm:text-sm break-all">
                {userId}
              </div>
            </div>

            <div className="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4 border-2 border-gray-200">
              <label className="block text-xs sm:text-sm font-bold text-primary mb-2">
                Miembro desde
              </label>
              <div className="text-gray-800 font-semibold text-sm sm:text-base">
                {memberSince}
              </div>
            </div>

            <div className="pt-6 border-t-2 border-gray-200 space-y-3">
              <Button 
                variant="primary" 
                fullWidth
                onClick={() => navigate('/')}
              >
                Volver al Dashboard
              </Button>
              <Button 
                variant="danger" 
                fullWidth
                onClick={handleLogout}
              >
                Cerrar Sesión
              </Button>
            </div>
          </div>
        </Card>
      </div>
    </div>
  );
};
export const ProfilePage = memo(ProfilePageComponent);