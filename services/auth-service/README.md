# Auth Service

Microservicio de autenticación con JWT y Argon2id para el Task Manager.

## Características

- Registro de usuarios con email y password
- Login con generación de JWT (claims: sub, iat, exp, jti)
- Logout con revocación de tokens (blacklist en Redis)
- Hashing de contraseñas con Argon2id
- Validación de tokens con middleware
- Arquitectura Hexagonal (Domain, Application, Infrastructure)

## Estructura

```
auth-service/
├── config/           # Configuraciones
├── migrations/       # Migraciones SQL
├── public/           # Entry point (index.php)
├── scripts/          # Scripts auxiliares
├── seeds/            # Datos de prueba
├── src/
│   ├── Domain/       # Entidades, Value Objects, Interfaces
│   ├── Application/  # Use Cases, DTOs, Ports
│   └── Infrastructure/ # Implementaciones, Controllers, Middleware
└── tests/            # Tests unitarios e integración
```

## Endpoints

Base URL (Docker Compose): http://localhost:8001

### POST /v1/api/register
Registra un nuevo usuario.

**Request:**
```json
{
  "email": "user@example.com",
  "password": "P@ssw0rd!"
}
```

**Response 201:**
```json
{
  "id": 1,
  "email": "user@example.com",
  "created_at": "2025-01-15T10:30:00Z",
  "updated_at": "2025-01-15T10:30:00Z"
}
```

### POST /v1/api/login
Autentica un usuario y retorna JWT.

**Request:**
```json
{
  "email": "user@example.com",
  "password": "P@ssw0rd!"
}
```

**Response 200:**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expires_in": 3600
}
```

### POST /v1/api/logout
Revoca un token JWT.

**Headers:**
```
Authorization: Bearer <jwt>
```

**Response 204:** No Content

### GET /health
Health check del servicio.

**Response 200:**
```json
{
  "status": "healthy",
  "service": "auth-service"
}
```

## Variables de Entorno

Ver `.env.example` para configuración completa:

- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
- `REDIS_HOST`, `REDIS_PORT`
- `JWT_SECRET` (mínimo 32 caracteres)
- `JWT_EXP` (segundos)
- `ARGON2_MEMORY_COST`, `ARGON2_TIME_COST`, `ARGON2_THREADS`

## Desarrollo

```bash
# Instalar dependencias
composer install

# Ejecutar migraciones (automático en Docker)
docker-compose exec auth-service ls migrations/

# Ejecutar seeders
docker-compose exec auth-service php scripts/seed.php

# Tests
composer test
composer test:unit
composer test:integration
```

## Seguridad

- **Password Hashing:** Argon2id con parámetros optimizados
- **JWT Secret:** Debe tener mínimo 32 caracteres
- **Token Revocation:** Blacklist en Redis con expiración automática
- **HTTPS:** Obligatorio en producción
- **CORS:** Configurado en index.php

## Códigos de Error

- `400` - Validación fallida
- `401` - Credenciales inválidas o token inválido
- `409` - Email ya existe
- `500` - Error interno del servidor
