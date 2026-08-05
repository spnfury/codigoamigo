<?php
/**
 * autofix/fleet.php — configuración del vigía de flota (fleet_watch.php).
 *
 * Auto-descubrimiento: en vez de listar portales a mano, el vigía escanea estos
 * roots en cada vuelta y coge cualquier *.log activo. Los sitios nuevos quedan
 * cubiertos solos, sin editar nada.
 *
 * NO repara código (eso lo hace orchestrator.php en CodigoAmigo). Esto solo
 * detecta errores nuevos, los clasifica ops/código y avisa a Telegram.
 */

return [
    // Dónde buscar portales.
    'roots' => [
        '/home/admin/web/*',
        '/var/www/*',
        '/home/planazosbcn',
        '/home/videoforge/vaideo-app',
        '/home/videoforge/app',
        '/home/money17/backend',
        // Logs REALES de dominio. Hestia escribe el error real en /var/log/apache2
        // (proxy_fcgi/AH01071) y /var/log/nginx; los .error.log de web/*/logs son
        // copias stale de ~50B que no se actualizan. Solo se toman *.error.log
        // (ver filtro en fleet_watch.php).
        '/var/log/apache2/domains',
        '/var/log/nginx',
    ],

    // Roots a excluir (por substring en la ruta). Otro sitio compartido, backups,
    // carpetas agregadas de sistema, vacías.
    'exclude_root' => [
        'casinuevo', 'backup', 'certbot', 'sysconf', 'document_errors',
        '/home/admin/web/logs', '/home/admin/web/reports', '/home/admin/web/tests',
        '/home/admin/web/fixes', '/home/admin/web/sql_fixes', '/var/www/html', 'agenciaia',
    ],

    // Rutas a excluir dentro de un portal (dependencias, VCS, cachés, backups).
    'exclude_path' => [
        'node_modules', '/vendor/', '/.git/', '/venv/', 'site-packages',
        'storage/framework', '/cache/', '_backup', 'public_html_backup',
    ],

    // Logs a ignorar por nombre (ruido, no errores de app).
    'ignorar_log' => [
        'access', 'fake_comments', 'debug_', 'sitemap', 'newsletter',
        'nightly_faqs', 'bulk_descripciones', 'cron_destacados',
    ],

    // Patrones de LÍNEA a ignorar: ruido esperado/manejado, no errores reales.
    // Evita falsas alarmas que entierran errores de verdad.
    'ignorar_patrones' => [
        // money17: rotación normal de categorías (unos slots fallan, otros publican 114/día)
        '/only \d+ products? passed filters|passed filters after \d+ category/i',
        // money17: URLs de clip de Amazon caducan; hay fallback a imagen Ken Burns (funciona)
        '/failed to download product clip|\[composer\]\s*❌ failed/i',
        // transitorios que reintentan solos
        '/gemini 503 transient|503 transient — sleeping|retrying|reintentando/i',
    ],

    'max_edad_dias'   => 7,          // solo logs tocados en los últimos 7 días
    'max_leer_bytes'  => 5242880,    // no leer más de 5MB de un log por vuelta

    // --- clasificación transversal ---
    '_ops_patterns' => [
        'token OAuth caducado'   => '/invalid_grant|token (has been )?(expired|revoked)|refresh token/i',
        'quota/rate-limit API'   => '/agotadas claves|HTTP 429|rate.?limit|quota exceeded|insufficient_quota|too many requests/i',
        'clave API inválida'     => '/invalid api key|unauthorized|401 unauthorized|authentication failed/i',
        // Upstream/app caído (nginx 502) — antes que DB para no confundir "connection refused".
        'backend caído (502)'    => '/failed .*connecting to upstream|connect\(\) failed|upstream (timed out|prematurely|sent)|502 bad gateway|no live upstreams/i',
        'DB caída/conexión'      => '/too many connections|sqlstate|mysqli|can\'t connect to (mysql|local|database)|mysql server has gone away|database.*(refused|down)/i',
        'disco lleno'            => '/no space left|disk full|quota exceeded on disk/i',
    ],
    // Errores de CÓDIGO (bug real). En CodigoAmigo son candidatos a autofix.
    '_code_patterns' => '/unsupported operand|undefined (variable|array key|property|index)|trying to access array offset|foreach\(\) argument|attempt to read property|must be of type|call to undefined (function|method)|typeerror|fatal error|uncaught|par.?error|traceback \(most recent/i',
];
