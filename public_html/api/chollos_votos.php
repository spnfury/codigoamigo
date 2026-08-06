<?php
include_once __DIR__ . '/../inc/logger.php';

/**
 * API para el sistema de votación de chollos
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir funciones necesarias
include_once __DIR__ . '/../myphp/funciones_chollos_votos.php';
include_once __DIR__ . '/../myphp/funciones_chollos.php';

// Configurar headers para JSON
header('Content-Type: application/json');

$usuario_id = $_SESSION['user_id'] ?? null;
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'votar':
            // Debug logs
            log_info("Intento de voto. Usuario ID: " . ($usuario_id ?? 'NULL'));
            log_info("POST data: " . print_r($_POST, true));

            // Verificar autenticación
            if (!$usuario_id) {
                log_error("Error: Usuario no autenticado al votar");
                echo json_encode(['success' => false, 'error' => 'No autenticado']);
                exit;
            }

            // Votar un chollo
            $chollo_id = $_POST['chollo_id'] ?? '';
            $tipo = $_POST['tipo'] ?? ''; // 'positivo' o 'negativo'
            
            if (empty($chollo_id) || empty($tipo)) {
                log_error("Error: Parámetros faltantes. Chollo: $chollo_id, Tipo: $tipo");
                echo json_encode(['success' => false, 'error' => 'Parámetros faltantes']);
                exit;
            }
            
            $resultado = votarChollo($chollo_id, $usuario_id, $tipo);
            log_info("Resultado votarChollo: " . print_r($resultado, true));
            
            echo json_encode($resultado);
            break;
            
        case 'get_voto':
            // Verificar autenticación
            if (!$usuario_id) {
                echo json_encode(['success' => false, 'error' => 'No autenticado']);
                exit;
            }

            // Obtener el voto del usuario para un chollo
            $chollo_id = $_GET['chollo_id'] ?? '';
            
            if (empty($chollo_id)) {
                echo json_encode(['success' => false, 'error' => 'Chollo ID faltante']);
                exit;
            }
            
            $voto = obtenerVotoUsuario($chollo_id, $usuario_id);
            echo json_encode([
                'success' => true,
                'voto' => $voto
            ]);
            break;
            
        case 'get_estadisticas':
            // Obtener estadísticas de votos de un chollo (PÚBLICO)
            $chollo_id = $_GET['chollo_id'] ?? '';
            
            if (empty($chollo_id)) {
                echo json_encode(['success' => false, 'error' => 'Chollo ID faltante']);
                exit;
            }
            
            $stats = obtenerEstadisticasVotosChollo($chollo_id);
            echo json_encode([
                'success' => true,
                'estadisticas' => $stats
            ]);
            break;
            
        case 'get_mas_calientes':
            // Obtener los chollos más calientes (PÚBLICO)
            $limite = isset($_GET['limite']) ? intval($_GET['limite']) : 5;
            $chollos = obtenerChollosMasCalientes($limite);
            
            echo json_encode([
                'success' => true,
                'chollos' => $chollos
            ]);
            break;

        case 'get_mas_calientes_24h':
            // Obtener los chollos más calientes 24h (PÚBLICO)
            $limite = isset($_GET['limite']) ? intval($_GET['limite']) : 5;
            $chollos = obtenerChollosMasCalientes24h($limite);
            
            echo json_encode([
                'success' => true,
                'chollos' => $chollos
            ]);
            break;

        case 'get_mas_populares_24h':
            // Obtener los chollos más populares 24h (PÚBLICO)
            $limite = isset($_GET['limite']) ? intval($_GET['limite']) : 5;
            $chollos = obtenerChollosMasPopulares24h($limite);
            
            echo json_encode([
                'success' => true,
                'chollos' => $chollos
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
            break;
    }
} catch (Throwable $e) {
    log_error("Error en API de votos de chollos: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
