# Script de Monitoreo de Telegram para Chollos

Este script monitorea canales de Telegram y sincroniza los mensajes con la base de datos de chollos.

## Requisitos

- Python 3.6 o superior
- Telethon
- Credenciales de Telegram API

## Instalación

1. Instalar dependencias:
```bash
pip install -r requirements.txt
```

2. Configurar credenciales:
```bash
cp config.example.py config.py
# Editar config.py con tus credenciales
```

3. Obtener credenciales de Telegram:
   - Ir a https://my.telegram.org/apps
   - Crear una aplicación
   - Copiar API ID y API Hash a `config.py`

4. Configurar token de API:
   - El `API_TOKEN` en `config.py` debe coincidir con `TELEGRAM_SYNC_TOKEN` en `config/ai_config.php`

## Primera ejecución

La primera vez que ejecutes el script, necesitarás autenticarte:

```bash
python3 telegram_monitor.py <fuente_id> <channel_username>
```

Te pedirá:
1. Tu número de teléfono
2. Código de verificación (que recibirás por Telegram)
3. Si tienes 2FA activado, tu contraseña

Después de la primera autenticación, se guardará una sesión en `telegram_session.session` y no necesitarás autenticarte de nuevo.

## Uso

### Desde línea de comandos:

```bash
python3 telegram_monitor.py <fuente_id> <channel_username> [ultimo_mensaje_id]
```

Ejemplo:
```bash
python3 telegram_monitor.py 507f1f77bcf86cd799439011 canalwolfvvi
```

### Desde Jenkins:

Ver `../jenkins/README.md` para instrucciones de configuración del pipeline.

## Parámetros

- `fuente_id`: ID de la fuente en MongoDB (obtenerlo desde el panel de admin)
- `channel_username`: Username del canal sin @ (ej: `canalwolfvvi`)
- `ultimo_mensaje_id` (opcional): ID del último mensaje procesado para continuar desde un punto específico

## Variables de entorno

También puedes usar variables de entorno:

```bash
export FUENTE_ID="507f1f77bcf86cd799439011"
export CHANNEL_USERNAME="canalwolfvvi"
export ULTIMO_MENSAJE_ID="12345"
python3 telegram_monitor.py
```

## Logs

Los logs se guardan en `telegram_monitor.log` en el mismo directorio del script.

## Solución de problemas

### Error: "No autorizado"
- Ejecutar el script manualmente la primera vez para autenticarte

### Error: "Token de autenticación inválido"
- Verificar que `API_TOKEN` en `config.py` coincide con `TELEGRAM_SYNC_TOKEN` en `config/ai_config.php`

### Error: "Fuente no encontrada"
- Verificar que el `fuente_id` existe en la base de datos
- Crear la fuente desde el panel de admin primero

### Error: "Rate limit"
- El script esperará automáticamente el tiempo necesario
- Si persiste, reducir la frecuencia de ejecución


