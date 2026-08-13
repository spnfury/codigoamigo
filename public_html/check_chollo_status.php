<?php
require_once __DIR__ . '/myphp/funciones.php';
require_once __DIR__ . '/myphp/funciones_chollos.php';

$chollo_id = '69709ca29012cfdc9f07dc75';

try {
    $db = createConnection();
    if (!$db) {
        echo "Error: No se pudo conectar a la base de datos.\n";
        exit;
    }

    $collection = $db->selectCollection('chollos');
    $objectId = new MongoDB\BSON\ObjectId($chollo_id);
    $doc = $collection->findOne(['_id' => $objectId]);

    if ($doc) {
        echo "Chollo encontrado:\n";
        echo "ID: " . (string)$doc['_id'] . "\n";
        echo "Enlace: " . ($doc['enlace'] ?? 'N/A') . "\n";
        echo "Enlace Expandido: " . ($doc['enlace_expandido'] ?? 'N/A') . "\n";
        echo "ASIN: " . ($doc['asin'] ?? 'N/A') . "\n";
        echo "Estado: " . ($doc['estado'] ?? 'N/A') . "\n";
        echo "Fuente: " . ($doc['fuente'] ?? 'N/A') . "\n";
        echo "Fecha Creación: " . (isset($doc['fecha_creacion']) ? $doc['fecha_creacion']->toDateTime()->format('Y-m-d H:i:s') : 'N/A') . "\n";
        
        $collection_historial = $db->selectCollection('chollos_clicks');
        $historial_count = $collection_historial->countDocuments(['chollo_id' => $objectId]);
        echo "Registros en chollos_clicks: " . $historial_count . "\n";
        
        $last_clicks = $collection_historial->find(
            ['chollo_id' => $objectId],
            ['sort' => ['fecha' => -1], 'limit' => 5]
        );
        
        echo "\nÚltimos 5 clicks en historial:\n";
        foreach ($last_clicks as $click) {
            echo "- Fecha: " . $click['fecha']->toDateTime()->format('Y-m-d H:i:s') . 
                 " | IP: " . ($click['ip'] ?? 'N/A') . 
                 " | Referer: " . ($click['referer'] ?? 'N/A') . "\n";
        }
    } else {
        echo "Chollo no encontrado con ID: " . $chollo_id . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
