# Realtime Service - BodyTech Task Manager

Servicio WebSocket construido con Swoole para actualizaciones en tiempo real.

## Features

- WebSocket server con Swoole
- Autenticación JWT
- Redis Pub/Sub para eventos
- Broadcasting a usuarios conectados
- Reconexión automática

## WebSocket Protocol

### Conexión
```
ws://localhost:9501?token=<jwt_token>
```

Base URL (Docker Compose): ws://localhost:9501

### Mensajes del Servidor

#### Connected
```json
{
  "type": "connected",
  "data": {
    "message": "Connected to realtime service"
  }
}
```

#### Task Created
```json
{
  "type": "task.created",
  "data": {
    "id": 1,
    "title": "New Task",
    "status": "pending",
    "created_at": "2024-01-01T12:00:00Z"
  }
}
```

#### Task Updated
```json
{
  "type": "task.updated",
  "data": {
    "id": 1,
    "title": "Updated Task",
    "status": "in_progress",
    "updated_at": "2024-01-01T12:05:00Z"
  }
}
```

#### Task Deleted
```json
{
  "type": "task.deleted",
  "data": {
    "id": 1
  }
}
```

## Eventos Redis

El servicio escucha eventos en el canal `task_events`:

- `task.created`
- `task.updated`  
- `task.deleted`

## Testing WebSocket

```bash
# Usando wscat
npm install -g wscat
wscat -c "ws://localhost:9501?token=YOUR_JWT_TOKEN"
```

## Variables de Entorno

```env
WS_PORT=9501
REDIS_HOST=localhost
REDIS_PORT=6379
JWT_SECRET=your-secret-key
```

## Development

```bash
# Iniciar servidor
php server.php

# Con Docker
docker-compose up -d realtime-service
```

## Troubleshooting

### No se conecta el WebSocket
- Verificar que el puerto 9501 esté disponible
- Verificar el JWT token es válido
- Revisar logs del servidor

### No se reciben eventos
- Verificar conexión con Redis
- Verificar que task-service publique eventos
- Revisar canal Redis (`task_events`)
