<?php
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
            echo "Método no encontrado";
            break;
    }
} else {
    echo "No se recibieron datos";
}
?>