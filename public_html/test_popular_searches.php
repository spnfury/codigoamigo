<?php
// Script de prueba para generar búsquedas de ejemplo
$doc_root = '/home/admin/web/codigoamigo.com/public_html';
include_once $doc_root . '/inc/includes.php';
include_once $doc_root . '/myphp/funciones_busqueda.php';

echo "<h1>Generando búsquedas de prueba...</h1>";

// Términos de ejemplo para diferentes períodos
$terms_today = ['booking', 'uber', 'amazon', 'netflix', 'spotify'];
$terms_week = ['airbnb', 'zalando', 'mediamarkt', 'pccomponentes', 'el corte ingles'];
$terms_month = ['zara', 'mango', 'pull and bear', 'bershka', 'stradivarius'];

// Generar búsquedas para hoy
echo "<h2>Generando búsquedas de hoy...</h2>";
foreach ($terms_today as $term) {
    for ($i = 0; $i < rand(5, 15); $i++) {
        record_search_term($term);
    }
    echo "✓ Generadas búsquedas para: $term<br>";
}

// Generar búsquedas para esta semana (simulando días anteriores)
echo "<h2>Generando búsquedas de esta semana...</h2>";
try {
    $collection = getCollectionLogs();
    foreach ($terms_week as $term) {
        for ($i = 0; $i < rand(10, 25); $i++) {
            $days_ago = rand(1, 6);
            $search_doc = array(
                'type' => 'search_term',
                'term' => strtolower(trim($term)),
                'date' => new MongoDB\BSON\UTCDateTime(),
                'timestamp' => time() - ($days_ago * 24 * 60 * 60),
                'day' => date('Y-m-d', strtotime("-{$days_ago} days")),
                'week' => date('Y-W'),
                'month' => date('Y-m'),
                'year' => date('Y')
            );
            $collection->insertOne($search_doc);
        }
        echo "✓ Generadas búsquedas para: $term<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Generar búsquedas para este mes (simulando semanas anteriores)
echo "<h2>Generando búsquedas de este mes...</h2>";
try {
    $collection = getCollectionLogs();
    foreach ($terms_month as $term) {
        for ($i = 0; $i < rand(15, 40); $i++) {
            $days_ago = rand(7, 28);
            $search_doc = array(
                'type' => 'search_term',
                'term' => strtolower(trim($term)),
                'date' => new MongoDB\BSON\UTCDateTime(),
                'timestamp' => time() - ($days_ago * 24 * 60 * 60),
                'day' => date('Y-m-d', strtotime("-{$days_ago} days")),
                'week' => date('Y-W', strtotime("-{$days_ago} days")),
                'month' => date('Y-m'),
                'year' => date('Y')
            );
            $collection->insertOne($search_doc);
        }
        echo "✓ Generadas búsquedas para: $term<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "<h2>✅ Búsquedas de prueba generadas exitosamente!</h2>";
echo "<p><a href='/'>Ver página principal</a></p>";

// Mostrar estadísticas
echo "<h2>Estadísticas actuales:</h2>";
$popular_by_period = get_popular_searches_all_periods(8);

echo "<h3>Búsquedas de hoy:</h3>";
echo "<pre>" . print_r($popular_by_period['today'], true) . "</pre>";

echo "<h3>Búsquedas de esta semana:</h3>";
echo "<pre>" . print_r($popular_by_period['week'], true) . "</pre>";

echo "<h3>Búsquedas de este mes:</h3>";
echo "<pre>" . print_r($popular_by_period['month'], true) . "</pre>";
?>
