<?php
// Headers para CORS y JSON
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Cargar autoloader de Composer para MongoDB
require_once __DIR__ . '/../vendor/autoload.php';

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir funciones de usuario
include_once __DIR__ . '/../myphp/funciones_usuario.php';

if ($_REQUEST) {
    $datos = $_REQUEST;
    switch ($_REQUEST["metodo"]) {
        case "google_login":
            google_login($datos);
            break;
        case "login_user":
            login_user($datos);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Método no encontrado']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No se recibieron datos']);
}
?>

