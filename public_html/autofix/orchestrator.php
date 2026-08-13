<?php
/**
 * autofix/orchestrator.php — cerebro del loop auto-reparador.
 *
 * Flujo por ejecución (cron):
 *   1. CANARIO   — revisa fixes en observación del run anterior; revierte si el
 *                  error reapareció.
 *   2. BREAKER   — si hubo demasiados rollbacks hoy, se pausa y sale.
 *   3. DETECTAR  — errores critical nuevos de hoy (vía detect.php).
 *   4. REPARAR   — por cada error 'auto': backup -> claude headless -> php -l
 *                  -> marca 'healing'. Los 'manual' solo se notifican.
 *
 * Guardarraíles: backup por archivo (rollback sin git), lint obligatorio,
 * canario con auto-rollback, circuit breaker, whitelist de tipos y rutas.
 *
 * Modo 'dry' (config): detecta y notifica pero NO edita ni ejecuta claude.
 */

$cfg = require (getenv('AUTOFIX_CONFIG') ?: __DIR__ . '/config.php');
require_once __DIR__ . '/detect.php';   // funciones autofix_*

$STATE   = $cfg['dir_autofix'] . '/state';
$BACKUPS = $STATE . '/backups';
$LOGFILE = $cfg['dir_autofix'] . '/logs/autofix-' . date('Y-m-d') . '.log';
$DRY     = ($cfg['modo'] ?? 'dry') !== 'live';

@mkdir($BACKUPS, 0775, true);

function alog(string $msg): void {
    global $LOGFILE;
    $linea = '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n";
    file_put_contents($LOGFILE, $linea, FILE_APPEND);
    echo $linea;
}
function jload(string $f): array { return is_file($f) ? (json_decode(file_get_contents($f), true) ?: []) : []; }
function jsave(string $f, array $d): void { file_put_contents($f, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); }

function notificar(array $cfg, string $texto): void {
    $bin = 'php ' . escapeshellarg($cfg['dir_autofix'] . '/notify.php');
    $p = popen($bin . ' 2>/dev/null', 'w');
    if ($p) { fwrite($p, $texto); pclose($p); }
}

/** Circuit breaker: nº de rollbacks registrados hoy. */
function breaker_count(string $STATE): int {
    $d = jload($STATE . '/rollbacks.json');
    return (int)($d[date('Y-m-d')] ?? 0);
}
function breaker_inc(string $STATE): void {
    $f = $STATE . '/rollbacks.json';
    $d = jload($f);
    $hoy = date('Y-m-d');
    $d[$hoy] = (int)($d[$hoy] ?? 0) + 1;
    jsave($f, $d);
}

$seenFile = $STATE . '/seen.json';
$seen = jload($seenFile);

alog("=== autofix run (modo=" . ($DRY ? 'DRY' : 'LIVE') . ") ===");

// ---------- PASO 1: CANARIO ----------
$hoyLog = $cfg['dir_critical'] . '/' . date('Y-m-d') . '.log';
$entradasHoy = autofix_parsear($hoyLog);
$ventana = ($cfg['ventana_canario_min'] ?? 30) * 60;

foreach ($seen as $firma => &$reg) {
    if (($reg['estado'] ?? '') !== 'healing') continue;
    $tsFix = strtotime($reg['ts_fix'] ?? 'now');
    if (time() - $tsFix < $ventana) { continue; }  // aún en observación

    // ¿Reapareció el MISMO error después del fix?
    $reaparece = false;
    foreach ($entradasHoy as $e) {
        if (!$e['archivo']) continue;
        if (autofix_firma($e) === $firma && strtotime($e['ts']) > $tsFix) { $reaparece = true; break; }
    }

    if ($reaparece) {
        // ROLLBACK automático
        $bk = $reg['backup'] ?? '';
        if ($bk && is_file($bk) && !$DRY) {
            copy($bk, $reg['archivo']);
            alog("ROLLBACK $firma -> restaurado " . $reg['archivo']);
        }
        breaker_inc($STATE);
        $reg['estado'] = 'rollback';
        $reg['ts_ultimo'] = date('Y-m-d H:i:s');
        notificar($cfg, "↩️ ROLLBACK automático\n{$reg['archivo']}:{$reg['linea']}\nEl error reapareció tras el fix. Restaurado el original. Requiere revisión manual.");
    } else {
        // Sano
        $reg['estado'] = 'healed';
        $reg['ts_ultimo'] = date('Y-m-d H:i:s');
        alog("HEALED $firma -> " . $reg['archivo'] . ':' . $reg['linea']);
        notificar($cfg, "✅ Fix confirmado sano\n{$reg['archivo']}:{$reg['linea']}\nSin reaparición en " . ($cfg['ventana_canario_min']) . " min.");
    }
}
unset($reg);
jsave($seenFile, $seen);

// ---------- PASO 2: CIRCUIT BREAKER ----------
$rb = breaker_count($STATE);
if ($rb >= ($cfg['max_rollbacks_dia'] ?? 3)) {
    alog("CIRCUIT BREAKER activo: $rb rollbacks hoy. Loop pausado.");
    notificar($cfg, "🛑 Circuit breaker: $rb rollbacks hoy. Loop pausado hasta mañana. Revisa qué está fallando.");
    exit(0);
}

// ---------- PASO 3: DETECTAR ----------
$vistos = [];
$nuevos = [];
foreach ($entradasHoy as $e) {
    if (!$e['archivo']) continue;
    $firma = autofix_firma($e);
    if (isset($vistos[$firma]) || isset($seen[$firma])) continue;
    $vistos[$firma] = true;
    $e['firma']  = $firma;
    $e['accion'] = autofix_clasificar($e, $cfg);
    $nuevos[] = $e;
}
alog("Errores nuevos: " . count($nuevos));

// ---------- PASO 4: REPARAR ----------
$reparados = 0;
$maxFix = $cfg['max_fixes_por_run'] ?? 2;

foreach ($nuevos as $e) {
    $firma = $e['firma'];

    if ($e['accion'] === 'manual') {
        $seen[$firma] = ['estado' => 'manual', 'archivo' => $e['archivo'], 'linea' => $e['linea'],
                         'tipo' => $e['tipo'], 'ts_ultimo' => date('Y-m-d H:i:s')];
        alog("MANUAL $firma -> " . $e['archivo'] . ':' . $e['linea']);
        notificar($cfg, "⚠️ Requiere revisión MANUAL (ruta/tipo sensible)\n{$e['archivo']}:{$e['linea']}\n{$e['tipo']}\n{$e['url']}");
        continue;
    }

    if ($reparados >= $maxFix) {
        alog("Límite de fixes por run alcanzado ($maxFix). El resto en el próximo ciclo.");
        break;
    }

    // --- backup ---
    $backup = $BACKUPS . '/' . $firma . '_' . date('YmdHis') . '_' . basename($e['archivo']) . '.bak';
    if (!$DRY) { @copy($e['archivo'], $backup); }
    alog(($DRY ? '[DRY] ' : '') . "FIX $firma -> " . $e['archivo'] . ':' . $e['linea'] . " (backup: " . basename($backup) . ")");

    // --- prompt headless ---
    $prompt = autofix_prompt($e);

    if ($DRY) {
        alog("[DRY] Se invocaría claude con prompt de " . strlen($prompt) . " chars. No se edita.");
        notificar($cfg, "🔎 [DRY] Detectado auto-reparable\n{$e['archivo']}:{$e['linea']}\n{$e['tipo']}\n(modo simulación: no se toca nada)");
        $reparados++;
        continue;
    }

    // --- invocar claude headless (whitelist estricta de tools) ---
    $allowed = implode(' ', array_map('escapeshellarg', $cfg['claude_allowed']));
    $cmd = escapeshellarg($cfg['claude_bin'])
         . ' -p ' . escapeshellarg($prompt)
         . ' --add-dir ' . escapeshellarg($cfg['raiz'])
         . ' --allowedTools ' . $allowed
         . ' --permission-mode acceptEdits'
         . ' --model ' . escapeshellarg($cfg['claude_model'])
         . ' 2>&1';
    alog("Ejecutando claude headless...");
    $salida = shell_exec($cmd);
    alog("claude salida: " . substr(trim((string)$salida), 0, 500));

    // --- GUARDARRAÍL: php -l obligatorio ---
    $lint = shell_exec('php -l ' . escapeshellarg($e['archivo']) . ' 2>&1');
    if (strpos((string)$lint, 'No syntax errors') === false) {
        // fix inválido -> restaurar de inmediato
        if (is_file($backup)) copy($backup, $e['archivo']);
        breaker_inc($STATE);
        $seen[$firma] = ['estado' => 'failed', 'archivo' => $e['archivo'], 'linea' => $e['linea'],
                         'tipo' => $e['tipo'], 'ts_ultimo' => date('Y-m-d H:i:s')];
        alog("LINT FAIL $firma -> restaurado. " . trim((string)$lint));
        notificar($cfg, "❌ Fix descartado (lint falló)\n{$e['archivo']}:{$e['linea']}\nRestaurado el original. Requiere revisión manual.");
        continue;
    }

    // --- fix aplicado, entra en observación (canario) ---
    $seen[$firma] = ['estado' => 'healing', 'archivo' => $e['archivo'], 'linea' => $e['linea'],
                     'tipo' => $e['tipo'], 'backup' => $backup,
                     'ts_fix' => date('Y-m-d H:i:s'), 'ts_ultimo' => date('Y-m-d H:i:s')];
    $reparados++;
    alog("APLICADO $firma -> en observación " . ($cfg['ventana_canario_min']) . " min.");
    notificar($cfg, "🔧 Fix aplicado (en observación)\n{$e['archivo']}:{$e['linea']}\n{$e['tipo']}\nSi reaparece en " . ($cfg['ventana_canario_min']) . " min se revierte solo.");
}

jsave($seenFile, $seen);
alog("=== fin run: $reparados fix(es) procesados ===");

/** Construye el prompt estricto para el agente headless. */
function autofix_prompt(array $e): string {
    $trace = implode("\n", array_slice($e['trace'], 0, 6));
    return <<<TXT
Eres un reparador automático de bugs de PHP en producción. Tienes UN error concreto que corregir.

ERROR:
{$e['tipo']}
Archivo: {$e['archivo']}
Línea: {$e['linea']}
URL: {$e['url']}
Trace:
{$trace}

REGLAS ESTRICTAS (obligatorias):
1. Corrige SOLO la causa raíz de ESTE error, en ESTE archivo. Nada más.
2. NO refactorices, NO cambies estilo, NO toques otras funciones ni otros archivos.
3. El cambio debe ser mínimo y defensivo (guards, casts, isset/is_numeric).
4. Mantén el idioma español en comentarios y el estilo del código existente.
5. Al terminar ejecuta `php -l` sobre el archivo y confirma que no hay errores de sintaxis.
6. Si no estás seguro de la causa raíz con alta confianza, NO edites nada y responde exactamente: "SIN CAMBIOS: causa incierta".

Aplica el fix ahora.
TXT;
}
