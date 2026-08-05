<?php
/**
 * autofix/portals/ayuname.php — config del portal ayuname.com para el motor
 * MULTI-PORTAL (orchestrator_portal.php). Independiente del autofix original
 * de CodigoAmigo.
 *
 * Stack: PHP + Apache+proxy_fcgi (errores envueltos en AH01071).
 */

$autofixRoot = dirname(__DIR__); // .../public_html/autofix

return [
    'portal_key' => 'ayuname',
    'raiz'       => '/home/admin/web/ayuname.com/public_html',
    'state_dir'  => $autofixRoot . '/state/ayuname',

    'sources' => [
        ['type' => 'php_generic', 'path' => '/var/log/apache2/domains/ayuname.com.error.log'],
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
