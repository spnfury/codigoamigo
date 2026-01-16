<?php
require_once __DIR__ . '/inc/conexion.php';
require_once __DIR__ . '/myphp/_super_landing_functions.php';

// 1. Get Landing Config
$landing = get_super_landing_by_slug('ing-cuenta-nomina');

// 2. Get All Codes using the function
$codes = get_super_landing_codes($landing, 100);

echo "Total Codes: " . count($codes) . "\n";
echo "--- Super Codes ---\n";
foreach ($codes as $c) {
    if (isset($c['tipo_destacado']) && $c['tipo_destacado'] === 'super') {
        echo "ID: " . $c['_id'] . " | User: " . ($c['usuario_creador'] ?? 'unknown') . "\n";
        echo "   Tipo: " . $c['tipo_destacado'] . "\n";
        if (isset($c['super_destacado_expira'])) {
            $date = $c['super_destacado_expira']->toDateTime()->format('Y-m-d H:i:s');
            echo "   Expires: " . $date . "\n";
        } else {
            echo "   Expires: NOT SET\n";
        }
    }
}
