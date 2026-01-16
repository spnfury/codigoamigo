# Instrucciones para Crear el Job de Sincronización de Chollos en Jenkins

## Método 1: Usando Script Groovy (Recomendado)

### Paso 1: Acceder a Script Console

1. Abre Jenkins en tu navegador
2. Ve a **Manage Jenkins** → **Script Console**
3. Copia y pega el contenido completo del archivo `create-telegram-sync-job.groovy`
4. Haz clic en **Run**
5. Deberías ver: `✅ Job 'telegram-sync-chollos' creado exitosamente`

### Paso 2: Verificar el Job

1. Ve al dashboard de Jenkins
2. Deberías ver el job `telegram-sync-chollos`
3. Haz clic en el job para ver su configuración

## Método 2: Crear Manualmente

### Paso 1: Crear Nuevo Pipeline Job

1. En Jenkins, haz clic en **New Item**
2. Ingresa el nombre: `telegram-sync-chollos`
3. Selecciona **Pipeline**
4. Haz clic en **OK**

### Paso 2: Configurar el Pipeline

1. En la sección **Pipeline**, selecciona:
   - **Definition**: `Pipeline script`
   - **Script**: Copia y pega el contenido completo del archivo `telegram-sync-chollos.groovy`

### Paso 3: Configurar Ejecución Periódica

1. En **Build Triggers**, marca:
   - **Build periodically**
   - En el campo **Schedule**, ingresa: `H/30 * * * *` (cada 30 minutos)

### Paso 4: Guardar

1. Haz clic en **Save**
2. El job se ejecutará automáticamente cada 30 minutos

## Configuración de Frecuencia

Para cambiar la frecuencia de ejecución, modifica el valor en **Build Triggers**:

- `H/15 * * * *` - Cada 15 minutos
- `H/30 * * * *` - Cada 30 minutos (recomendado)
- `H * * * *` - Cada hora
- `0 */2 * * *` - Cada 2 horas
- `0 */6 * * *` - Cada 6 horas

**Nota:** La `H` distribuye la carga aleatoriamente para evitar que todos los jobs se ejecuten al mismo tiempo.

## Verificar que Funciona

### Ejecutar Manualmente

1. Ve al job `telegram-sync-chollos`
2. Haz clic en **Build Now**
3. Espera a que termine
4. Revisa el **Console Output** para ver los resultados

### Ver Logs

Los logs se guardan en:
- **Jenkins Console Output**: Ver en el build específico
- **Script Log**: `/home/admin/web/codigoamigo.com/public_html/scripts/telegram_monitor.log`

## Solución de Problemas

### Error: "No se encontró config.py"

1. Verifica que existe `/home/admin/web/codigoamigo.com/public_html/scripts/config.py`
2. Si no existe, copia `config.example.py` a `config.py` y configura los valores

### Error: "Telethon no instalado"

Ejecuta en el servidor:
```bash
cd /home/admin/web/codigoamigo.com/public_html/scripts
source venv/bin/activate
pip install telethon requests
```

### Error: "Python no encontrado"

1. Verifica que Python 3 está instalado: `python3 --version`
2. Verifica que el venv existe: `ls -la /home/admin/web/codigoamigo.com/public_html/scripts/venv`

### El job no se ejecuta automáticamente

1. Verifica que el trigger esté configurado en **Build Triggers**
2. Verifica que Jenkins esté corriendo
3. Revisa los logs de Jenkins para ver si hay errores

## Configuración de Telegram

Para que los chollos se publiquen automáticamente en Telegram:

1. Edita `config/ai_config.php`
2. Configura `TELEGRAM_CHOLLOS_CHAT_ID_SALIDA` con el ID de tu canal
3. Asegúrate de que el bot sea administrador del canal

Ver `CONFIGURACION_TELEGRAM_CHOLLOS.md` para más detalles.


