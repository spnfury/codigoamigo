<?php
include_once __DIR__ . '/../inc/logger.php';

/**
 * API para el sistema de comentarios de chollos
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir funciones necesarias
include_once __DIR__ . '/../myphp/funciones_chollos_comentarios.php';

// Configurar headers para JSON
header('Content-Type: application/json');

$usuario_id = $_SESSION['user_id'] ?? null;
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'crear':
            // Verificar autenticación
            if (!$usuario_id) {
                echo json_encode(['success' => false, 'error' => 'No autenticado']);
                exit;
            }
            
            // Crear un nuevo comentario
            $chollo_id = $_POST['chollo_id'] ?? '';
            $comentario = $_POST['comentario'] ?? '';
            $padre_id = $_POST['padre_id'] ?? null;
            
            if (empty($chollo_id) || empty($comentario)) {
                echo json_encode(['success' => false, 'error' => 'Parámetros faltantes']);
                exit;
            }
            
            $datos = [
                'chollo_id' => $chollo_id,
                'usuario_id' => $usuario_id,
                'comentario' => $comentario
            ];
            
            if ($padre_id) {
                $datos['padre_id'] = $padre_id;
            }
            
            $resultado = crearComentario($datos);
            echo json_encode($resultado);
            break;
            
        case 'listar':
            // Listar comentarios de un chollo (PÚBLICO)
            $chollo_id = $_GET['chollo_id'] ?? '';
            $orden = $_GET['orden'] ?? 'antiguos'; // antiguos, nuevos, votados
            
            if (empty($chollo_id)) {
                echo json_encode(['success' => false, 'error' => 'Chollo ID faltante']);
                exit;
            }
            
            $comentarios = obtenerComentarios($chollo_id, $orden);
            echo json_encode([
                'success' => true,
                'comentarios' => $comentarios,
                'total' => count($comentarios)
            ]);
            break;
            
        case 'votar':
            // Verificar autenticación
            if (!$usuario_id) {
                echo json_encode(['success' => false, 'error' => 'No autenticado']);
                exit;
            }

            // Votar un comentario
            $comentario_id = $_POST['comentario_id'] ?? '';
            $tipo = $_POST['tipo'] ?? ''; // 'positivo' o 'negativo'
            
            if (empty($comentario_id) || empty($tipo)) {
                echo json_encode(['success' => false, 'error' => 'Parámetros faltantes']);
                exit;
            }
            
            $resultado = votarComentario($comentario_id, $usuario_id, $tipo);
            echo json_encode($resultado);
            break;
            
        case 'editar':
            // Verificar autenticación
            if (!$usuario_id) {
                echo json_encode(['success' => false, 'error' => 'No autenticado']);
                exit;
            }

            // Editar un comentario
            $comentario_id = $_POST['comentario_id'] ?? '';
            $texto = $_POST['comentario'] ?? '';
            
            if (empty($comentario_id) || empty($texto)) {
                echo json_encode(['success' => false, 'error' => 'Parámetros faltantes']);
                exit;
            }
            
            $resultado = editarComentario($comentario_id, $usuario_id, $texto);
            echo json_encode($resultado);
            break;
            
        case 'eliminar':
            // Verificar autenticación
            if (!$usuario_id) {
                echo json_encode(['success' => false, 'error' => 'No autenticado']);
                exit;
            }

            // Eliminar un comentario
            $comentario_id = $_POST['comentario_id'] ?? '';
            
            if (empty($comentario_id)) {
                echo json_encode(['success' => false, 'error' => 'Comentario ID faltante']);
                exit;
            }
            
            $resultado = eliminarComentario($comentario_id, $usuario_id);
            echo json_encode($resultado);
            break;
            
        case 'get_voto':
            // Obtener el voto del usuario para un comentario
            $comentario_id = $_GET['comentario_id'] ?? '';
            
            if (empty($comentario_id)) {
                echo json_encode(['success' => false, 'error' => 'Comentario ID faltante']);
                exit;
            }
            
            $voto = obtenerVotoUsuarioComentario($comentario_id, $usuario_id);
            echo json_encode([
                'success' => true,
                'voto' => $voto
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
            break;
    }
} catch (Throwable $e) {
    log_error("Error en API de comentarios de chollos: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
