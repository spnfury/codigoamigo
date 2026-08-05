<?php
/**
 * Config de LABORATORIO. Apunta el loop a la carpeta demo/ aislada.
 * Se usa con: AUTOFIX_CONFIG=.../demo/config_demo.php php orchestrator.php
 */
$base = '/home/admin/web/codigoamigo.com/public_html/autofix';

return [
    'proyecto'      => 'codigoamigo-DEMO',
    'raiz'          => $base . '/demo',
    'dir_critical'  => $base . '/demo/critical',
    'dir_autofix'   => $base . '/demo',            // state/logs propios del demo
    'claude_bin'    => '/root/.local/bin/claude',
    'claude_model'  => 'sonnet',
    'claude_allowed'=> ['Edit', 'Read', 'Grep', 'Bash(php -l:*)'],
    'bootstrap_env' => '/home/admin/web/codigoamigo.com/public_html/config/ai_config.php',

    'tipos_seguros' => ['Unsupported operand types', 'Undefined array key', 'Undefined variable'],
    'rutas_sensibles' => ['stripe', 'webhook', 'pago', 'auth', 'login', '/config/', 'secret', 'private'],

    'max_rollbacks_dia'   => 3,
    'max_fixes_por_run'   => 2,
    'ventana_canario_min' => 0,

    'modo' => 'live',   // laboratorio: autónomo real
];
