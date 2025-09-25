<?php
// Simular el entorno web
$_SERVER['QUERY_STRING'] = 'q=bmw';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Cambiar al directorio correcto
chdir('/home/admin/web/codigoamigo.com/public_html');

// Incluir archivos necesarios
include_once '/home/admin/web/codigoamigo.com/public_html/inc/includes.php';
include_once '/home/admin/web/codigoamigo.com/public_html/myphp/funciones.php';

echo "=== PRUEBA DE BÚSQUEDA DE MARCAS ===\n\n";

// Simular la lógica del archivo buscar_marcas.php
if (isset($_SERVER['QUERY_STRING'])) {
    parse_str($_SERVER['QUERY_STRING'], $params);
    $query = $params['q'] ?? '';
} else {
    $query = $_REQUEST['q'] ?? '';
}

echo "Query: " . $query . "\n\n";

if (empty($query) || strlen($query) < 2) {
    echo "Query vacía o muy corta\n";
    exit;
}

try {
    // Conectar a la base de datos
    $db = createConnection();
    $collection = $db->selectCollection('marcas');
    
    echo "Conectado a la base de datos\n";
    
    // Buscar marcas que coincidan con el término de búsqueda
    $marcas = $collection->find(
        [
            'nombre' => new MongoDB\BSON\Regex($query, 'i')
        ],
        [
            'limit' => 10,
            'sort' => ['nombre' => 1]
        ]
    )->toArray();
    
    echo "Marcas encontradas: " . count($marcas) . "\n\n";
    
    $resultados = [];
    
    foreach ($marcas as $marca) {
        echo "Marca: " . $marca['nombre'] . "\n";
        echo "Imagen: " . ($marca['imagen'] ?? 'no_image') . "\n";
        echo "Categoría: " . ($marca['categoria'] ?? 'General') . "\n";
        echo "---\n";
        
        $resultados[] = [
            'nombre' => $marca['nombre'],
            'imagen' => $marca['imagen'] ?? '/img/no_image.png',
            'categoria' => $marca['categoria'] ?? 'General'
        ];
    }
    
    // Si no hay resultados en la base de datos, agregar opción para crear nueva marca
    if (empty($resultados)) {
        echo "No hay resultados, agregando nueva marca\n";
        $resultados[] = [
            'nombre' => $query,
            'imagen' => '/img/no_image.png',
            'categoria' => 'Nueva marca',
            'is_new' => true
        ];
    }
    
    echo "\nResultado JSON:\n";
    echo json_encode($resultados, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    
    // En caso de error, devolver el término de búsqueda como nueva marca
    $resultados = [
        [
            'nombre' => $query,
            'imagen' => '/img/no_image.png',
            'categoria' => 'Nueva marca',
            'is_new' => true
        ]
    ];
    
    echo "\nResultado de error:\n";
    echo json_encode($resultados, JSON_PRETTY_PRINT);
}

echo "\n=== FIN DE LA PRUEBA ===\n";
?>
