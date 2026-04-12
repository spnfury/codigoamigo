<?php
// Compatibilidad con callers legacy que importan esta variable directamente.
// Incluido actualmente por public/success.php.
require_once __DIR__ . '/../config/stripe.php';

if (!empty($_ENV['STRIPE_SECRET_KEY_SECRETS_PHP'])) {
    $stripeSecretKey = $_ENV['STRIPE_SECRET_KEY_SECRETS_PHP'];
} else {
    $stripeSecretKey = get_stripe_live_secret_key();
}
