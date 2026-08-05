<?php
require_once __DIR__ . '/myphp/funciones.php';
require_once __DIR__ . '/myphp/funciones_chollos.php';

$id = '69709ca29012cfdc9f07dc75';

function getClicks($id) {
    $db = createConnection();
    $col = $db->selectCollection('chollos');
    $doc = $col->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
    return $doc['clicks'] ?? 0;
}

$initial_clicks = getClicks($id);
echo "Initial clicks: $initial_clicks\n";

// Simular vista de página (ahora por defecto no debería incrementar)
echo "Simulating page view (obtenerCholloPorId without params)...\n";
obtenerCholloPorId($id);
$after_view_clicks = getClicks($id);
echo "Clicks after view: $after_view_clicks\n";

if ($after_view_clicks == $initial_clicks) {
    echo "SUCCESS: Page view did NOT increment clicks.\n";
} else {
    echo "FAILURE: Page view incremented clicks!\n";
}

// Simular clic real (usando el acortador logic)
echo "Simulating real click (registrarClickChollo)...\n";
registrarClickChollo($id, ['source' => 'test_verification']);
$final_clicks = getClicks($id);
echo "Final clicks: $final_clicks\n";

if ($final_clicks == $after_view_clicks + 1) {
    echo "SUCCESS: Real click incremented clicks correctly.\n";
} else {
    echo "FAILURE: Real click DID NOT increment clicks correctly.\n";
}
