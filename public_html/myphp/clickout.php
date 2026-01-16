<?php
/**
 * Clickout Handler - Redirige a enlaces de afiliados usando el motor de reglas
 * Uso: clickout.php?id=[brand_id] o clickout.php?brand=[brand_name]
 */

require_once __DIR__ . '/../inc/conexion.php';
require_once __DIR__ . '/sistemas/afiliacion/AffiliationService.php';

use CodigoAmigo\Systems\Affiliation\AffiliationService;

$brand_id = $_GET['id'] ?? null;
$brand_name = $_GET['brand'] ?? null;

// Logica de resolución de marca si viene por nombre
if (!$brand_id && $brand_name) {
    // Buscar brand_id por nombre_clave
    $col_marcas = getCollectionMarcas();
    $marca = $col_marcas->findOne(['nombre_clave' => $brand_name]);
    if ($marca) {
        $brand_id = $marca['nombre_clave'];
    }
}

if (!$brand_id) {
    header("Location: /");
    exit;
}

$service = AffiliationService::getInstance();
$program = $service->getBestLinkForBrand($brand_id);

if ($program) {
    // 1. Registrar el click (analytics)
    // Asumiendo que program tiene network_id
    $net_id = isset($program['network_id']) ? (string)$program['network_id'] : 'unknown';
    $prog_id = $program['program_id'] ?? null;
    $service->recordClick($brand_id, $net_id, $prog_id);

    // 2. Construir la URL final
    $final_url = $program['url'] ?? '/';
    
    // Si queremos añadir subids, lo haríamos aquí
    // $final_url .= "&subid1=" . session_id();

    header("Location: " . $final_url);
} else {
    // Fallback: ir a la página de la marca o home
    // Si tenemos el nombre de la marca, podemos redirigir a su ficha
    if ($brand_name || $brand_id) {
        $target = $brand_name ? $brand_name : $brand_id;
        // Evitar loop infinito si clickout.php se llama desde la ficha
        // Pero clickout es para ir EXTERNO.
        header("Location: /marca/" . $target);
    } else {
        header("Location: /");
    }
}
exit;
