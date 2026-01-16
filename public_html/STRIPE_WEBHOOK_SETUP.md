# Configuración del Webhook de Stripe

## Archivos Implementados

1. `public/crear_sesion_destacar.php` - Crea sesiones de Stripe con metadata completa
2. `public/webhook_stripe.php` - Recibe eventos de Stripe y registra transacciones
3. Modificaciones en:
   - `public/destacar_codigo.php` - Usa AJAX para crear sesión
   - `public/felicidades_destacar.php` - Registra transacciones (backup)
   - `public/felicidades_recarga.php` - Registra transacciones de recarga
   - `app_with_mongo.php` - Incluye campos de Stripe en transacciones con saldo
   - `public/admin_transacciones.php` - Muestra nueva información de Stripe

## Configuración del Webhook en Stripe Dashboard

### Paso 1: Crear el Webhook

1. Ve a https://dashboard.stripe.com/webhooks
2. Haz clic en "Add endpoint"
3. Ingresa la URL: `https://www.codigoamigo.com/webhook_stripe.php`
4. Haz clic en "Add endpoint"

### Paso 2: Seleccionar Eventos

En la lista de eventos, selecciona:
- `checkout.session.completed`

### Paso 3: Copiar el Signing Secret

1. Una vez creado el webhook, haz clic en él
2. En la sección "Signing secret", haz clic en "Reveal"
3. Copia el secreto (comienza con `whsec_`)

### Paso 4: Configurar el Signing Secret

**Opción 1: Variable de entorno (Recomendado)**
```bash
# En el archivo .env o configuración del servidor
export STRIPE_WEBHOOK_SECRET=whsec_tu_secreto_aqui
```

**Opción 2: Editar directamente el archivo**
Edita `public/webhook_stripe.php` y reemplaza:
```php
$endpoint_secret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? 'whsec_TU_WEBHOOK_SECRET_OBTENIDO_DEL_DASHBOARD';
```
Con:
```php
$endpoint_secret = 'whsec_tu_secreto_copiado';
```

### Paso 5: Verificar el Webhook

1. En el Dashboard de Stripe, ve al webhook creado
2. Haz clic en "Send test webhook"
3. Selecciona "checkout.session.completed"
4. Verifica en los logs del servidor que se procesó correctamente

## Probar la Integración

### 1. Probar Destacar Código

1. Inicia sesión en CodigoAmigo
2. Ve a "Mis Anuncios"
3. Haz clic en "Destacar" en un código
4. Elige "Destacar Normal" o "Destacar Super"
5. Selecciona "Tarjeta" y completa el pago
6. Verifica en el panel de transacciones que se registró correctamente

### 2. Verificar en Stripe Dashboard

1. Ve a https://dashboard.stripe.com/payments
2. Busca el pago reciente
3. En "Metadata", deberías ver:
   - `tipo`: destacar_codigo
   - `codigo_id`: ID del código
   - `marca`: nombre de la marca
   - `usuario_id`: ID del usuario
   - `tipo_destacado`: normal o super
   - `descripcion`: descripción del código

### 3. Verificar Panel de Transacciones

1. Ve a `admin_transacciones.php`
2. Busca la transacción reciente
3. Verifica que se muestre:
   - Código/Marca: Link al código patrocinado
   - Método: Tarjeta
   - Enlace a Stripe (si está disponible)
   - Email del cliente

## Troubleshooting

### El webhook no se está ejecutando

1. Verifica que la URL sea correcta y accesible
2. Revisa los logs de error del servidor: `tail -f /var/log/apache2/error.log`
3. Verifica que el webhook esté activo en Stripe Dashboard

### Las transacciones no se registran

1. Verifica los logs: `grep "Transacción registrada" /var/log/apache2/error.log`
2. Verifica que el webhook tenga los permisos correctos
3. Verifica que la firma del webhook sea correcta

### Metadata no aparece en Stripe

1. Verifica que estés usando `crear_sesion_destacar.php` (no el código antiguo)
2. Limpia la caché del navegador
3. Verifica los logs para ver si hay errores

## Campos de Transacción Actualizados

Las transacciones ahora incluyen:

### Para Pagos con Tarjeta:
- `stripe_session_id`: ID de la sesión de Stripe
- `stripe_payment_intent`: ID del intento de pago
- `stripe_customer_email`: Email del cliente
- `stripe_payment_status`: Estado del pago
- `metodo_pago`: 'tarjeta'
- `marca`: Nombre de la marca del código
- `codigo_id`: ID del código patrocinado
- `tipo_destacado`: normal o super
- `subtipo`: normal o super

### Para Pagos con Saldo:
- `metodo_pago`: 'saldo'
- `stripe_session_id`: null
- `stripe_payment_intent`: null
- `marca`: Nombre de la marca del código
- `codigo_id`: ID del código patrocinado
- `tipo_destacado`: normal o super
- `subtipo`: normal o super

## Archivos Modificados

1. `public/crear_sesion_destacar.php` - NUEVO
2. `public/webhook_stripe.php` - NUEVO
3. `public/destacar_codigo.php` - MODIFICADO
4. `public/felicidades_destacar.php` - MODIFICADO
5. `public/felicidades_recarga.php` - MODIFICADO
6. `app_with_mongo.php` - MODIFICADO
7. `public/admin_transacciones.php` - MODIFICADO

## Notas Importantes

- El webhook se ejecuta automáticamente cuando Stripe recibe un pago
- Si el webhook falla, el registro se hace desde la página de éxito (backup)
- Todas las transacciones con Stripe incluyen metadata completa
- Los pagos con saldo no tienen referencia a Stripe
- El panel de transacciones muestra enlaces directos a Stripe Dashboard
