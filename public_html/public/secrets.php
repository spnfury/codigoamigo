<?php
// Compatibilidad con callers legacy que importan esta variable directamente.
// Incluido actualmente por public/success.php.
//
// Históricamente contenía hardcodeadas una sk_test_... y una sk_live_...
// distinta del resto de la app. Ahora las credenciales se leen del helper
// centralizado config/stripe.php → que a su vez lee del fichero privado
// fuera del webroot.
//
// La clave usada aquí es la "legacy" (ver STRIPE_SECRET_KEY_SECRETS_PHP en
// private/stripe_secrets.php). Si se confirma que puede consolidarse con la
// clave principal, sustituir por get_stripe_live_secret_key().

require_once __DIR__ . '/../config/stripe.php';

if (!empty($_ENV['STRIPE_SECRET_KEY_SECRETS_PHP'])) {
    $stripeSecretKey = $_ENV['STRIPE_SECRET_KEY_SECRETS_PHP'];
} else {
    // Fallback a la clave principal si no hay la legacy definida.
    $stripeSecretKey = get_stripe_live_secret_key();
}
