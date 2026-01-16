# Configuración de Jenkins para Ejecución Cada 1 Minuto

## Pasos para Configurar

### 1. Acceder a Jenkins

1. Abre tu panel de Jenkins
2. Navega al job `telegram-sync-chollos` (o créalo si no existe)

### 2. Configurar Build Triggers

1. En el job, haz clic en **"Configure"** (Configurar)
2. Desplázate hasta la sección **"Build Triggers"** (Disparadores de construcción)
3. Marca la casilla **"Build periodically"** (Construir periódicamente)
4. En el campo **"Schedule"**, ingresa:
   ```
   * * * * *
   ```
   Esto ejecutará el job cada minuto.

### 3. Configurar Parámetros por Defecto

Para que siempre use el modo AUTOMATICO:

1. En la sección **"This project is parameterized"** (Este proyecto está parametrizado)
2. Asegúrate de que el parámetro `MODO_EJECUCION` tenga como valor por defecto: `AUTOMATICO`

### 4. Verificar Configuración

La configuración debería verse así:

```
Build Triggers:
☑ Build periodically
  Schedule: * * * * *

Parameters:
- MODO_EJECUCION: AUTOMATICO (default)
- FUENTE_ID: (vacío)
- CHANNEL_USERNAME: canalwolfvvi
- ULTIMO_MENSAJE_ID: (vacío)
```

### 5. Guardar y Probar

1. Haz clic en **"Save"** (Guardar)
2. Espera 1-2 minutos y verifica que el job se ejecute automáticamente
3. Revisa los logs en **"Build History"** para confirmar que funciona

## Sintaxis Cron

La sintaxis `* * * * *` significa:
- `*` = cada minuto
- `*` = cada hora
- `*` = cada día del mes
- `*` = cada mes
- `*` = cada día de la semana

## Consideraciones Importantes

### ⚠️ Rate Limiting

Ejecutar cada minuto puede causar:
- **Rate limits de Telegram**: El script debe manejar errores 429 (rate limit)
- **Carga en el servidor**: Asegúrate de que el servidor pueda manejar la carga
- **Logs**: Los logs crecerán rápidamente, considera rotación de logs

### ✅ Recomendaciones

1. **Monitorear logs**: Revisa regularmente los logs para detectar problemas
2. **Rate limiting en el script**: El script ya maneja rate limits, pero verifica que funcione
3. **Notificaciones**: Considera configurar alertas si el job falla repetidamente
4. **Backup**: Asegúrate de tener backups de la base de datos

### 🔄 Alternativas

Si cada minuto es demasiado frecuente, considera:
- `*/2 * * * *` - Cada 2 minutos
- `*/5 * * * *` - Cada 5 minutos
- `*/10 * * * *` - Cada 10 minutos

## Verificar que Funciona

Para verificar que el job se ejecuta cada minuto:

1. Ve a **"Build History"** del job
2. Deberías ver builds nuevos cada minuto
3. Revisa el **"Console Output"** de cada build para ver los resultados

## Solución de Problemas

### El job no se ejecuta automáticamente

1. Verifica que la casilla "Build periodically" esté marcada
2. Verifica la sintaxis cron: debe ser exactamente `* * * * *`
3. Revisa los logs de Jenkins para errores
4. Verifica que el reloj del servidor esté sincronizado

### El job falla frecuentemente

1. Revisa los logs para identificar el error
2. Verifica rate limits de Telegram
3. Considera aumentar el intervalo (cada 2-5 minutos)
4. Verifica que el script Python tenga permisos de ejecución

### Demasiados builds en cola

1. Considera aumentar el intervalo de ejecución
2. Verifica que los builds anteriores terminen antes de que comience el siguiente
3. Configura "Quiet period" si es necesario

