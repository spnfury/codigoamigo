<?php
/**
 * autofix/pm2_watchdog.php — vigía de apps PM2 con AUTO-REINICIO.
 *
 * Complementa a fleet_watch.php (detecta y avisa) con la parte reactiva:
 * - App PM2 con estado distinto de 'online'  -> pm2 restart (acotado).
 * - App online pero su puerto HTTP no responde (colgada) -> pm2 restart.
 * - Ráfaga de reinicios entre dos vueltas (crash-loop) -> alerta Telegram.
 *
 * Presupuesto anti-bucle: como mucho `max_restarts_dia` reinicios POR APP Y DÍA;
 * superado, no se reinicia más y se avisa (requiere acción manual).
 *
 * Uso:   php pm2_watchdog.php            (normal, notifica)
 *        php pm2_watchdog.php --print    (no notifica, solo log)
 */

$dir   = __DIR__;
$STATE = $dir . '/state';
$stateFile = $STATE . '/pm2_watchdog.json';
$DRY = in_array('--print', $argv, true);

$cfg = [
    'intervalo_cron_min' => 5,               // solo informativo, para dimensionar el budget
    'max_restarts_dia'   => 3,               // reinicios automáticos por app y día (anti-bucle)
    'probe_timeout'      => 3,               // segundos por probe HTTP
    'alerta_storm'       => 5,               // >=N reinicios nuevos entre vueltas => crash-loop
    'probe' => [                             // puerto HTTP de health-check (127.0.0.1)
        'gasolina-barata' => 3001,
        'radargas'        => 3002,
        'flexroute'       => 3210,
        'cartastral'      => 3300,
        'motionclone'     => 3456,
        'estelar'         => 4101,
    ],
    // Apps que NUNCA se reinician solas: requieren intervención manual
    // (p.ej. re-escanear QR de WhatsApp). El vigía las detecta y alerta, pero
    // no las toca para no entrar en bucle de reinicios inútiles.
    'no_autorestart' => ['wsaap-king', 'wsaap-comunidad'],
    'ignorar' => ['pm2-logrotate'],          // módulo de sistema, no es app
];

@mkdir($STATE, 0775, true);
$st = is_file($stateFile) ? (json_decode(file_get_contents($stateFile), true) ?: []) : [];
$hoy = date('Y-m-d');
if (($st['fecha'] ?? '') !== $hoy) {         // budget diario renovado
    $st['fecha'] = $hoy;
    $st['reinicios'] = [];
}

// ---- leer estado de PM2 ----
exec('pm2 jlist 2>/dev/null', $raw, $rc);
if ($rc !== 0) { fwrite(STDERR, "pm2_watchdog: pm2 jlist falló\n"); exit(1); }
$salida = implode("\n", $raw);
// pm2 imprime a veces avisos antes del JSON (p.ej. "In-memory PM2 is
// out-of-date") cuando el entorno es mínimo (cron). Extraemos el array JSON.
$ini = strpos($salida, '[');
$fin = strrpos($salida, ']');
$apps = ($ini !== false && $fin !== false && $fin > $ini)
    ? json_decode(substr($salida, $ini, $fin - $ini + 1), true) : null;
if (!is_array($apps)) { fwrite(STDERR, "pm2_watchdog: salida pm2 no parseable\n"); exit(1); }

// Agrupar por nombre (cluster = varias entradas con el mismo nombre).
$grupos = [];
foreach ($apps as $p) {
    $n = $p['name'] ?? '?';
    if (in_array($n, $cfg['ignorar'], true)) continue;
    $e = $p['pm2_env'] ?? [];
    if (!isset($grupos[$n])) $grupos[$n] = ['statuses' => [], 'max_rt' => 0, 'port' => null];
    $grupos[$n]['statuses'][] = $e['status'] ?? 'unknown';
    $grupos[$n]['max_rt'] = max($grupos[$n]['max_rt'], (int)($e['restart_time'] ?? 0));
    if (isset($e['env']['PORT']) && (int)$e['env']['PORT'] > 0) $grupos[$n]['port'] = (int)$e['env']['PORT'];
}

$msg = [];
$acciones = 0;

/** Intenta reiniciar una app respetando el budget diario. Devuelve bool. */
function intentar_restart(string $nombre, string $razon, array &$st, array $cfg, bool $DRY, array &$msg, int &$acciones): bool {
    $d = $st['reinicios'][$nombre] ?? 0;
    if ($d >= $cfg['max_restarts_dia']) {
        $msg[] = "⛔ {$nombre}: budget agotado ({$d}/{$cfg['max_restarts_dia']}) — {$razon}. Requiere revisión manual.";
        return false;
    }
    if ($DRY) {
        $msg[] = "▶ [dry] {$nombre}: se reiniciaría ({$razon})";
        $acciones++;
        return true;
    }
    exec('pm2 restart ' . escapeshellarg($nombre) . ' >/dev/null 2>&1', $_, $r);
    if ($r === 0) {
        $st['reinicios'][$nombre] = $d + 1;
        $msg[] = "🔄 {$nombre} reiniciada ({$razon}) — {$st['reinicios'][$nombre]}/{$cfg['max_restarts_dia']} hoy";
        $acciones++;
        return true;
    }
    $msg[] = "❌ {$nombre}: fallo al reiniciar ({$razon})";
    return false;
}

foreach ($grupos as $nombre => $g) {
    $rt = $g['max_rt'];
    $prevRt = $st['last_rt'][$nombre] ?? null;
    $st['last_rt'][$nombre] = $rt;

    // Ráfaga de reinicios (crash-loop) aunque ahora esté online.
    if ($prevRt !== null && ($rt - $prevRt) >= $cfg['alerta_storm']) {
        $msg[] = "🚨 {$nombre}: +" . ($rt - $prevRt) . " reinicios desde la última vuelta — crash-loop (¿bug de código?)";
    }

    $todoCaido = count(array_filter($g['statuses'], fn($s) => $s !== 'online')) === count($g['statuses']);
    if ($todoCaido) {
        if (in_array($nombre, $cfg['no_autorestart'], true)) {
            $msg[] = "⛔ {$nombre}: estado '{$g['statuses'][0]}' — en lista no_autorestart, requiere acción manual";
        } else {
            intentar_restart($nombre, 'estado: ' . implode('/', $g['statuses']), $st, $cfg, $DRY, $msg, $acciones);
        }
        continue;
    }

    // Health-check HTTP de las que tienen puerto conocido.
    $puerto = $g['port'] ?? ($cfg['probe'][$nombre] ?? null);
    if ($puerto) {
        $code = @file_get_contents("http://127.0.0.1:{$puerto}/", false, stream_context_create(['http' => ['timeout' => $cfg['probe_timeout']]]));
        if ($code === false) {
            if (intentar_restart($nombre, "sin respuesta HTTP en :{$puerto} (colgada)", $st, $cfg, $DRY, $msg, $acciones) && !$DRY) {
                usleep(4000000); // espera al arranque y re-prueba
                $code2 = @file_get_contents("http://127.0.0.1:{$puerto}/", false, stream_context_create(['http' => ['timeout' => $cfg['probe_timeout']]]));
                if ($code2 === false) {
                    $msg[] = "🚨 {$nombre}: sigue sin responder tras el reinicio en :{$puerto}";
                }
            }
        }
    }
}

// ---- persistir estado y notificar ----
$log = date('Y-m-d H:i:s') . " pm2_watchdog: " . count($apps) . " procesos, " . count($grupos) . " apps, $acciones acción(es)\n";
if ($DRY) $log .= "  [--print] sin cambios persistidos\n";
if (!is_dir($dir . '/logs')) @mkdir($dir . '/logs', 0775, true);
file_put_contents($dir . '/logs/pm2_watchdog.log', $log, FILE_APPEND);

$texto = implode("\n", $msg);
echo $texto !== '' ? "pm2_watchdog:\n{$texto}\n" : "pm2_watchdog: todo ok · " . count($grupos) . " apps vigiladas\n";

if ($texto !== '' && !$DRY) {
    file_put_contents($stateFile, json_encode($st, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    $p = popen('php ' . escapeshellarg($dir . '/notify.php') . ' 2>/dev/null', 'w');
    if ($p) { fwrite($p, "🛠 PM2 watchdog\n\n" . $texto); pclose($p); }
} elseif (!$DRY) {
    file_put_contents($stateFile, json_encode($st, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}
