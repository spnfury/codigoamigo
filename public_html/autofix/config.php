<?php
/**
 * autofix — configuración del loop auto-reparador
 *
 * Guardarraíles centrales. Cambiar aquí, nunca hardcodear en los scripts.
 */

return [
    // Proyecto que este loop vigila. Replicable: copiar autofix/ a otro sitio
    // y ajustar solo estas rutas.
    'proyecto'      => 'codigoamigo',
    'raiz'          => '/home/admin/web/codigoamigo.com/public_html',
    'dir_critical'  => '/home/admin/web/codigoamigo.com/public_html/logs/critical',
    'dir_autofix'   => '/home/admin/web/codigoamigo.com/public_html/autofix',

    // CLI de Claude Code (headless)
    'claude_bin'    => '/root/.local/bin/claude',
    'claude_model'  => 'sonnet',
    // Herramientas permitidas al agente headless. Whitelist estricta:
    // solo puede editar, leer, buscar y ejecutar `php -l`. Nada más.
    'claude_allowed'=> ['Edit', 'Read', 'Grep', 'Bash(php -l:*)'],

    // Notificación Telegram: reutiliza defines de config/ai_config.php
    'bootstrap_env' => '/home/admin/web/codigoamigo.com/public_html/config/ai_config.php',

    // --- GUARDARRAÍL 1: clases de error auto-reparables ---
    // Solo errores cuya causa raíz es mecánica y de bajo riesgo se auto-arreglan.
    // El resto se notifica para revisión manual (nunca se toca solo).
    'tipos_seguros' => [
        'Unsupported operand types',
        'Undefined array key',
        'Undefined variable',
        'Undefined property',
        'Trying to access array offset',
        'foreach() argument must be',
        'Attempt to read property',
        'must be of type',
    ],

    // --- GUARDARRAÍL 2: rutas prohibidas (siempre revisión manual) ---
    // Aunque el tipo sea "seguro", si el error está en dinero/auth/pagos NO se toca.
    'rutas_sensibles' => [
        'stripe', 'webhook', 'pago', 'suscrip', 'vip', 'checkout',
        'auth', 'login', 'registro', 'register', 'password', 'sesion',
        '/api/v1/', '/config/', 'conexion', 'secret', 'private',
    ],

    // --- GUARDARRAÍL 4: circuit breaker ---
    // Si el loop revierte demasiados fixes en un día, se pausa solo.
    'max_rollbacks_dia'   => 3,
    'max_fixes_por_run'   => 2,   // no arreglar más de N errores por ejecución

    // --- Canario ---
    // Tras aplicar un fix, se marca "healing". En el siguiente run se comprueba
    // si el MISMO error reapareció después del fix. Si sí -> rollback automático.
    'ventana_canario_min' => 30,  // minutos que espera antes de dar por sano

    // Modo. 'dry' = detecta y notifica pero NO edita ni despliega. 'live' = autónomo real.
    'modo' => 'live',
];
