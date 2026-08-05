<?php
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_flash_promos.php';

$promos = obtenerFlashPromosPorMarca('imaginbank');

if (!empty($promos)) {
    echo "SUCCESS: Se encontraron " . count($promos) . " promociones para Imaginbank.\n";
    foreach ($promos as $p) {
        echo "- " . $p['titulo'] . " (" . $p['beneficio'] . ")\n";
    }
} else {
    echo "ERROR: No se encontraron promociones para Imaginbank.\n";
}
