import { useState, useEffect, memo, useCallback } from 'react';
import { Button } from './Button';
import { compressImage } from '../utils/imageCompression';

const AvatarUploadComponent = ({ userName, onImageChange, initialImage = null }) => {
  const [preview, setPreview] = useState(initialImage);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    if (initialImage) {
      setPreview(initialImage);
    }
  }, [initialImage]);

  useEffect(() => {
    if (error) {
      const timer = setTimeout(() => setError(''), 3000);
      return () => clearTimeout(timer);
    }
  }, [error]);

  const handleFileChange = useCallback(async (e) => {
    const file = e.target.files[0];
    setError('');
    setIsLoading(true);

    if (file) {
      if (file.size > 5 * 1024 * 1024) {
        setError('La imagen debe ser menor a 5MB');
        setIsLoading(false);
        return;
      }

      if (!file.type.startsWith('image/')) {
        setError('Por favor selecciona una imagen válida');
        setIsLoading(false);
        return;
      }

      const reader = new FileReader();
      reader.onloadend = async () => {
        try {
          const compressedImage = await compressImage(reader.result, 0.7, 256);
          setPreview(compressedImage);
          onImageChange?.(compressedImage);
        } catch (err) {
          setError('Error al procesar la imagen');
        } finally {
          setIsLoading(false);
        }
      };
      reader.onerror = () => {
        setError('Error al leer la imagen');
        setIsLoading(false);
      };
      reader.readAsDataURL(file);
    }
  }, [onImageChange]);

  return (
    <div className="text-center">
      <div className="relative inline-block mb-4 group">
        <div className="w-24 h-24 sm:w-28 sm:h-28 bg-gradient-to-br from-secondary to-orange-600 rounded-full flex items-center justify-center mx-auto overflow-hidden border-4 border-white shadow-lg">
          {preview ? (
            <img src={preview} alt="Preview" className="w-full h-full object-cover" />
          ) : (
            <span className="text-4xl sm:text-5xl text-white font-bold">
              {userName?.charAt(0).toUpperCase() || 'U'}
            </span>
          )}
        </div>
        <label className="absolute bottom-2 right-2 bg-secondary text-white rounded-full p-2.5 cursor-pointer hover:bg-orange-600 transition-all duration-300 hover:shadow-lg hover:scale-110 group-hover:block">
          <input
            type="file"
            accept="image/*"
            onChange={handleFileChange}
            className="hidden"
          />
          <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" />
          </svg>
        </label>
      </div>
      {error ? (
        <p className="text-sm text-red-600 mb-2 font-semibold">{error}</p>
      ) : (
        <p className="text-xs sm:text-sm text-gray-600">Haz clic en el ícono de cámara para cambiar tu imagen</p>
      )}
    </div>
  );
};

export const AvatarUpload = memo(AvatarUploadComponent);
