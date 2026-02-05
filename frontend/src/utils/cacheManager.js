/**
 * Administrador de caché simple para solicitudes API.
 * Reduce llamadas innecesarias y mejora el rendimiento.
 */

class CacheManager {
  constructor(defaultTTL = 5 * 60 * 1000) { // 5 minutes default
    this.cache = new Map();
    this.defaultTTL = defaultTTL;
  }

  /**
   * Obtiene datos cacheados si existen y no han expirado.
   */
  get(key) {
    const cached = this.cache.get(key);
    
    if (!cached) {
      return null;
    }

    // Verifica si el caché ha expirado
    if (Date.now() > cached.expiresAt) {
      this.cache.delete(key);
      return null;
    }

    return cached.data;
  }

  /**
   * Almacena datos en caché con un TTL (tiempo de vida).
   */
  set(key, data, ttl = this.defaultTTL) {
    this.cache.set(key, {
      data,
      expiresAt: Date.now() + ttl,
    });
  }

  /**
   * Limpia una entrada específica del caché o todo el caché.
   */
  clear(key = null) {
    if (key) {
      this.cache.delete(key);
    } else {
      this.cache.clear();
    }
  }

  /**
   * Verifica si una clave existe y es válida (no expirada).
   */
  has(key) {
    return this.get(key) !== null;
  }
}

export const cacheManager = new CacheManager();
