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

// Función para enviar email de bienvenida VIP
function enviarEmailBienvenidaVIP($usuario) {
    if (!function_exists('enviarEmailConBrevoYRegistrar')) {
        include_once __DIR__ . '/email_helper.php';
    }
    
    $to_email = $usuario['mail'] ?? $usuario['email'] ?? '';
    $username = $usuario['username'] ?? 'Usuario';
    $user_id = isset($usuario['_id']) ? (string)$usuario['_id'] : null;
    
    if (empty($to_email)) {
        error_log("enviarEmailBienvenidaVIP: No email found for user $username");
        return false;
    }
    
    $subject = "👑 ¡Bienvenido al VIP, $username! Tu badge dorado está activo";
    
    $html = '<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Bienvenido al VIP - CodigoAmigo</title>
    </head>
    <body style="font-family: Segoe UI, Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4;">
        <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
            
            <!-- Header VIP -->
            <div style="background: linear-gradient(135deg, #ffd700 0%, #E30613 100%); color: white; padding: 40px 20px; text-align: center;">
                <div style="font-size: 50px; margin-bottom: 10px;">👑</div>
                <h1 style="margin: 0; font-size: 28px; font-weight: 800;">¡Bienvenido al VIP!</h1>
                <p style="margin: 10px 0 0 0; font-size: 16px; opacity: 0.95;">Tu badge dorado ya está activo en todos tus códigos</p>
            </div>
            
            <!-- Contenido -->
            <div style="padding: 40px 30px;">
                <h2 style="margin-top: 0;">Hola ' . htmlspecialchars($username) . ',</h2>
                
                <p>¡Enhorabuena por dar el paso! Ahora eres parte del grupo exclusivo de usuarios VIP de CodigoAmigo. Esto es lo que ya tienes activo:</p>
                
                <!-- Beneficios -->
                <div style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border-radius: 15px; padding: 25px; margin: 25px 0; color: white;">
                    <div style="display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <span style="font-size: 24px;">🏆</span>
                        <div>
                            <strong style="color: #ffd700;">Badge VIP Dorado</strong><br>
                            <span style="font-size: 14px; opacity: 0.9;">Visible en todos tus códigos y tu perfil</span>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <span style="font-size: 24px;">💬</span>
                        <div>
                            <strong style="color: #ffd700;">Chat ilimitado con viewers</strong><br>
                            <span style="font-size: 14px; opacity: 0.9;">Contacta a quien vea tus códigos</span>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <span style="font-size: 24px;">📨</span>
                        <div>
                            <strong style="color: #ffd700;">Mensajes masivos</strong><br>
                            <span style="font-size: 14px; opacity: 0.9;">Escribe a todos tus viewers a la vez</span>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px; padding: 10px 0;">
                        <span style="font-size: 24px;">💰</span>
                        <div>
                            <strong style="color: #ffd700;">+10€ de saldo GRATIS</strong><br>
                            <span style="font-size: 14px; opacity: 0.9;">Ya tienes 10€ para destacar tus códigos</span>
                        </div>
                    </div>
                </div>
                
                <!-- Próximos pasos -->
                <h3 style="color: #1a1a2e; margin-bottom: 15px;">🚀 Tus próximos pasos:</h3>
                
                <div style="background: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 15px;">
                        <div style="background: #E30613; color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; flex-shrink: 0;">1</div>
                        <div>
                            <strong>Publica un código</strong><br>
                            <span style="font-size: 14px; color: #666;">Tu badge VIP lo hará destacar sobre los demás</span>
                        </div>
                    </div>
                    <div style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 15px;">
                        <div style="background: #E30613; color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; flex-shrink: 0;">2</div>
                        <div>
                            <strong>Destácalo con tu saldo</strong><br>
                            <span style="font-size: 14px; color: #666;">Usa tus 10€ para ponerlo en primera posición</span>
                        </div>
                    </div>
                    <div style="display: flex; align-items: flex-start; gap: 12px;">
                        <div style="background: #E30613; color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; flex-shrink: 0;">3</div>
                        <div>
                            <strong>Chatea con tus viewers</strong><br>
                            <span style="font-size: 14px; color: #666;">Cuando alguien vea tu código, contacta y cierra el deal</span>
                        </div>
                    </div>
                </div>
                
                <!-- CTA -->
                <div style="text-align: center; margin: 30px 0;">
                    <a href="https://www.codigoamigo.com/publicar" style="display: inline-block; background: linear-gradient(135deg, #ffd700 0%, #E30613 100%); color: white; padding: 16px 40px; text-decoration: none; border-radius: 30px; font-weight: 700; font-size: 16px;">
                        👑 Publicar mi primer código VIP
                    </a>
                </div>
                
                <p style="color: #666; font-size: 14px; text-align: center;">Tu suscripción se renueva automáticamente cada mes. Puedes cancelarla en cualquier momento.</p>
            </div>
            
            <!-- Footer -->
            <div style="background: #1a1a2e; color: white; padding: 25px; text-align: center;">
                <p style="margin: 5px 0; font-size: 14px;"><strong>CodigoAmigo</strong></p>
                <p style="margin: 5px 0; font-size: 13px; opacity: 0.8;">
                    <a href="https://www.codigoamigo.com" style="color: #ffd700; text-decoration: none;">www.codigoamigo.com</a>
                </p>
            </div>
        </div>
    </body>
    </html>';
    
    $resultado = enviarEmailConBrevoYRegistrar(
        $to_email,
        $username,
        $subject,
        $html,
        'bienvenida_vip',
        $user_id,
        ['tipo' => 'bienvenida_vip'],
        '',
        'noreply@codigoamigo.com',
        'Código Amigo VIP'
    );
    
    if ($resultado['success']) {
        error_log("Email de bienvenida VIP enviado a: $to_email");
    } else {
        error_log("Error enviando email de bienvenida VIP a: $to_email - " . ($resultado['error'] ?? ''));
    }

    return $resultado;
}

// Email de suscripción VIP cancelada por fallo de cobro
function enviarEmailVIPPagoFallido($usuario, $motivo_decline = '', $card_last4 = '', $card_brand = '') {
    if (!function_exists('enviarEmailConBrevoYRegistrar')) {
        include_once __DIR__ . '/email_helper.php';
    }

    $to_email = $usuario['mail'] ?? $usuario['email'] ?? '';
    $username = $usuario['username'] ?? 'Usuario';
    $user_id = isset($usuario['_id']) ? (string)$usuario['_id'] : null;

    if (empty($to_email)) {
        error_log("enviarEmailVIPPagoFallido: No email found for user $username");
        return ['success' => false, 'error' => 'No email'];
    }

    $subject = "⚠️ Tu suscripción VIP ha sido cancelada - CodigoAmigo";

    $card_info = '';
    if ($card_brand && $card_last4) {
        $card_info = '<p style="margin: 5px 0; font-size: 14px; color: #666;">Tarjeta: ' . htmlspecialchars(strtoupper($card_brand)) . ' •••• ' . htmlspecialchars($card_last4) . '</p>';
    }

    $motivo_html = '';
    if (!empty($motivo_decline)) {
        $motivo_html = '<p style="margin: 5px 0; font-size: 14px; color: #666;">Motivo del banco: <em>' . htmlspecialchars($motivo_decline) . '</em></p>';
    }

    $html = '<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Suscripción VIP cancelada - CodigoAmigo</title>
    </head>
    <body style="font-family: Segoe UI, Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4;">
        <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">

            <div style="background: linear-gradient(135deg, #c0392b 0%, #7b1a12 100%); color: white; padding: 40px 20px; text-align: center;">
                <div style="font-size: 50px; margin-bottom: 10px;">⚠️</div>
                <h1 style="margin: 0; font-size: 26px; font-weight: 800;">Tu VIP ha sido cancelado</h1>
                <p style="margin: 10px 0 0 0; font-size: 16px; opacity: 0.95;">No hemos podido cobrar la renovación</p>
            </div>

            <div style="padding: 40px 30px;">
                <h2 style="margin-top: 0;">Hola ' . htmlspecialchars($username) . ',</h2>

                <p>Tu banco ha rechazado el cobro de la renovación mensual de tu suscripción VIP (9,99€). Hemos intentado varias veces sin éxito, por lo que <strong>hemos cancelado la suscripción y desactivado los beneficios VIP</strong> en tu cuenta.</p>

                <div style="background: #fdf3f2; border-left: 4px solid #c0392b; border-radius: 6px; padding: 18px 20px; margin: 25px 0;">
                    <p style="margin: 0 0 8px 0; font-weight: 700; color: #7b1a12;">Detalles del rechazo</p>
                    ' . $card_info . '
                    ' . $motivo_html . '
                </div>

                <h3 style="color: #1a1a2e; margin-bottom: 10px;">¿Qué ha pasado?</h3>
                <p>El código de rechazo indica que <strong>tu banco no permite este tipo de transacción</strong> (suscripciones recurrentes online). Esto suele deberse a:</p>
                <ul style="padding-left: 20px;">
                    <li>Restricciones de la tarjeta para pagos online o recurrentes</li>
                    <li>Límites de compras internacionales</li>
                    <li>Bloqueo del banco a comercios de determinada categoría</li>
                </ul>

                <h3 style="color: #1a1a2e; margin-bottom: 10px;">Cómo reactivar tu VIP</h3>
                <ol style="padding-left: 20px;">
                    <li>Contacta con tu banco y pide que desbloquee pagos recurrentes online, o</li>
                    <li>Usa otra tarjeta al volver a suscribirte</li>
                </ol>

                <div style="text-align: center; margin: 30px 0;">
                    <a href="https://www.codigoamigo.com/suscripcion-vip" style="display: inline-block; background: linear-gradient(135deg, #ffd700 0%, #E30613 100%); color: white; padding: 16px 40px; text-decoration: none; border-radius: 30px; font-weight: 700; font-size: 16px;">
                        👑 Reactivar mi VIP
                    </a>
                </div>

                <p style="color: #666; font-size: 14px; text-align: center;">Si crees que esto es un error, responde a este email y te ayudamos.</p>
            </div>

            <div style="background: #1a1a2e; color: white; padding: 25px; text-align: center;">
                <p style="margin: 5px 0; font-size: 14px;"><strong>CodigoAmigo</strong></p>
                <p style="margin: 5px 0; font-size: 13px; opacity: 0.8;">
                    <a href="https://www.codigoamigo.com" style="color: #ffd700; text-decoration: none;">www.codigoamigo.com</a>
                </p>
            </div>
        </div>
    </body>
    </html>';

    $resultado = enviarEmailConBrevoYRegistrar(
        $to_email,
        $username,
        $subject,
        $html,
        'vip_pago_fallido',
        $user_id,
        ['tipo' => 'vip_pago_fallido', 'motivo_decline' => $motivo_decline, 'card_last4' => $card_last4, 'card_brand' => $card_brand],
        '',
        'noreply@codigoamigo.com',
        'Código Amigo'
    );

    if ($resultado['success']) {
        error_log("Email VIP pago fallido enviado a: $to_email");
    } else {
        error_log("Error enviando email VIP pago fallido a: $to_email - " . ($resultado['error'] ?? ''));
    }

    return $resultado;
}

function enviarEmailVIPPagoFallidoReintento($usuario, $proximo_intento_fecha = null) {
    if (!function_exists('enviarEmailConBrevoYRegistrar')) {
        include_once __DIR__ . '/email_helper.php';
    }

    $to_email = $usuario['mail'] ?? $usuario['email'] ?? '';
    $username = trim($usuario['username'] ?? 'Usuario');
    $user_id  = isset($usuario['_id']) ? (string)$usuario['_id'] : null;

    if (empty($to_email)) {
        error_log("enviarEmailVIPPagoFallidoReintento: sin email para usuario $username");
        return ['success' => false, 'error' => 'No email'];
    }

    $subject = "Hemos tenido un problema con tu pago VIP - CodigoAmigo";
    $intento_html = $proximo_intento_fecha
        ? '<p style="margin:5px 0;font-size:14px;color:#555;">Próximo intento de cobro: <strong>' . htmlspecialchars($proximo_intento_fecha) . '</strong></p>'
        : '';

    $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>'
          . '<body style="font-family:Segoe UI,Tahoma,sans-serif;background:#f4f4f4;margin:0;padding:0;">'
          . '<div style="max-width:600px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.1);">'
          . '<div style="background:linear-gradient(135deg,#f39c12,#d35400);color:#fff;padding:30px 20px;text-align:center;">'
          . '<h1 style="margin:0;font-size:24px;">Hola ' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '</h1>'
          . '<p style="margin:10px 0 0;opacity:0.95;">Problema con tu pago VIP</p></div>'
          . '<div style="padding:30px;">'
          . '<p>No hemos podido cobrar la renovación mensual de tu suscripción VIP (9,99€). Tu banco ha rechazado el cargo.</p>'
          . '<div style="background:#fff8e1;border-left:4px solid #f39c12;border-radius:6px;padding:15px 20px;margin:20px 0;">'
          . '<p style="margin:0;font-size:14px;color:#555;">Tu suscripción VIP <strong>sigue activa</strong> de momento. Reintentaremos automáticamente el cobro en los próximos días.</p>'
          . $intento_html
          . '</div>'
          . '<p>Para evitar perder tu VIP, actualiza tus datos de pago:</p>'
          . '<div style="text-align:center;margin:25px 0;">'
          . '<a href="https://www.codigoamigo.com/suscripcion-vip" style="display:inline-block;background:linear-gradient(135deg,#ffd700,#E30613);color:#fff;padding:14px 32px;text-decoration:none;border-radius:30px;font-weight:700;">Actualizar tarjeta</a>'
          . '</div>'
          . '<p style="font-size:13px;color:#999;text-align:center;">Si todos los intentos fallan, cancelaremos automáticamente tu suscripción y desactivaremos los beneficios VIP.</p>'
          . '<p>Un saludo,<br>El equipo de CodigoAmigo</p>'
          . '</div></div></body></html>';

    $text = "Hola $username,\n\nNo hemos podido cobrar la renovación mensual VIP (9,99€). Tu banco ha rechazado el cargo.\n\nTu VIP sigue activo de momento. Reintentaremos automáticamente."
          . ($proximo_intento_fecha ? "\nPróximo intento: $proximo_intento_fecha" : "")
          . "\n\nActualiza tu tarjeta: https://www.codigoamigo.com/suscripcion-vip\n\nSi todos los intentos fallan, cancelaremos la suscripción.\n\nEl equipo de CodigoAmigo";

    $resultado = enviarEmailConBrevoYRegistrar(
        $to_email,
        $username,
        $subject,
        $html,
        'vip_pago_fallido_reintento',
        $user_id,
        ['proximo_intento' => $proximo_intento_fecha],
        $text
    );

    if (empty($resultado['success'])) {
        error_log("Error enviando email VIP reintento a: $to_email - " . ($resultado['error'] ?? ''));
    }
    return $resultado;
}
?>
