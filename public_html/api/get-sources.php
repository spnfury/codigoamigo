<?php
/**
 * API endpoint para obtener fuentes activas de Telegram
 * Usado por el script Python para sincronización automática
 */

// Headers para bypass de Cloudflare cuando se accede desde scripts internos
header('X-CF-Bypass: ENABLED');
header('X-API-Internal: true');
header('X-Requested-With: TelegramSyncScript');
header('Content-Type: application/json');

// Incluir archivos necesarios
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../myphp/funciones_chollos_fuentes.php';

// Incluir configuración
if (file_exists(__DIR__ . '/../config/ai_config.php')) {
    include_once __DIR__ . '/../config/ai_config.php';
}

// Validar token de autenticación (puede venir en header, query o POST body)
$token_secreto = defined('TELEGRAM_SYNC_TOKEN') ? TELEGRAM_SYNC_TOKEN : 'cambiar_token_secreto_aqui';

// Intentar obtener token de diferentes fuentes
$token_recibido = '';
if (isset($_SERVER['HTTP_X_TELEGRAM_SYNC_TOKEN'])) {
    $token_recibido = $_SERVER['HTTP_X_TELEGRAM_SYNC_TOKEN'];
} elseif (isset($_GET['token'])) {
    $token_recibido = $_GET['token'];
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $token_recibido = $data['token'] ?? '';
}

if (empty($token_recibido) || $token_recibido !== $token_secreto) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Token de autenticación inválido']);
    exit;
}

// Obtener tipo de fuente (opcional, por defecto 'telegram')
$tipo = $_GET['tipo'] ?? 'telegram';

// Obtener fuentes activas
$fuentes = obtenerFuentesActivas($tipo);

// Formatear respuesta
$response = [
    'success' => true,
    'sources' => []
];

foreach ($fuentes as $fuente) {
    $response['sources'][] = [
        'id' => $fuente['id'],
        'nombre' => $fuente['nombre'],
        'tipo' => $fuente['tipo'],
        'url' => $fuente['url'],
        'ultimo_mensaje_id' => $fuente['ultimo_mensaje_id'],
        'ultima_sincronizacion' => $fuente['ultima_sincronizacion'],
        'mensajes_procesados' => $fuente['mensajes_procesados'],
        'configuracion' => $fuente['configuracion'] ?? []
    ];
}

echo json_encode($response);


