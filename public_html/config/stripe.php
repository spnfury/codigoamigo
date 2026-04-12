<?php
/**
 * Helper centralizado para las credenciales de Stripe.
 *
 * Las claves se obtienen de (en orden de precedencia):
 *
 *   1. Variables de entorno $_ENV / getenv():
 *        STRIPE_SECRET_KEY       = "sk_live_..."  (obligatoria)
 *        STRIPE_TEST_SECRET_KEY  = "sk_test_..."  (opcional, solo admin sandbox)
 *
 *   2. Fichero privado fuera del webroot:
 *        /home/admin/web/codigoamigo.com/private/stripe_secrets.php
 *      (útil porque el pool PHP-FPM de Hestia no permite env[...] customs
 *      de forma persistente — el template se reescribe al rebuildear el dominio)
 *
 * Si STRIPE_SECRET_KEY no está definida por ninguna vía se lanza RuntimeException
 * para fallar ruidosamente en vez de procesar pagos en producción con credenciales
 * vacías.
 *
 * Regla de negocio histórica: ciertos usuarios admin usan el sandbox de Stripe
 * para hacer pruebas de compra sin cargos reales. Esa lista está aquí para no
 * duplicarla en cada caller.
 */

// Bootstrap: si no hay STRIPE_SECRET_KEY en el entorno, intentar cargar el
// fichero privado de secrets. Gestionado vía función para no filtrar vars al
// scope global del caller más allá de $_ENV.
(function () {
    if (!empty($_ENV['STRIPE_SECRET_KEY']) || getenv('STRIPE_SECRET_KEY') !== false) {
        return;
    }
    $secrets_path = dirname(__DIR__, 2) . '/private/stripe_secrets.php';
    if (is_readable($secrets_path)) {
        require_once $secrets_path;
    }
})();

if (!function_exists('get_stripe_secret_key')) {

    /**
     * Devuelve la clave secreta de Stripe apropiada para esta petición.
     *
     * Si el usuario actual es un "admin de sandbox" (identificado por email o
     * user_id) y hay STRIPE_TEST_SECRET_KEY configurada, devuelve esa. En cualquier
     * otro caso devuelve la clave live.
     *
     * @param string|null $email   Email del usuario autenticado (opcional)
     * @param string|null $user_id _id de MongoDB del usuario autenticado (opcional)
     * @return string Clave secreta de Stripe (sk_live_... o sk_test_...)
     * @throws RuntimeException si STRIPE_SECRET_KEY no está configurada
     */
    function get_stripe_secret_key($email = null, $user_id = null) {
        // Lista cerrada de admins que usan sandbox de Stripe para pruebas
        $admin_emails = [
            'thevega82@gmail.com',
        ];
        $admin_user_ids = [
            '639899bc6321ee0d0e4010d2',
            '58bd851da54e295b8b52f702',
            '5db1af3a2f55c82b47342172',
        ];

        $is_sandbox_admin = false;
        if ($email !== null && $email !== '' && in_array($email, $admin_emails, true)) {
            $is_sandbox_admin = true;
        }
        if ($user_id !== null && $user_id !== '' && in_array((string)$user_id, $admin_user_ids, true)) {
            $is_sandbox_admin = true;
        }

        if ($is_sandbox_admin) {
            $test_key = $_ENV['STRIPE_TEST_SECRET_KEY'] ?? getenv('STRIPE_TEST_SECRET_KEY') ?: null;
            if (!empty($test_key)) {
                return $test_key;
            }
            // Si no hay test key configurada, caer a live. Mejor cobrar de verdad
            // al admin que dejar el pago roto — el admin puede revertir manualmente.
        }

        return get_stripe_live_secret_key();
    }
}

if (!function_exists('get_stripe_live_secret_key')) {

    /**
     * Devuelve siempre la clave LIVE. Usar en flujos que no dependen del usuario
     * (webhooks, scripts de sincronización, monitores).
     *
     * @return string Clave sk_live_...
     * @throws RuntimeException si STRIPE_SECRET_KEY no está configurada
     */
    function get_stripe_live_secret_key() {
        $key = $_ENV['STRIPE_SECRET_KEY'] ?? getenv('STRIPE_SECRET_KEY') ?: null;
        if (empty($key)) {
            throw new RuntimeException(
                'STRIPE_SECRET_KEY no está configurada en el entorno. ' .
                'Añade env[STRIPE_SECRET_KEY] al pool PHP-FPM ' .
                '(/etc/php/8.3/fpm/pool.d/codigoamigo.com.conf) y ejecuta ' .
                '"systemctl reload php8.3-fpm".'
            );
        }
        return $key;
    }
}

if (!function_exists('get_stripe_test_secret_key')) {

    /**
     * Devuelve siempre la clave TEST. Usar en scripts de diagnóstico/sync que
     * necesitan operar sobre ambos entornos de Stripe en paralelo.
     *
     * @return string Clave sk_test_...
     * @throws RuntimeException si STRIPE_TEST_SECRET_KEY no está configurada
     */
    function get_stripe_test_secret_key() {
        $key = $_ENV['STRIPE_TEST_SECRET_KEY'] ?? getenv('STRIPE_TEST_SECRET_KEY') ?: null;
        if (empty($key)) {
            throw new RuntimeException(
                'STRIPE_TEST_SECRET_KEY no está configurada en el entorno. ' .
                'Añade env[STRIPE_TEST_SECRET_KEY] al pool PHP-FPM y recarga.'
            );
        }
        return $key;
    }
}

if (!function_exists('get_stripe_webhook_secret')) {

    /**
     * Devuelve el signing secret del webhook de Stripe, usado por
     * Stripe\Webhook::constructEvent() para verificar que los eventos POSTeados
     * al endpoint webhook_stripe.php vienen realmente de Stripe.
     *
     * Se obtiene de STRIPE_WEBHOOK_SECRET vía entorno o private/stripe_secrets.php.
     *
     * @return string whsec_...
     * @throws RuntimeException si no está configurado
     */
    function get_stripe_webhook_secret() {
        $key = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? getenv('STRIPE_WEBHOOK_SECRET') ?: null;
        if (empty($key)) {
            throw new RuntimeException(
                'STRIPE_WEBHOOK_SECRET no está configurada. ' .
                'Obtén el signing secret desde Stripe Dashboard > Webhooks > [tu endpoint] > Signing secret ' .
                'y añade STRIPE_WEBHOOK_SECRET a private/stripe_secrets.php.'
            );
        }
        return $key;
    }
}
