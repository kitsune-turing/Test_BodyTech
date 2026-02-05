# Task Service - BodyTech Task Manager

Microservicio de gestión de tareas con soporte para paginación, búsqueda, ordenamiento y exportación.

## Features

- CRUD completo de tareas
- Paginación, búsqueda y ordenamiento
- Exportación a CSV y PDF
- Rate limiting por endpoint
- Event publishing (Redis Pub/Sub)
- Arquitectura hexagonal

## API Endpoints

Base URL (Docker Compose): http://localhost:8002

### GET /v1/api/tasks
Lista tareas con filtros opcionales.

**Query Parameters:**
- `page` - Número de página (default: 1)
- `limit` - Items por página (default: 20, max: 100)
- `status` - Filtrar por estado (pending, in_progress, done)
- `search` - Buscar en título y descripción
- `sort` - Campo de ordenamiento (created_at, updated_at, title, status)
- `order` - Dirección (ASC, DESC)

**Response 200:**
```json
{
  "items": [...],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 50,
    "pages": 3
  }
}
```

### POST /v1/api/tasks
Crea una nueva tarea.

### GET /v1/api/tasks/{id}
Obtiene una tarea específica.

### PUT /v1/api/tasks/{id}
Actualiza una tarea.

### DELETE /v1/api/tasks/{id}
Elimina una tarea.

### GET /v1/api/tasks/export/csv
Exporta tareas a CSV.

### GET /v1/api/tasks/export/pdf
Exporta tareas a PDF.

## Rate Limiting

- List: 60 req/min
- Create: 20 req/min
- Update/Delete: 30 req/min
- Export: 10 req/min

## Variables de Entorno

Ver `.env.example`:
- Database, Redis, JWT settings
- Rate limiting configuration
- `AUTH_SERVICE_URL` (opcional, validación de tokens)

## Testing

```bash
composer test
```
