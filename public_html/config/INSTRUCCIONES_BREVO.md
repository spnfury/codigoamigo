# Configuración de Brevo para Código Amigo

## ✅ Configuración Completada

El sistema ya está configurado con las credenciales SMTP de Brevo:

- **Servidor SMTP:** smtp-relay.brevo.com
- **Puerto:** 587
- **Usuario:** thevega82@gmail.com
- **Contraseña:** dtIZ1gzUkPW7xVH4
- **Encriptación:** TLS

## Pasos para configurar Brevo (anteriormente Sendinblue)

### 1. Crear cuenta en Brevo
1. Ve a [https://www.brevo.com](https://www.brevo.com)
2. Crea una cuenta gratuita
3. Verifica tu email

### 2. Obtener credenciales SMTP
1. En el panel de Brevo, ve a **SMTP & API**
2. Selecciona **SMTP**
3. Copia las credenciales SMTP:
   - Servidor: smtp-relay.brevo.com
   - Puerto: 587
   - Usuario: tu_email@dominio.com
   - Contraseña: tu_contraseña_smtp

### 3. Configurar el dominio
1. En el panel de Brevo, ve a **Senders & IP**
2. Selecciona **Domains**
3. Añade tu dominio: `codigoamigo.com`
4. Configura los registros DNS según las instrucciones de Brevo

### 4. Configuración actual
El archivo `config/email_config.php` ya está configurado con:

```php
define('BREVO_SMTP_HOST', 'smtp-relay.brevo.com');
define('BREVO_SMTP_PORT', 587);
define('BREVO_SMTP_USERNAME', 'thevega82@gmail.com');
define('BREVO_SMTP_PASSWORD', 'dtIZ1gzUkPW7xVH4');
define('BREVO_SMTP_ENCRYPTION', 'tls');
```

### 5. Verificar configuración
1. Ve a `https://www.codigoamigo.com/test_brevo_email.php`
2. Verifica que los emails se envían correctamente
3. Revisa tu bandeja de entrada y spam

## Sistema de Fallback

El sistema está configurado con tres niveles de fallback:

1. **Brevo** (Principal) - Mejor deliverability
2. **SendGrid** (Fallback) - Si Brevo falla
3. **Elastic Email** (Último recurso) - Si ambos fallan

## Ventajas de Brevo

- ✅ Mejor deliverability que SendGrid
- ✅ Precios más competitivos
- ✅ API más simple
- ✅ Mejor soporte para España
- ✅ Menos probabilidad de ir a spam

## Monitoreo

Los logs de envío se guardan en:
- `/var/log/apache2/error.log` (errores del servidor)
- Logs de Brevo en su panel de control
- Notificaciones via Telegram (función mandaBot)

## Troubleshooting

### Si los emails no llegan:
1. Verifica que la API key sea correcta
2. Comprueba que el dominio esté verificado en Brevo
3. Revisa los logs de error
4. Verifica que no estén en spam

### Si hay errores de API:
1. Verifica los límites de tu plan de Brevo
2. Comprueba que la API key tenga permisos de envío
3. Revisa la configuración de DNS del dominio
