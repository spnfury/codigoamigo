<?php
/**
 * Baja de correo en un clic, sin login.
 *
 * Hasta 2026-08-07 el único modo de dejar de recibir correos era el enlace a
 * /usuario#preferencias, que exige iniciar sesión. Eso es una barrera que la
 * LSSI (art. 21) no admite como "procedimiento sencillo y gratuito", y además
 * Gmail y Yahoo exigen desde 2024 baja de un clic (RFC 8058) a los remitentes
 * de volumen: sin ella el correo se filtra antes de llegar a la bandeja.
 *
 * El enlace lleva el email y una firma HMAC, así que no hace falta sesión ni
 * consultar la base de datos para saber que el enlace es legítimo, y nadie
 * puede dar de baja a un tercero cambiando el parámetro.
 */

if (!function_exists('log_info')) {
    require_once __DIR__ . '/../inc/logger.php';
}

/** Secreto de firma. Vive en /private/api_secrets.php, nunca en git. */
function baja_email_secreto() {
    if (empty($_ENV['EMAIL_UNSUB_SECRET'])) {
        $priv = dirname(__DIR__, 2) . '/private/api_secrets.php';
        if (file_exists($priv)) include_once $priv;
    }
    return $_ENV['EMAIL_UNSUB_SECRET'] ?? '';
}

/** Firma del email. Truncada a 32 hex: suficiente contra fuerza bruta y cabe en la URL. */
function baja_email_token($email) {
    $secreto = baja_email_secreto();
    if ($secreto === '') return '';
    return substr(hash_hmac('sha256', strtolower(trim($email)), $secreto), 0, 32);
}

/** Comprueba la firma en tiempo constante. */
function baja_email_token_valido($email, $token) {
    $esperado = baja_email_token($email);
    return $esperado !== '' && is_string($token) && hash_equals($esperado, $token);
}

/** URL absoluta de baja para un destinatario. */
function baja_email_url($email) {
    return 'https://www.codigoamigo.com/baja?e=' . rawurlencode(strtolower(trim($email)))
        . '&t=' . baja_email_token($email);
}

/**
 * ¿Este correo ha pedido la baja?
 *
 * Se consulta por email y no por id de usuario porque la misma dirección puede
 * estar en varias cuentas (hay 310 duplicadas): si alguien se da de baja, deja
 * de recibir por todas.
 */
function email_tiene_baja($email) {
    $email = strtolower(trim((string)$email));
    if ($email === '') return false;

    try {
        if (!function_exists('createConnection')) include_once __DIR__ . '/funciones.php';
        $db = createConnection();
        if (!$db) return false;
        return $db->selectCollection('usuarios')->countDocuments([
            'mail'       => $email,
            'email_baja' => true,
        ]) > 0;
    } catch (\Throwable $e) {
        // Ante un fallo de BD no se bloquea el envío: se registra y se sigue.
        log_error('[baja_email] error comprobando baja: ' . $e->getMessage());
        return false;
    }
}

/**
 * Registra la baja de todas las cuentas con ese correo.
 * Devuelve el número de cuentas afectadas.
 */
function registrar_baja_email($email, $origen = 'enlace') {
    $email = strtolower(trim((string)$email));
    if ($email === '') return 0;

    try {
        if (!function_exists('createConnection')) include_once __DIR__ . '/funciones.php';
        $db = createConnection();
        if (!$db) return 0;

        $res = $db->selectCollection('usuarios')->updateMany(
            ['mail' => $email],
            ['$set' => [
                'email_baja'        => true,
                'email_baja_fecha'  => new MongoDB\BSON\UTCDateTime(),
                'email_baja_origen' => $origen,
            ]]
        );

        $n = $res->getModifiedCount();
        log_info('[baja_email] baja registrada', ['email' => $email, 'cuentas' => $n, 'origen' => $origen]);
        return $n;
    } catch (\Throwable $e) {
        log_error('[baja_email] error registrando baja: ' . $e->getMessage());
        return 0;
    }
}
