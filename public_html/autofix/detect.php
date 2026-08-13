<?php
/**
 * autofix/detect.php — ingesta y triaje de errores critical.
 *
 * Parsea el log critical de hoy, agrupa entradas multilínea, extrae
 * (tipo, archivo, línea, url, mensaje, trace), deduplica contra state/seen.json
 * y clasifica cada error nuevo como 'auto' (auto-reparable) o 'manual'.
 *
 * Uso:  php detect.php            -> imprime JSON de errores NUEVOS
 *       php detect.php --all      -> incluye también los ya vistos
 */

$cfg = require (getenv('AUTOFIX_CONFIG') ?: __DIR__ . '/config.php');

/** Devuelve la firma estable de un error (para deduplicar). */
function autofix_firma(array $e): string {
    return substr(md5($e['archivo'] . ':' . $e['linea'] . ':' . $e['tipo']), 0, 16);
}

/** Clasifica: 'auto' si tipo seguro Y ruta no sensible; si no 'manual'. */
function autofix_clasificar(array $e, array $cfg): string {
    $tipo_ok = false;
    foreach ($cfg['tipos_seguros'] as $t) {
        if (stripos($e['tipo'], $t) !== false || stripos($e['mensaje'], $t) !== false) { $tipo_ok = true; break; }
    }
    if (!$tipo_ok) return 'manual';

    $objetivo = strtolower($e['archivo'] . ' ' . $e['url']);
    foreach ($cfg['rutas_sensibles'] as $r) {
        if (strpos($objetivo, strtolower($r)) !== false) return 'manual';
    }
    return 'auto';
}

/** Parsea un fichero de log critical en entradas estructuradas. */
function autofix_parsear(string $ruta): array {
    if (!is_file($ruta)) return [];
    $lineas = file($ruta, FILE_IGNORE_NEW_LINES);
    $entradas = [];
    $actual = null;

    foreach ($lineas as $l) {
        // Nueva entrada: "[2026-07-01 05:35:16] [critical] <mensaje>"
        if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s*\[critical\]\s*(.*)$/', $l, $m)) {
            if ($actual) $entradas[] = $actual;
            $actual = [
                'ts'      => $m[1],
                'mensaje' => trim($m[2]),
                'tipo'    => '',
                'archivo' => '',
                'linea'   => 0,
                'url'     => '',
                'trace'   => [],
            ];
            // Tipo = parte antes de ":" del mensaje si lo hay, si no el mensaje
            $actual['tipo'] = trim($m[2]);
            continue;
        }
        if (!$actual) continue;

        if (strpos($l, '📄') !== false && preg_match('#(/\S+\.php):(\d+)#', $l, $mm)) {
            $actual['archivo'] = $mm[1];
            $actual['linea']   = (int)$mm[2];
        } elseif (strpos($l, '🌐') !== false) {
            $actual['url'] = trim(str_replace(['🌐'], '', $l));
        } elseif (preg_match('/^#\d+\s/', trim($l))) {
            $actual['trace'][] = trim($l);
        }
    }
    if ($actual) $entradas[] = $actual;
    return $entradas;
}

// ---- Ejecución CLI ----
if (php_sapi_name() === 'cli' && realpath($argv[0]) === realpath(__FILE__)) {
    $incluir_todo = in_array('--all', $argv, true);

    $hoy   = date('Y-m-d');
    $ruta  = $cfg['dir_critical'] . '/' . $hoy . '.log';
    $todas = autofix_parsear($ruta);

    $seen_file = $cfg['dir_autofix'] . '/state/seen.json';
    $seen = is_file($seen_file) ? (json_decode(file_get_contents($seen_file), true) ?: []) : [];

    $vistos = [];
    $salida = [];
    foreach ($todas as $e) {
        if (!$e['archivo']) continue;                 // sin ubicación no se puede arreglar
        $firma = autofix_firma($e);
        if (isset($vistos[$firma])) continue;         // dedup dentro del mismo log
        $vistos[$firma] = true;

        $ya = isset($seen[$firma]);
        if ($ya && !$incluir_todo) continue;

        $e['firma']  = $firma;
        $e['accion'] = autofix_clasificar($e, $cfg);
        $e['estado_previo'] = $ya ? $seen[$firma]['estado'] : 'nuevo';
        $salida[] = $e;
    }

    echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}
