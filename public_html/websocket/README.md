# Servidor WebSocket para Chat

## Instalación

1. Instalar dependencias de Composer:
```bash
cd /home/admin/web/codigoamigo.com/public_html
composer install
```

2. Crear índices de MongoDB:
```bash
php scripts/create_chat_indexes.php
```

## Ejecutar el Servidor

### Desarrollo (manual)
```bash
php websocket/chat_server.php
```

### Producción (con Supervisor)

Crear archivo `/etc/supervisor/conf.d/chat-websocket.conf`:

```ini
[program:chat-websocket]
command=php /home/admin/web/codigoamigo.com/public_html/websocket/chat_server.php
directory=/home/admin/web/codigoamigo.com/public_html/websocket
autostart=true
autorestart=true
user=admin
redirect_stderr=true
stdout_logfile=/home/admin/web/codigoamigo.com/public_html/logs/websocket.log
```

Luego:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start chat-websocket
```

## Configuración

Editar `chat_server.php` para cambiar:
- Puerto (por defecto: 8080)
- Host (por defecto: 0.0.0.0)
- Orígenes permitidos

## Verificar que funciona

1. El servidor debe estar corriendo en el puerto 8080
2. Verificar logs en `/home/admin/web/codigoamigo.com/public_html/logs/websocket.log`
3. En el navegador, abrir la consola y verificar que se conecta al WebSocket

## Troubleshooting

- **Error de conexión**: Verificar que el puerto 8080 esté abierto
- **Autenticación fallida**: Verificar que las sesiones PHP funcionen correctamente
- **Mensajes no llegan**: Verificar logs del servidor y del cliente

