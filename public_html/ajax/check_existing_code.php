<?php
// ajax/check_existing_code.php
// Script para verificar si un usuario ya tiene un código publicado para una marca

// Incluir configuración global
require_once __DIR__ . '/../inc/includes.php';

// Cabeceras JSON
header('Content-Type: application/json');

// Verificar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'error' => 'Usuario no identificado']);
    exit;
}

// Obtener parámetros
$marca = isset($_GET['marca']) ? $_GET['marca'] : (isset($_POST['marca']) ? $_POST['marca'] : '');

if (empty($marca)) {
    echo json_encode(['success' => false, 'error' => 'Marca no especificada']);
    exit;
}

try {
    // Normalizar nombre de marca
    $marca_normalizada = normalizeMarcaName($marca);
    
    // Obtener colección
    $collection = getCollectionCodigos();
    
    // Buscar código existente
    $codigo_existente = $collection->findOne([
        'marca' => $marca_normalizada,
        'id_usuario' => new MongoDB\BSON\ObjectId($_SESSION["user_id"])
    ]);
    
    if ($codigo_existente) {
        echo json_encode([
            'success' => true,
            'exists' => true,
            'code_id' => (string)$codigo_existente['_id'],
            'marca' => $codigo_existente['marca'],
            'codigo' => $codigo_existente['codigo'] ?? 'N/A'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'exists' => false
        ]);
    }

} catch (Exception $e) {
    error_log("Error check_existing_code: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error, intente de nuevo']);
}
?>
