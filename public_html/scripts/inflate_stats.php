<?php
/**
 * Script de inflado automático de visitas, impresiones y votos/temperatura
 * Programado para correr cada 30 minutos vía Jenkins
 */

require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_chollos.php';
require_once __DIR__ . '/../myphp/funciones_codigo.php';

echo "Iniciando proceso de inflado de estadísticas [" . date('Y-m-d H:i:s') . "]\n";

try {
    $db = createConnection();
    if (!$db) {
        die("Error: No se pudo conectar a la base de datos.\n");
    }

    $fecha_hoy = date('Y-m-d');

    // 1. Inflar CHOLLOS
    echo "Procesando Chollos...\n";
    $collection_chollos = $db->selectCollection('chollos');
    $chollos_activos = $collection_chollos->find(['estado' => 1]);

    $count_chollos = 0;
    foreach ($chollos_activos as $chollo) {
        $chollo_id = $chollo['_id'];
        
        // Incrementar clicks: entre 1 y 5
        $inc_clicks = rand(1, 5);
        
        // Si tiene 0 clicks, asegurar un mínimo inicial más alto
        if (!isset($chollo['clicks']) || $chollo['clicks'] == 0) {
            $inc_clicks = rand(10, 30);
        }

        // Temperatura/Votos: 1 de cada 4 veces aumentar temperatura
        $inc_temp = (rand(1, 4) == 1) ? rand(1, 3) : 0;

        $collection_chollos->updateOne(
            ['_id' => $chollo_id],
            ['$inc' => [
                'clicks' => $inc_clicks,
                'temperatura' => $inc_temp,
                'votos_positivos' => $inc_temp
            ]]
        );
        $count_chollos++;
    }
    echo "✓ $count_chollos Chollos actualizados.\n";

    // 2. Inflar CÓDIGOS (anuncios)
    echo "Procesando Códigos...\n";
    $collection_codigos = $db->selectCollection('codigos');
    // estado 0 es activo para códigos
    $codigos_activos = $collection_codigos->find(['estado' => 0]);

    $count_codigos = 0;
    $campo_stats_clicks = 'stats_diarias.' . $fecha_hoy . '.clicks';
    $campo_stats_impresiones = 'stats_diarias.' . $fecha_hoy . '.impresiones';

    foreach ($codigos_activos as $codigo) {
        $codigo_id = $codigo['_id'];

        // Clicks: entre 1 y 3
        $inc_clicks = rand(1, 3);
        
        // Si tiene 0 clicks, asegurar mínimo
        if (!isset($codigo['totalclicks']) || $codigo['totalclicks'] == 0) {
            $inc_clicks = rand(5, 15);
        }

        // Impresiones: entre 10 y 50
        $inc_impresiones = rand(10, 50);
        if (!isset($codigo['total_impressions']) || $codigo['total_impressions'] == 0) {
            $inc_impresiones = rand(100, 300);
        }

        $collection_codigos->updateOne(
            ['_id' => $codigo_id],
            ['$inc' => [
                'totalclicks' => $inc_clicks,
                'total_impressions' => $inc_impresiones,
                $campo_stats_clicks => $inc_clicks,
                $campo_stats_impresiones => $inc_impresiones
            ]]
        );
        $count_codigos++;
    }
    echo "✓ $count_codigos Códigos actualizados.\n";

    echo "Proceso finalizado con éxito.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
