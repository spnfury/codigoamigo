<?php
/**
 * API endpoint para sincronización de mensajes de Telegram
 * Recibe datos del script Python y procesa los mensajes para crear chollos
 */

// Configurar límites de tiempo y memoria
set_time_limit(300); // 5 minutos
ini_set('memory_limit', '512M');

// Configurar manejo de errores
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Capturar errores fatales
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Error fatal: ' . $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
    }
});

header('Content-Type: application/json');

// Incluir archivos necesarios
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../myphp/funciones_chollos.php';
include_once __DIR__ . '/../myphp/funciones_chollos_fuentes.php';
include_once __DIR__ . '/../myphp/funciones_chollos_amazon.php';
include_once __DIR__ . '/../myphp/funciones_chollos_groq.php';
include_once __DIR__ . '/../myphp/telegram_chollos_bot.php';

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Leer datos JSON del body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Incluir configuración
if (file_exists(__DIR__ . '/../config/ai_config.php')) {
    include_once __DIR__ . '/../config/ai_config.php';
}

// Validar token de autenticación
$token_secreto = defined('TELEGRAM_SYNC_TOKEN') ? TELEGRAM_SYNC_TOKEN : 'cambiar_token_secreto_aqui';
$token_recibido = $data['token'] ?? $_POST['token'] ?? '';

if (empty($token_recibido) || $token_recibido !== $token_secreto) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Token de autenticación inválido']);
    exit;
}

// Validar datos requeridos
if (empty($data['fuente_id']) || empty($data['mensajes'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'fuente_id y mensajes son requeridos']);
    exit;
}

$fuente_id = $data['fuente_id'];
$mensajes = $data['mensajes'];

// Validar que la fuente existe y está activa
$fuente = obtenerFuentePorId($fuente_id);
if (!$fuente) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Fuente no encontrada']);
    exit;
}

if (!$fuente['activo']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Fuente inactiva']);
    exit;
}

// Opción para eliminar chollos anteriores de esta fuente antes de procesar
$eliminar_anteriores = $data['eliminar_anteriores'] ?? false;

if ($eliminar_anteriores) {
    try {
        $collection_chollos = getCollectionChollos();
        if ($collection_chollos) {
            try {
                $fuenteObjectId = new MongoDB\BSON\ObjectId($fuente_id);
                $resultado_eliminacion = $collection_chollos->deleteMany(['fuente_id' => $fuenteObjectId]);
                $eliminados = $resultado_eliminacion->getDeletedCount();
                error_log("Eliminados {$eliminados} chollos anteriores de la fuente {$fuente_id}");
            } catch (Exception $e) {
                error_log("Error al eliminar chollos anteriores: " . $e->getMessage());
            }
        }
    } catch (Throwable $e) {
        error_log("Error al eliminar chollos anteriores: " . $e->getMessage());
    }
}

$resultados = [
    'procesados' => 0,
    'creados' => 0,
    'duplicados' => 0,
    'errores' => 0,
    'eliminados_anteriores' => $eliminar_anteriores ? ($eliminados ?? 0) : 0,
    'errores_detalle' => []
];

$ultimo_mensaje_id = null;

// --- FASE 1: Extracción y Limpieza Local ---
$chollos_preparados = [];
foreach ($mensajes as $mensaje_data) {
    try {
        $resultados['procesados']++;
        $mensaje_id = $mensaje_data['id'] ?? null;
        $texto = $mensaje_data['texto'] ?? '';
        $imagen_url = $mensaje_data['imagen'] ?? '';
        $fecha = $mensaje_data['fecha'] ?? date('Y-m-d H:i:s');
        
        if (empty($texto)) continue;
        
        $info = extraerInfoChollo($texto);
        if (empty($info['titulo']) && empty($info['enlace'])) continue;
        
        if (!empty($imagen_url) && filter_var($imagen_url, FILTER_VALIDATE_URL)) {
            $info['imagen'] = $imagen_url;
        }
        
        
        // Procesar enlaces de Amazon correctamente con expansión y caché
        if (!empty($info['enlace']) && esEnlaceAmazon($info['enlace'])) {
            // 1. Intentar expandir shortener primero
            $enlace_expandido = expandirAcortadorAmazon($info['enlace']);
            
            if ($enlace_expandido !== $info['enlace'] && esEnlaceAmazon($enlace_expandido)) {
                // Expansión exitosa - guardar para caché posterior
                $info['enlace_expandido'] = $enlace_expandido;
                $info['fecha_expansion'] = new MongoDB\BSON\UTCDateTime();
                
                // Extraer ASIN del enlace expandido
                $asin_detectado = extraerASIN($enlace_expandido);
                if ($asin_detectado) {
                    $info['asin'] = $asin_detectado;
                    error_log("Telegram sync - ASIN extraído: $asin_detectado para: " . substr($info['titulo'] ?? '', 0, 50));
                } else {
                    error_log("Telegram sync - No se pudo extraer ASIN de enlace expandido: $enlace_expandido");
                }
            } else {
                // No se pudo expandir - intentar extraer ASIN del enlace original
                $asin_detectado = extraerASIN($info['enlace']);
                if ($asin_detectado) {
                    $info['asin'] = $asin_detectado;
                    error_log("Telegram sync - ASIN extraído directamente: $asin_detectado");
                } else {
                    error_log("Telegram sync - ADVERTENCIA: No se pudo expandir ni extraer ASIN de: " . $info['enlace']);
                }
            }
            
            // Asegurar que el enlace final tenga el tag de afiliado
            if (!empty($info['asin'])) {
                // Si tenemos ASIN, crear enlace limpio directo
                $info['enlace'] = "https://www.amazon.es/dp/" . $info['asin'] . "?tag=spnfuryy-21";
            } else {
                // Sin ASIN, al menos intentar agregar el tag al enlace existente
                $info['enlace'] = convertirEnlaceAmazon($info['enlace_expandido'] ?? $info['enlace']);
            }
        }


        $info['id_interno'] = "msg_" . ($mensaje_id ?? uniqid());
        $info['mensaje_original_id'] = $mensaje_id;
        $info['texto_para_ia'] = $texto;
        $info['fecha_inicio'] = $fecha;
        
        $chollos_preparados[] = $info;
    } catch (Throwable $e) {
        error_log("Error preparando mensaje: " . $e->getMessage());
    }
}

// --- FASE 2: Procesamiento por Lotes con Groq ---
$reescribir_automatico = $data['reescribir_automatico'] ?? true;
if ($reescribir_automatico && !empty($chollos_preparados)) {
    $batch_size = 5;
    $total_preparados = count($chollos_preparados);
    
    for ($i = 0; $i < $total_preparados; $i += $batch_size) {
        $lote = array_slice($chollos_preparados, $i, $batch_size);
        $res_lote = procesarPaqueteChollosConGroq($lote);
        
        // Mapear resultados del lote a los chollos preparados
        foreach ($chollos_preparados as &$chollo) {
            $id_int = $chollo['id_interno'];
            if (isset($res_lote[$id_int])) {
                $chollo['titulo'] = $res_lote[$id_int]['titulo'] ?? $chollo['titulo'];
                $chollo['descripcion'] = $res_lote[$id_int]['descripcion'] ?? $chollo['descripcion'];
                $chollo['categoria'] = $res_lote[$id_int]['categoria'] ?? ['general'];
                $chollo['texto_reescrito'] = true;
            } else {
                // Si este chollo estaba en este lote pero no vino en la respuesta, o si ya falló Groq
                // solo le ponemos categorías si aún no las tiene (por ser parte de un lote que falló)
                if (in_array($chollo, $lote, true) && !isset($chollo['categoria'])) {
                    $chollo['categoria'] = detectarCategoria($chollo['texto_para_ia']);
                }
            }
        }
        unset($chollo);
        
        // Delay mínimo para no saturar incluso con lotes
        if ($i + $batch_size < $total_preparados) sleep(2); 
    }
} else {
    // Si no hay IA, detectar categoría localmente
    foreach ($chollos_preparados as &$chollo) {
        if (!isset($chollo['categoria'])) {
            $chollo['categoria'] = detectarCategoria($chollo['texto_para_ia']);
        }
    }
}

// --- FASE 3: Guardado en Base de Datos ---
foreach ($chollos_preparados as $info) {
    try {
        $info['fuente'] = $fuente['tipo'];
        $info['fuente_id'] = $fuente_id;
        $info['estado'] = $data['estado_por_defecto'] ?? 1;
        
        $resultado_db = crearChollo($info);
        
        if ($resultado_db['success']) {
            $resultados['creados']++;
            if ($info['mensaje_original_id']) $ultimo_mensaje_id = $info['mensaje_original_id'];
        } elseif (isset($resultado_db['duplicado']) && $resultado_db['duplicado']) {
            $resultados['duplicados']++;
            if ($info['mensaje_original_id']) $ultimo_mensaje_id = $info['mensaje_original_id'];
        } else {
            $resultados['errores']++;
            $resultados['errores_detalle'][] = "ID {$info['mensaje_original_id']}: " . ($resultado_db['error'] ?? 'DB Error');
        }
    } catch (Throwable $e) {
        $resultados['errores']++;
        error_log("Error guardando chollo: " . $e->getMessage());
    }
}

// Actualizar última sincronización de la fuente
if ($resultados['procesados'] > 0) {
    actualizarUltimaSincronizacion(
        $fuente_id,
        $resultados['creados'],
        $ultimo_mensaje_id
    );
}

// Retornar resultados
echo json_encode([
    'success' => true,
    'resultados' => $resultados,
    'fuente' => $fuente['nombre'],
    'eliminados_anteriores' => $resultados['eliminados_anteriores'] ?? 0
]);
