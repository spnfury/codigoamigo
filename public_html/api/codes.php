<?php
// API para cargar códigos por defecto

// Configuración de errores
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 0);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

// Headers para JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir funciones necesarias
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../myphp/funciones_modern.php';

try {
    // Obtener parámetros
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20;
    
    // Validar parámetros
    if ($page < 1) {
        $page = 1;
    }
    
    // Calcular skip
    $skip = ($page - 1) * $limit;
    
    // Construir filtro para códigos normales
    $array_filtro = array("estado" => 0);
    $array_filtro = array_merge($array_filtro, array("destacado" => 0));
    $array_filtro = array_merge($array_filtro, array("destacado_social" => array('$exists' => false)));
    
    // Configurar opciones
    $array_skip = array(
        "limit" => $limit,
        "skip" => $skip,
        "sort" => array('_id' => -1)
    );
    
    // Obtener códigos
    $resultado = get_all_listado_codigos_array($array_filtro, $array_skip);
    
    // Procesar resultados
    $codigos = isset($resultado["results"]) && is_array($resultado["results"]) ? $resultado["results"] : [];
    $total = isset($resultado["total_number"]) ? $resultado["total_number"] : 0;
    
    // Calcular información de paginación
    $total_pages = ceil($total / $limit);
    $start_page = max(1, $page - 2);
    $end_page = min($total_pages, $page + 2);
    
    // Preparar respuesta
    $response = array(
        'success' => true,
        'codes' => $codigos,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'pagination' => array(
            'currentPage' => $page,
            'totalPages' => $total_pages,
            'startPage' => $start_page,
            'endPage' => $end_page,
            'start' => $skip + 1,
            'end' => min($skip + $limit, $total),
            'total' => $total
        )
    );
    
    // Enviar respuesta
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Manejar errores
    http_response_code(400);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ), JSON_UNESCAPED_UNICODE);
}
?>
