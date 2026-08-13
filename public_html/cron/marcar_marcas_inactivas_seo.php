<?php
/**
 * Cron: marcar como inactiva_seo a marcas sin actividad reciente.
 *
 * Una marca se considera inactiva (a efectos SEO) si NO tiene ningún código
 * activo creado en los últimos 12 meses.
 *
 * Efecto:
 *   - Sitemap excluye la marca (funciones_sitemaps.php filtra inactiva_seo != true)
 *   - Página de la marca añade <meta robots="noindex,follow">
 *   - La marca sigue accesible vía URL directa, no se borra nada
 *
 * Si tras marcar inactiva alguien publica un código nuevo en esa marca,
 * el flag debería retirarse en la próxima ejecución del cron (revive).
 *
 * Ejecutar via Jenkins. Frecuencia recomendada: mensual.
 *
 * Uso:
 *   php marcar_marcas_inactivas_seo.php             → dry-run
 *   php marcar_marcas_inactivas_seo.php --apply
 */

date_default_timezone_set('Europe/Madrid');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones.php';

$apply = in_array('--apply', $argv ?? [], true);
echo "Modo: " . ($apply ? "APPLY" : "DRY-RUN") . "\n\n";

$db = createConnection();
$col_marcas = $db->selectCollection('marcas');
$col_codigos = $db->selectCollection('codigos');

$ts_12m = (new DateTime('-12 months'))->getTimestamp();
$min_oid = new MongoDB\BSON\ObjectId(str_pad(dechex($ts_12m), 8, '0', STR_PAD_LEFT) . str_repeat('0', 16));

// Marcas con código activo creado en últimos 12m → ACTIVAS
$activas = $col_codigos->distinct(
    'marca',
    [
        'estado' => ['$in' => [0, -1, 1]],
        '_id' => ['$gte' => $min_oid],
    ]
);
$set_activas = array_flip($activas);
echo "Marcas con código creado últimos 12m (ACTIVAS): " . count($activas) . "\n";

// Marcar inactiva_seo a las que no están en el set activas
$cursor = $col_marcas->find(
    ['estado' => 1],
    ['projection' => ['_id' => 1, 'nombre_clave' => 1, 'inactiva_seo' => 1]]
);

$marcadas_inactiva = 0;
$revivias = 0;
$ya_correctas = 0;
$skip_categoria_top = ['amazon', 'aliexpress', 'bookingcom']; // marcas evergreen, no tocar nunca

foreach ($cursor as $m) {
    $nc = $m['nombre_clave'] ?? '';
    if (empty($nc)) continue;
    if (in_array($nc, $skip_categoria_top, true)) continue;

    $es_activa = isset($set_activas[$nc]);
    $marcada_inactiva = !empty($m['inactiva_seo']);

    if (!$es_activa && !$marcada_inactiva) {
        // Debe marcarse inactiva
        if ($apply) {
            $col_marcas->updateOne(['_id' => $m['_id']], ['$set' => ['inactiva_seo' => true, 'inactiva_seo_fecha' => new MongoDB\BSON\UTCDateTime()]]);
        }
        $marcadas_inactiva++;
    } elseif ($es_activa && $marcada_inactiva) {
        // Debe revivirse (alguien publicó tras estar marcada)
        if ($apply) {
            $col_marcas->updateOne(['_id' => $m['_id']], ['$unset' => ['inactiva_seo' => '', 'inactiva_seo_fecha' => '']]);
        }
        $revivias++;
        echo "  REVIVE: $nc (publicaron tras estar marcada)\n";
    } else {
        $ya_correctas++;
    }
}

echo "\n=== RESUMEN ===\n";
echo "Marcadas como inactiva_seo: $marcadas_inactiva\n";
echo "Revividas (vuelven al sitemap): $revivias\n";
echo "Sin cambios: $ya_correctas\n";
echo ($apply ? "Aplicado.\n" : "Dry-run.\n");
