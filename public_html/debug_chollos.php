<?php
/**
 * Script de depuración para verificar chollos
 */

include_once __DIR__ . '/myphp/funciones.php';
include_once __DIR__ . '/myphp/funciones_chollos.php';

header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Debug Chollos</title></head><body>';
echo '<h1>Debug de Chollos</h1>';

$collection = getCollectionChollos();

// Estadísticas generales
echo '<h2>Estadísticas Generales</h2>';
$total = $collection->countDocuments([]);
echo '<p>Total chollos: <strong>' . $total . '</strong></p>';

$activos = $collection->countDocuments(['estado' => 1]);
echo '<p>Chollos activos: <strong>' . $activos . '</strong></p>';

$telegram = $collection->countDocuments(['fuente' => 'telegram', 'estado' => 1]);
echo '<p>Chollos de Telegram activos: <strong>' . $telegram . '</strong></p>';

// Simular consulta del panel sin filtros
echo '<h2>Consulta del Panel (sin filtros)</h2>';
$filtros = [];
$chollos_cursor = $collection->find($filtros, [
    'sort' => ['fecha_creacion' => -1],
    'skip' => 0,
    'limit' => 50
]);

$chollos = [];
foreach ($chollos_cursor as $doc) {
    $chollos[] = $doc;
}

echo '<p>Chollos encontrados: <strong>' . count($chollos) . '</strong></p>';

// Mostrar primeros 10 chollos
echo '<h2>Primeros 10 Chollos</h2>';
echo '<table border="1" cellpadding="5" style="border-collapse: collapse; width: 100%;">';
echo '<tr><th>ID</th><th>Título</th><th>Estado</th><th>Fuente</th><th>Fecha</th></tr>';

foreach (array_slice($chollos, 0, 10) as $chollo) {
    echo '<tr>';
    echo '<td>' . substr((string)$chollo['_id'], 0, 10) . '...</td>';
    echo '<td>' . htmlspecialchars(substr($chollo['titulo'] ?? 'SIN TÍTULO', 0, 50)) . '...</td>';
    echo '<td>' . ($chollo['estado'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($chollo['fuente'] ?? 'N/A') . '</td>';
    if (isset($chollo['fecha_creacion']) && $chollo['fecha_creacion'] instanceof MongoDB\BSON\UTCDateTime) {
        echo '<td>' . $chollo['fecha_creacion']->toDateTime()->format('Y-m-d H:i:s') . '</td>';
    } else {
        echo '<td>N/A</td>';
    }
    echo '</tr>';
}

echo '</table>';

// Verificar chollos de la fuente específica
echo '<h2>Chollos de la Fuente Telegram</h2>';
try {
    $fuenteObjectId = new MongoDB\BSON\ObjectId('69289620669cd974a20a2da2');
    $chollos_fuente = $collection->find(['fuente_id' => $fuenteObjectId], [
        'sort' => ['fecha_creacion' => -1],
        'limit' => 10
    ])->toArray();
    
    echo '<p>Chollos de esta fuente: <strong>' . count($chollos_fuente) . '</strong></p>';
    
    if (count($chollos_fuente) > 0) {
        echo '<ul>';
        foreach ($chollos_fuente as $chollo) {
            echo '<li>';
            echo htmlspecialchars(substr($chollo['titulo'] ?? 'SIN TÍTULO', 0, 60));
            echo ' - Estado: ' . ($chollo['estado'] ?? 'N/A');
            echo ' - Fuente: ' . htmlspecialchars($chollo['fuente'] ?? 'N/A');
            echo '</li>';
        }
        echo '</ul>';
    }
} catch (Exception $e) {
    echo '<p style="color: red;">Error: ' . $e->getMessage() . '</p>';
}

echo '</body></html>';
?>

