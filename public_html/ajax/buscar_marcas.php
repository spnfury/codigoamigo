<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir archivos necesarios
$doc_root = '/home/admin/web/codigoamigo.com/public_html';
include_once $doc_root . '/inc/includes.php';

// Incluir sistema de logging si no está ya incluido
if (!function_exists('log_error')) {
    require_once $doc_root . '/inc/logger.php';
}
include_once $doc_root . '/myphp/funciones.php';

// Obtener término de búsqueda
if (isset($_SERVER['QUERY_STRING'])) {
    parse_str($_SERVER['QUERY_STRING'], $params);
    $query = $params['q'] ?? '';
} else {
    $query = $_REQUEST['q'] ?? '';
}


// Si no hay query o es muy corto, devolver marcas populares o todas las marcas
if (empty($query) || strlen($query) < 1) {
    try {
        // Conectar a la base de datos
        $db = createConnection();
        $collection = $db->selectCollection('marcas');
        
        // Obtener marcas populares o las primeras 100 marcas
        $marcas = $collection->find(
            [
                'nombre' => ['$ne' => 'false', '$ne' => false, '$ne' => '']
            ],
            [
                'limit' => 100,
                'sort' => ['nombre' => 1]
            ]
        )->toArray();
        
        $resultados = [];
        
        foreach ($marcas as $marca) {
            if (isset($marca['nombre']) && $marca['nombre'] !== 'false' && $marca['nombre'] !== false && !empty($marca['nombre'])) {
                $resultados[] = [
                    'id' => $marca['nombre_clave'] ?? $marca['nombre'],
                    'text' => $marca['nombre'],
                    'nombre' => $marca['nombre'],
                    'nombre_clave' => $marca['nombre_clave'] ?? normalizeMarcaName($marca['nombre']),
                    'imagen' => $marca['imagen'] ?? '/img/no_image.png',
                    'categoria' => $marca['categoria'] ?? 'General',
                    'beneficio_oficial' => isset($marca['beneficio_oficial']) && !empty($marca['beneficio_oficial']['cantidad']) ? [
                        'cantidad' => $marca['beneficio_oficial']['cantidad'],
                        'tipo' => $marca['beneficio_oficial']['tipo'] ?? 'euros',
                        'texto' => $marca['beneficio_oficial']['texto'] ?? ''
                    ] : null
                ];
            }
        }
        
        echo json_encode($resultados);
        exit;
    } catch (Exception $e) {
        log_error("Error obteniendo marcas", ['error' => $e->getMessage(), 'search_term' => $search_term]);
        echo json_encode([]);
        exit;
    }
}

try {
    // Conectar a la base de datos
    $db = createConnection();
    $collection = $db->selectCollection('marcas');
    
    // Buscar marcas que coincidan con el término de búsqueda (escapar chars regex)
    $marcas = $collection->find(
        [
            'nombre' => new MongoDB\BSON\Regex(preg_quote($query, '/'), 'i')
        ],
        [
            'limit' => 50,
            'sort' => ['nombre' => 1]
        ]
    )->toArray();
    
    $resultados = [];
    
    foreach ($marcas as $marca) {
        // Filtrar valores que sean "false" o vacíos
        if (isset($marca['nombre']) && $marca['nombre'] !== 'false' && $marca['nombre'] !== false && !empty($marca['nombre'])) {
            $resultados[] = [
                'id' => $marca['nombre_clave'] ?? $marca['nombre'],
                'text' => $marca['nombre'],
                'nombre' => $marca['nombre'],
                'nombre_clave' => $marca['nombre_clave'] ?? normalizeMarcaName($marca['nombre']),
                'imagen' => $marca['imagen'] ?? '/img/no_image.png',
                'categoria' => $marca['categoria'] ?? 'General',
                'beneficio_oficial' => isset($marca['beneficio_oficial']) && !empty($marca['beneficio_oficial']['cantidad']) ? [
                    'cantidad' => $marca['beneficio_oficial']['cantidad'],
                    'tipo' => $marca['beneficio_oficial']['tipo'] ?? 'euros',
                    'texto' => $marca['beneficio_oficial']['texto'] ?? ''
                ] : null
            ];
        }
    }
    
    // Si no hay resultados en la base de datos, agregar opción para crear nueva marca
    if (empty($resultados)) {
        $resultados[] = [
            'id' => 'nueva_marca_' . $query,
            'text' => 'Añadir nueva marca: ' . $query,
            'nombre' => $query,
            'imagen' => '/img/no_image.png',
            'categoria' => 'Nueva marca',
            'is_new' => true
        ];
    }
    
    echo json_encode($resultados);
    
} catch (Exception $e) {
    log_error("Error en búsqueda de marcas", ['error' => $e->getMessage(), 'query' => $query]);
    
    // En caso de error, devolver el término de búsqueda como nueva marca
    echo json_encode([
        [
            'id' => 'nueva_marca_' . $query,
            'text' => 'Añadir nueva marca: ' . $query,
            'nombre' => $query,
            'imagen' => '/img/no_image.png',
            'categoria' => 'Nueva marca',
            'is_new' => true
        ]
    ]);
}
?>
