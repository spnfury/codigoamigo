<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones_chollos_groq.php';

// Verificar permisos (solo admin)
$array_codigos_acceso = [
    "58bd851da54e295b8b52f702", //thevega82@gmail.com
    "5e78170e6b68e6519b7c5df2", //edna
    "639899bc6321ee0d0e4010d2", //aron
    "5c8a10ce2f55c86d6e707d82"  //jose
];

if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]) || !in_array($_SESSION["user_id"], $array_codigos_acceso)) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// Obtener datos
$data = json_decode(file_get_contents('php://input'), true);
$keyword = $data['keyword'] ?? '';
$currentTitle = $data['current_title'] ?? '';

if (empty($keyword)) {
    echo json_encode(['success' => false, 'error' => 'Keyword vacía']);
    exit;
}

// Generar título optimizado
// Pasamos la keyword como texto original
$resultado = reescribirTextoGroq($keyword, 'seo_optimizer');

if ($resultado['success']) {
    echo json_encode([
        'success' => true, 
        'optimized_title' => $resultado['texto_reescrito']
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'error' => $resultado['error']
    ]);
}
