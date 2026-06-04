<?php
/**
 * Verificación de cuenta por CÓDIGO (en vez de enlace)
 *
 * Flujo:
 *   1. Al registrarse (web), se genera un código de 4 dígitos con caducidad de 10 min.
 *   2. Se envía por email (sin enlaces → mejor deliverability, menos spam).
 *   3. El usuario lo introduce en un modal con temporizador.
 *   4. Si es correcto y no ha caducado → se activa la cuenta (estado=1) y auto-login.
 *
 * Colección `verificacion_codigos` (también alimenta el KPI del admin):
 *   { usuario_id, mail, nombre, codigo, source, creado, expira, intentos,
 *     max_intentos, verificado, verificado_at, email_enviado, brevo_message_id, reenvios }
 */

if (!defined('VERIF_CODIGO_TTL_MIN'))   define('VERIF_CODIGO_TTL_MIN', 10);   // minutos de validez
if (!defined('VERIF_MAX_INTENTOS'))     define('VERIF_MAX_INTENTOS', 5);      // intentos antes de invalidar
if (!defined('VERIF_REENVIO_COOLDOWN')) define('VERIF_REENVIO_COOLDOWN', 60); // segundos mínimos entre reenvíos

/**
 * Colección de códigos de verificación.
 */
function getCollectionVerificacionCodigos() {
    $db = createConnection();
    if (!$db) {
        log_error("verificacion_codigos: sin conexión a BBDD");
        return null;
    }
    return $db->selectCollection('verificacion_codigos');
}

/**
 * Genera un código numérico de 4 dígitos (0000-9999) de forma segura.
 */
function generar_codigo_4_digitos() {
    return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * Crea (o regenera) un código de verificación para un usuario y lo envía por email.
 * Invalida cualquier código pendiente anterior del mismo usuario.
 *
 * @return array ['success'=>bool, 'expira_en'=>segundos, 'error'=>string]
 */
function generar_y_enviar_codigo_verificacion($usuario_id, $mail, $nombre = 'Usuario', $source = 'registro', $es_reenvio = false) {
    $coll = getCollectionVerificacionCodigos();
    if (!$coll) {
        return ['success' => false, 'error' => 'db'];
    }

    try {
        $oid = ($usuario_id instanceof MongoDB\BSON\ObjectId)
            ? $usuario_id
            : new MongoDB\BSON\ObjectId((string) $usuario_id);
    } catch (Throwable $e) {
        log_error("verificacion: usuario_id inválido: " . $e->getMessage());
        return ['success' => false, 'error' => 'usuario'];
    }

    // Cooldown de reenvío: evita spam de "reenviar código"
    if ($es_reenvio) {
        $ultimo = $coll->findOne(
            ['usuario_id' => $oid, 'verificado' => false],
            ['sort' => ['creado' => -1]]
        );
        if ($ultimo && isset($ultimo['creado'])) {
            $segundos_desde = time() - ($ultimo['creado']->toDateTime()->getTimestamp());
            if ($segundos_desde < VERIF_REENVIO_COOLDOWN) {
                return ['success' => false, 'error' => 'cooldown', 'espera' => VERIF_REENVIO_COOLDOWN - $segundos_desde];
            }
        }
    }

    // Invalidar códigos pendientes anteriores (solo cuenta el último)
    $coll->updateMany(
        ['usuario_id' => $oid, 'verificado' => false],
        ['$set' => ['invalidado' => true]]
    );

    $codigo = generar_codigo_4_digitos();
    $ahora  = time();
    $expira = $ahora + VERIF_CODIGO_TTL_MIN * 60;

    $doc = [
        'usuario_id'    => $oid,
        'mail'          => $mail,
        'nombre'        => $nombre,
        'codigo'        => $codigo,
        'source'        => $source,
        'creado'        => new MongoDB\BSON\UTCDateTime($ahora * 1000),
        'expira'        => new MongoDB\BSON\UTCDateTime($expira * 1000),
        'intentos'      => 0,
        'max_intentos'  => VERIF_MAX_INTENTOS,
        'verificado'    => false,
        'verificado_at' => null,
        'invalidado'    => false,
        'email_enviado' => false,
        'reenvio'       => $es_reenvio,
    ];

    $insert = $coll->insertOne($doc);

    // Enviar el email con el código
    $enviado = enviar_email_codigo_verificacion($mail, $nombre, $codigo);
    if ($insert->getInsertedId()) {
        $coll->updateOne(
            ['_id' => $insert->getInsertedId()],
            ['$set' => ['email_enviado' => (bool) $enviado]]
        );
    }

    log_info("Código verificación generado para $mail (source=$source, reenvio=" . ($es_reenvio ? 'si' : 'no') . ")");

    return [
        'success'  => true,
        'expira_en' => VERIF_CODIGO_TTL_MIN * 60,
        'email_enviado' => (bool) $enviado,
    ];
}

/**
 * Envía el email con el código de verificación (sin enlaces → anti-spam).
 */
function enviar_email_codigo_verificacion($mail, $nombre, $codigo) {
    $asunto = "Tu código de verificación es: $codigo";

    $body  = '<div style="font-family:Arial,Helvetica,sans-serif;max-width:480px;margin:0 auto;color:#1a1a1a;">';
    $body .= '<h1 style="font-size:20px;margin:0 0 8px;">¡Bienvenido a Código Amigo!</h1>';
    $body .= '<p style="font-size:15px;line-height:1.5;margin:0 0 20px;color:#444;">Hola ' . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . ', usa este código para verificar tu cuenta y empezar a ahorrar:</p>';
    $body .= '<div style="background:#f4f6fb;border-radius:14px;padding:28px 0;text-align:center;margin:0 0 20px;">';
    $body .= '<span style="font-size:46px;font-weight:800;letter-spacing:14px;color:#2d5bff;font-family:monospace;">' . htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') . '</span>';
    $body .= '</div>';
    $body .= '<p style="font-size:14px;color:#666;margin:0 0 6px;">Introdúcelo en la ventana de verificación para activar tu cuenta.</p>';
    $body .= '<p style="font-size:14px;color:#666;margin:0 0 18px;">El código caduca en <strong>' . VERIF_CODIGO_TTL_MIN . ' minutos</strong>.</p>';
    $body .= '<p style="font-size:12px;color:#999;margin:0;">Si no te has registrado en Código Amigo, puedes ignorar este correo.</p>';
    $body .= '</div>';

    $text = "Tu código de verificación de Código Amigo es: $codigo\n"
          . "Caduca en " . VERIF_CODIGO_TTL_MIN . " minutos.\n"
          . "Si no te has registrado, ignora este correo.";

    try {
        return enviarEmailConBrevoYRegistrar(
            $mail,
            $nombre ?: 'Usuario',
            $asunto,
            $body,
            'codigo_verificacion',
            null,
            ['codigo' => $codigo],
            $text
        );
    } catch (Throwable $e) {
        log_error("Error enviando email código verificación a $mail: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica un código introducido por el usuario.
 * Si es válido → activa la cuenta (estado=1) y deja la sesión iniciada (auto-login).
 *
 * @return array estructura JSON-ready
 */
function verificar_codigo_cuenta($mail, $codigo_introducido) {
    $mail = trim((string) $mail);
    $codigo_introducido = preg_replace('/\D/', '', (string) $codigo_introducido);

    $coll = getCollectionVerificacionCodigos();
    if (!$coll) {
        return ['success' => false, 'error' => 'db', 'mensaje' => 'Error de conexión. Inténtalo de nuevo.'];
    }

    // Último código no verificado ni invalidado para ese email
    $doc = $coll->findOne(
        ['mail' => $mail, 'verificado' => false, 'invalidado' => ['$ne' => true]],
        ['sort' => ['creado' => -1]]
    );

    if (!$doc) {
        return ['success' => false, 'error' => 'no_codigo', 'mensaje' => 'No hay ningún código activo. Pide uno nuevo.'];
    }

    // ¿Caducado?
    $ahora = time();
    $expira_ts = $doc['expira']->toDateTime()->getTimestamp();
    if ($ahora > $expira_ts) {
        $coll->updateOne(['_id' => $doc['_id']], ['$set' => ['invalidado' => true]]);
        return ['success' => false, 'error' => 'caducado', 'mensaje' => 'El código ha caducado. Pide uno nuevo.'];
    }

    // ¿Demasiados intentos?
    $intentos = (int) ($doc['intentos'] ?? 0);
    if ($intentos >= (int) ($doc['max_intentos'] ?? VERIF_MAX_INTENTOS)) {
        $coll->updateOne(['_id' => $doc['_id']], ['$set' => ['invalidado' => true]]);
        return ['success' => false, 'error' => 'max_intentos', 'mensaje' => 'Demasiados intentos. Pide un código nuevo.'];
    }

    // ¿Coincide?
    if (!hash_equals((string) $doc['codigo'], (string) $codigo_introducido)) {
        $coll->updateOne(['_id' => $doc['_id']], ['$inc' => ['intentos' => 1]]);
        $restantes = max(0, (int) ($doc['max_intentos'] ?? VERIF_MAX_INTENTOS) - ($intentos + 1));
        return [
            'success' => false,
            'error' => 'incorrecto',
            'intentos_restantes' => $restantes,
            'mensaje' => $restantes > 0 ? "Código incorrecto. Te quedan $restantes intentos." : 'Código incorrecto. Pide uno nuevo.'
        ];
    }

    // ✓ Correcto → marcar verificado + activar cuenta
    $coll->updateOne(
        ['_id' => $doc['_id']],
        ['$set' => ['verificado' => true, 'verificado_at' => new MongoDB\BSON\UTCDateTime($ahora * 1000)]]
    );

    $activado = activar_usuario($mail);
    if (!$activado) {
        // Puede que ya estuviera activo; comprobamos
        $collection_usuarios = getCollectionUsuarios();
        $usuario = $collection_usuarios ? $collection_usuarios->findOne(['mail' => $mail]) : null;
        if (!$usuario || (int) ($usuario['estado'] ?? 0) !== 1) {
            return ['success' => false, 'error' => 'activacion', 'mensaje' => 'No se pudo activar la cuenta. Contacta con soporte.'];
        }
    }

    // Auto-login: dejar sesión iniciada
    $collection_usuarios = getCollectionUsuarios();
    $usuario = $collection_usuarios ? $collection_usuarios->findOne(['mail' => $mail]) : null;
    if ($usuario) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id']  = (string) $usuario['_id'];
        $_SESSION['mail']     = $usuario['mail'];
        $_SESSION['username'] = $usuario['username'] ?? '';
    }

    log_info("Cuenta verificada por código + auto-login: $mail");

    return [
        'success' => true,
        'mensaje' => '¡Cuenta verificada!',
        'username' => $usuario['username'] ?? '',
        'redirect' => '/'
    ];
}
