<?php
// Cargar autoloader de Composer para MongoDB
require_once __DIR__ . '/../vendor/autoload.php';

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../myphp/funciones_codigo.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Llamar a la función interna que ya maneja la lógica, seguridad y respuesta JSON
borrar_codigo($_POST);
