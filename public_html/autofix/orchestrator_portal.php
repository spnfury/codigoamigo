<?php
/**
 * autofix/orchestrator_portal.php — motor MULTI-PORTAL del auto-reparador.
 *
 * Mismo cerebro que orchestrator.php (canario -> breaker -> detectar -> reparar,
 * mismos guardarraíles), pero parametrizado por portal vía --portal=NOMBRE,
 * cargando autofix/portals/<NOMBRE>.php. Usa parsers.php (offset de bytes +
 * extractor universal de errores PHP) en vez del parser específico de
 * CodigoAmigo — así cubre cualquier portal PHP sin depender de su logger.
 *
 * NO toca ni depende de config.php/detect.php/orchestrator.php (el autofix
 * original de CodigoAmigo sigue corriendo exactamente igual, por su cuenta).
 *
 * Uso: php orchestrator_portal.php --portal=camarerooo
 */

require_once __DIR__ . '/parsers.php';

$AUTOFIX_ROOT = __DIR__; // física, portal-independiente (aquí vive notify.php)

$portal = null;
foreach ($argv as $a) {
    if (preg_match('/^--portal=(.+)$/', $a, $m)) { $portal = $m[1]; break; }
}
if (!$portal) { fwrite(STDERR, "Uso: php orchestrator_portal.php --portal=NOMBRE\n"); exit(1); }

$portalFile = $AUTOFIX_ROOT . '/portals/' . basename($portal) . '.php';
if (!is_file($portalFile)) { fwrite(STDERR, "Portal desconocido: $portal ($portalFile no existe)\n"); exit(1); }
$cfg = require $portalFile;

$STATE   = $cfg['state_dir'];
$BACKUPS = $STATE . '/backups';
$LOGFILE = $AUTOFIX_ROOT . '/logs/autofix-' . $cfg['portal_key'] . '-' . date('Y-m-d') . '.log';
$DRY     = ($cfg['modo'] ?? 'dry') !== 'live';

@mkdir($BACKUPS, 0775, true);

function palog(string $msg): void {
    global $LOGFILE;
    $linea = '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n";
    file_put_contents($LOGFILE, $linea, FILE_APPEND);
    echo $linea;
}
function pjload(string $f): array { return is_file($f) ? (json_decode(file_get_contents($f), true) ?: []) : []; }
function pjsave(string $f, array $d): void { file_put_contents($f, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); }

function pnotificar(string $autofixRoot, string $portalKey, string $texto): void {
    $bin = 'php ' . escapeshellarg($autofixRoot . '/notify.php');
    $p = popen($bin . ' 2>/dev/null', 'w');
    if ($p) { fwrite($p, "[{$portalKey}]\n" . $texto); pclose($p); }
}

function pbreaker_count(string $STATE): int {
    $d = pjload($STATE . '/rollbacks.json');
    return (int)($d[date('Y-m-d')] ?? 0);
}
function pbreaker_inc(string $STATE): void {
    $f = $STATE . '/rollbacks.json';
    $d = pjload($f);
    $hoy = date('Y-m-d');
    $d[$hoy] = (int)($d[$hoy] ?? 0) + 1;
    pjsave($f, $d);
}

$seenFile = $STATE . '/seen.json';
$seen = pjload($seenFile);

palog("=== autofix run portal={$cfg['portal_key']} (modo=" . ($DRY ? 'DRY' : 'LIVE') . ") ===");

// ---------- Lectura de fuentes (una sola vez; offset avanza aquí) ----------
$frescas = autofixp_detectar($cfg); // entradas nuevas desde el último run, ya con 'firma'

// ---------- PASO 1: CANARIO ----------
$ventana = ($cfg['ventana_canario_min'] ?? 30) * 60;
$frescasPorFirma = [];
foreach ($frescas as $e) { $frescasPorFirma[$e['firma']] = $e; }

foreach ($seen as $firma => &$reg) {
    if (($reg['estado'] ?? '') !== 'healing') continue;
    $tsFix = strtotime($reg['ts_fix'] ?? 'now');
    if (time() - $tsFix < $ventana) continue; // aún en observación

    if (isset($frescasPorFirma[$firma])) {
        // ROLLBACK automático
        $bk = $reg['backup'] ?? '';
        if ($bk && is_file($bk) && !$DRY) {
            copy($bk, $reg['archivo']);
            palog("ROLLBACK $firma -> restaurado " . $reg['archivo']);
        }
        pbreaker_inc($STATE);
        $reg['estado'] = 'rollback';
        $reg['ts_ultimo'] = date('Y-m-d H:i:s');
        pnotificar($AUTOFIX_ROOT, $cfg['portal_key'], "↩️ ROLLBACK automático\n{$reg['archivo']}:{$reg['linea']}\nEl error reapareció tras el fix. Restaurado el original. Requiere revisión manual.");
    } else {
        $reg['estado'] = 'healed';
        $reg['ts_ultimo'] = date('Y-m-d H:i:s');
        palog("HEALED $firma -> " . $reg['archivo'] . ':' . $reg['linea']);
        pnotificar($AUTOFIX_ROOT, $cfg['portal_key'], "✅ Fix confirmado sano\n{$reg['archivo']}:{$reg['linea']}\nSin reaparición en " . ($cfg['ventana_canario_min']) . " min.");
    }
}
unset($reg);
pjsave($seenFile, $seen);

// ---------- PASO 2: CIRCUIT BREAKER ----------
$rb = pbreaker_count($STATE);
if ($rb >= ($cfg['max_rollbacks_dia'] ?? 3)) {
    palog("CIRCUIT BREAKER activo: $rb rollbacks hoy. Loop pausado.");
    pnotificar($AUTOFIX_ROOT, $cfg['portal_key'], "🛑 Circuit breaker: $rb rollbacks hoy. Loop pausado hasta mañana. Revisa qué está fallando.");
    exit(0);
}

// ---------- PASO 3: DETECTAR nuevos (no vistos antes) ----------
$nuevos = [];
foreach ($frescas as $e) {
    if (isset($seen[$e['firma']])) continue; // ya conocido (cualquier estado previo)
    $e['accion'] = autofixp_clasificar($e, $cfg);
    $nuevos[] = $e;
}
palog("Errores nuevos: " . count($nuevos));

// ---------- PASO 4: REPARAR ----------
$reparados = 0;
$maxFix = $cfg['max_fixes_por_run'] ?? 2;

foreach ($nuevos as $e) {
    $firma = $e['firma'];

    if ($e['accion'] === 'manual') {
        $seen[$firma] = ['estado' => 'manual', 'archivo' => $e['archivo'], 'linea' => $e['linea'],
                         'tipo' => $e['tipo'], 'ts_ultimo' => date('Y-m-d H:i:s')];
        palog("MANUAL $firma -> " . $e['archivo'] . ':' . $e['linea']);
        pnotificar($AUTOFIX_ROOT, $cfg['portal_key'], "⚠️ Requiere revisión MANUAL (ruta/tipo sensible)\n{$e['archivo']}:{$e['linea']}\n{$e['tipo']}");
        continue;
    }

    if ($reparados >= $maxFix) {
        palog("Límite de fixes por run alcanzado ($maxFix). El resto en el próximo ciclo.");
        break;
    }

    $backup = $BACKUPS . '/' . $firma . '_' . date('YmdHis') . '_' . basename($e['archivo']) . '.bak';
    if (!$DRY) { @copy($e['archivo'], $backup); }
    palog(($DRY ? '[DRY] ' : '') . "FIX $firma -> " . $e['archivo'] . ':' . $e['linea'] . " (backup: " . basename($backup) . ")");

    $prompt = autofixp_prompt($e);

    if ($DRY) {
        palog("[DRY] Se invocaría claude con prompt de " . strlen($prompt) . " chars. No se edita.");
        pnotificar($AUTOFIX_ROOT, $cfg['portal_key'], "🔎 [DRY] Detectado auto-reparable\n{$e['archivo']}:{$e['linea']}\n{$e['tipo']}\n(modo simulación: no se toca nada)");
        $reparados++;
        continue;
    }

    $allowed = implode(' ', array_map('escapeshellarg', $cfg['claude_allowed']));
    $cmd = escapeshellarg($cfg['claude_bin'])
         . ' -p ' . escapeshellarg($prompt)
         . ' --add-dir ' . escapeshellarg($cfg['raiz'])
         . ' --allowedTools ' . $allowed
         . ' --permission-mode acceptEdits'
         . ' --model ' . escapeshellarg($cfg['claude_model'])
         . ' 2>&1';
    palog("Ejecutando claude headless...");
    $salida = shell_exec($cmd);
    palog("claude salida: " . substr(trim((string)$salida), 0, 500));

    $lint = shell_exec('php -l ' . escapeshellarg($e['archivo']) . ' 2>&1');
    if (strpos((string)$lint, 'No syntax errors') === false) {
        if (is_file($backup)) copy($backup, $e['archivo']);
        pbreaker_inc($STATE);
        $seen[$firma] = ['estado' => 'failed', 'archivo' => $e['archivo'], 'linea' => $e['linea'],
                         'tipo' => $e['tipo'], 'ts_ultimo' => date('Y-m-d H:i:s')];
        palog("LINT FAIL $firma -> restaurado. " . trim((string)$lint));
        pnotificar($AUTOFIX_ROOT, $cfg['portal_key'], "❌ Fix descartado (lint falló)\n{$e['archivo']}:{$e['linea']}\nRestaurado el original. Requiere revisión manual.");
        continue;
    }

    $seen[$firma] = ['estado' => 'healing', 'archivo' => $e['archivo'], 'linea' => $e['linea'],
                     'tipo' => $e['tipo'], 'backup' => $backup,
                     'ts_fix' => date('Y-m-d H:i:s'), 'ts_ultimo' => date('Y-m-d H:i:s')];
    $reparados++;
    palog("APLICADO $firma -> en observación " . ($cfg['ventana_canario_min']) . " min.");
    pnotificar($AUTOFIX_ROOT, $cfg['portal_key'], "🔧 Fix aplicado (en observación)\n{$e['archivo']}:{$e['linea']}\n{$e['tipo']}\nSi reaparece en " . ($cfg['ventana_canario_min']) . " min se revierte solo.");
}

pjsave($seenFile, $seen);
palog("=== fin run: $reparados fix(es) procesados ===");

/** Construye el prompt estricto para el agente headless (igual criterio que orchestrator.php). */
function autofixp_prompt(array $e): string {
    return <<<TXT
Eres un reparador automático de bugs de PHP en producción. Tienes UN error concreto que corregir.

ERROR:
{$e['tipo']}
Archivo: {$e['archivo']}
Línea: {$e['linea']}

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
