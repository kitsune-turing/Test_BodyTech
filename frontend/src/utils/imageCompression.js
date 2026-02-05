/**
 * Utilidad de compresión de imágenes para reducir tamaño de almacenamiento y transferencia.
 */

/**
 * Comprime una imagen desde una cadena base64.
 * @param {string} base64String - Cadena de imagen codificada en base64.
 * @param {number} quality - Calidad de 0 a 1 (predeterminado 0.7).
 * @param {number} maxWidth - Ancho máximo en píxeles (predeterminado 256).
 * @returns {Promise<string>} - Imagen base64 comprimida.
 */
export const compressImage = (base64String, quality = 0.7, maxWidth = 256) => {
  return new Promise((resolve, reject) => {
    const img = new Image();
    
    img.onload = () => {
      const canvas = document.createElement('canvas');
      let width = img.width;
      let height = img.height;
      if (width > maxWidth) {
        height = (maxWidth / width) * height;
        width = maxWidth;
      }
      
      canvas.width = width;
      canvas.height = height;
      
      const ctx = canvas.getContext('2d');
      ctx.drawImage(img, 0, 0, width, height);
      
      canvas.toBlob(
        (blob) => {
          const reader = new FileReader();
          reader.onload = () => resolve(reader.result);
          reader.onerror = reject;
          reader.readAsDataURL(blob);
        },
        'image/jpeg',
        quality
      );
    };
    
    img.onerror = reject;
    img.src = base64String;
  });
};

/**
 * Obtiene el tamaño de una cadena base64 en KB.
 * @param {string} base64String - Cadena base64.
 * @returns {number} - Tamaño en KB.
 */
export const getBase64Size = (base64String) => {
  const sizeInBytes = (base64String.length * 3) / 4;
  return sizeInBytes / 1024;
};
