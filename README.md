# BodyTech Task Manager

Un sistema completo de gestión de tareas construido con arquitectura de microservicios, featuring real-time updates, rate limiting, and comprehensive testing.

## Arquitectura

El proyecto está organizado en una arquitectura de microservicios:

```
┌─────────────┐
│   Frontend  │ (React + Redux + Vite)
│  Port: 5173 │
└──────┬──────┘
       │
       ├──────────┐
       │          │
┌──────▼──────┐  │  ┌────────────────┐
│Auth Service │  └─→│  Task Service  │
│  Port: 8001 │     │   Port: 8002   │
└──────┬──────┘     └────────┬───────┘
       │                     │
       │                     │
       ├─────────────────────┤
       │                     │
┌──────▼─────────────────────▼─────┐
│       Realtime Service            │
│     (Swoole WebSocket)            │
│          Port: 9501               │
└────────────┬──────────────────────┘
             │
    ┌────────┴────────┐
    │                 │
┌───▼───┐      ┌────────────┐   ┌────────────┐
│  Redis │      │PostgreSQL  │   │PostgreSQL  │
│  :6379 │      │ Auth :5432 │   │ Task :5433 │
└────────┘      └────────────┘   └────────────┘
```

## Características

### Backend
- **Microservicios** con Phalcon 4.x
- **Arquitectura hexagonal** (DDD + Clean Architecture)
- **Autenticación JWT** con Argon2id hashing
- **Rate limiting** con Redis (sliding window)
- **Paginación, búsqueda y ordenamiento** de tareas
- **Exportación** a CSV y PDF
- **WebSocket** para actualizaciones en tiempo real
- **Event-driven architecture** con Redis Pub/Sub
- **Testing completo**: Unit, Integration, y E2E tests

### Frontend
- **React 18** con Hooks
- **Redux Toolkit** para state management
- **Vite** para bundling y dev server
- **TailwindCSS** para styling
- **React Testing Library** con Jest
- **WebSocket** para real-time updates
- **Responsive design**

## Quick Start

### Prerrequisitos

- Docker y Docker Compose
- Node.js 18+ (para desarrollo frontend)
- PHP 7.4+ (para desarrollo backend)

### Instalación

1. **Clonar el repositorio**
```bash
git clone <repository-url>
cd Test_BodyTech
cd frontend
npm install
```

2. **Iniciar servicios con Docker Compose**
```bash
# Construir e iniciar todos los servicios (frontend + backend + bases de datos)
docker-compose up --build

# O en modo detached (background)
docker-compose up -d --build
```

3. **Esperar a que los servicios estén listos**
```bash
# Verificar el estado de los contenedores
docker-compose ps

# Ver los logs en tiempo real
docker-compose logs -f
```

4. **Acceder a la aplicación**
- **Frontend**: http://localhost:3000
- **Auth Service**: http://localhost:8001
- **Task Service**: http://localhost:8002
- **Realtime Service**: ws://localhost:9501

### Comandos útiles de Docker

```bash
# Detener todos los servicios
docker-compose down

# Detener y eliminar volúmenes (limpieza completa)
docker-compose down -v

# Ver logs de un servicio específico
docker-compose logs -f frontend
docker-compose logs -f auth-service
docker-compose logs -f task-service

# Reconstruir un servicio específico
docker-compose up -d --build frontend

# Ejecutar seeders (después de que los servicios estén corriendo)
docker-compose exec auth-service php scripts/seed.php
docker-compose exec task-service php scripts/seed.php
```

### Desarrollo Local (sin Docker)

Si prefieres desarrollar sin Docker:

1. **Configurar variables de entorno**
```bash
# Copiar archivos de ejemplo
cp .env.example .env
cp frontend/.env.example frontend/.env
```

2. **Iniciar bases de datos y Redis con Docker**
```bash
docker-compose up -d postgres_auth postgres_task redis
```

3. **Iniciar servicios backend manualmente**
```bash
# Auth Service
cd services/auth-service
composer install
php -S localhost:8001 -t public

# Task Service (en otra terminal)
cd services/task-service
composer install
php -S localhost:8002 -t public

# Realtime Service (en otra terminal)
cd services/realtime-service
composer install
php server.php
```

4. **Acceder a la aplicación**
- Frontend: http://localhost:5173

## Documentación de Servicios

- [Auth Service](./services/auth-service/README.md)
- [Task Service](./services/task-service/README.md)
- [Realtime Service](./services/realtime-service/README.md)
- [Frontend](./frontend/README.md)

## Configuración

### Variables de Entorno Principales

#### Backend Services (ejecución local sin Docker)
```env
# Database
DB_HOST=localhost
DB_PORT=5432
DB_NAME=auth_db
DB_USER=auth_user
DB_PASS=auth_pass

# Redis
REDIS_HOST=localhost
REDIS_PORT=6379

# JWT
JWT_SECRET=your-secret-key
JWT_EXP=3600

# Rate Limiting
RATE_LIMIT_ENABLED=true
RATE_LIMIT_DEFAULT=100
RATE_LIMIT_WINDOW=60
```

#### Frontend
```env
VITE_API_AUTH_URL=http://localhost:8001/v1/api
VITE_API_TASK_URL=http://localhost:8002/v1/api
VITE_WS_URL=ws://localhost:9501
```

> Si usas Docker Compose, los servicios backend se conectan internamente a
> `postgres_auth`/`postgres_task` y Redis, por lo que no necesitas ajustar
> variables de entorno para la conectividad entre contenedores.

## Testing

### Frontend Tests
```bash
cd frontend
npm install
npm test                # Run all tests
npm run test:watch      # Watch mode
npm run test:coverage   # With coverage
```

### Backend Tests
```bash
# Auth Service
cd services/auth-service
composer install
composer test

# Task Service
cd services/task-service
composer install
composer test
```

### E2E Tests
```bash
cd tests
phpunit
```

## API Documentation

### Auth Service Endpoints
- `POST /v1/api/register` - Registrar usuario
- `POST /v1/api/login` - Iniciar sesión
- `POST /v1/api/logout` - Cerrar sesión

### Task Service Endpoints
- `GET /v1/api/tasks` - Listar tareas (con paginación, búsqueda, ordenamiento)
- `POST /v1/api/tasks` - Crear tarea
- `GET /v1/api/tasks/{id}` - Obtener tarea
- `PUT /v1/api/tasks/{id}` - Actualizar tarea
- `DELETE /v1/api/tasks/{id}` - Eliminar tarea
- `GET /v1/api/tasks/export/csv` - Exportar a CSV
- `GET /v1/api/tasks/export/pdf` - Exportar a PDF

### Query Parameters (GET /v1/api/tasks)
- `page` - Número de página (default: 1)
- `limit` - Items por página (default: 20, max: 100)
- `status` - Filtrar por estado (pending, in_progress, done)
- `search` - Buscar en título y descripción
- `sort` - Campo de ordenamiento (created_at, updated_at, title, status)
- `order` - Dirección (ASC, DESC)

## Rate Limiting

Todos los endpoints están protegidos con rate limiting:

- **Register**: 10 requests/minuto
- **Login**: 20 requests/minuto
- **List Tasks**: 60 requests/minuto
- **Create Task**: 20 requests/minuto
- **Update/Delete Task**: 30 requests/minuto
- **Export**: 10 requests/minuto

Headers de respuesta:
- `X-RateLimit-Limit` - Límite de requests
- `X-RateLimit-Remaining` - Requests restantes
- `X-RateLimit-Reset` - Timestamp de reset

## WebSocket Protocol

### Conexión
```javascript
ws://localhost:9501?token=<jwt_token>
```

### Mensajes del Servidor
```json
{
  "type": "task.created",
  "data": {
    "id": 1,
    "title": "Nueva tarea",
    "status": "pending"
  }
}
```

Tipos de eventos:
- `connected` - Conexión establecida
- `task.created` - Tarea creada
- `task.updated` - Tarea actualizada
- `task.deleted` - Tarea eliminada

## Desarrollo

### Estructura del Proyecto
```
Test_BodyTech/
├── frontend/               # React application
│   ├── src/
│   │   ├── components/    # React components
│   │   ├── pages/         # Page components
│   │   ├── services/      # API services
│   │   ├── store/         # Redux store
│   │   └── hooks/         # Custom hooks
│   └── __tests__/         # Tests
├── services/
│   ├── auth-service/      # Authentication microservice
│   ├── task-service/      # Task management microservice
│   └── realtime-service/  # WebSocket microservice
└── tests/                 # E2E tests
```

### Tech Stack

**Frontend:**
- React 18
- Redux Toolkit
- React Router DOM
- TailwindCSS
- Vite
- Jest + React Testing Library

**Backend:**
- PHP 7.4+
- Phalcon 4.x
- PostgreSQL
- Redis
- Swoole (WebSocket)
- TCPDF (PDF export)
- PHPUnit