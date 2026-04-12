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

// Verificar que el usuario esté logueado
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$user_id = $_SESSION["user_id"];
$action = $_REQUEST["action"] ?? '';

// Verificar permisos de administrador
$array_admins = [
    "58bd851da54e295b8b52f702",
    "5e78170e6b68e6519b7c5df2",
    "639899bc6321ee0d0e4010d2",
    "5c8a10ce2f55c86d6e707d82"
];
$es_admin = in_array($user_id, $array_admins);

try {
    switch ($action) {
        case 'get_conversaciones':
            if (!$es_admin) {
                echo json_encode(['success' => false, 'error' => 'Solo administradores']);
                exit;
            }
            
            $conversaciones = obtenerConversacionesAdmin();
            echo json_encode(['success' => true, 'conversaciones' => $conversaciones]);
            break;
            
        case 'get_conversaciones_usuario':
            // Permitir que tanto usuarios como admins usen esta acción
            
            // Si se solicita migración, ejecutarla primero
            if (isset($_REQUEST['migrate']) && $_REQUEST['migrate'] === '1') {
                $migrados = migrarMensajesSinConversacionId();
                log_info("get_conversaciones_usuario: Migración ejecutada, mensajes migrados: $migrados");
            }
            
            // Intentar obtener conversaciones
            $conversaciones = obtenerConversacionesUsuario($user_id);
            $count = count($conversaciones);
            
            // Si no hay conversaciones, verificar si hay mensajes sin conversacion_id y migrarlos automáticamente
            if ($count === 0 && !isset($_REQUEST['migrate'])) {
                $collection_mensajes = getCollectionMensajes();
                if ($collection_mensajes) {
                    $object_id = new MongoDB\BSON\ObjectId($user_id);
                    
                    // Verificar si hay mensajes para este usuario (con o sin conversacion_id)
                    $count_total_mensajes = $collection_mensajes->countDocuments([
                        '$or' => [
                            ['de_usuario_id' => $object_id],
                            ['para_usuario_id' => $object_id]
                        ]
                    ]);
                    
                    // Si hay mensajes pero no conversaciones, forzar migración
                    if ($count_total_mensajes > 0) {
                        $count_sin_id = $collection_mensajes->countDocuments([
                            '$and' => [
                                [
                                    '$or' => [
                                        ['de_usuario_id' => $object_id],
                                        ['para_usuario_id' => $object_id]
                                    ]
                                ],
                                [
                                    '$or' => [
                                        ['conversacion_id' => ['$exists' => false]],
                                        ['conversacion_id' => null],
                                        ['conversacion_id' => '']
                                    ]
                                ]
                            ]
                        ]);
                        
                        // Si hay mensajes sin conversacion_id, migrarlos automáticamente
                        if ($count_sin_id > 0) {
                            $migrados = migrarMensajesSinConversacionId();
                            log_info("get_conversaciones_usuario: Migración automática ejecutada, mensajes migrados: $migrados");
                            // Intentar obtener conversaciones nuevamente después de la migración
                            $conversaciones = obtenerConversacionesUsuario($user_id);
                            $count = count($conversaciones);
                        } else {
                            // Si hay mensajes pero todos tienen conversacion_id, puede ser un problema de agregación
                            log_warning("get_conversaciones_usuario: Hay $count_total_mensajes mensajes pero 0 conversaciones. Posible problema en agregación.");
                        }
                    }
                }
            }
            
            log_info("get_conversaciones_usuario: user_id=$user_id, count=$count");
            
            // Para debugging: incluir información adicional siempre si no hay conversaciones o si se solicita debug
            $debug_info = [];
            $should_debug = ($count === 0 || isset($_REQUEST['debug']) && $_REQUEST['debug'] === '1');
            if ($should_debug) {
                $collection_mensajes = getCollectionMensajes();
                $object_id = new MongoDB\BSON\ObjectId($user_id);
                
                // Contar mensajes directamente
                $count_directo = $collection_mensajes->countDocuments([
                    '$or' => [
                        ['de_usuario_id' => $object_id],
                        ['para_usuario_id' => $object_id]
                    ]
                ]);
                
                // Contar mensajes sin conversacion_id
                $count_sin_conversacion_id = $collection_mensajes->countDocuments([
                    '$and' => [
                        [
                            '$or' => [
                                ['de_usuario_id' => $object_id],
                                ['para_usuario_id' => $object_id]
                            ]
                        ],
                        [
                            '$or' => [
                                ['conversacion_id' => ['$exists' => false]],
                                ['conversacion_id' => null]
                            ]
                        ]
                    ]
                ]);
                
                // Obtener algunos mensajes de ejemplo
                $mensajes_ejemplo = $collection_mensajes->find([
                    '$or' => [
                        ['de_usuario_id' => $object_id],
                        ['para_usuario_id' => $object_id]
                    ]
                ], ['limit' => 5]);
                
                $ejemplos = [];
                $conversacion_ids_unicos = [];
                foreach ($mensajes_ejemplo as $msg) {
                    $conv_id = isset($msg['conversacion_id']) ? (is_string($msg['conversacion_id']) ? $msg['conversacion_id'] : (string)$msg['conversacion_id']) : 'NO TIENE';
                    if ($conv_id !== 'NO TIENE' && !in_array($conv_id, $conversacion_ids_unicos)) {
                        $conversacion_ids_unicos[] = $conv_id;
                    }
                    $ejemplos[] = [
                        '_id' => (string)$msg['_id'],
                        'de_usuario_id' => (string)$msg['de_usuario_id'],
                        'para_usuario_id' => (string)$msg['para_usuario_id'],
                        'conversacion_id' => $conv_id,
                        'tipo_conversacion_id' => isset($msg['conversacion_id']) ? gettype($msg['conversacion_id']) : 'NO EXISTE',
                        'mensaje' => substr($msg['mensaje'] ?? '', 0, 50),
                        'fecha' => isset($msg['fecha']) ? (is_object($msg['fecha']) ? 'UTCDateTime' : gettype($msg['fecha'])) : 'NO TIENE'
                    ];
                }
                
                $debug_info['conversacion_ids_unicos'] = $conversacion_ids_unicos;
                
                // Probar la agregación directamente
                $pipeline_test = [
                    [
                        '$match' => [
                            '$or' => [
                                ['de_usuario_id' => $object_id],
                                ['para_usuario_id' => $object_id]
                            ]
                        ]
                    ],
                    [
                        '$group' => [
                            '_id' => '$conversacion_id',
                            'count' => ['$sum' => 1],
                            'ultimo_mensaje' => ['$max' => '$fecha']
                        ]
                    ]
                ];
                
                $test_aggregation = $collection_mensajes->aggregate($pipeline_test);
                $test_results = iterator_to_array($test_aggregation);
                
                $debug_info['user_id'] = $user_id;
                $debug_info['count'] = $count;
                $debug_info['count_directo_mensajes'] = $count_directo;
                $debug_info['count_sin_conversacion_id'] = $count_sin_conversacion_id;
                $debug_info['mensajes_ejemplo'] = $ejemplos;
                $debug_info['test_aggregation_count'] = count($test_results);
                $debug_info['test_aggregation_sample'] = count($test_results) > 0 ? $test_results[0] : null;
                $debug_info['conversaciones_sample'] = $count > 0 ? $conversaciones[0] : null;
                
                // Si hay mensajes sin conversacion_id, sugerir migración
                if ($count_sin_conversacion_id > 0) {
                    $debug_info['needs_migration'] = true;
                    $debug_info['migration_hint'] = "Ejecuta con ?migrate=1 para migrar mensajes antiguos";
                }
            }
            
            $response = [
                'success' => true, 
                'conversaciones' => $conversaciones
            ];
            
            // Incluir debug info si está disponible
            if (!empty($debug_info)) {
                $response['debug'] = $debug_info;
            }
            
            // Si no hay conversaciones, incluir información básica de debug
            if ($count === 0 && empty($debug_info)) {
                $response['debug'] = [
                    'message' => 'No se encontraron conversaciones',
                    'user_id' => $user_id,
                    'hint' => 'Añade ?debug=1 a la URL para más información'
                ];
            }
            
            echo json_encode($response);
            break;
            
        case 'get_mensajes':
            $conversacion_id = $_REQUEST["conversacion_id"] ?? '';
            if (empty($conversacion_id)) {
                echo json_encode(['success' => false, 'error' => 'conversacion_id requerido']);
                exit;
            }
            
            // Verificar que el usuario tiene acceso a esta conversación
            if (!$es_admin) {
                // Para usuarios normales, verificar que participan en la conversación
                $mensajes_temp = obtenerMensajesConversacion($conversacion_id, null);
                $tiene_acceso = false;
                foreach ($mensajes_temp as $msg) {
                    if ((string)$msg['de_usuario_id'] === $user_id || (string)$msg['para_usuario_id'] === $user_id) {
                        $tiene_acceso = true;
                        break;
                    }
                }
                
                if (!$tiene_acceso) {
                    echo json_encode(['success' => false, 'error' => 'Sin acceso a esta conversación']);
                    exit;
                }
            }
            
            $ultimo_id = $_REQUEST["ultimo_id"] ?? null;
            $mensajes = obtenerMensajesConversacion($conversacion_id, $ultimo_id);
            
            // Marcar como leídos si es el usuario que recibe los mensajes
            if (!$ultimo_id) {
                marcarMensajesComoLeidos($conversacion_id, $user_id);
            }
            
            echo json_encode(['success' => true, 'mensajes' => $mensajes]);
            break;
            
        case 'get_nuevos_mensajes':
            $conversacion_id = $_REQUEST["conversacion_id"] ?? '';
            $ultimo_id = $_REQUEST["ultimo_id"] ?? null;
            
            if (empty($conversacion_id) || empty($ultimo_id)) {
                echo json_encode(['success' => false, 'error' => 'conversacion_id y ultimo_id requeridos']);
                exit;
            }
            
            // Verificar acceso
            if (!$es_admin) {
                $mensajes_temp = obtenerMensajesConversacion($conversacion_id, null);
                $tiene_acceso = false;
                foreach ($mensajes_temp as $msg) {
                    if ((string)$msg['de_usuario_id'] === $user_id || (string)$msg['para_usuario_id'] === $user_id) {
                        $tiene_acceso = true;
                        break;
                    }
                }
                
                if (!$tiene_acceso) {
                    echo json_encode(['success' => false, 'error' => 'Sin acceso']);
                    exit;
                }
            }
            
            $mensajes = obtenerMensajesConversacion($conversacion_id, $ultimo_id);
            
            // Marcar como leídos los nuevos
            foreach ($mensajes as $msg) {
                if ((string)$msg['para_usuario_id'] === $user_id && !$msg['leido']) {
                    marcarMensajesComoLeidos($conversacion_id, $user_id);
                    break;
                }
            }
            
            echo json_encode(['success' => true, 'mensajes' => $mensajes]);
            break;
            
        case 'enviar_mensaje':
            $para_usuario_id = $_REQUEST["para_usuario_id"] ?? '';
            $mensaje_texto = $_REQUEST["mensaje"] ?? '';
            
            if (empty($para_usuario_id) || empty($mensaje_texto)) {
                echo json_encode(['success' => false, 'error' => 'para_usuario_id y mensaje requeridos']);
                exit;
            }
            
            // Sanitizar mensaje
            $mensaje_texto = htmlspecialchars(trim($mensaje_texto), ENT_QUOTES, 'UTF-8');
            if (strlen($mensaje_texto) > 2000) {
                $mensaje_texto = substr($mensaje_texto, 0, 2000);
            }
            
            if (isset($_REQUEST['solo_obtener_id']) && $_REQUEST['solo_obtener_id'] === 'true') {
                 // Si solo queremos ID, no requerimos mensaje
                 $mensaje_texto = '';
            } else {
                if (empty($mensaje_texto)) {
                    echo json_encode(['success' => false, 'error' => 'Mensaje vacío']);
                    exit;
                }
            }
            
            // Validar que para_usuario_id sea ObjectId válido
            if (!preg_match('/^[a-f\d]{24}$/i', $para_usuario_id)) {
                echo json_encode(['success' => false, 'error' => 'ID de usuario inválido']);
                exit;
            }
            
            if (isset($_REQUEST['solo_obtener_id']) && $_REQUEST['solo_obtener_id'] === 'true') {
                 $conv_id = crearConversacionId($user_id, $para_usuario_id);
                 echo json_encode([
                    'success' => true,
                    'mensaje_id' => null,
                    'conversacion_id' => $conv_id
                 ]);
                 exit;
            }

            $mensaje_id = enviarMensaje($user_id, $para_usuario_id, $mensaje_texto, $es_admin);
            
            if ($mensaje_id) {
                echo json_encode([
                    'success' => true,
                    'mensaje_id' => (string)$mensaje_id,
                    'conversacion_id' => crearConversacionId($user_id, $para_usuario_id)
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error al enviar mensaje']);
            }
            break;
            
        case 'marcar_leido':
            $conversacion_id = $_REQUEST["conversacion_id"] ?? '';
            
            if (empty($conversacion_id)) {
                echo json_encode(['success' => false, 'error' => 'conversacion_id requerido']);
                exit;
            }
            
            $resultado = marcarMensajesComoLeidos($conversacion_id, $user_id);
            echo json_encode(['success' => $resultado]);
            break;
            
        case 'buscar_usuarios':
            $busqueda = $_REQUEST["busqueda"] ?? '';
            
            if (empty($busqueda) || strlen($busqueda) < 2) {
                echo json_encode(['success' => true, 'usuarios' => []]);
                exit;
            }
            
            // Buscar usuarios por username o email
            $collection_usuarios = getCollectionUsuarios();
            if (!$collection_usuarios) {
                echo json_encode(['success' => false, 'error' => 'Error de conexión']);
                exit;
            }
            
            try {
                $filtros = [
                    '$or' => [
                        ['username' => ['$regex' => $busqueda, '$options' => 'i']],
                        ['mail' => ['$regex' => $busqueda, '$options' => 'i']]
                    ],
                    'estado' => 1, // Solo usuarios activos
                    '_id' => ['$ne' => new MongoDB\BSON\ObjectId($user_id)] // Excluir al usuario actual
                ];
                
                $usuarios = $collection_usuarios->find(
                    $filtros,
                    ['limit' => 20, 'sort' => ['username' => 1]]
                );
                
                $resultado = [];
                foreach ($usuarios as $usuario) {
                    $resultado[] = [
                        '_id' => (string)$usuario['_id'],
                        'username' => $usuario['username'] ?? $usuario['mail'] ?? 'Usuario',
                        'mail' => $usuario['mail'] ?? '',
                        'img' => $usuario['img'] ?? ''
                    ];
                }
                
                echo json_encode(['success' => true, 'usuarios' => $resultado]);
            } catch (Throwable $e) {
                error_log("Error al buscar usuarios: " . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Error al buscar usuarios']);
            }
            break;
        
        case 'get_usuario_chat':
            $usuario_chat_id = $_REQUEST['usuario_id'] ?? '';
            
            if (empty($usuario_chat_id) || !preg_match('/^[a-f\\d]{24}$/i', $usuario_chat_id)) {
                echo json_encode(['success' => false, 'error' => 'usuario_id inválido']);
                exit;
            }

            try {
                $usuario_obj = get_object_user('_id', new MongoDB\BSON\ObjectId($usuario_chat_id));
            } catch (Throwable $e) {
                error_log("Error al obtener usuario para chat: " . $e->getMessage());
                $usuario_obj = null;
            }

            if (!$usuario_obj) {
                echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
                exit;
            }

            // Evitar que los usuarios se auto envíen mensajes
            if ((string)$usuario_obj['_id'] === $user_id) {
                echo json_encode(['success' => false, 'error' => 'No puedes iniciar una conversación contigo mismo']);
                exit;
            }

            $es_admin_destino = in_array((string)$usuario_obj['_id'], $array_admins);
            
            // Obtener estadísticas del usuario
            $codigos_count = 0;
            $fecha_registro = null;
            $tiempo_miembro = '';
            
            try {
                // Contar códigos del usuario
                $collection_codigos = getCollectionCodigos();
                if ($collection_codigos) {
                    $codigos_count = $collection_codigos->countDocuments([
                        'id_usuario' => new MongoDB\BSON\ObjectId($usuario_chat_id),
                        'estado' => 0
                    ]);
                }
                
                // Obtener fecha de registro
                if (isset($usuario_obj['fecha']) && $usuario_obj['fecha'] instanceof MongoDB\BSON\UTCDateTime) {
                    $fecha_registro = $usuario_obj['fecha']->toDateTime()->format('d/m/Y');
                    
                    // Calcular tiempo como miembro
                    $now = new DateTime();
                    $registro = $usuario_obj['fecha']->toDateTime();
                    $diff = $now->diff($registro);
                    
                    if ($diff->y > 0) {
                        $tiempo_miembro = $diff->y . ' año' . ($diff->y > 1 ? 's' : '');
                    } elseif ($diff->m > 0) {
                        $tiempo_miembro = $diff->m . ' mes' . ($diff->m > 1 ? 'es' : '');
                    } else {
                        $tiempo_miembro = $diff->d . ' día' . ($diff->d > 1 ? 's' : '');
                    }
                } elseif (isset($usuario_obj['_id'])) {
                    // Fallback: usar ObjectId timestamp si no hay fecha explícita
                    $timestamp = substr((string)$usuario_obj['_id'], 0, 8);
                    $fecha_registro_dt = new DateTime('@' . hexdec($timestamp));
                    $fecha_registro = $fecha_registro_dt->format('d/m/Y');
                    
                    $now = new DateTime();
                    $diff = $now->diff($fecha_registro_dt);
                    
                    if ($diff->y > 0) {
                        $tiempo_miembro = $diff->y . ' año' . ($diff->y > 1 ? 's' : '');
                    } elseif ($diff->m > 0) {
                        $tiempo_miembro = $diff->m . ' mes' . ($diff->m > 1 ? 'es' : '');
                    } else {
                        $tiempo_miembro = $diff->d . ' día' . ($diff->d > 1 ? 's' : '');
                    }
                }
            } catch (Throwable $e) {
                error_log("Error al obtener estadísticas de usuario: " . $e->getMessage());
            }
            
            $respuesta_usuario = [
                '_id' => (string)$usuario_obj['_id'],
                'username' => $usuario_obj['username'] ?? $usuario_obj['mail'] ?? 'Usuario',
                'mail' => $usuario_obj['mail'] ?? '',
                'img' => $usuario_obj['img'] ?? '',
                'profile_url' => link_usuario($usuario_obj['username'] ?? ($usuario_obj['mail'] ?? 'usuario'), (string)$usuario_obj['_id']),
                'es_admin' => $es_admin_destino,
                'codigos_count' => $codigos_count,
                'fecha_registro' => $fecha_registro,
                'tiempo_miembro' => $tiempo_miembro
            ];

            echo json_encode(['success' => true, 'usuario' => $respuesta_usuario]);
            break;
            
        case 'get_websocket_token':
            // Generar token temporal para autenticación WebSocket
            // El token expira en 1 hora
            $token = bin2hex(random_bytes(32));
            $expires = time() + 3600;
            
            // Guardar token en sesión (o en Redis/Memcached para producción)
            if (!isset($_SESSION['ws_tokens'])) {
                $_SESSION['ws_tokens'] = [];
            }
            $_SESSION['ws_tokens'][$token] = [
                'user_id' => $user_id,
                'expires' => $expires
            ];
            
            // Limpiar tokens expirados
            foreach ($_SESSION['ws_tokens'] as $t => $data) {
                if ($data['expires'] < time()) {
                    unset($_SESSION['ws_tokens'][$t]);
                }
            }
            
            // Determinar URL del WebSocket según el entorno
            $ws_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $ws_port = '2096';
            $ws_protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'wss' : 'ws';
            $ws_url = $ws_protocol . '://' . $ws_host . ':' . $ws_port . '/chat';
            
            echo json_encode([
                'success' => true,
                'token' => $token,
                'ws_url' => $ws_url
            ]);
            break;
            
        case 'search_mensajes':
            $conversacion_id = $_REQUEST['conversacion_id'] ?? '';
            $query = $_REQUEST['query'] ?? '';
            
            if (empty($conversacion_id) || empty($query)) {
                echo json_encode(['success' => false, 'error' => 'conversacion_id y query requeridos']);
                exit;
            }
            
            // Verificar acceso
            if (!$es_admin) {
                $mensajes_temp = obtenerMensajesConversacion($conversacion_id, null);
                $tiene_acceso = false;
                foreach ($mensajes_temp as $msg) {
                    if ((string)$msg['de_usuario_id'] === $user_id || (string)$msg['para_usuario_id'] === $user_id) {
                        $tiene_acceso = true;
                        break;
                    }
                }
                
                if (!$tiene_acceso) {
                    echo json_encode(['success' => false, 'error' => 'Sin acceso']);
                    exit;
                }
            }
            
            $resultados = buscarMensajesEnConversacion($conversacion_id, $query);
            echo json_encode(['success' => true, 'resultados' => $resultados]);
            break;
            
        case 'enviar_masivo':
            $destinatarios = $_REQUEST['destinatarios'] ?? []; // Array of IDs or JSON string
            $mensaje = $_REQUEST['mensaje'] ?? '';
            
            // Si destinatarios viene como string JSON, decodificar
            if (is_string($destinatarios)) {
                $destinatarios = json_decode($destinatarios, true);
            }
            
            if (empty($destinatarios) || !is_array($destinatarios)) {
                echo json_encode(['success' => false, 'error' => 'Destinatarios inválidos']);
                exit;
            }
            
            if (empty($mensaje)) {
                echo json_encode(['success' => false, 'error' => 'Mensaje vacío']);
                exit;
            }
            
            // Check VIP
            if (!function_exists('es_usuario_vip')) { include_once __DIR__ . '/../myphp/funciones_usuario.php'; }
            $es_vip = es_usuario_vip($user_id);
            
            if (!$es_vip && !$es_admin) {
                echo json_encode(['success' => false, 'error' => 'Funcionalidad exclusiva para usuarios VIP']);
                exit;
            }
            
            $stats = enviarMensajeMasivo($user_id, $destinatarios, $mensaje);
            echo json_encode(['success' => true, 'stats' => $stats]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    error_log("Error en chat_api.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
?>

