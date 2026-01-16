# Configuración del Webhook de Stripe - GUÍA COMPLETA

## ⚠️ PROBLEMA IDENTIFICADO

Se detectó que las transacciones no se estaban registrando automáticamente porque:

1. **El endpoint secret del webhook NO está configurado** - Está usando el valor por defecto
2. **El webhook probablemente NO está recibiendo eventos** de Stripe
3. **El sistema de backup** en `felicidades_destacar.php` tenía un bug (usaba clave TEST en lugar de LIVE)

## ✅ SOLUCIONES IMPLEMENTADAS

1. ✅ Corregido bug en `felicidades_destacar.php` - Ahora detecta automáticamente si usar TEST o LIVE
2. ✅ Mejorado logging del webhook para detectar problemas
3. ✅ Creado script de monitoreo automático
4. ✅ Creado scripts de diagnóstico y sincronización

## 📋 PASOS PARA CONFIGURAR EL WEBHOOK CORRECTAMENTE

### Paso 1: Verificar/Crear el Webhook en Stripe Dashboard

1. Ve a **https://dashboard.stripe.com/webhooks** (modo LIVE)
2. Si no existe, haz clic en **"Add endpoint"**
3. Ingresa la URL: `https://www.codigoamigo.com/webhook_stripe.php`
4. Haz clic en **"Add endpoint"**

### Paso 2: Configurar Eventos

En la lista de eventos, selecciona:
- ✅ `checkout.session.completed`

### Paso 3: Obtener el Signing Secret

1. Una vez creado el webhook, haz clic en él
2. En la sección **"Signing secret"**, haz clic en **"Reveal"**
3. Copia el secreto (comienza con `whsec_`)
4. **IMPORTANTE**: Guarda este secreto de forma segura

### Paso 4: Configurar el Signing Secret en el Servidor

**Opción A: Variable de entorno (RECOMENDADO)**

1. Edita el archivo de configuración del servidor (ej: `.htaccess`, `php.ini`, o archivo de configuración de Apache)
2. Agrega:
   ```apache
   SetEnv STRIPE_WEBHOOK_SECRET whsec_tu_secreto_aqui
   ```

O si usas PHP-FPM, agrega al archivo de configuración:
```ini
env[STRIPE_WEBHOOK_SECRET] = whsec_tu_secreto_aqui
```

**Opción B: Editar directamente el archivo**

1. Edita `public/webhook_stripe.php`
2. Reemplaza la línea 7:
   ```php
   $endpoint_secret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? 'whsec_TU_WEBHOOK_SECRET_OBTENIDO_DEL_DASHBOARD';
   ```
   Con:
   ```php
   $endpoint_secret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? 'whsec_tu_secreto_copiado_aqui';
   ```

### Paso 5: Verificar el Webhook

1. En el Dashboard de Stripe, ve al webhook creado
2. Haz clic en **"Send test webhook"**
3. Selecciona **"checkout.session.completed"**
4. Verifica en los logs del servidor que se procesó correctamente:
   ```bash
   tail -f /home/admin/web/codigoamigo.com/public_html/php_errors.log | grep WEBHOOK
   ```

### Paso 6: Configurar Monitoreo Automático

Para detectar automáticamente transacciones faltantes, configura un cron job:

```bash
# Editar crontab
crontab -e

# Agregar esta línea para ejecutar cada hora
0 * * * * /usr/bin/php /home/admin/web/codigoamigo.com/public_html/public/monitor_transacciones_stripe.php >> /home/admin/web/codigoamigo.com/public_html/logs/monitor_stripe.log 2>&1
```

## 🔍 VERIFICACIÓN

### Verificar que el webhook está configurado:

```bash
php public/verificar_webhook_stripe.php
```

O accede desde el navegador (requiere login de admin):
```
https://www.codigoamigo.com/public/verificar_webhook_stripe.php
```

### Verificar transacciones faltantes:

```bash
php public/diagnostico_transacciones_stripe_cli.php --dias=7 --modo=live
```

### Sincronizar transacciones faltantes:

```bash
php public/sync_stripe_transactions_cli.php --dias=7 --modo=live --confirmar
```

## 📊 MONITOREO

El script `monitor_transacciones_stripe.php` se ejecuta automáticamente y:

- ✅ Revisa las últimas transacciones cada hora
- ✅ Detecta transacciones faltantes
- ✅ Registra alertas en los logs
- ✅ Puede enviar emails de alerta (si está configurado)

## 🐛 TROUBLESHOOTING

### El webhook no recibe eventos

1. Verifica que la URL sea accesible: `https://www.codigoamigo.com/webhook_stripe.php`
2. Verifica que el endpoint secret esté configurado correctamente
3. Revisa los logs del servidor para ver errores de firma
4. En Stripe Dashboard, revisa la sección "Recent events" del webhook

### Las transacciones no se registran

1. Ejecuta el diagnóstico: `php public/diagnostico_transacciones_stripe_cli.php`
2. Si hay faltantes, sincroniza: `php public/sync_stripe_transactions_cli.php --confirmar`
3. Revisa los logs del webhook en `php_errors.log`
4. Verifica que el webhook esté activo en Stripe Dashboard

### Errores de firma inválida

1. Verifica que el endpoint secret sea correcto
2. Asegúrate de usar el secreto del webhook LIVE (no TEST)
3. Verifica que no haya espacios o caracteres extra en el secreto

## 📝 NOTAS IMPORTANTES

- El webhook debe estar configurado en modo **LIVE** para producción
- El sistema tiene un **backup automático** en `felicidades_destacar.php` que registra transacciones si el webhook falla
- Las transacciones sincronizadas manualmente tienen el campo `sincronizado_manual: true`
- El monitoreo automático revisa los últimos 7 días por defecto

## 🔐 SEGURIDAD

- **NUNCA** compartas el endpoint secret
- **NUNCA** lo subas a repositorios públicos
- Usa variables de entorno cuando sea posible
- Revisa regularmente los logs del webhook

