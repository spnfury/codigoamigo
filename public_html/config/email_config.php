<?php
// Configuración de proveedores de email

// Brevo (Sendinblue) - Proveedor principal - Configuración SMTP
define('BREVO_SMTP_HOST', 'smtp-relay.brevo.com');
define('BREVO_SMTP_PORT', 587);
define('BREVO_SMTP_USERNAME', '98bc32001@smtp-brevo.com');
define('BREVO_SMTP_PASSWORD', '59UFq8jANBhQHI1D');
define('BREVO_SMTP_ENCRYPTION', 'tls');

// SendGrid - Fallback
define('SENDGRID_API_KEY', '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');

// Elastic Email - Último recurso
define('ELASTIC_EMAIL_API_KEY', '05575e45-958d-470a-ae00-0ddfa9366845');

// Brevo API Key para newsletters
define('BREVO_API_KEY', 'xkeysib-YOUR_API_KEY_HERE'); // TODO: Reemplazar con tu API key de Brevo

// Configuración de email
define('FROM_EMAIL', 'noreply@codigoamigo.com');
define('FROM_NAME', 'Código Amigo');
define('REPLY_TO_EMAIL', 'info@codigoamigo.com');
?>
