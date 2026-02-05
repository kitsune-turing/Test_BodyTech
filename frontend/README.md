# Frontend - BodyTech Task Manager

Aplicación web moderna de gestión de tareas construida con React, Redux y WebSocket para actualizaciones en tiempo real.

## Tech Stack

- **React 18** - UI library
- **Redux Toolkit** - State management
- **React Router DOM** - Routing
- **TailwindCSS** - Styling
- **Vite** - Build tool & dev server
- **Jest** - Testing framework
- **React Testing Library** - Component testing
- **WebSocket** - Real-time updates

## Instalación

```bash
# Ejecutar dentro de la carpeta frontend/
# Instalar dependencias
npm install

# Iniciar servidor de desarrollo
npm run dev

# Build para producción
npm run build

# Preview build de producción
npm run preview
```

## Testing

```bash
# Ejecutar todos los tests
npm test

# Watch mode
npm run test:watch

# Coverage report
npm run test:coverage
```

## Variables de Entorno

Crear un archivo `.env` basado en `.env.example`:

```env
# API URLs
VITE_API_AUTH_URL=http://localhost:8001/v1/api
VITE_API_TASK_URL=http://localhost:8002/v1/api

# WebSocket URL
VITE_WS_URL=ws://localhost:9501
```

## Componentes Principales

### TaskList
Componente principal que muestra la lista de tareas con:
- Búsqueda en tiempo real
- Filtros por estado
- Ordenamiento personalizado
- Paginación
- Botones de exportación

### TaskCard
Tarjeta individual de tarea con:
- Visualización de título, descripción y estado
- Botones de editar y eliminar
- Modal de confirmación para eliminar

### Pagination
Componente de paginación con:
- Navegación entre páginas
- Selector de items por página
- Información de página actual

### SortControls
Controles de ordenamiento con:
- Selector de campo (fecha, título, estado)
- Toggle de orden (ASC/DESC)
- Indicadores visuales

### ExportButtons
Botones para exportar tareas a:
- CSV
- PDF

### WebSocketStatus
Indicador de conexión WebSocket en tiempo real:
- Verde: Conectado
- Amarillo: Conectando
- Rojo: Desconectado

## State Management

### Auth Slice
Maneja la autenticación del usuario:
- Login/Logout
- Almacenamiento de token
- Estado de carga

```javascript
// Actions
login(email, password)
logout()
initializeAuth({ token, user })
```

### Tasks Slice
Maneja el estado de las tareas:
- CRUD operations
- Paginación
- Búsqueda y filtros
- WebSocket updates

```javascript
// Actions
fetchTasks()
createTask(taskData)
updateTask(taskId, taskData)
deleteTask(taskId)
setPage(page)
setLimit(limit)
setSearch(search)
setSort({ sortBy, sortOrder })
setFilter(filter)

// WebSocket Actions
taskCreatedViaWebSocket(task)
taskUpdatedViaWebSocket(task)
taskDeletedViaWebSocket(taskId)
```

## WebSocket Integration

El servicio WebSocket se conecta automáticamente cuando el usuario está autenticado:

```javascript
import { useWebSocket } from './hooks/useWebSocket';

function Component() {
  const { connectionState, isConnected } = useWebSocket();

  return (
    <div>
      Estado: {connectionState}
    </div>
  );
}
```

### Eventos WebSocket
- `task.created` - Nueva tarea creada
- `task.updated` - Tarea actualizada
- `task.deleted` - Tarea eliminada

## Features

### Búsqueda en Tiempo Real
- Debounced search (300ms)
- Búsqueda en título y descripción
- Botón de limpiar búsqueda

### Paginación
- Navegación entre páginas
- Selector de items por página (10, 20, 50, 100)
- Información de página actual y total

### Ordenamiento
- Por fecha de creación
- Por fecha de actualización
- Por título
- Por estado
- Orden ascendente/descendente

### Filtros
- Todas las tareas
- Pendientes
- En progreso
- Completadas

### Exportación
- CSV - Tabla simple con todas las tareas
- PDF - Reporte formateado con header y resumen

### Actualizaciones en Tiempo Real
- Las tareas se actualizan automáticamente cuando:
  - Otro usuario crea una tarea
  - Otro usuario actualiza una tarea
  - Otro usuario elimina una tarea

## Testing

### Coverage Goals
- Statements: 80%+
- Branches: 80%+
- Functions: 80%+
- Lines: 80%+

### Test Files
- Components: `src/components/__tests__/*.test.jsx`
- Pages: `src/pages/__tests__/*.test.jsx`
- Store: `src/store/__tests__/*.test.js`
- Services: `src/services/__tests__/*.test.js`

### Example Test
```javascript
import { render, screen } from '../../utils/test-utils';
import { Button } from '../Button';
import userEvent from '@testing-library/user-event';

describe('Button Component', () => {
  it('handles click events', async () => {
    const user = userEvent.setup();
    const handleClick = jest.fn();
    render(<Button onClick={handleClick}>Click me</Button>);

    const button = screen.getByRole('button');
    await user.click(button);

    expect(handleClick).toHaveBeenCalledTimes(1);
  });
});
```

## Build para Producción

```bash
# Build
npm run build

# Output en dist/
# Los archivos están optimizados y minificados
```

### Optimizaciones
- Code splitting automático
- Tree shaking
- Minificación
- CSS purging con TailwindCSS

## Seguridad

- JWT tokens almacenados en localStorage
- Headers CORS configurados
- Validación de inputs
- Sanitización de datos

## Responsive Design

La aplicación está optimizada para:
- Desktop (1024px+)
- Tablet (768px - 1023px)
- Mobile (< 768px)

## Tailwind Configuration

Colores personalizados:
- Primary: #1a1a1a (negro)
- Secondary: #FF9500 (naranja)

## Debugging

### Redux DevTools
```javascript
// Instalar extensión de navegador
// Las acciones y state están visibles en DevTools
```

### React DevTools
```javascript
// Instalar extensión de navegador
// Componentes y props visibles en DevTools
```