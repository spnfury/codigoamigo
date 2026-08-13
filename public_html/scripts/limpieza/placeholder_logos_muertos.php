<?php
/**
 * Genera placeholders SVG locales para marcas cuya imagen sigue en un dominio
 * externo (hotlinks muertos que migrar_logos_externos.php no pudo descargar).
 *
 * - Re-verifica cada URL: si responde imagen 200, la descarga (segunda oportunidad).
 * - Si sigue muerta: SVG con las iniciales de la marca y color derivado del nombre.
 * - Actualiza marcas.imagen a la URL local.
 * - Log de reversión en logs/placeholder_logos_YYYY-MM-DD.json.
 *
 * Uso: php scripts/limpieza/placeholder_logos_muertos.php [--dry-run]
 */

include_once __DIR__ . '/../../inc/includes.php';

$dry = in_array('--dry-run', $argv ?? []);

$dir_destino = __DIR__ . '/../../img/panel_marcas/ext/';
if (!is_dir($dir_destino)) {
    mkdir($dir_destino, 0755, true);
}

// Paleta sobria; el color se elige por hash del nombre para que sea estable
$colores = ['#E30613', '#1565C0', '#2E7D32', '#6A1B9A', '#EF6C00', '#00838F', '#4527A0', '#C62828'];

function genera_svg_placeholder($nombre, $color) {
    $palabras = preg_split('/[\s\-_]+/', trim($nombre));
    $iniciales = '';
    foreach ($palabras as $p) {
        if ($p !== '' && strlen($iniciales) < 2) {
            $iniciales .= mb_strtoupper(mb_substr($p, 0, 1));
        }
    }
    if ($iniciales === '') { $iniciales = '?'; }
    $iniciales = htmlspecialchars($iniciales, ENT_QUOTES, 'UTF-8');
    return '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">'
        . '<rect width="200" height="200" rx="24" fill="' . $color . '"/>'
        . '<text x="100" y="100" text-anchor="middle" dominant-baseline="central" '
        . 'font-family="Arial, Helvetica, sans-serif" font-size="84" font-weight="700" fill="#ffffff">'
        . $iniciales . '</text></svg>';
}

$db = createConnection();
$col = $db->selectCollection('marcas');

$cur = $col->find(
    ['imagen' => new MongoDB\BSON\Regex('^https?://(?!www\.codigoamigo|cdn\.codigoamigo|codigoamigo)', '')],
    ['projection' => ['nombre_clave' => 1, 'nombre' => 1, 'imagen' => 1]]
);

$log = [];
$rescatada = 0; $placeholder = 0;

foreach ($cur as $m) {
    $clave = $m['nombre_clave'] ?? (string)$m['_id'];
    $nombre = $m['nombre'] ?? $clave;
    $url = $m['imagen'];

    // Segunda oportunidad de descarga
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

    $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($clave)) ?: (string)$m['_id'];

    $ext_map = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif', 'image/svg+xml' => 'svg'];
    $ext_img = null;
    foreach ($ext_map as $ct => $e) {
        if (strpos($ctype, $ct) === 0) { $ext_img = $e; break; }
    }

    if ($code === 200 && $data !== false && strlen($data) >= 100 && $ext_img !== null) {
        // Descargable después de todo
        $fichero = $slug . '.' . $ext_img;
        if (!$dry) {
            file_put_contents($dir_destino . $fichero, $data);
        }
        $rescatada++;
        $resultado = 'rescatada';
    } else {
        // Placeholder con iniciales
        $color = $colores[crc32($clave) % count($colores)];
        $fichero = $slug . '-placeholder.svg';
        if (!$dry) {
            file_put_contents($dir_destino . $fichero, genera_svg_placeholder($nombre, $color));
        }
        $placeholder++;
        $resultado = 'placeholder';
    }

    $url_local = 'https://www.codigoamigo.com/img/panel_marcas/ext/' . $fichero;
    if (!$dry) {
        $col->updateOne(['_id' => $m['_id']], ['$set' => ['imagen' => $url_local]]);
    }
    $log[] = ['marca' => $clave, 'antes' => $url, 'despues' => $url_local, 'resultado' => $resultado];
}

$log_file = __DIR__ . '/../../logs/placeholder_logos_' . date('Y-m-d') . '.json';
file_put_contents($log_file, json_encode(['dry_run' => $dry, 'rescatada' => $rescatada, 'placeholder' => $placeholder, 'detalle' => $log], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

echo "rescatada:$rescatada placeholder:$placeholder log:$log_file\n";
