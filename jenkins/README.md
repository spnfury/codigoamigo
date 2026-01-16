# Pipeline de Jenkins para Sincronización de Telegram

Este pipeline ejecuta el script Python `telegram_monitor.py` para sincronizar mensajes de canales de Telegram con la base de datos de chollos.

## Configuración en Jenkins

### 1. Crear un nuevo Pipeline Job

1. En Jenkins, crear un nuevo item de tipo "Pipeline"
2. Nombre sugerido: `telegram-sync-chollos`

### 2. Configurar el Pipeline

**IMPORTANTE**: Para evitar problemas con checkouts locales de Git, usa la opción "Pipeline script" directamente:

#### Opción A: Pipeline Script Directo (Recomendado)

1. En la configuración del job, selecciona:
   - **Definition**: `Pipeline script`
   - **Script**: Copia y pega el contenido completo del archivo `jenkins/telegram-sync-job.groovy`

2. **NO** uses "Pipeline script from SCM" con rutas locales (`file://`), ya que Jenkins no permite checkouts locales por seguridad.

#### Opción B: Usar SCM con Repositorio Remoto

Si tu código está en un repositorio Git remoto (GitHub, GitLab, etc.):

1. **Definition**: `Pipeline script from SCM`
2. **SCM**: Git
3. **Repository URL**: URL remota de tu repositorio (ej: `https://github.com/usuario/repo.git`)
4. **Script Path**: `jenkins/telegram-sync-job.groovy`
5. **Credentials**: Si el repositorio es privado, agrega las credenciales necesarias

#### Opción C: Habilitar Checkouts Locales (No Recomendado)

Si necesitas usar rutas locales, puedes habilitar checkouts locales en Jenkins:

1. Ir a **Manage Jenkins** → **Configure System**
2. Buscar la propiedad del sistema: `hudson.plugins.git.GitSCM.ALLOW_LOCAL_CHECKOUT`
3. Establecer el valor a `true`
4. Reiniciar Jenkins

⚠️ **Nota de Seguridad**: Habilitar checkouts locales puede ser un riesgo de seguridad. Se recomienda usar la Opción A o B.

### 3. Modos de ejecución

El pipeline soporta dos modos:

#### Modo AUTOMATICO (Recomendado)
- El script obtiene automáticamente todas las fuentes activas desde la API
- No requiere parámetros
- Procesa todas las fuentes de Telegram configuradas como activas
- Ideal para ejecución periódica automática

#### Modo MANUAL
- Requiere parámetros específicos:
  - **FUENTE_ID** (obligatorio): ID de la fuente en MongoDB
  - **CHANNEL_USERNAME** (obligatorio): Username del canal sin @
  - **ULTIMO_MENSAJE_ID** (opcional): ID del último mensaje procesado

### 4. Configurar ejecución periódica

En "Build Triggers", configurar:

- **Build periodically**: Usar sintaxis cron:
  - `* * * * *` (cada 1 minuto) ⚠️ **Recomendado para sincronización frecuente**
  - `*/2 * * * *` (cada 2 minutos)
  - `*/5 * * * *` (cada 5 minutos)
  - `H/30 * * * *` (cada 30 minutos)
  - `0 */2 * * *` (cada 2 horas)
  - `0 * * * *` (cada hora)

**Nota**: Ejecutar cada minuto puede generar mucho tráfico. Asegúrate de que:
- El script maneje correctamente los rate limits de Telegram
- La API tenga suficiente capacidad
- Los logs se gestionen adecuadamente

### 5. Requisitos del servidor

- Python 3.6 o superior
- pip para instalar dependencias
- Acceso de lectura/escritura al directorio `public_html/scripts`
- Acceso de escritura al directorio `public_html/images/chollos`
- Archivo `config.py` configurado en `public_html/scripts/`
- Token de API configurado en `config/ai_config.php` (TELEGRAM_SYNC_TOKEN)

## Uso manual

También puedes ejecutar el pipeline manualmente desde Jenkins:

1. Ir al job
2. Click en "Build with Parameters"
3. Seleccionar modo:
   - **AUTOMATICO**: No requiere parámetros, obtiene fuentes desde la API
   - **MANUAL**: Ingresar FUENTE_ID y CHANNEL_USERNAME
4. Click en "Build"

## Logs

Los logs se guardan en:
- Jenkins: Ver "Console Output" del build
- Script Python: `public_html/scripts/telegram_monitor.log`

## Solución de problemas

### Error: "No se encontró config.py"
- Asegúrate de que existe `public_html/scripts/config.py` basado en `config.example.py`
- Verifica que el workspace de Jenkins apunta al directorio correcto

### Error: "Python3 no está instalado"
- Instalar Python 3 en el servidor Jenkins
- Verificar que está en el PATH: `which python3`

### Error: "No autorizado" en Telegram
- Ejecutar el script manualmente la primera vez para autenticarse:
  ```bash
  cd public_html/scripts
  python3 telegram_monitor.py <fuente_id> <channel_username>
  ```

### Error: "Token de autenticación inválido"
- Verificar que `TELEGRAM_SYNC_TOKEN` en `config/ai_config.php` coincide con `API_TOKEN` en `scripts/config.py`




