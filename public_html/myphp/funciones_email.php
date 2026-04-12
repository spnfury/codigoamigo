<?php
// Funciones para envío de emails del sistema

// Incluir funciones de usuario para acceder a getCollectionEmailLogs
if (!function_exists('getCollectionEmailLogs')) {
    include_once __DIR__ . '/funciones_usuario.php';
}

// Asegurar disponibilidad del logger (log_info) para mensajes informativos
if (!function_exists('log_info')) {
    require_once __DIR__ . '/../inc/logger.php';
}

// Función para registrar un email en el log
function registrarEmailLog($to_email, $to_name, $subject, $tipo, $usuario_id = null, $detalles = [], $enviado = true, $metodo = 'PHP mail()', $error = '', $html_body = '') {
    try {
        $collection_email_logs = getCollectionEmailLogs();
        
        $email_log = [
            'to_email' => $to_email,
            'to_name' => $to_name,
            'subject' => $subject,
            'tipo' => $tipo, // 'recarga_saldo', 'notificacion', 'activacion', etc.
            'usuario_id' => $usuario_id,
            'detalles' => $detalles,
            'enviado' => $enviado,
            'metodo' => $metodo,
            'error' => $error,
            'html_body' => $html_body, // Contenido HTML del email para visualización en admin
            'fecha' => new MongoDB\BSON\UTCDateTime(),
            'fecha_humana' => date('Y-m-d H:i:s')
        ];
        
        $result = $collection_email_logs->insertOne($email_log);
        
        if ($result->getInsertedId()) {
            log_info("Email log registrado: $tipo a $to_email - Método: $metodo");
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error al registrar email log: " . $e->getMessage());
        return false;
    }
}

// Función para enviar email de notificación de saldo cargado
function enviarEmailSaldoCargado($usuario, $cantidad, $nuevo_saldo, $motivo) {
    $to = $usuario['mail'];
    $username = $usuario['username'] ?? 'Usuario';
    $subject = "¡Felicidades! Tu saldo ha sido incrementado - CodigoAmigo";
    
    // Crear el HTML del email
    $html = crearPlantillaEmailSaldo($username, $cantidad, $nuevo_saldo, $motivo);
    
    // Headers del email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: CodigoAmigo <noreply@codigoamigo.com>" . "\r\n";
    $headers .= "Reply-To: soporte@codigoamigo.com" . "\r\n";
    
    // Enviar email
    $enviado = mail($to, $subject, $html, $headers);
    
    // Log del envío
    if ($enviado) {
        error_log("Email de saldo enviado exitosamente a: " . $to . " - Cantidad: " . $cantidad . "€");
    } else {
        error_log("Error al enviar email de saldo a: " . $to);
    }
    
    return $enviado;
}

// Función para crear la plantilla HTML del email
function crearPlantillaEmailSaldo($username, $cantidad, $nuevo_saldo, $motivo) {
    $fecha = date('d/m/Y H:i');
    
    return '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Saldo Incrementado - CodigoAmigo</title>
        <style>
            body {
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #333;
                margin: 0;
                padding: 0;
                background-color: #f4f4f4;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                background-color: #ffffff;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            }
            .header {
                background: linear-gradient(135deg, #E30613, #C40510);
                color: white;
                padding: 30px 20px;
                text-align: center;
            }
            .header h1 {
                margin: 0;
                font-size: 28px;
                font-weight: 700;
            }
            .header p {
                margin: 10px 0 0 0;
                font-size: 16px;
                opacity: 0.9;
            }
            .content {
                padding: 40px 30px;
            }
            .saldo-info {
                background: #f8f9fa;
                border-radius: 10px;
                padding: 25px;
                margin: 25px 0;
                text-align: center;
                border-left: 4px solid #E30613;
            }
            .saldo-cantidad {
                font-size: 36px;
                font-weight: 800;
                color: #E30613;
                margin: 10px 0;
            }
            .saldo-total {
                font-size: 24px;
                font-weight: 600;
                color: #28a745;
                margin: 15px 0;
            }
            .motivo {
                background: #e9ecef;
                padding: 15px;
                border-radius: 8px;
                margin: 20px 0;
                font-style: italic;
            }
            .cta-button {
                display: inline-block;
                background: linear-gradient(135deg, #E30613, #C40510);
                color: white;
                padding: 15px 30px;
                text-decoration: none;
                border-radius: 25px;
                font-weight: 600;
                font-size: 16px;
                margin: 20px 0;
                transition: all 0.3s ease;
            }
            .cta-button:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(227, 6, 19, 0.3);
            }
            .features {
                display: flex;
                justify-content: space-around;
                margin: 30px 0;
                flex-wrap: wrap;
            }
            .feature {
                text-align: center;
                margin: 10px;
                flex: 1;
                min-width: 150px;
            }
            .feature-icon {
                font-size: 32px;
                margin-bottom: 10px;
            }
            .feature h3 {
                margin: 10px 0 5px 0;
                color: #E30613;
                font-size: 18px;
            }
            .feature p {
                margin: 0;
                font-size: 14px;
                color: #666;
            }
            .footer {
                background: #2c3e50;
                color: white;
                padding: 25px;
                text-align: center;
            }
            .footer p {
                margin: 5px 0;
                font-size: 14px;
            }
            .footer a {
                color: #E30613;
                text-decoration: none;
            }
            @media (max-width: 600px) {
                .container {
                    margin: 10px;
                    border-radius: 0;
                }
                .content {
                    padding: 20px;
                }
                .saldo-cantidad {
                    font-size: 28px;
                }
                .features {
                    flex-direction: column;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🎉 ¡Felicidades!</h1>
                <p>Tu saldo ha sido incrementado exitosamente</p>
            </div>
            
            <div class="content">
                <h2>Hola ' . htmlspecialchars($username) . ',</h2>
                
                <p>¡Excelentes noticias! Tu saldo en CodigoAmigo ha sido incrementado y ya está disponible para usar.</p>
                
                <div class="saldo-info">
                    <h3>💰 Saldo Añadido</h3>
                    <div class="saldo-cantidad">+' . number_format($cantidad, 2) . '€</div>
                    <h3>💳 Saldo Total Actual</h3>
                    <div class="saldo-total">' . number_format($nuevo_saldo, 2) . '€</div>
                </div>
                
                <div class="motivo">
                    <strong>Motivo:</strong> ' . htmlspecialchars($motivo) . '<br>
                    <strong>Fecha:</strong> ' . $fecha . '
                </div>
                
                <p>Ahora puedes empezar a patrocinar tus códigos y hacer que más gente los vea. ¡Es hora de darle visibilidad a tus ofertas!</p>
                
                <div style="text-align: center;">
                    <a href="https://www.codigoamigo.com/mis-anuncios" class="cta-button">
                        🚀 Ver Mi Saldo y Patrocinar
                    </a>
                </div>
                
                <div class="features">
                    <div class="feature">
                        <div class="feature-icon">⭐</div>
                        <h3>Destaca tus códigos</h3>
                        <p>Haz que tus códigos aparezcan en primera posición</p>
                    </div>
                    <div class="feature">
                        <div class="feature-icon">👥</div>
                        <h3>Más visibilidad</h3>
                        <p>Llega a más usuarios interesados</p>
                    </div>
                    <div class="feature">
                        <div class="feature-icon">📈</div>
                        <h3>Mejores resultados</h3>
                        <p>Aumenta los clicks en tus códigos</p>
                    </div>
                </div>
                
                <p>Si tienes alguna pregunta sobre tu saldo o necesitas ayuda, no dudes en contactarnos.</p>
                
                <p>¡Gracias por confiar en CodigoAmigo!</p>
                
                <p><strong>El equipo de CodigoAmigo</strong></p>
            </div>
            
            <div class="footer">
                <p><strong>CodigoAmigo</strong></p>
                <p>La plataforma líder en códigos de descuento</p>
                <p>
                    <a href="https://www.codigoamigo.com">www.codigoamigo.com</a> | 
                    <a href="mailto:soporte@codigoamigo.com">soporte@codigoamigo.com</a>
                </p>
                <p style="font-size: 12px; opacity: 0.8;">
                    Este email fue enviado automáticamente. Por favor, no respondas a este mensaje.
                </p>
            </div>
        </div>
    </body>
    </html>';
}
?>
