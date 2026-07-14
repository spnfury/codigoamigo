<?php
// Helper para envío de emails con Brevo SMTP como principal y SendGrid como fallback

// Cargar autoloader de Composer si existe
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Asegurar disponibilidad del logger (log_info) para mensajes informativos
if (!function_exists('log_info')) {
    require_once __DIR__ . '/../inc/logger.php';
}

use SendGrid\Mail\Mail;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Incluir configuración
include_once __DIR__ . '/../config/email_config.php';

// Incluir funciones de usuario para acceso a getCollectionEmailLogs
if (!function_exists('getCollectionEmailLogs')) {
    include_once __DIR__ . '/funciones_usuario.php';
}

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
        $mail->Encoding = 'base64'; // Evitar corrupción de caracteres largos en SMTP 8bit
        
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
        log_info("Email enviado correctamente via Brevo SMTP a: " . $to_email);
        
    } catch (Exception $e) {
        $resultado['error'] = "Error PHPMailer: " . $e->getMessage();
        error_log("Error enviando email via Brevo SMTP: " . $e->getMessage());
    }
    
    return $resultado;
}

// Función wrapper que registra en el log después del envío
function enviarEmailConBrevoYRegistrar($to_email, $to_name, $subject, $html_content, $tipo, $usuario_id = null, $detalles = [], $text_content = '', $from_email = 'noreply@codigoamigo.com', $from_name = 'Código Amigo') {

    // Validar email antes de cualquier procesamiento — evita excepciones de Brevo SMTP
    $to_email_trim = is_string($to_email) ? strtolower(trim($to_email)) : '';
    if (!filter_var($to_email_trim, FILTER_VALIDATE_EMAIL)) {
        error_log("enviarEmailConBrevoYRegistrar: email destinatario inválido — tipo=$tipo usuario=$usuario_id email='" . (string)$to_email . "'");
        return ['success' => false, 'error' => 'Email destinatario inválido: ' . (string)$to_email];
    }
    $to_email = $to_email_trim;

    // Añadir footer de desuscripción a emails no transaccionales
    $tipos_transaccionales = ['activacion_usuario', 'recuperacion_password', 'contacto_form', 'codigo_publicado'];
    if (!in_array($tipo, $tipos_transaccionales)) {
        $unsub_footer = '<hr style="border:none;border-top:1px solid #eee;margin:30px 0 15px;">'
            . '<p style="font-size:12px;color:#999;text-align:center;margin:0;">'
            . 'Si no deseas recibir estos correos, puedes '
            . '<a href="https://www.codigoamigo.com/usuario#preferencias" style="color:#E30613;text-decoration:underline;">configurar tus preferencias de notificación</a>'
            . ' en tu perfil.</p>';
        
        // Insertar antes de </body> si existe, si no al final
        if (stripos($html_content, '</body>') !== false) {
            $html_content = str_ireplace('</body>', $unsub_footer . '</body>', $html_content);
        } else {
            $html_content .= $unsub_footer;
        }
        
        // También añadir al texto plano
        $unsub_text = "\n\n---\nSi no deseas recibir estos correos, configura tus preferencias en: https://www.codigoamigo.com/usuario";
        $text_content .= $unsub_text;
    }
    
    // Enviar el email
    $resultado = enviarEmailConBrevo($to_email, $to_name, $subject, $html_content, $text_content, $from_email, $from_name);
    
    // Registrar en el log
    if (!function_exists('registrarEmailLog')) {
        include_once __DIR__ . '/funciones_email.php';
    }
    
    registrarEmailLog(
        $to_email,
        $to_name,
        $subject,
        $tipo,
        $usuario_id,
        $detalles,
        $resultado['success'],
        $resultado['method'] ?? 'Desconocido',
        $resultado['error'] ?? '',
        $html_content // Pasar el HTML para poder visualizarlo en el admin
    );
    
    return $resultado;
}

/**
 * Comprueba si un usuario acepta recibir un tipo de email determinado.
 * Los emails transaccionales (activación, recuperación password, contacto, publicación) siempre se envían.
 * 
 * @param string $usuario_id ID del usuario en MongoDB
 * @param string $tipo_email Tipo de email (competencia, apertura_codigo, destacado_expira_pronto, etc.)
 * @return bool true si el usuario acepta (o no tiene preferencia definida), false si ha desactivado ese tipo
 */
function usuarioAceptaEmail($usuario_id, $tipo_email) {
    // Emails transaccionales: siempre se envían
    $transaccionales = [
        'activacion_usuario',
        'recuperacion_password',
        'contacto_form',
        'codigo_publicado',
    ];
    
    if (in_array($tipo_email, $transaccionales)) {
        return true;
    }
    
    // Mapeo de tipo de email a campo de preferencia del usuario
    $mapa_preferencias = [
        // Competencia
        'competencia' => 'email_competencia',
        'competencia_home' => 'email_competencia',
        'competencia_home_super' => 'email_competencia',
        'destacado_competencia' => 'email_competencia',
        // Aperturas / uso de código
        'apertura_codigo' => 'email_aperturas',
        // Destacados (ciclo de vida)
        'destacado_expira_pronto' => 'email_destacados',
        'destacado_expirado' => 'email_destacados',
        'destacado_auto_renovado' => 'email_destacados',
        'destacado_saldo_insuficiente' => 'email_destacados',
        // Reenganche / win-back (marketing): debe ser opt-out-able
        'reengagement_publicar' => 'email_reengagement',
        'reengagement_vip_publicador' => 'email_reengagement',
        'winback_vip_caducado' => 'email_reengagement',
        'power_publisher_vip' => 'email_reengagement',
        'cross_sell_destacar_vip' => 'email_reengagement',
        'followup_ia_modal_vip' => 'email_reengagement',
    ];

    $campo = $mapa_preferencias[$tipo_email] ?? null;

    // Buscar usuario (siempre, para comprobar estado de baja además de la preferencia)
    try {
        $collection_usuarios = getCollectionUsuarios();
        $projection = ['estado' => 1, 'email_marketing' => 1];
        if ($campo) {
            $projection[$campo] = 1;
        }
        $usuario = $collection_usuarios->findOne(
            ['_id' => new \MongoDB\BSON\ObjectId($usuario_id)],
            ['projection' => $projection]
        );

        if (!$usuario) {
            return true; // usuario no encontrado, enviar por defecto
        }

        // GUARD BAJA/ELIMINADO: nunca enviar marketing a cuentas con estado negativo
        // (estado=-3 baja por usuario, otros estados negativos = suspendida/eliminada).
        if (isset($usuario['estado']) && (int)$usuario['estado'] < 0) {
            return false;
        }

        // Opt-out global de marketing (si el usuario lo ha desactivado, respetar).
        if (isset($usuario['email_marketing']) && (int)$usuario['email_marketing'] === 0) {
            return false;
        }

        // Si el tipo no está mapeado a un campo específico, enviar por defecto
        if (!$campo) {
            return true;
        }

        // Si el campo no existe en el documento, default = 1 (activo)
        if (!isset($usuario[$campo])) {
            return true;
        }

        return (int)$usuario[$campo] === 1;

    } catch (\Exception $e) {
        error_log("Error comprobando preferencia email ($tipo_email) para usuario $usuario_id: " . $e->getMessage());
        return true; // En caso de error, enviar por defecto
    }
}

/**
 * Envía email usando Brevo SMTP como principal y SendGrid como fallback
 */
function enviarEmailConBrevo($to_email, $to_name, $subject, $html_content, $text_content = '', $from_email = 'noreply@codigoamigo.com', $from_name = 'Código Amigo') {

    // Validar parámetros
    if (empty($to_email) || empty($to_name) || empty($subject) || empty($html_content)) {
        error_log("Error enviarEmailConBrevo: Parámetros inválidos - to_email: $to_email, to_name: $to_name");
        return [
            'success' => false,
            'method' => '',
            'error' => 'Parámetros inválidos'
        ];
    }

    // Validar formato email destinatario — evita excepciones SMTP
    if (!filter_var(trim((string)$to_email), FILTER_VALIDATE_EMAIL)) {
        error_log("Error enviarEmailConBrevo: email destinatario malformado: '" . (string)$to_email . "'");
        return [
            'success' => false,
            'method' => '',
            'error' => 'Email destinatario inválido: ' . (string)$to_email
        ];
    }
    
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
            log_info("Email enviado correctamente via Brevo SMTP a: " . $to_email);
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
            log_info("Email enviado correctamente via SendGrid (fallback) a: " . $to_email);
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
            log_info("Email enviado correctamente via Elastic Email (último recurso) a: " . $to_email);
            return $resultado;
        } else {
            $resultado['error'] .= " | Elastic Email: " . json_encode($resultado_elastic);
        }
        
    } catch (Exception $e) {
        error_log("Error enviando email via Elastic Email (último recurso): " . $e->getMessage());
        $resultado['error'] .= " | Elastic Email: " . $e->getMessage();
    }
    
    // Último intento: PHP mail() nativo
    try {
        $headers  = 'From: ' . $from_name . ' <' . $from_email . ">\r\n";
        $headers .= 'Reply-To: ' . $from_email . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        $mail_ok = @mail($to_email, $subject, $html_content, $headers);
        if ($mail_ok) {
            $resultado['success'] = true;
            $resultado['method'] = 'PHP mail()';
            return $resultado;
        } else {
            $resultado['error'] .= ' | PHP mail() falló';
        }
    } catch (Exception $e) {
        $resultado['error'] .= ' | PHP mail(): ' . $e->getMessage();
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
                <p><a href="https://www.codigoamigo.com/nuevo_password?codigo=' . $email_encode . '" style="background-color: #E30613; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Cambiar contraseña</a></p>
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
    
    // Añadir nota de sistema si existe (para fallback de reCAPTCHA)
    if (!empty($datos['_sistema_nota'])) {
        $html_content .= "<hr style='border: 1px dashed #ccc; margin: 20px 0;'>" .
                         "<p style='color: #999; font-size: 12px; font-style: italic;'>⚠️ Nota interna: " . 
                         htmlspecialchars($datos['_sistema_nota']) . "</p>";
    }

    $text_content = "Nuevo contacto desde el formulario " . $datos["origin"] . ":\n\n" .
                   "Nombre: " . $datos["nombre"] . "\n" .
                   "Correo: " . $datos["correo"] . "\n" .
                   "Teléfono: " . $datos["telefono"] . "\n" .
                   "Mensaje: " . $datos["mensaje"];
    
    if (!empty($datos['_sistema_nota'])) {
        $text_content .= "\n\n---\nNota interna: " . $datos['_sistema_nota'];
    }

    // Datos para logging
    $usuario_id = $_SESSION['user_id'] ?? null;
    $detalles = [
        'origin' => $datos['origin'] ?? 'contacto',
        'telefono' => $datos['telefono'] ?? '',
        'from_email' => $datos['correo'] ?? ''
    ];

    return enviarEmailConBrevoYRegistrar(
        "thevega82@gmail.com",
        "Sergi",
        $datos["origin"],
        $html_content,
        'contacto_form',
        $usuario_id,
        $detalles,
        $text_content,
        "info@codigoamigo.com",
        "Código Amigo"
    );
}

/**
 * Envía un email notificando a los seguidores que un usuario ha publicado algo
 */
function enviarEmailNotificacionPublicacionUsuario($to_email, $to_name, $usuario_autor_nombre, $item_tipo, $item_titulo, $item_url, $item_imagen = null) {
    if (!$to_email) return false;
    
    $tipo_label = $item_tipo === 'chollo' ? 'chollo' : 'código';
    $subject = "¡Novedades! $usuario_autor_nombre ha publicado un nuevo $tipo_label";
    
    $html_content = '
        <html>
            <head>
                <title>' . $subject . '</title>
            </head>
            <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f9f9f9; padding: 20px;">
                <div style="max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <img src="https://www.codigoamigo.com/img/logo_codigoamigo.png" alt="Código Amigo" style="max-width: 150px;">
                    </div>
                    <h2 style="color: #E30613; text-align: center;">¡Nuevas publicaciones!</h2>
                    <p>Hola <strong>' . htmlspecialchars($to_name) . '</strong>,</p>
                    <p>El usuario <strong>' . htmlspecialchars($usuario_autor_nombre) . '</strong> al que sigues acaba de publicar un nuevo ' . $tipo_label . ' que podría interesarte.</p>
                    
                    <div style="background: #f5f5f5; border-left: 4px solid #E30613; padding: 15px; margin: 20px 0; border-radius: 4px;">
                        <h3 style="margin-top: 0; color: #333;">' . htmlspecialchars($item_titulo) . '</h3>';
    
    if ($item_imagen) {
        $html_content .= '<div style="text-align:center; margin: 15px 0;"><img src="' . htmlspecialchars($item_imagen) . '" style="max-width: 100%; max-height: 200px; border-radius: 8px;"></div>';
    }
    
    $html_content .= '</div>
                    
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="' . htmlspecialchars($item_url) . '" style="background-color: #E30613; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">Ver ' . ucfirst($tipo_label) . '</a>
                    </div>
                    
                    <p style="color: #666; font-size: 14px; text-align: center; margin-top: 40px;">
                        Has recibido este email porque sigues a ' . htmlspecialchars($usuario_autor_nombre) . ' en <a href="https://www.codigoamigo.com">Código Amigo</a>.
                    </p>
                </div>
            </body>
        </html>
    ';
    
    $text_content = "¡Novedades! " . $usuario_autor_nombre . " ha publicado un nuevo " . $tipo_label . "\n\n" .
                   "Hola " . $to_name . ",\n\n" .
                   "El usuario " . $usuario_autor_nombre . " al que sigues acaba de publicar un nuevo " . $tipo_label . ".\n\n" .
                   "Título: " . $item_titulo . "\n" .
                   "Puedes verlo aquí: " . $item_url . "\n\n" .
                   "-----------------------------------\n" .
                   "Has recibido este email porque sigues a " . $usuario_autor_nombre . " en Código Amigo.";
    
    return enviarEmailConBrevo(
        $to_email,
        $to_name,
        $subject,
        $html_content,
        $text_content,
        "noreply@codigoamigo.com",
        "Código Amigo"
    );
}
?>
