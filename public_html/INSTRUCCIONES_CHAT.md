# 🚀 Cómo Acceder al Nuevo Sistema de Chat

## 📍 URL de Acceso

**✅ URL que funciona (directa):** 
```
https://www.codigoamigo.com/public/chat_usuario.php
```

**URLs alternativas (pueden no funcionar según configuración):**
- https://www.codigoamigo.com/chat
- https://www.codigoamigo.com/chat-usuario

> 💡 **Usa siempre la URL directa:** `/public/chat_usuario.php` para asegurar que funcione

> ⚠️ **Importante:** Debes estar logueado para acceder. Si no lo estás, te redirigirá al login.

## 🔧 Pasos para Activar el Chat en Tiempo Real

### 1. Instalar Dependencias (si no lo has hecho)

```bash
cd /home/admin/web/codigoamigo.com/public_html
composer install
```

### 2. Crear Índices de MongoDB (optimización)

```bash
php scripts/create_chat_indexes.php
```

### 3. Iniciar el Servidor WebSocket

**Opción A: Manual (desarrollo)**
```bash
cd /home/admin/web/codigoamigo.com/public_html/websocket
php chat_server.php
```

O usar el script:
```bash
./websocket/start_chat_server.sh
```

**Opción B: Con Supervisor (producción - recomendado)**

1. Crear archivo `/etc/supervisor/conf.d/chat-websocket.conf`:

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

2. Ejecutar:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start chat-websocket
```

3. Verificar que está corriendo:
```bash
sudo supervisorctl status chat-websocket
```

### 4. Verificar que Funciona

1. **Abre el chat:** https://www.codigoamigo.com/chat
2. **Verifica el indicador de conexión:** En la esquina superior derecha debe aparecer un punto verde (online) o rojo (offline)
3. **Abre la consola del navegador (F12):** Deberías ver mensajes como:
   - `[WebSocket] Conectado`
   - `[Chat] WebSocket conectado`

## ✨ Funcionalidades Nuevas

Una vez activado, tendrás:

- ✅ **Tiempo Real:** Los mensajes aparecen instantáneamente
- ✅ **Indicador "Escribiendo...":** Verás cuando alguien está escribiendo
- ✅ **Emojis:** Botón de emojis en el input de mensajes
- ✅ **Búsqueda:** Busca mensajes dentro de las conversaciones
- ✅ **Notificaciones:** Notificaciones del navegador cuando recibes mensajes
- ✅ **Fallback Automático:** Si el WebSocket falla, usa polling automáticamente

## 🔍 Solución de Problemas

### El chat no funciona en tiempo real

1. Verifica que el servidor WebSocket esté corriendo:
   ```bash
   ps aux | grep chat_server.php
   ```

2. Verifica que el puerto 2096 esté abierto (actualizado desde 8090):
   ```bash
   netstat -tuln | grep 2096
   ```

3. Revisa los logs:
   ```bash
   tail -f /home/admin/web/codigoamigo.com/public_html/logs/websocket.log
   ```

### Error de conexión en el navegador

- Verifica que la URL del WebSocket sea correcta en `api/chat_api.php`
- Asegúrate de que el puerto 2096 esté accesible desde el navegador
- Si usas HTTPS, necesitas WSS (WebSocket Secure) - ajusta la configuración

### El chat funciona pero sin tiempo real

- El sistema tiene fallback automático a polling
- Verifica que el servidor WebSocket esté corriendo
- Revisa la consola del navegador para ver errores

## 📝 Notas

- El chat funciona **sin** el servidor WebSocket, pero usará polling (menos eficiente)
- Con el servidor WebSocket activo, todo es en tiempo real
- El servidor WebSocket debe estar corriendo 24/7 para producción

## 🎯 Acceso Rápido

```bash
# Iniciar servidor (desarrollo)
php /home/admin/web/codigoamigo.com/public_html/websocket/chat_server.php

# Ver logs
tail -f /home/admin/web/codigoamigo.com/public_html/logs/websocket.log

# Verificar estado (si usas supervisor)
sudo supervisorctl status chat-websocket
```

