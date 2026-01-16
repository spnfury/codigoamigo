# Instrucciones para Crear el Job de Jenkins "telegram-sync-chollos"

## Método Recomendado: Script Console de Jenkins

### Paso 1: Acceder a Jenkins
1. Abre tu navegador y ve a: `http://tu-servidor:8085/jenkins` (o la URL de tu Jenkins)
2. Inicia sesión con tus credenciales

### Paso 2: Abrir Script Console
1. Haz clic en **"Manage Jenkins"** (Gestionar Jenkins)
2. Haz clic en **"Script Console"** (Consola de Script)

### Paso 3: Ejecutar el Script
1. Copia TODO el contenido del archivo `jenkins/create-telegram-sync-job.groovy`
2. Pégalo en el campo de texto de la Script Console
3. Haz clic en **"Run"** (Ejecutar)

### Paso 4: Verificar
Deberías ver un mensaje como:
```
✅ Job 'telegram-sync-chollos' creado exitosamente
   - Ejecuta cada 30 minutos automáticamente
   - Sincroniza hasta 500 mensajes por canal
   - Publica automáticamente nuevos chollos en Telegram
```

### Paso 5: Verificar el Job
1. Ve al dashboard de Jenkins
2. Deberías ver el job `telegram-sync-chollos`
3. Haz clic en el job para ver su configuración
4. Puedes hacer clic en **"Build Now"** para probarlo manualmente

---

## Método Alternativo: Usar API de Jenkins

Si prefieres usar la API, ejecuta:

```bash
cd /home/admin/web/codigoamigo.com
curl -X POST -u USUARIO:TOKEN \
  http://localhost:8085/jenkins/scriptText \
  --data-urlencode "script=$(cat jenkins/create-telegram-sync-job.groovy)"
```

Reemplaza:
- `USUARIO`: Tu usuario de Jenkins
- `TOKEN`: Tu token de API de Jenkins (o contraseña)

---

## Configuración del Job

El job está configurado para:
- ✅ Ejecutarse automáticamente cada 30 minutos
- ✅ Validar el entorno antes de ejecutar
- ✅ Verificar que Python y Telethon estén instalados
- ✅ Ejecutar el script `telegram_monitor.py`
- ✅ Registrar logs en `telegram_monitor.log`
- ✅ Reintentar hasta 2 veces si falla
- ✅ Timeout de 30 minutos

---

## Solución de Problemas

### El job no se crea
- Verifica que tienes permisos de administrador en Jenkins
- Revisa los logs de Jenkins para ver errores

### El job no se ejecuta automáticamente
- Verifica que el trigger de cron esté activo en la configuración del job
- Revisa la configuración: **Configure** → **Build Triggers** → **Build periodically**

### El job falla al ejecutarse
- Revisa el **Console Output** del build
- Verifica que el script Python existe: `/home/admin/web/codigoamigo.com/public_html/scripts/telegram_monitor.py`
- Verifica que `config.py` existe en el mismo directorio

