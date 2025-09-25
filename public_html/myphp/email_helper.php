<?php
// Helper para envío de emails con Brevo SMTP como principal y SendGrid como fallback

use SendGrid\Mail\Mail;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Incluir configuración
include_once __DIR__ . '/../config/email_config.php';

/**
 * Envía email usando SMTP de Brevo con PHPMailer
 */
function enviarEmailSMTPBrevo($to_email, $to_name, $subject, $html_content, $text_content = '', $from_email = 'noreply@codigoamigo.com', $from_name = 'Código Amigo') {
    
    $resultado = array(
        'success' => false,
        'error' => ''
    );
    
    try {
        // Crear instancia de PHPMailer
        $mail = new PHPMailer(true);
        
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = BREVO_SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = BREVO_SMTP_USERNAME;
        $mail->Password = BREVO_SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = BREVO_SMTP_PORT;
        
        // Configuración de charset
        $mail->CharSet = 'UTF-8';
        
        // Configurar remitente
        $mail->setFrom($from_email, $from_name);
        $mail->addReplyTo($from_email, $from_name);
        
        // Configurar destinatario
        $mail->addAddress($to_email, $to_name);
        
        // Configurar contenido
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html_content;
        
        if (!empty($text_content)) {
            $mail->AltBody = $text_content;
        } else {
            $mail->AltBody = strip_tags($html_content);
        }
        
        // Enviar email
        $mail->send();
        
        $resultado['success'] = true;
        error_log("Email enviado correctamente via Brevo SMTP a: " . $to_email);
        
    } catch (Exception $e) {
        $resultado['error'] = "Error PHPMailer: " . $e->getMessage();
        error_log("Error enviando email via Brevo SMTP: " . $e->getMessage());
    }
    
    return $resultado;
}

/**
 * Envía email usando Brevo SMTP como principal y SendGrid como fallback
 */
function enviarEmailConBrevo($to_email, $to_name, $subject, $html_content, $text_content = '', $from_email = 'noreply@codigoamigo.com', $from_name = 'Código Amigo') {
    
    $resultado = array(
        'success' => false,
        'method' => '',
        'error' => ''
    );
    
    // Intentar envío con Brevo SMTP primero
    try {
        $resultado_brevo = enviarEmailSMTPBrevo($to_email, $to_name, $subject, $html_content, $text_content, $from_email, $from_name);
        
        if ($resultado_brevo['success']) {
            $resultado['success'] = true;
            $resultado['method'] = 'Brevo SMTP';
            error_log("Email enviado correctamente via Brevo SMTP a: " . $to_email);
            return $resultado;
        } else {
            $resultado['error'] = "Brevo SMTP: " . $resultado_brevo['error'];
        }
        
    } catch (Exception $e) {
        error_log("Error enviando email via Brevo SMTP: " . $e->getMessage());
        $resultado['error'] = "Brevo SMTP: " . $e->getMessage();
    }
    
    // Si Brevo falla, intentar con SendGrid como fallback
    try {
        $email = new Mail();
        $email->setFrom($from_email, $from_name);
        $email->setSubject($subject);
        $email->addTo($to_email, $to_name);
        
        if (!empty($text_content)) {
            $email->addContent("text/plain", $text_content);
        } else {
            $email->addContent("text/plain", strip_tags($html_content));
        }
        
        $email->addContent("text/html", $html_content);
        
        $sendgrid = new \SendGrid(SENDGRID_API_KEY);
        $response = $sendgrid->send($email);
        
        if ($response->statusCode() == 202) {
            $resultado['success'] = true;
            $resultado['method'] = 'SendGrid (fallback)';
            error_log("Email enviado correctamente via SendGrid (fallback) a: " . $to_email);
            return $resultado;
        } else {
            $resultado['error'] .= " | SendGrid: Status " . $response->statusCode() . " - " . $response->body();
        }
        
    } catch (Exception $e) {
        error_log("Error enviando email via SendGrid (fallback): " . $e->getMessage());
        $resultado['error'] .= " | SendGrid: " . $e->getMessage();
    }
    
    // Si ambos fallan, intentar con Elastic Email como último recurso
    try {
        $data = array(
            "apikey" => ELASTIC_EMAIL_API_KEY,
            "to" => $to_email,
            "body" => $html_content,
            "subject" => $subject,
            "from" => $from_email,
            "fromName" => $from_name,
            "bodyText" => !empty($text_content) ? $text_content : strip_tags($html_content),
            "charsetBodyHtml" => "utf-8",
            "charset" => "utf-8",
            'isTransactional' => true,
        );
        
        $resultado_elastic = send_mail_elastic($data);
        
        if ($resultado_elastic && isset($resultado_elastic->success) && $resultado_elastic->success) {
            $resultado['success'] = true;
            $resultado['method'] = 'Elastic Email (último recurso)';
            error_log("Email enviado correctamente via Elastic Email (último recurso) a: " . $to_email);
            return $resultado;
        } else {
            $resultado['error'] .= " | Elastic Email: " . json_encode($resultado_elastic);
        }
        
    } catch (Exception $e) {
        error_log("Error enviando email via Elastic Email (último recurso): " . $e->getMessage());
        $resultado['error'] .= " | Elastic Email: " . $e->getMessage();
    }
    
    // Si todos los métodos fallan
    error_log("Error: No se pudo enviar email a " . $to_email . " con ningún método. Errores: " . $resultado['error']);
    mandaBot("Error crítico: No se pudo enviar email a " . $to_email . " con ningún método. Errores: " . $resultado['error']);
    
    return $resultado;
}

/**
 * Función específica para envío de emails de recuperación de contraseña
 */
function enviarEmailRecuperacionPassword($datos) {
    $nombre = $datos["username"];
    $email_dire = $datos['mail'];
    $email_encriptado = encriptar($email_dire);
    $email_encode = urlencode($email_encriptado);
    
    $html_content = '
        <html>
            <head>
                <title>Recuperación de contraseña - Código Amigo</title>
            </head>
            <body>
                <img src="https://www.codigoamigo.com/img/logo_codigoamigo.png" alt="Código Amigo" style="max-width: 200px;"><br><br>
                <h2>Recuperación de contraseña</h2>
                <p>Estimado usuario <strong>' . htmlspecialchars($nombre) . '</strong>:</p>
                <p>Te agradecemos que te pongas en contacto con nosotros. Hemos recuperado tu cuenta de usuario de <a href="https://www.codigoamigo.com">Código Amigo</a>.</p>
                <p>Para cambiar tu contraseña, por favor, accede al siguiente enlace. Si el enlace no estuviera activo, por favor, copia y pega en el navegador.</p><br>
                <p><a href="https://www.codigoamigo.com/nuevo_password?codigo=' . $email_encode . '" style="background-color: #ff6b35; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Cambiar contraseña</a></p>
                <p>O copia y pega este enlace en tu navegador:</p>
                <p>https://www.codigoamigo.com/nuevo_password?codigo=' . $email_encode . '</p>
                <br>
                <p>Si no solicitaste este cambio de contraseña, puedes ignorar este email.</p>
                <p>Saludos,<br>El equipo de Código Amigo</p>
            </body>
        </html>
    ';
    
    $text_content = "Recuperación de contraseña - Código Amigo\n\n" .
                   "Estimado usuario " . $nombre . ":\n\n" .
                   "Te agradecemos que te pongas en contacto con nosotros. Hemos recuperado tu cuenta de usuario de Código Amigo.\n\n" .
                   "Para cambiar tu contraseña, por favor, accede al siguiente enlace:\n" .
                   "https://www.codigoamigo.com/nuevo_password?codigo=" . $email_encode . "\n\n" .
                   "Si no solicitaste este cambio de contraseña, puedes ignorar este email.\n\n" .
                   "Saludos,\nEl equipo de Código Amigo";
    
    return enviarEmailConBrevo(
        $email_dire,
        $nombre,
        "Recuperación de contraseña de Código Amigo",
        $html_content,
        $text_content,
        "noreply@codigoamigo.com",
        "Tamara de Código Amigo"
    );
}

/**
 * Función específica para envío de emails de contacto
 */
function enviarEmailContacto($datos, $url_logo_web) {
    $html_content = "<img src='" . $url_logo_web . "' alt='logo codigo amigo' /><br><br>" .
                   "<h1>Nuevo contacto desde el formulario " . htmlspecialchars($datos["origin"]) . ":</h1>" .
                   "<ul>" .
                   "<li><b>Nombre: </b>" . htmlspecialchars($datos["nombre"]) . "</li><br>" .
                   "<li><b>Correo: </b>" . htmlspecialchars($datos["correo"]) . "</li><br>" .
                   "<li><b>Teléfono: </b>" . htmlspecialchars($datos["telefono"]) . "</li><br>" .
                   "<li><b>Mensaje: </b>" . htmlspecialchars($datos["mensaje"]) . "</li><br>" .
                   "</ul>";
    
    $text_content = "Nuevo contacto desde el formulario " . $datos["origin"] . ":\n\n" .
                   "Nombre: " . $datos["nombre"] . "\n" .
                   "Correo: " . $datos["correo"] . "\n" .
                   "Teléfono: " . $datos["telefono"] . "\n" .
                   "Mensaje: " . $datos["mensaje"];
    
    return enviarEmailConBrevo(
        "thevega82@gmail.com",
        "Sergi",
        $datos["origin"],
        $html_content,
        $text_content,
        "info@codigoamigo.com",
        "Código Amigo"
    );
}
?>
