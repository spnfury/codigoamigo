<?php
/**
 * Migra imágenes de marca alojadas en dominios externos (hotlinks frágiles:
 * seeklogo, wikimedia, glassdoor, S3 viejo…) a /img/panel_marcas/ext/.
 *
 * - Descarga con timeout corto; solo migra si HTTP 200 + content-type imagen.
 * - Actualiza marcas.imagen a la URL local.
 * - Log de reversión en logs/migracion_logos_YYYY-MM-DD.json (antes/después).
 *
 * Uso: php scripts/limpieza/migrar_logos_externos.php [--dry-run]
 */

include_once __DIR__ . '/../../inc/includes.php';

$dry = in_array('--dry-run', $argv ?? []);

$dir_destino = __DIR__ . '/../../img/panel_marcas/ext/';
if (!is_dir($dir_destino)) {
    mkdir($dir_destino, 0755, true);
}

$db = createConnection();
$col = $db->selectCollection('marcas');

$cur = $col->find(
    ['imagen' => new MongoDB\BSON\Regex('^https?://(?!www\.codigoamigo|cdn\.codigoamigo|codigoamigo)', '')],
    ['projection' => ['nombre_clave' => 1, 'imagen' => 1]]
);

$log = [];
$ok = 0; $fallo = 0; $saltada = 0;

foreach ($cur as $m) {
    $clave = $m['nombre_clave'] ?? (string)$m['_id'];
    $url = $m['imagen'];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; CodigoAmigoMigracion/1.0)',
    ]);
    $data = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $ctype = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if ($code !== 200 || $data === false || strlen($data) < 100 || strpos($ctype, 'image') !== 0) {
        $fallo++;
        $log[] = ['marca' => $clave, 'antes' => $url, 'resultado' => "fallo http:$code ctype:$ctype"];
        continue;
    }

    // Extensión por content-type (más fiable que la URL)
    $ext_map = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif', 'image/svg+xml' => 'svg'];
    $ext = null;
    foreach ($ext_map as $ct => $e) {
        if (strpos($ctype, $ct) === 0) { $ext = $e; break; }
    }
    if ($ext === null) { $saltada++; $log[] = ['marca' => $clave, 'antes' => $url, 'resultado' => "saltada ctype:$ctype"]; continue; }

    $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($clave)) ?: (string)$m['_id'];
    $fichero = $slug . '.' . $ext;
    $url_local = 'https://www.codigoamigo.com/img/panel_marcas/ext/' . $fichero;

    if (!$dry) {
        file_put_contents($dir_destino . $fichero, $data);
        $col->updateOne(['_id' => $m['_id']], ['$set' => ['imagen' => $url_local]]);
    }
    $ok++;
    $log[] = ['marca' => $clave, 'antes' => $url, 'despues' => $url_local, 'resultado' => 'ok'];
}

$log_file = __DIR__ . '/../../logs/migracion_logos_' . date('Y-m-d') . '.json';
file_put_contents($log_file, json_encode(['dry_run' => $dry, 'ok' => $ok, 'fallo' => $fallo, 'saltada' => $saltada, 'detalle' => $log], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

echo "ok:$ok fallo:$fallo saltada:$saltada log:$log_file\n";
