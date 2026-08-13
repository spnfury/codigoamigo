<?php
/**
 * autofix/parsers.php — librería compartida para el motor MULTI-PORTAL
 * (orchestrator_portal.php). NO la usa el autofix original de CodigoAmigo
 * (detect.php/orchestrator.php/config.php), que sigue intacto y sin tocar.
 *
 * Provee:
 *  - lectura por offset de bytes para logs que crecen sin parar (nginx/apache/php),
 *    con baseline en 1ª vista (igual que fleet_watch.php) para no leer histórico
 *    ni reventar con logs de decenas de MB.
 *  - un extractor UNIVERSAL de errores PHP fatales/warnings que funciona sea cual
 *    sea el envoltorio de la línea (Apache+proxy_fcgi "AH01071: ... PHP message:
 *    PHP Fatal error: ...", nginx "FastCGI sent in stderr: PHP message: ...",
 *    o el formato nativo de php_errors.log) — todos contienen el mismo núcleo
 *    "PHP <tipo>: <mensaje> in <archivo> on line <N>", así que un solo regex
 *    cubre los tres formatos sin necesitar un parser por stack.
 *  - firma/clasificación de errores (mismo criterio que detect.php, duplicado
 *    aquí a propósito para no depender de — ni arriesgar — el código en producción).
 */

/** Firma estable de un error, para deduplicar (idéntico criterio a detect.php). */
function autofixp_firma(array $e): string {
    return substr(md5($e['archivo'] . ':' . $e['linea'] . ':' . $e['tipo']), 0, 16);
}

/** Clasifica 'auto' (tipo seguro + ruta no sensible) o 'manual'. */
function autofixp_clasificar(array $e, array $cfg): string {
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

/**
 * Lee los bytes NUEVOS de un fichero desde el offset guardado en
 * <state_dir>/offsets.json. Primera vez que se ve el fichero: baseline al
 * tamaño actual, no se lee histórico (evita OOM en logs de decenas de MB y
 * evita re-alertar errores viejos ya conocidos/resueltos).
 */
function autofixp_leer_offset(string $file, string $stateDir, int $maxRead = 5242880): string {
    @mkdir($stateDir, 0775, true);
    $offFile = $stateDir . '/offsets.json';
    $offsets = is_file($offFile) ? (json_decode(file_get_contents($offFile), true) ?: []) : [];

    $size = @filesize($file);
    if ($size === false) return '';

    if (!array_key_exists($file, $offsets)) {
        $offsets[$file] = $size;
        file_put_contents($offFile, json_encode($offsets, JSON_PRETTY_PRINT));
        return '';
    }

    $prev = $offsets[$file];
    if ($prev > $size) $prev = 0;                       // el log rotó -> desde 0
    if ($size - $prev > $maxRead) $prev = $size - $maxRead; // tope de lectura

    $data = '';
    if ($size > $prev) {
        $fh = @fopen($file, 'r');
        if ($fh) { fseek($fh, $prev); $data = stream_get_contents($fh); fclose($fh); }
    }
    $offsets[$file] = $size;
    file_put_contents($offFile, json_encode($offsets, JSON_PRETTY_PRINT));
    return $data;
}

/**
 * Extractor UNIVERSAL de errores PHP desde texto crudo (multi-formato).
 * Reconoce Fatal error / Parse error / Uncaught * / Warning, con o sin
 * envoltorio de Apache (AH01071) o nginx (FastCGI sent in stderr).
 * Devuelve entradas en el MISMO formato que autofix_parsear() de detect.php
 * (ts, mensaje, tipo, archivo, linea, url, trace) para reusar el resto del
 * pipeline sin cambios — url/trace quedan vacíos (esta fuente no los tiene).
 */
function autofixp_extraer_php(string $raw): array {
    if ($raw === '') return [];
    $rx = '/PHP\s+(Fatal error|Parse error|Uncaught\s+\w+(?:Error|Exception)|Warning|Notice):\s*(.+?)\s+in\s+(\/\S+\.php)(?:\((\d+)\)|\s+on\s+line\s+(\d+))/i';
    $tsRx = '/(\d{4}[-\/]\d{2}[-\/]\d{2}[T ]\d{2}:\d{2}:\d{2})|(\d{2}-[A-Za-z]{3}-\d{4} \d{2}:\d{2}:\d{2})/';

    $entradas = [];
    foreach (preg_split('/\r?\n/', $raw) as $linea) {
        if (strlen($linea) < 20) continue;
        if (!preg_match($rx, $linea, $m)) continue;

        $ts = date('Y-m-d H:i:s');
        if (preg_match($tsRx, $linea, $tm)) {
            $raw_ts = $tm[1] ?: $tm[2];
            $parsed = strtotime(str_replace('/', '-', $raw_ts));
            if ($parsed) $ts = date('Y-m-d H:i:s', $parsed);
        }

        $entradas[] = [
            'ts'      => $ts,
            'mensaje' => trim($m[1] . ': ' . $m[2]),
            'tipo'    => trim($m[1] . ': ' . $m[2]),
            'archivo' => $m[3],
            'linea'   => (int)($m[4] ?: $m[5]),
            'url'     => '',
            'trace'   => [],
        ];
    }
    return $entradas;
}

/**
 * Recolecta entradas nuevas de TODAS las 'sources' de un portal, según su
 * tipo. Único tipo soportado por ahora: 'php_generic' (log que crece, offset
 * por bytes). Devuelve entradas ya deduplicadas dentro de esta misma pasada.
 */
function autofixp_detectar(array $cfg): array {
    $stateDir = $cfg['state_dir'];
    $vistos = [];
    $salida = [];

    foreach ($cfg['sources'] as $src) {
        if (($src['type'] ?? '') !== 'php_generic') continue; // único tipo soportado hoy
        if (!is_file($src['path'])) continue;

        $raw = autofixp_leer_offset($src['path'], $stateDir, $cfg['max_leer_bytes'] ?? 5242880);
        foreach (autofixp_extraer_php($raw) as $e) {
            $firma = autofixp_firma($e);
            if (isset($vistos[$firma])) continue;
            $vistos[$firma] = true;
            $e['firma'] = $firma;
            $salida[] = $e;
        }
    }
    return $salida;
}
