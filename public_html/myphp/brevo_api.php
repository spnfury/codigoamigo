<?php
/**
 * Integración con Brevo API para envío de newsletters
 * 
 * Usa la librería oficial getbrevo/brevo-php ya instalada en el proyecto
 * Brevo API Documentation: https://developers.brevo.com/
 */

// Cargar autoloader de Composer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Asegurar disponibilidad del logger (log_info) para mensajes informativos
if (!function_exists('log_info')) {
    require_once __DIR__ . '/../inc/logger.php';
}

// Incluir configuración
if (!defined('BREVO_API_KEY')) {
    include_once __DIR__ . '/../config/email_config.php';
}

// Usar la librería oficial de Brevo
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Api\AccountApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use Brevo\Client\Model\SendSmtpEmailTo;
use Brevo\Client\Model\SendSmtpEmailSender;
use Brevo\Client\Model\SendSmtpEmailReplyTo;

// PHPMailer para fallback SMTP
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Envía un email usando la API REST de Brevo
 * 
 * @param string $to_email Email del destinatario
 * @param string $to_name Nombre del destinatario
 * @param string $subject Asunto del email
 * @param string $html_content Contenido HTML del email
 * @param string $text_content Contenido texto plano (opcional)
 * @param string $from_email Email del remitente (opcional)
 * @param string $from_name Nombre del remitente (opcional)
 * @param array $tags Tags para tracking (opcional)
 * @return array Resultado del envío ['success' => bool, 'message_id' => string, 'error' => string]
 */
function enviarNewsletterBrevoAPI($to_email, $to_name, $subject, $html_content, $text_content = '', $from_email = 'noreply@codigoamigo.com', $from_name = 'Código Amigo', $tags = []) {
    
    $resultado = [
        'success' => false,
        'message_id' => null,
        'error' => ''
    ];
    
    // Validar API key - Si no está configurada, usar SMTP como fallback
    if (empty(BREVO_API_KEY) || BREVO_API_KEY === 'xkeysib-YOUR_API_KEY_HERE') {
        // Intentar usar SMTP como fallback si está configurado
        if (defined('BREVO_SMTP_USERNAME') && !empty(BREVO_SMTP_USERNAME)) {
            log_warning("Brevo API key no configurada, usando SMTP como fallback");
            return enviarNewsletterBrevoSMTP($to_email, $to_name, $subject, $html_content, $text_content, $from_email, $from_name, $tags);
        }
        $resultado['error'] = 'BREVO_API_KEY no configurada y SMTP no disponible';
        log_error("Error Brevo API: API key no configurada y SMTP no disponible");
        return $resultado;
    }
    
    // Validar parámetros
    if (empty($to_email) || empty($subject) || empty($html_content)) {
        $resultado['error'] = 'Parámetros inválidos';
        log_error("Error Brevo API: Parámetros inválidos - to_email: $to_email");
        return $resultado;
    }
    
    // Preparar contenido texto
    if (empty($text_content)) {
        $text_content = strip_tags($html_content);
    }
    
    try {
        // Configurar la API de Brevo
        $config = Configuration::getDefaultConfiguration();
        $config->setApiKey('api-key', BREVO_API_KEY);
        
        // Crear instancia de la API
        $apiInstance = new TransactionalEmailsApi(null, $config);
        
        // Configurar remitente
        $sender = new SendSmtpEmailSender();
        $sender->setName($from_name);
        $sender->setEmail($from_email);
        
        // Configurar destinatario
        $to = new SendSmtpEmailTo();
        $to->setEmail($to_email);
        $to->setName($to_name ?: $to_email);
        
        // Configurar reply-to
        $replyTo = new SendSmtpEmailReplyTo();
        $replyTo->setEmail(REPLY_TO_EMAIL);
        $replyTo->setName(FROM_NAME);
        
        // Crear objeto de email
        $sendSmtpEmail = new SendSmtpEmail();
        $sendSmtpEmail->setSender($sender);
        $sendSmtpEmail->setTo([$to]);
        $sendSmtpEmail->setSubject($subject);
        $sendSmtpEmail->setHtmlContent($html_content);
        $sendSmtpEmail->setTextContent($text_content);
        $sendSmtpEmail->setReplyTo($replyTo);

        // Baja en un clic (RFC 8058), igual que en la rama SMTP.
        include_once __DIR__ . '/funciones_baja_email.php';
        $unsub_url = baja_email_url($to_email);
        if ($unsub_url !== '') {
            $sendSmtpEmail->setHeaders([
                'List-Unsubscribe'      => '<' . $unsub_url . '>, <mailto:baja@codigoamigo.com>',
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            ]);
        }

        // Añadir tags si se proporcionan
        if (!empty($tags)) {
            $sendSmtpEmail->setTags($tags);
        }
        
        // Enviar email
        $response = $apiInstance->sendTransacEmail($sendSmtpEmail);
        
        // Éxito
        $resultado['success'] = true;
        $resultado['message_id'] = $response->getMessageId();
        log_info("Email enviado correctamente via Brevo API a: $to_email (Message ID: " . $resultado['message_id'] . ")");
        
    } catch (\Exception $e) {
        // Error
        $resultado['error'] = "Error Brevo API: " . $e->getMessage();
        log_error("Error Brevo API: " . $e->getMessage());
    }
    
    return $resultado;
}

/**
 * Envía newsletter usando SMTP de Brevo como fallback
 * 
 * @param string $to_email Email del destinatario
 * @param string $to_name Nombre del destinatario
 * @param string $subject Asunto del email
 * @param string $html_content Contenido HTML del email
 * @param string $text_content Contenido texto plano (opcional)
 * @param string $from_email Email del remitente (opcional)
 * @param string $from_name Nombre del remitente (opcional)
 * @param array $tags Tags para tracking (opcional)
 * @return array Resultado del envío ['success' => bool, 'message_id' => string, 'error' => string]
 */
function enviarNewsletterBrevoSMTP($to_email, $to_name, $subject, $html_content, $text_content = '', $from_email = 'noreply@codigoamigo.com', $from_name = 'Código Amigo', $tags = []) {
    
    $resultado = [
        'success' => false,
        'message_id' => null,
        'error' => ''
    ];
    
    // Incluir PHPMailer si no está disponible
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
    }
    
    // Incluir configuración si no está definida
    if (!defined('BREVO_SMTP_HOST')) {
        include_once __DIR__ . '/../config/email_config.php';
    }
    
    try {
        $mail = new PHPMailer(true);
        
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = BREVO_SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = BREVO_SMTP_USERNAME;
        $mail->Password = BREVO_SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = BREVO_SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        
        // Remitente
        $mail->setFrom($from_email, $from_name);
        $mail->addReplyTo(defined('REPLY_TO_EMAIL') ? REPLY_TO_EMAIL : $from_email, defined('FROM_NAME') ? FROM_NAME : $from_name);
        
        // Destinatario
        $mail->addAddress($to_email, $to_name ?: $to_email);

        // Baja en un clic (RFC 8058). Imprescindible en la newsletter: es el
        // envío de volumen, justo el que Gmail y Yahoo filtran si falta.
        include_once __DIR__ . '/funciones_baja_email.php';
        $unsub_url = baja_email_url($to_email);
        if ($unsub_url !== '') {
            $mail->addCustomHeader('List-Unsubscribe', '<' . $unsub_url . '>, <mailto:baja@codigoamigo.com>');
            $mail->addCustomHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
        }

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html_content;
        $mail->AltBody = $text_content ?: strip_tags($html_content);
        
        // Enviar
        $mail->send();
        
        // Generar un ID de mensaje simulado (SMTP no devuelve message_id)
        $resultado['success'] = true;
        $resultado['message_id'] = 'smtp-' . md5($to_email . $subject . time());
        
        log_info("Email enviado correctamente via Brevo SMTP a: $to_email");
        
    } catch (Exception $e) {
        $resultado['error'] = "Error Brevo SMTP: " . $mail->ErrorInfo;
        log_error("Error Brevo SMTP: " . $mail->ErrorInfo);
    }
    
    return $resultado;
}

/**
 * Obtiene estadísticas de un email enviado desde Brevo
 * 
 * @param string $message_id ID del mensaje de Brevo
 * @return array Estadísticas ['abierto' => bool, 'clicado' => bool, 'rebotado' => bool]
 */
function obtenerEstadisticasBrevo($message_id) {
    
    $resultado = [
        'abierto' => false,
        'clicado' => false,
        'rebotado' => false,
        'error' => ''
    ];
    
    if (empty($message_id)) {
        $resultado['error'] = 'Message ID vacío';
        return $resultado;
    }
    
    // Validar API key
    if (empty(BREVO_API_KEY) || BREVO_API_KEY === 'xkeysib-YOUR_API_KEY_HERE') {
        $resultado['error'] = 'BREVO_API_KEY no configurada';
        return $resultado;
    }
    
    // Nota: La API de Brevo requiere usar webhooks para tracking en tiempo real
    // Esta función es un placeholder para futuras implementaciones
    
    return $resultado;
}

/**
 * Verifica el estado de la cuenta Brevo y límites
 * 
 * @return array Información de la cuenta ['limite_diario' => int, 'enviados_hoy' => int]
 */
function verificarLimitesBrevo() {
    
    $resultado = [
        'limite_diario' => 300,
        'enviados_hoy' => 0,
        'disponibles' => 300,
        'error' => ''
    ];
    
    // Validar API key
    if (empty(BREVO_API_KEY) || BREVO_API_KEY === 'xkeysib-YOUR_API_KEY_HERE') {
        $resultado['error'] = 'BREVO_API_KEY no configurada';
        return $resultado;
    }
    
    try {
        // Usar la librería oficial de Brevo
        $config = Configuration::getDefaultConfiguration();
        $config->setApiKey('api-key', BREVO_API_KEY);
        
        $apiInstance = new AccountApi(null, $config);
        $account = $apiInstance->getAccount();
        
        // El plan gratuito tiene 300 emails/día
        $resultado['limite_diario'] = 300;
        // Nota: La API no devuelve directamente los emails enviados hoy
        // Se debe llevar un contador propio en la base de datos
        
    } catch (\Exception $e) {
        $resultado['error'] = "Error al verificar cuenta: " . $e->getMessage();
        log_error("Error al verificar límites Brevo: " . $e->getMessage());
    }
    
    return $resultado;
}

?>

