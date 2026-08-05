<?php
/**
 * autofix/portals/camarerooo.php — config del portal camarerooo.cladoo.com
 * para el motor MULTI-PORTAL (orchestrator_portal.php). Independiente del
 * autofix original de CodigoAmigo.
 *
 * Stack: Laravel. Errores fatales de PHP llegan envueltos por Apache+proxy_fcgi
 * (AH01071: ... PHP message: PHP Fatal error: ...) al error log de Apache.
 * El extractor universal de parsers.php los reconoce igual que a los nativos.
 */

$autofixRoot = dirname(__DIR__); // .../public_html/autofix

return [
    'portal_key' => 'camarerooo',
    'raiz'       => '/home/admin/web/camarerooo.cladoo.com/laravel',
    'state_dir'  => $autofixRoot . '/state/camarerooo',

    'sources' => [
        ['type' => 'php_generic', 'path' => '/home/admin/web/camarerooo.cladoo.com/logs/camarerooo.cladoo.com.error.log'],
    ],

    'claude_bin'     => '/root/.local/bin/claude',
    'claude_model'   => 'sonnet',
    'claude_allowed' => ['Edit', 'Read', 'Grep', 'Bash(php -l:*)'],
    'bootstrap_env'  => '/home/admin/web/codigoamigo.com/public_html/config/ai_config.php', // Telegram compartido

    'tipos_seguros' => [
        'Unsupported operand types', 'Undefined array key', 'Undefined variable',
        'Undefined global variable', 'Undefined property', 'Trying to access array offset',
        'foreach() argument must be', 'Attempt to read property', 'must be of type',
    ],
    'rutas_sensibles' => [
        'stripe', 'webhook', 'pago', 'checkout', 'auth', 'login', 'password',
        '/config/', 'secret', '.env', 'database.php',
    ],

    'max_rollbacks_dia'   => 3,
    'max_fixes_por_run'   => 2,
    'ventana_canario_min' => 30,
    'max_leer_bytes'      => 5242880,

    'modo' => 'dry', // burn-in: detecta y notifica, no edita. Pasar a 'live' cuando se valide.
];
