<?php
require_once __DIR__ . '/myphp/funciones.php';
require_once __DIR__ . '/myphp/funciones_chollos.php';
require_once __DIR__ . '/myphp/funciones_chollos_amazon.php';

$chollo_id = '69709ca29012cfdc9f07dc75';

try {
    $db = createConnection();
    $collection = $db->selectCollection('chollos');
    $objectId = new MongoDB\BSON\ObjectId($chollo_id);
    $chollo = $collection->findOne(['_id' => $objectId]);

    if (!$chollo) {
        die("Chollo no encontrado\n");
    }

    $enlace = $chollo['enlace'];
    echo "Enlace actual: $enlace\n";

    // Intentar expandir de nuevo con el nuevo sistema
    $expandida = expandirAcortadorAmazon($enlace);
    echo "Enlace expandido: $expandida\n";

    if ($expandida === $enlace) {
        // Si falló la expansión automática, pero sabemos que es Amazon (ganga.ad),
        // podemos intentar forzar un tag o usar el "Self-Healing" manual.
        // Pero vamos a ver si el script de expansión funciona ahora.
    }

    $asin = extraerASIN($expandida);
    echo "ASIN extraído: " . ($asin ?? 'null') . "\n";

    $enlace_final = convertirEnlaceAmazon($expandida);
    echo "Enlace final con tag: $enlace_final\n";

    $updateData = [
        'enlace' => $enlace_final,
        'enlace_expandido' => $expandida,
        'asin' => $asin,
        'fecha_expansion' => new MongoDB\BSON\UTCDateTime()
    ];

    $collection->updateOne(['_id' => $objectId], ['$set' => $updateData]);
    echo "Chollo actualizado correctamente.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
