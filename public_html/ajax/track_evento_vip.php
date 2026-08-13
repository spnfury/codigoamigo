<?php
/**
 * AJAX Endpoint: registrar evento de bloqueo VIP visto client-side
 * (ej. modal "Completar con IA" mostrado sin llegar a pedir nada al servidor).
 * Fire-and-forget: el frontend no espera ni actúa sobre la respuesta.
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit;
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$tipos_permitidos = ['modal_ia_bloqueada'];
$tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : '';

if (!in_array($tipo, $tipos_permitidos, true)) {
    echo json_encode(['success' => false]);
    exit;
}

$ok = registrar_evento_vip_bloqueo($_SESSION['user_id'], $tipo);
echo json_encode(['success' => $ok]);
