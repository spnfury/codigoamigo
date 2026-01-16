<?php
session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$usuario_id = $_SESSION['user_id'];

// Incluir funciones
include_once '../myphp/funciones.php';
include_once '../myphp/funciones_afiliados.php';
include_once '../myphp/funciones_usuario.php';

// Obtener el método de la petición
$metodo = $_POST['metodo'] ?? $_GET['metodo'] ?? '';

try {
    switch ($metodo) {
        case 'agregar_url':
            $url = $_POST['url'] ?? '';
            $nombre_plataforma = $_POST['nombre_plataforma'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            
            if (empty($url) || empty($nombre_plataforma)) {
                echo json_encode(['success' => false, 'error' => 'URL y nombre de plataforma son obligatorios']);
                exit;
            }
            
            $resultado = agregarUrlAfiliado($usuario_id, $url, $nombre_plataforma, $descripcion);
            
            // Asegurarse de que el resultado tenga url_id si fue exitoso
            if ($resultado['success'] && isset($resultado['id'])) {
                $resultado['url_id'] = $resultado['id'];
            }
            
            echo json_encode($resultado);
            break;
            
        case 'registrar_ingreso':
            $url_id = $_POST['url_id'] ?? '';
            $monto = $_POST['monto'] ?? '';
            $periodo = $_POST['periodo'] ?? '';
            $notas = $_POST['notas'] ?? '';
            
            if (empty($url_id) || empty($monto) || empty($periodo)) {
                echo json_encode(['success' => false, 'error' => 'URL, monto y período son obligatorios']);
                exit;
            }
            
            // Manejar subida de archivo si existe
            $captura_path = null;
            if (isset($_FILES['captura']) && $_FILES['captura']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/afiliados/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = pathinfo($_FILES['captura']['name'], PATHINFO_EXTENSION);
                $filename = 'captura_' . time() . '_' . $usuario_id . '.' . $file_extension;
                $file_path = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['captura']['tmp_name'], $file_path)) {
                    $captura_path = $file_path;
                }
            }
            
            $resultado = registrarIngresosAfiliado($url_id, $usuario_id, $monto, $periodo, $captura_path, $notas);
            echo json_encode($resultado);
            break;
            
        case 'obtener_ingresos':
            $url_id = $_GET['url_id'] ?? null;
            $resultado = obtenerIngresosAfiliadosUsuario($usuario_id, $url_id);
            echo json_encode($resultado);
            break;
            
        case 'eliminar_url':
            $url_id = $_POST['url_id'] ?? '';
            
            if (empty($url_id)) {
                echo json_encode(['success' => false, 'error' => 'ID de URL es obligatorio']);
                exit;
            }
            
            $resultado = eliminarUrlAfiliado($url_id, $usuario_id);
            echo json_encode($resultado);
            break;
            
        case 'obtener_codigos_sin_afiliado':
            $resultado = obtenerCodigosSinAfiliado($usuario_id);
            echo json_encode($resultado);
            break;
            
        case 'obtener_urls_usuario':
            $resultado = obtenerUrlsAfiliadosUsuario($usuario_id);
            echo json_encode($resultado);
            break;
            
        case 'obtener_afiliados_con_codigos':
            $resultado = obtenerEstadisticasAfiliadosConCodigos($usuario_id);
            echo json_encode($resultado);
            break;
            
        case 'asignar_codigo':
            $codigo_id = $_POST['codigo_id'] ?? '';
            $url_afiliado_id = $_POST['url_afiliado_id'] ?? '';
            
            if (empty($codigo_id) || empty($url_afiliado_id)) {
                echo json_encode(['success' => false, 'error' => 'ID de código y URL de afiliado son obligatorios']);
                exit;
            }
            
            $resultado = asignarCodigoAAfiliado($codigo_id, $url_afiliado_id, $usuario_id);
            echo json_encode($resultado);
            break;
            
        case 'desasignar_codigo':
            $codigo_id = $_POST['codigo_id'] ?? '';
            
            if (empty($codigo_id)) {
                echo json_encode(['success' => false, 'error' => 'ID de código es obligatorio']);
                exit;
            }
            
            $resultado = desasignarCodigoDeAfiliado($codigo_id, $usuario_id);
            echo json_encode($resultado);
            break;
            
        case 'obtener_codigos_asignados':
            $url_afiliado_id = $_GET['url_afiliado_id'] ?? '';
            
            if (empty($url_afiliado_id)) {
                echo json_encode(['success' => false, 'error' => 'ID de URL de afiliado es obligatorio']);
                exit;
            }
            
            $resultado = obtenerCodigosAsignadosAAfiliado($url_afiliado_id, $usuario_id);
            echo json_encode($resultado);
            break;
            
        case 'obtener_codigos_sin_afiliado_por_marca':
            $marca = $_GET['marca'] ?? '';
            
            if (empty($marca)) {
                echo json_encode(['success' => false, 'error' => 'Nombre de marca es obligatorio']);
                exit;
            }
            
            $resultado = obtenerCodigosSinAfiliadoPorMarca($usuario_id, $marca);
            echo json_encode($resultado);
            break;
            
        case 'asignar_multiples_codigos':
            $codigos_ids = $_POST['codigos_ids'] ?? [];
            $url_afiliado_id = $_POST['url_afiliado_id'] ?? '';
            
            if (empty($codigos_ids) || empty($url_afiliado_id)) {
                echo json_encode(['success' => false, 'error' => 'IDs de códigos y URL de afiliado son obligatorios']);
                exit;
            }
            
            // Normalizar "codigos_ids" aceptando JSON (desde JS: JSON.stringify) o CSV
            if (is_string($codigos_ids)) {
                $str = trim($codigos_ids);
                if (strlen($str) > 0 && $str[0] === '[') {
                    $decoded = json_decode($str, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $codigos_ids = $decoded;
                    } else {
                        $codigos_ids = array_filter(array_map('trim', explode(',', $str)));
                    }
                } else {
                    $codigos_ids = array_filter(array_map('trim', explode(',', $str)));
                }
            }
            
            // Asegurar que es array simple de strings
            if (!is_array($codigos_ids)) {
                $codigos_ids = [];
            }
            
            $resultado = asignarMultiplesCodigosAAfiliado($codigos_ids, $url_afiliado_id, $usuario_id);
            echo json_encode($resultado);
            break;
            
        case 'actualizar_url':
            $url_id = $_POST['url_id'] ?? '';
            $datos = [
                'url' => $_POST['url'] ?? '',
                'nombre_plataforma' => $_POST['nombre_plataforma'] ?? '',
                'descripcion' => $_POST['descripcion'] ?? ''
            ];
            
            if (empty($url_id)) {
                echo json_encode(['success' => false, 'error' => 'ID de URL es obligatorio']);
                exit;
            }
            
            $resultado = actualizarUrlAfiliado($url_id, $usuario_id, $datos);
            echo json_encode($resultado);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Método no válido']);
            break;
    }
} catch (Exception $e) {
    error_log("Error en afiliados_handler.php: " . $e->getMessage());
    error_log("Error en afiliados_handler.php - Trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>