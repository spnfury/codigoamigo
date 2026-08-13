<?php
/**
 * autofix/portals/casinuevo.php — config del portal casinuevo (casinuevo.com /
 * casinovios.com + todos los subdominios de país) para el motor MULTI-PORTAL.
 *
 * TODOS los subdominios (mexico., peru., etc.) comparten el mismo código
 * (public_html es symlink a casinovios.com/public_html), así que un solo
 * portal basta: el fix se aplica una vez al código compartido.
 *
 * Stack: PHP + Apache+proxy_fcgi. Los errores llegan envueltos en AH01071
 * ("PHP message: PHP Fatal error: ..."); el extractor universal de parsers.php
 * los reconoce igual que a los nativos.
 */

$autofixRoot = dirname(__DIR__); // .../public_html/autofix

return [
    'portal_key' => 'casinuevo',
    'raiz'       => '/home/casinuevo_user/web/casinovios.com/public_html',
    'state_dir'  => $autofixRoot . '/state/casinuevo',

    'sources' => [
        ['type' => 'php_generic', 'path' => '/var/log/apache2/domains/casinovios.com.error.log'],
        ['type' => 'php_generic', 'path' => '/var/log/apache2/domains/casinuevo.com.error.log'],
        ['type' => 'php_generic', 'path' => '/var/log/apache2/domains/mexico.homesya.com.error.log'],
        ['type' => 'php_generic', 'path' => '/var/log/apache2/domains/mexico.casinuevo.com.error.log'],
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
