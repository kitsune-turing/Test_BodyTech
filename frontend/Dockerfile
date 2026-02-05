# Etapa de construcción
FROM node:20-alpine AS builder

WORKDIR /app

# Copia los archivos de dependencias
COPY package*.json ./

# Instala las dependencias
RUN npm ci

# Copia el resto del código
COPY . .

# Argumentos de build para variables de entorno de Vite
ARG VITE_API_AUTH_URL=http://localhost:8001/v1/api
ARG VITE_API_TASK_URL=http://localhost:8002/v1/api
ARG VITE_WS_URL=ws://localhost:9501

# Establece las variables de entorno para el build
ENV VITE_API_AUTH_URL=${VITE_API_AUTH_URL}
ENV VITE_API_TASK_URL=${VITE_API_TASK_URL}
ENV VITE_WS_URL=${VITE_WS_URL}

# Construye la aplicación para producción
RUN npm run build

# Etapa de producción con nginx
FROM nginx:1.25-alpine

# Copia la configuración personalizada de nginx
COPY <<EOF /etc/nginx/conf.d/default.conf
server {
    listen 80;
    server_name localhost;
    root /usr/share/nginx/html;
    index index.html;

    # Compresión gzip
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/json application/xml+rss;

    # Cache para assets estáticos
    location ~* \.(js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Manejo de SPA - todas las rutas van a index.html
    location / {
        try_files \$uri \$uri/ /index.html;
    }

    # Headers de seguridad
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
}
EOF

# Copia los archivos construidos desde la etapa de builder
COPY --from=builder /app/dist /usr/share/nginx/html

# Expone el puerto 80
EXPOSE 80

# Comando para ejecutar nginx
CMD ["nginx", "-g", "daemon off;"]
