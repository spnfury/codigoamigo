# Configuración de Publicación Automática de Chollos en Telegram

## Descripción

El sistema ahora publica automáticamente nuevas ofertas en un canal de Telegram cada vez que se crea un chollo activo. Además, Jenkins ejecuta el script de sincronización periódicamente para obtener nuevas ofertas de los canales de Telegram.

## Configuración Requerida

### 1. Configurar Chat ID de Telegram

Edita el archivo `config/ai_config.php` y configura el chat ID del canal donde quieres publicar los chollos:

```php
define('TELEGRAM_CHOLLOS_CHAT_ID_SALIDA', '-1001234567890'); // Tu chat ID aquí
```

**Cómo obtener el Chat ID:**
1. Crea un canal en Telegram
2. Agrega el bot como administrador del canal
3. Envía un mensaje al canal
4. Visita: `https://api.telegram.org/bot<TOKEN>/getUpdates`
5. Busca el `chat.id` en la respuesta (será un número negativo para canales)

### 2. Configurar Jenkins para Ejecución Periódica

El pipeline `telegram-sync-job.groovy` está configurado para ejecutarse cada 30 minutos automáticamente.

**Para cambiar la frecuencia:**

Edita `jenkins/telegram-sync-job.groovy` y modifica la línea:

```groovy
triggers {
    cron('H/30 * * * *')  // Cambiar aquí
}
```

**Opciones de frecuencia:**
- `H/15 * * * *` - Cada 15 minutos
- `H/30 * * * *` - Cada 30 minutos (actual)
- `H * * * *` - Cada hora
- `0 */2 * * *` - Cada 2 horas
- `0 */6 * * *` - Cada 6 horas

**Nota:** La `H` distribuye la carga aleatoriamente dentro del intervalo para evitar que todos los jobs se ejecuten al mismo tiempo.

### 3. Verificar que el Bot tiene Permisos

Asegúrate de que el bot de Telegram:
- Está agregado como administrador del canal de salida
- Tiene permisos para enviar mensajes
- Tiene permisos para enviar fotos (si los chollos tienen imágenes)

## Funcionamiento

### Publicación Automática

Cuando se crea un nuevo chollo con `estado = 1` (activo):
1. El sistema automáticamente intenta publicarlo en el canal de Telegram configurado
2. Si el envío es exitoso, se marca el chollo como `publicado_telegram = true`
3. Si falla, se registra un error pero el chollo se crea igualmente

### Sincronización Periódica (Jenkins)

El pipeline de Jenkins:
1. Se ejecuta automáticamente cada 30 minutos
2. Obtiene todas las fuentes activas de Telegram desde la API
3. Descarga los últimos 500 mensajes de cada canal
4. Procesa los mensajes y crea chollos en la base de datos
5. Los chollos nuevos se publican automáticamente en Telegram (si están activos)

## Formato del Mensaje en Telegram

Los chollos se publican con el siguiente formato:

```
🔥 *Título del Chollo*

Descripción del chollo...

💰 ~~Precio Original~~ *Precio Descuento* (-X%)

🔗 [Ver oferta en Amazon](https://www.codigoamigo.com/chollo/ID)
```

Si el chollo tiene imagen, se envía como foto con el texto como caption.

## Solución de Problemas

### Los chollos no se publican en Telegram

1. Verifica que `TELEGRAM_CHOLLOS_CHAT_ID_SALIDA` esté configurado en `config/ai_config.php`
2. Verifica que el bot sea administrador del canal
3. Revisa los logs de PHP para ver errores: `tail -f /var/log/php*.log`

### Jenkins no ejecuta el pipeline

1. Verifica que el job esté configurado correctamente en Jenkins
2. Revisa los logs de Jenkins: "Console Output" del build
3. Verifica que el script Python tenga permisos de ejecución

### El script Python falla

1. Revisa `public_html/scripts/telegram_monitor.log`
2. Verifica que `config.py` esté configurado correctamente
3. Verifica que la sesión de Telegram esté activa (`telegram_session.session`)

## Desactivar Publicación Automática

Si quieres desactivar la publicación automática temporalmente:

1. Deja `TELEGRAM_CHOLLOS_CHAT_ID_SALIDA` vacío en `config/ai_config.php`
2. O cambia el estado de los chollos a `0` (inactivo) antes de crearlos


