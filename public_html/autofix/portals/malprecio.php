<?php
/**
 * autofix/portals/malprecio.php — config del portal malprecio.com
 * para el motor MULTI-PORTAL (orchestrator_portal.php).
 *
 * Stack: PHP plano (mismo patrón que CodigoAmigo), nginx + php-fpm. Errores
 * fatales llegan al error log de nginx como "FastCGI sent in stderr: PHP
 * message: PHP Fatal error: ...". El extractor universal los reconoce igual.
 */

$autofixRoot = dirname(__DIR__);

return [
    'portal_key' => 'malprecio',
    'raiz'       => '/home/admin/web/malprecio.com/public_html',
    'state_dir'  => $autofixRoot . '/state/malprecio',

    'sources' => [
        ['type' => 'php_generic', 'path' => '/var/log/nginx/malprecio.com.error.log'],
    ],

    'claude_bin'     => '/root/.local/bin/claude',
    'claude_model'   => 'sonnet',
    'claude_allowed' => ['Edit', 'Read', 'Grep', 'Bash(php -l:*)'],
    'bootstrap_env'  => '/home/admin/web/codigoamigo.com/public_html/config/ai_config.php',

    'tipos_seguros' => [
        'Unsupported operand types', 'Undefined array key', 'Undefined variable',
        'Undefined global variable', 'Undefined property', 'Trying to access array offset',
        'foreach() argument must be', 'Attempt to read property', 'must be of type',
    ],
    'rutas_sensibles' => [
        'stripe', 'webhook', 'pago', 'checkout', 'auth', 'login', 'password',
        '/config/', 'secret', 'gsc-', 'google_credentials', 'private',
    ],

    'max_rollbacks_dia'   => 3,
    'max_fixes_por_run'   => 2,
    'ventana_canario_min' => 30,
    'max_leer_bytes'      => 5242880,

    'modo' => 'dry',
];
