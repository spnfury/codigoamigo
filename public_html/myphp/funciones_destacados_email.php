<?php
/**
 * Funciones de email para el sistema de destacados
 * 
 * Templates HTML para emails del ciclo de vida de códigos destacados:
 * - Aviso pre-expiración (2 días antes)
 * - Notificación de expiración
 * - Confirmación de auto-renovación
 * - Aviso de saldo insuficiente
 */

require_once __DIR__ . '/email_helper.php';

/**
 * Genera el HTML base de un email de destacados
 */
function _templateBaseDestacadoEmail($titulo, $contenido, $cta_texto = '', $cta_url = '') {
    $cta_html = '';
    if ($cta_texto && $cta_url) {
        $cta_html = '
            <div style="text-align:center;margin:35px 0 15px;">
                <a href="' . htmlspecialchars($cta_url) . '" style="background:#E30613;color:white;padding:16px 35px;text-decoration:none;border-radius:8px;font-weight:bold;font-size:16px;display:inline-block;box-shadow: 0 4px 6px rgba(227, 6, 19, 0.2);">
                    ' . $cta_texto . '
                </a>
            </div>';
    }

    return '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
    <body style="margin:0;padding:0;background-color:#f4f7fa;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;">
        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color:#f4f7fa;padding:40px 20px;">
            <tr>
                <td align="center">
                    <table style="max-width:600px;width:100%;margin:0 auto;background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.05);" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td align="center" style="padding:40px 30px 20px;border-bottom: 1px solid #f0f0f0;">
                                <img src="https://www.codigoamigo.com/img/logo_codigoamigo.png" alt="Código Amigo" style="max-width:180px;height:auto;display:block;margin:0 auto;">
                                <h1 style="color:#222222;margin:25px 0 0;font-size:24px;font-weight:700;letter-spacing:-0.5px;">' . $titulo . '</h1>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:30px 40px;color:#444444;line-height:1.6;font-size:16px;">
                                ' . $contenido . '
                                ' . $cta_html . '
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color:#f8f9fa;padding:25px 40px;text-align:center;border-top: 1px solid #f0f0f0;">
                                <p style="color:#888888;font-size:13px;margin:0;">
                                    Este email fue enviado por <a href="https://www.codigoamigo.com" style="color:#E30613;text-decoration:none;font-weight:600;">Código Amigo</a><br>
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>';
}

/**
 * Email: Tu destacado expira en 2 días
 */
function enviarEmailDestacadoExpiraPronto($usuario, $codigo, $marca_nombre, $dias_restantes) {
    $email = $usuario['mail'] ?? '';
    $nombre = $usuario['username'] ?? 'Usuario';
    if (empty($email)) return false;

    // Comprobar preferencia del usuario
    $uid = (string)($usuario['_id'] ?? '');
    if ($uid && !usuarioAceptaEmail($uid, 'destacado_expira_pronto')) {
        error_log("Email destacado_expira_pronto NO enviado a $email: usuario ha desactivado esta notificación");
        return false;
    }

    $tipo = ($codigo['tipo_destacado'] ?? 'normal') === 'super' ? 'Super Destacado' : 'Destacado Normal';
    $precio = ($codigo['tipo_destacado'] ?? 'normal') === 'super' ? '3,99€' : '0,99€';
    
    $fecha_fin = '';
    if (isset($codigo['fecha_fin_destacado']) && $codigo['fecha_fin_destacado'] instanceof MongoDB\BSON\UTCDateTime) {
        $fecha_fin = $codigo['fecha_fin_destacado']->toDateTime()->format('d/m/Y');
    }

    $contenido = '
        <p style="margin-top:0;">Hola <strong>' . htmlspecialchars($nombre) . '</strong>,</p>
        <p>Tu código <strong>' . htmlspecialchars($tipo) . '</strong> en la marca <strong>' . htmlspecialchars(ucfirst($marca_nombre)) . '</strong> expira en <strong>' . $dias_restantes . ' días</strong> (el ' . $fecha_fin . ').</p>
        
        <div style="background-color:#fdf3f4;border:1px solid #f8d7da;border-radius:8px;padding:16px;margin:25px 0;">
            <p style="margin:0;color:#c7254e;font-weight:600;font-size:15px;">&#9888;&#65039; Atención: Perderás visibilidad</p>
            <p style="margin:8px 0 0 0;color:#a94442;font-size:14px;">Cuando expire, tu código dejará de aparecer en posición destacada y podrías recibir menos referidos.</p>
        </div>
        
        <p>Renueva ahora por solo <strong>' . $precio . '</strong> para mantener tu visibilidad sin interrupciones.</p>
        
        <div style="background-color:#f4f7fa;padding:20px;border-radius:8px;border-left:4px solid #3466FF;margin-top:30px;">
            <p style="margin:0 0 8px 0;color:#222;font-weight:bold;font-size:15px;">&#128260; ¿Prefieres que se renueve solo?</p>
            <p style="margin:0;color:#555;font-size:14px;line-height:1.5;">Si tienes saldo disponible en tu cuenta, tu código se auto-renovará automáticamente cuando expire, para que no pierdas ni un día de visibilidad. También puedes hacerte <a href="https://www.codigoamigo.com/public/suscripcion_vip.php" style="color:#3466FF;font-weight:bold;text-decoration:none;">Usuario VIP</a> para recibir saldo gratis todos los meses y que tus códigos se renueven solos. ¡Despreocúpate!</p>
        </div>';

    $codigo_id = (string)($codigo['_id'] ?? '');
    $cta_url = "https://www.codigoamigo.com/renovar-destacado?codigo=" . urlencode($codigo_id);

    $html = _templateBaseDestacadoEmail('¡Tu destacado expira pronto!', $contenido, 'Renovar ahora', $cta_url);

    return enviarEmailConBrevoYRegistrar(
        $email, $nombre,
        'Tu destacado en ' . ucfirst($marca_nombre) . ' expira en ' . $dias_restantes . ' dias',
        $html,
        'destacado_expira_pronto',
        (string)($usuario['_id'] ?? ''),
        ['codigo_id' => $codigo_id, 'marca' => $marca_nombre, 'tipo' => $tipo]
    );
}

/**
 * Email: Tu destacado ha expirado
 */
function enviarEmailDestacadoExpirado($usuario, $codigo, $marca_nombre) {
    $email = $usuario['mail'] ?? '';
    $nombre = $usuario['username'] ?? 'Usuario';
    if (empty($email)) return false;

    // Comprobar preferencia del usuario
    $uid = (string)($usuario['_id'] ?? '');
    if ($uid && !usuarioAceptaEmail($uid, 'destacado_expirado')) {
        error_log("Email destacado_expirado NO enviado a $email: usuario ha desactivado esta notificación");
        return false;
    }

    $tipo = ($codigo['tipo_destacado'] ?? 'normal') === 'super' ? 'Super Destacado' : 'Destacado Normal';
    $precio = ($codigo['tipo_destacado'] ?? 'normal') === 'super' ? '3,99€' : '0,99€';
    $clicks = $codigo['totalclicks'] ?? 0;

    $contenido = '
        <p style="margin-top:0;">Hola <strong>' . htmlspecialchars($nombre) . '</strong>,</p>
        <p>Tu código <strong>' . htmlspecialchars($tipo) . '</strong> en <strong>' . htmlspecialchars(ucfirst($marca_nombre)) . '</strong> ha expirado.</p>
        <div style="background-color:#f8ebed;border:1px solid #f5c6cb;border-radius:8px;padding:16px;margin:25px 0;">
            <p style="margin:0;color:#721c24;font-weight:600;">Tu código ya no aparece en posición destacada.</p>
        </div>
        <div style="background-color:#e8f5e9;border-radius:8px;padding:16px;margin:25px 0;border-left:4px solid #28a745;">
            <p style="margin:0 0 8px 0;color:#155724;font-weight:bold;">Resumen de tu periodo destacado:</p>
            <p style="margin:5px 0 0 0;color:#155724;">Clicks totales: <strong>' . $clicks . '</strong></p>
        </div>
        <p>¿Quieres volver a destacar? Renueva por solo <strong>' . $precio . '</strong>.</p>
        
        <div style="background-color:#f4f7fa;padding:20px;border-radius:8px;border-left:4px solid #3466FF;margin-top:30px;">
            <p style="margin:0 0 8px 0;color:#222;font-weight:bold;font-size:15px;">&#128260; Activa la auto-renovación</p>
            <p style="margin:0;color:#555;font-size:14px;line-height:1.5;">Hazte <a href="https://www.codigoamigo.com/public/suscripcion_vip.php" style="color:#3466FF;font-weight:bold;text-decoration:none;">Usuario VIP</a> y obtén saldo gratis todos los meses de forma automática. Así tus códigos se renovarán solos sin que te quedes a cero, y no perderás referidos.</p>
        </div>';

    $codigo_id = (string)($codigo['_id'] ?? '');
    $cta_url = "https://www.codigoamigo.com/renovar-destacado?codigo=" . urlencode($codigo_id);

    $html = _templateBaseDestacadoEmail('Tu destacado ha expirado', $contenido, 'Renovar ahora', $cta_url);

    return enviarEmailConBrevoYRegistrar(
        $email, $nombre,
        'Tu destacado en ' . ucfirst($marca_nombre) . ' ha expirado - renueva ahora',
        $html,
        'destacado_expirado',
        (string)($usuario['_id'] ?? ''),
        ['codigo_id' => $codigo_id, 'marca' => $marca_nombre, 'clicks' => $clicks]
    );
}

/**
 * Email: Tu destacado se ha renovado automáticamente
 */
function enviarEmailDestacadoAutoRenovado($usuario, $codigo, $marca_nombre, $nuevo_saldo, $duracion_dias) {
    $email = $usuario['mail'] ?? '';
    $nombre = $usuario['username'] ?? 'Usuario';
    if (empty($email)) return false;

    // Comprobar preferencia del usuario
    $uid = (string)($usuario['_id'] ?? '');
    if ($uid && !usuarioAceptaEmail($uid, 'destacado_auto_renovado')) {
        error_log("Email destacado_auto_renovado NO enviado a $email: usuario ha desactivado esta notificación");
        return false;
    }

    $tipo = ($codigo['tipo_destacado'] ?? 'normal') === 'super' ? 'Super Destacado' : 'Destacado Normal';
    $precio = ($codigo['tipo_destacado'] ?? 'normal') === 'super' ? '3,99€' : '0,99€';
    $fecha_nueva = date('d/m/Y', time() + ($duracion_dias * 86400));

    $contenido = '
        <p style="margin-top:0;">Hola <strong>' . htmlspecialchars($nombre) . '</strong>,</p>
        <p>¡Buenas noticias! Tu código <strong>' . htmlspecialchars($tipo) . '</strong> en <strong>' . htmlspecialchars(ucfirst($marca_nombre)) . '</strong> se ha auto-renovado con éxito.</p>
        <div style="background-color:#e8f5e9;border:1px solid #c3e6cb;border-radius:8px;padding:16px;margin:25px 0;">
            <p style="margin:5px 0;color:#155724;"><strong>Renovado por:</strong> ' . $duracion_dias . ' días más (hasta el ' . $fecha_nueva . ')</p>
            <p style="margin:5px 0;color:#155724;"><strong>Cobrado:</strong> ' . $precio . ' de tu saldo</p>
            <p style="margin:5px 0;color:#155724;"><strong>Saldo restante:</strong> ' . number_format($nuevo_saldo, 2, ',', '.') . '€</p>
        </div>
        <p>Tu posición está asegurada. Al tener saldo disponible, el sistema lo ha hecho automáticamente para que no pierdas visibilidad.</p>';

    $html = _templateBaseDestacadoEmail('¡Destacado renovado automáticamente!', $contenido);

    return enviarEmailConBrevoYRegistrar(
        $email, $nombre,
        'Destacado automaticamente renovado en ' . ucfirst($marca_nombre),
        $html,
        'destacado_auto_renovado',
        (string)($usuario['_id'] ?? ''),
        ['marca' => $marca_nombre, 'nuevo_saldo' => $nuevo_saldo, 'duracion' => $duracion_dias]
    );
}

/**
 * Email: No tienes saldo suficiente para renovar
 */
function enviarEmailDestacadoSaldoInsuficiente($usuario, $codigo, $marca_nombre, $saldo_actual) {
    $email = $usuario['mail'] ?? '';
    $nombre = $usuario['username'] ?? 'Usuario';
    if (empty($email)) return false;

    // Comprobar preferencia del usuario
    $uid = (string)($usuario['_id'] ?? '');
    if ($uid && !usuarioAceptaEmail($uid, 'destacado_saldo_insuficiente')) {
        error_log("Email destacado_saldo_insuficiente NO enviado a $email: usuario ha desactivado esta notificación");
        return false;
    }

    $tipo = ($codigo['tipo_destacado'] ?? 'normal') === 'super' ? 'Super Destacado' : 'Destacado Normal';
    $precio_num = ($codigo['tipo_destacado'] === 'super') ? DESTACADO_PRECIO_SUPER : DESTACADO_PRECIO_NORMAL;
    $precio = number_format($precio_num, 2, ',', '.') . '€';

    $contenido = '
        <p style="margin-top:0;">Hola <strong>' . htmlspecialchars($nombre) . '</strong>,</p>
        <p>Tu código <strong>' . htmlspecialchars($tipo) . '</strong> en <strong>' . htmlspecialchars(ucfirst($marca_nombre)) . '</strong> ha expirado y no hemos podido renovarlo porque tu saldo es insuficiente.</p>
        
        <div style="background-color:#fff3cd;border:1px solid #ffeeba;border-radius:8px;padding:16px;margin:25px 0;">
            <p style="margin:5px 0;color:#856404;"><strong>Saldo actual:</strong> ' . number_format($saldo_actual, 2, ',', '.') . '€</p>
            <p style="margin:5px 0;color:#856404;"><strong>Precio de la renovación:</strong> ' . $precio . '</p>
            <p style="margin:8px 0 0 0;color:#856404;font-size:14px;"><strong>Se necesitaban ' . number_format($precio_num - $saldo_actual, 2, ',', '.') . '€ adicionales.</strong></p>
        </div>
        
        <p>Vuelve a destacar pulsando el botón de abajo y recargando saldo. Así mantendrás la auto-renovación activa la próxima vez.</p>
        
        <div style="background-color:#f4f7fa;padding:20px;border-radius:8px;border-left:4px solid #3466FF;margin-top:30px;">
            <p style="margin:0 0 8px 0;color:#222;font-weight:bold;font-size:15px;">&#128260; ¿La forma más fácil de renovar?</p>
            <p style="margin:0;color:#555;font-size:14px;line-height:1.5;">Hazte <a href="https://www.codigoamigo.com/public/suscripcion_vip.php" style="color:#3466FF;font-weight:bold;text-decoration:none;">Usuario VIP</a> y recibirás saldo extra automáticamente cada mes. Tus códigos nunca más se quedarán offline por falta de fondos.</p>
        </div>';

    $codigo_id = (string)($codigo['_id'] ?? '');
    $cta_url = "https://www.codigoamigo.com/renovar-destacado?codigo=" . urlencode($codigo_id);
    
    $html = _templateBaseDestacadoEmail('Sin saldo para auto-renovar', $contenido, 'Recargar y renovar ahora', $cta_url);  

    return enviarEmailConBrevoYRegistrar(
        $email, $nombre,
        'Sin saldo para auto-renovar tu destacado en ' . ucfirst($marca_nombre),  
        $html,
        'destacado_saldo_insuficiente',
        (string)($usuario['_id'] ?? ''),
        ['marca' => $marca_nombre, 'saldo' => $saldo_actual, 'precio' => $precio]
    );
}

/**
 * Email: Alguien más ha destacado en tu misma marca
 */
function enviarEmailDestacadoCompetencia($usuario, $codigo, $marca_nombre, $nuevo_tipo) {
    $email = $usuario['mail'] ?? '';
    $nombre = $usuario['username'] ?? 'Usuario';
    if (empty($email)) return false;

    $tipo_actual = ($codigo['tipo_destacado'] ?? 'normal') === 'super' ? 'Super Destacado' : 'Destacado Normal';
    $nuevo_tipo_label = ($nuevo_tipo === 'super') ? 'Super Destacado' : 'Destacado Normal';
    
    $dias_rest = '';
    if (isset($codigo['fecha_fin_destacado']) && $codigo['fecha_fin_destacado'] instanceof MongoDB\BSON\UTCDateTime) {
        $ts_fin = $codigo['fecha_fin_destacado']->toDateTime()->getTimestamp();
        $diff = max(0, ceil(($ts_fin - time()) / 86400));
        $dias_rest = $diff . ' días';
    }

    $contenido = '
        <p style="margin-top:0;">Hola <strong>' . htmlspecialchars($nombre) . '</strong>,</p>
        <p>Un nuevo usuario acaba de destacar un código como <strong>' . htmlspecialchars($nuevo_tipo_label) . '</strong> en la marca <strong>' . htmlspecialchars(ucfirst($marca_nombre)) . '</strong>.</p>
        <div style="background-color:#fff3cd;border:1px solid #ffeeba;border-radius:8px;padding:16px;margin:25px 0;">
            <p style="margin:5px 0;color:#856404;">Tu código (<strong>' . htmlspecialchars($tipo_actual) . '</strong>) sigue activo' . ($dias_rest ? ' — <strong>' . $dias_rest . ' restantes</strong>' : '') . '</p>
            <p style="margin:5px 0 0 0;color:#856404;font-weight:600;">Ahora hay más competencia por la visibilidad en esta marca.</p>
        </div>
        <p>Te recomendamos revisar tu posición. Si tu destacado es Normal, puedes subirlo a <strong>Super Destacado</strong> (14 días, posición prioritaria y aparición en el Home) para recuperar la ventaja.</p>';

    $codigo_id = (string)($codigo['_id'] ?? '');
    $cta_url = "https://www.codigoamigo.com/renovar-destacado?codigo=" . urlencode($codigo_id);

    $html = _templateBaseDestacadoEmail('Nueva competencia en ' . ucfirst($marca_nombre), $contenido, 'Mejorar a Super Destacado', $cta_url);

    return enviarEmailConBrevoYRegistrar(
        $email, $nombre,
        'Nuevo destacado en ' . ucfirst($marca_nombre) . ' - revisa tu posicion',
        $html,
        'destacado_competencia',
        (string)($usuario['_id'] ?? ''),
        ['codigo_id' => $codigo_id, 'marca' => $marca_nombre, 'nuevo_tipo' => $nuevo_tipo]
    );
}

/**
 * Notifica a usuarios con códigos destacados activos en una marca cuando alguien nuevo destaca
 * 
 * @param string $marca_nombre_clave Nombre clave de la marca
 * @param string $nuevo_usuario_id ID del usuario que acaba de destacar (para excluirlo)
 * @param string $nuevo_tipo Tipo de destacado del nuevo usuario
 * @return int Número de notificaciones enviadas
 */
function notificarCompetenciaDestacado($marca_nombre_clave, $nuevo_usuario_id, $nuevo_tipo) {
    $collection_codigos = getCollectionCodigos();
    $collection_usuarios = getCollectionUsuarios();
    $now = new MongoDB\BSON\UTCDateTime();
    
    // Convertir a ObjectId para que el $ne funcione correctamente
    // (id_usuario en la BD es ObjectId, pero $nuevo_usuario_id puede llegar como string)
    try {
        $nuevo_usuario_oid = new MongoDB\BSON\ObjectId($nuevo_usuario_id);
    } catch (Exception $e) {
        $nuevo_usuario_oid = $nuevo_usuario_id;
    }
    
    // Buscar códigos destacados activos en esta marca (excluyendo al nuevo usuario)
    $codigos_activos = $collection_codigos->find([
        'marca' => $marca_nombre_clave,
        'destacado' => ['$ne' => 0],
        'estado' => 0,
        'fecha_fin_destacado' => ['$gt' => $now],
        'id_usuario' => ['$ne' => $nuevo_usuario_oid]
    ]);
    
    $enviados = 0;
    $usuarios_notificados = []; // Evitar duplicados si un usuario tiene varios códigos
    
    foreach ($codigos_activos as $codigo) {
        $uid = (string)$codigo['id_usuario'];
        if (in_array($uid, $usuarios_notificados)) continue;
        
        try {
            $usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($uid)]);
            if (!$usuario) continue;
            
            // Verificar que acepta emails de competencia
            if (!usuarioAceptaEmail($uid, 'destacado_competencia')) {
                error_log("Email destacado_competencia NO enviado a usuario $uid: ha desactivado esta notificación");
                $usuarios_notificados[] = $uid;
                continue;
            }
            
            $resultado = enviarEmailDestacadoCompetencia($usuario, (array)$codigo, $marca_nombre_clave, $nuevo_tipo);
            if ($resultado && $resultado['success']) {
                $enviados++;
            }
            $usuarios_notificados[] = $uid;
        } catch (Throwable $e) {
            error_log("Error notificando competencia a usuario $uid: " . $e->getMessage());
        }
    }
    
    return $enviados;
}
