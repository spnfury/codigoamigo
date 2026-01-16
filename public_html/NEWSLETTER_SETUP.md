# Sistema de Newsletters con Brevo

## Configuración Inicial

### 1. Obtener API Key de Brevo

1. Accede a tu cuenta de Brevo: https://www.brevo.com
2. Ve a **SMTP & API** > **API Keys**
3. Crea una nueva API key o copia una existente
4. La API key tiene el formato: `xkeysib-XXXXXXXXXXXXXXXXXXXXXXXXX`

### 2. Configurar API Key

Edita el archivo `config/email_config.php` y reemplaza:

```php
define('BREVO_API_KEY', 'xkeysib-YOUR_API_KEY_HERE');
```

Por tu API key real:

```php
define('BREVO_API_KEY', 'xkeysib-tu-api-key-aqui');
```

### 3. Configurar Cron Job

El sistema necesita ejecutarse cada hora para procesar la cola de newsletters.

Añade esta línea a tu crontab:

```bash
0 * * * * /usr/bin/php /home/admin/web/codigoamigo.com/public_html/cron/newsletter_processor.php
```

Para editar el crontab:

```bash
crontab -e
```

### 4. Verificar Permisos

Asegúrate de que el archivo de log tenga permisos de escritura:

```bash
chmod 666 /home/admin/web/codigoamigo.com/public_html/cron/newsletter_processor.log
```

## Uso del Sistema

### Crear una Newsletter

1. Accede al panel de administración: `/admin_newsletters`
2. Haz clic en "Crear Nueva Newsletter"
3. Completa los campos:
   - **Título**: Nombre interno de la newsletter
   - **Asunto**: Asunto del email que recibirán los usuarios
   - **Contenido HTML**: Contenido del email (puedes usar el editor WYSIWYG)
   - **Contenido Texto**: Versión en texto plano (opcional, se genera automáticamente)
4. Haz clic en "Crear Newsletter"

### Procesamiento Automático

- El sistema procesa automáticamente hasta 300 emails por día
- Los emails se envían cada hora (según el cron job)
- Si hay más de 300 destinatarios, se distribuyen en varios días
- El sistema respeta el límite diario de Brevo

### Estadísticas

El panel muestra:
- **Emails Disponibles Hoy**: Cuántos emails puedes enviar hoy (300 - enviados)
- **Pendientes en Cola**: Emails esperando envío
- **Total Newsletters**: Número de newsletters creadas

Para cada newsletter:
- **Destinatarios**: Total de usuarios que recibirán el email
- **Enviados**: Emails enviados exitosamente
- **Abiertos**: Emails abiertos por los usuarios (con tasa de apertura)
- **Clics**: Clics en enlaces del email (con tasa de clics)
- **Progreso**: Barra de progreso del envío

## Estructura de Base de Datos

### Colección `newsletters`
Almacena las campañas de newsletter:
- `titulo`, `asunto`, `contenido_html`, `contenido_texto`
- `estado`: borrador, programada, enviando, completada
- `total_destinatarios`, `total_enviados`, `total_errores`
- `total_abiertos`, `total_clics` (actualizados automáticamente)
- `fecha_creacion`, `fecha_programada`, `fecha_inicio_envio`, `fecha_fin_envio`

### Colección `newsletter_queue`
Cola de envíos pendientes:
- `newsletter_id`, `usuario_email`, `usuario_nombre`, `usuario_id`
- `estado`: pendiente, enviado, error
- `intentos`, `fecha_envio`, `brevo_message_id`

### Colección `newsletter_stats`
Estadísticas de tracking:
- `newsletter_id`, `usuario_email`
- `tipo`: apertura, clic
- `url`: URL del enlace (solo para clics)
- `fecha`, `ip`, `user_agent`

## Tracking

El sistema incluye tracking automático:

- **Aperturas**: Se registran cuando el usuario abre el email (pixel invisible)
- **Clics**: Se registran cuando el usuario hace clic en un enlace

Los enlaces del email se reemplazan automáticamente con URLs de tracking que redirigen a la URL original después de registrar el clic.

## Límites y Consideraciones

- **Límite diario**: 300 emails/día (plan gratuito de Brevo)
- **Distribución automática**: Si hay más de 300 destinatarios, se distribuyen en varios días
- **Reintentos**: Máximo 3 intentos por email en caso de error
- **Usuarios activos**: Solo se envían a usuarios con `estado = 1` y email válido

## Solución de Problemas

### Los emails no se envían

1. Verifica que el cron job esté configurado correctamente
2. Revisa el log: `/public_html/cron/newsletter_processor.log`
3. Verifica que la API key de Brevo sea correcta
4. Comprueba que haya emails pendientes en la cola

### Error de API Key

Si ves el error "BREVO_API_KEY no configurada":
1. Verifica que hayas configurado la API key en `config/email_config.php`
2. Asegúrate de que el archivo tenga permisos de lectura

### Límite diario alcanzado

Si el sistema indica que el límite diario está alcanzado:
- Espera hasta el día siguiente (el contador se reinicia a medianoche)
- O considera actualizar tu plan de Brevo para más emails/día

## Notas Importantes

- El sistema solo envía a usuarios activos (`estado = 1`)
- Los emails se validan antes de añadirse a la cola
- El tracking funciona automáticamente sin configuración adicional
- Las estadísticas se actualizan en tiempo real



