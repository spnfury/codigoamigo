#!/usr/bin/env php
<?php
/**
 * Script para buscar chollos con precios incorrectos
 * Un precio es incorrecto si precio_original < precio_descuento
 */

require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_chollos.php';

echo "🔍 Buscando chollos con precios incorrectos...\n\n";

$collection = getCollectionChollos();
if (!$collection) {
    die("Error: No se pudo conectar a MongoDB\n");
}

// Buscar chollos donde precio_original < precio_descuento
$query = [
    '$and' => [
        ['precio_original' => ['$exists' => true, '$ne' => null]],
        ['precio_descuento' => ['$exists' => true, '$ne' => null]],
        ['$expr' => ['$lt' => ['$precio_original', '$precio_descuento']]]
    ]
];

$cursor = $collection->find($query);
$chollos_incorrectos = [];
foreach ($cursor as $doc) {
    $chollos_incorrectos[] = $doc;
}

$total = count($chollos_incorrectos);
echo "📊 Encontrados {$total} chollos con precios incorrectos\n\n";

if ($total == 0) {
    echo "✅ No hay chollos con precios incorrectos\n";
    exit(0);
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
foreach ($chollos_incorrectos as $doc) {
    $id = (string)$doc['_id'];
    $titulo = substr($doc['titulo'] ?? 'Sin título', 0, 60);
    $precio_original = $doc['precio_original'] ?? 0;
    $precio_descuento = $doc['precio_descuento'] ?? 0;
    
    echo "ID: {$id}\n";
    echo "Título: {$titulo}...\n";
    echo "Precio original: {$precio_original} €\n";
    echo "Precio descuento: {$precio_descuento} €\n";
    echo "URL: https://www.codigoamigo.com/chollos/general/{$id}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
}

echo "\n💡 Para corregir un chollo, ejecuta:\n";
echo "   php verificar_chollo_precio.php ID PRECIO_ORIGINAL PRECIO_DESCUENTO\n";
echo "   Ejemplo: php verificar_chollo_precio.php {$chollos_incorrectos[0]['_id']} 1158.99 856.99\n";





