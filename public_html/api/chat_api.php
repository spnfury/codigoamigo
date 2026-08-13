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

// VIP gating: lectura/listado solo para VIPs y admins. Envío permitido para todos.
$es_vip_actual = $es_admin ? true : es_usuario_vip($user_id);
$acciones_solo_vip = [
    'get_conversaciones',
    'get_conversaciones_usuario',
    'get_mensajes',
    'get_nuevos_mensajes',
    'marcar_leido',
    'search_mensajes',
    'get_websocket_token',
    'get_interacciones_codigo',
    'archive_conversation',
    'unarchive_conversation',
    'pin_conversation',
    'unpin_conversation',
    'delete_conversation',
    'enviar_masivo'
];
if (!$es_vip_actual && in_array($action, $acciones_solo_vip, true)) {
    echo json_encode([
        'success' => false,
        'error' => 'Mensajería directa exclusiva para usuarios VIP. Hazte VIP para leer y responder mensajes.',
        'requiere_vip' => true,
        'cta_url' => '/public/mis_viewers.php'
    ]);
    exit;
}

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
            
            $tab = $_REQUEST['tab'] ?? 'inbox';
            $conversaciones = obtenerConversacionesUsuario($user_id, $tab);
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
                            $conversaciones = obtenerConversacionesUsuario($user_id, $tab);
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
            
            // Parámetros de contexto del código (opcionales)
            $codigo_id = $_REQUEST["codigo_id"] ?? '';
            $marca_slug = $_REQUEST["marca_slug"] ?? '';
            $beneficio = isset($_REQUEST["beneficio"]) ? (int)$_REQUEST["beneficio"] : 0;
            
            if (empty($para_usuario_id) || empty($mensaje_texto)) {
                echo json_encode(['success' => false, 'error' => 'para_usuario_id y mensaje requeridos']);
                exit;
            }
            
            // Sanitizar mensaje
            $mensaje_texto = trim($mensaje_texto);
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
                 $conv_id = crearConversacionId($user_id, $para_usuario_id, !empty($codigo_id) ? $codigo_id : null);
                 echo json_encode([
                    'success' => true,
                    'mensaje_id' => null,
                    'conversacion_id' => $conv_id
                 ]);
                 exit;
            }

            // Preparar contexto del código si existe
            $contexto = [];
            if (!empty($codigo_id) && preg_match('/^[a-f\d]{24}$/i', $codigo_id)) {
                $contexto = [
                    'codigo_id' => $codigo_id,
                    'marca_slug' => $marca_slug,
                    'beneficio' => $beneficio
                ];
            }

            // Validar que el usuario no envíe mensajes consecutivos sin respuesta del destinatario
            // (aplica a TODOS los usuarios, incluidos admins, para evitar spam)
            {
                // Obtener el ID de la conversación
                $conversacion_id_actual = crearConversacionId($user_id, $para_usuario_id, !empty($codigo_id) ? $codigo_id : null);
                
                $collection_mensajes = getCollectionMensajes();
                if ($collection_mensajes) {
                    // Verificar si el destinatario ya ha contestado alguna vez
                    $ha_contestado = $collection_mensajes->countDocuments([
                        'conversacion_id' => $conversacion_id_actual,
                        'de_usuario_id' => ['$in' => [(string)$para_usuario_id, new MongoDB\BSON\ObjectId($para_usuario_id)]]
                    ]);

                    // Si nunca ha contestado, se mantiene el límite de un solo mensaje esperando respuesta
                    if ($ha_contestado === 0) {
                        // Obtener el último mensaje de la conversación
                        $ultimo_mensaje = $collection_mensajes->findOne(
                            ['conversacion_id' => $conversacion_id_actual],
                            ['sort' => ['fecha' => -1], 'projection' => ['de_usuario_id' => 1]]
                        );

                        // Si el último mensaje fue enviado por el usuario actual, bloquear
                        if ($ultimo_mensaje && (string)$ultimo_mensaje['de_usuario_id'] === $user_id) {
                            echo json_encode(['success' => false, 'error' => 'Debes esperar a que el usuario te conteste por primera vez antes de enviarle otro mensaje.']);
                            exit;
                        }
                    }
                }
            }

            // Cap diario antispam para no-VIP: máximo 10 mensajes salientes por 24h
            // VIPs y admins exentos. Frena floods sin bloquear uso legítimo.
            if (!$es_vip_actual && !$es_admin) {
                $collection_mensajes_cap = getCollectionMensajes();
                if ($collection_mensajes_cap) {
                    $cap_no_vip = 10;
                    $cutoff_24h = new MongoDB\BSON\UTCDateTime((time() - 86400) * 1000);
                    $user_object_id = new MongoDB\BSON\ObjectId($user_id);
                    $msgs_ultimas_24h = $collection_mensajes_cap->countDocuments([
                        'de_usuario_id' => ['$in' => [$user_id, $user_object_id]],
                        'fecha' => ['$gte' => $cutoff_24h]
                    ]);
                    if ($msgs_ultimas_24h >= $cap_no_vip) {
                        log_info("chat cap_no_vip alcanzado", ['user_id' => $user_id, 'count_24h' => $msgs_ultimas_24h]);
                        echo json_encode([
                            'success' => false,
                            'error' => "Has alcanzado el límite de {$cap_no_vip} mensajes diarios. Hazte VIP para enviar mensajes sin límite y leer respuestas.",
                            'requiere_vip' => true,
                            'cta_url' => '/public/mis_viewers.php'
                        ]);
                        exit;
                    }
                }
            }

            $mensaje_id = enviarMensaje($user_id, $para_usuario_id, $mensaje_texto, $es_admin, $contexto);
            
            if ($mensaje_id) {
                // Si el mensaje se envió con éxito, marcar al viewer como contactado
                marcar_viewer_contactado($user_id, $para_usuario_id);
                
                echo json_encode([
                    'success' => true,
                    'mensaje_id' => (string)$mensaje_id,
                    'conversacion_id' => crearConversacionId($user_id, $para_usuario_id, !empty($codigo_id) ? $codigo_id : null)
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
                log_error("Error al buscar usuarios: " . $e->getMessage());
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
                log_error("Error al obtener usuario para chat: " . $e->getMessage());
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
                log_error("Error al obtener estadísticas de usuario: " . $e->getMessage());
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
            
        case 'get_interacciones_codigo':
            // Obtener contexto de la conversación: quién contactó a quién y por qué código
            $usuario_objetivo_id = $_REQUEST['usuario_id'] ?? '';
            $conversacion_id_ctx = $_REQUEST['conversacion_id'] ?? '';
            
            if (empty($usuario_objetivo_id) || !preg_match('/^[a-f\\d]{24}$/i', $usuario_objetivo_id)) {
                echo json_encode(['success' => false, 'error' => 'usuario_id inválido']);
                exit;
            }
            
            try {
                $resultado_ctx = [
                    'success' => true,
                    'contexto' => 'directo', // directo, yo_contacte, me_contactaron
                    'codigo_info' => null,
                    'soy_owner_codigo' => false,
                    'quien_inicio' => null, // ID del usuario que envió el primer mensaje
                    'interacciones' => [],  // Mantener retrocompatibilidad
                    'total' => 0,
                    'total_potencial' => 0
                ];
                
                // 1. Determinar si hay un código asociado a la conversación
                $codigo_id_conv = null;
                if (!empty($conversacion_id_ctx)) {
                    $parts_conv = explode('-', $conversacion_id_ctx);
                    // Formato: userA(24)-userB(24)-codigoId(24)
                    if (count($parts_conv) >= 3) {
                        $last_part = end($parts_conv);
                        if (strlen($last_part) === 24 && ctype_xdigit($last_part)) {
                            $codigo_id_conv = $last_part;
                        }
                    }
                }
                
                // 2. Si hay código, obtener su info
                if ($codigo_id_conv) {
                    $collection_codigos = getCollectionCodigos();
                    $codigo_obj = $collection_codigos->findOne(['_id' => new MongoDB\BSON\ObjectId($codigo_id_conv)]);
                    
                    if ($codigo_obj) {
                        $codigo_owner_id = (string)$codigo_obj['id_usuario'];
                        $soy_owner = ($codigo_owner_id === $user_id);
                        
                        $resultado_ctx['codigo_info'] = [
                            'codigo_id' => $codigo_id_conv,
                            'marca' => ucfirst($codigo_obj['marca'] ?? 'Desconocida'),
                            'beneficio' => $codigo_obj['num_beneficio'] ?? 0,
                            'owner_id' => $codigo_owner_id,
                            'str_codigo' => $codigo_obj['codigo'] ?? null,
                            'url' => $codigo_obj['url'] ?? null
                        ];
                        $resultado_ctx['soy_owner_codigo'] = $soy_owner;
                        
                        // Mantener retrocompatibilidad con frontend antiguo
                        $resultado_ctx['interacciones'] = [[
                            'codigo_id' => $codigo_id_conv,
                            'marca' => ucfirst($codigo_obj['marca'] ?? 'Desconocida'),
                            'beneficio' => $codigo_obj['num_beneficio'] ?? 0
                        ]];
                        $resultado_ctx['total'] = 1;
                        $resultado_ctx['total_potencial'] = $codigo_obj['num_beneficio'] ?? 0;
                    }
                }
                
                // 3. Determinar quién inició la conversación (primer mensaje)
                if (!empty($conversacion_id_ctx)) {
                    $collection_mensajes = getCollectionMensajes();
                    if ($collection_mensajes) {
                        $primer_mensaje = $collection_mensajes->findOne(
                            ['conversacion_id' => $conversacion_id_ctx],
                            ['sort' => ['fecha' => 1], 'projection' => ['de_usuario_id' => 1]]
                        );
                        
                        if ($primer_mensaje) {
                            $quien_inicio_id = (string)$primer_mensaje['de_usuario_id'];
                            $resultado_ctx['quien_inicio'] = $quien_inicio_id;
                            
                            if ($quien_inicio_id === $user_id) {
                                $resultado_ctx['contexto'] = 'yo_contacte';
                            } else {
                                $resultado_ctx['contexto'] = 'me_contactaron';
                            }
                        }
                    }
                }
                
                // 4. Si no hay código en la conversación, sin contexto de código → directo
                if (!$codigo_id_conv) {
                    $resultado_ctx['contexto'] = 'directo';
                }
                
                // 5. Verificar estado completado en la DB
                if ($codigo_id_conv) {
                    $db = createConnection();
                    $collection_completados = $db->selectCollection('codigos_completados');
                    $completado_doc = $collection_completados->findOne([
                        'owner_id' => new MongoDB\BSON\ObjectId($resultado_ctx['soy_owner_codigo'] ? $user_id : $usuario_objetivo_id),
                        'viewer_id' => new MongoDB\BSON\ObjectId($resultado_ctx['soy_owner_codigo'] ? $usuario_objetivo_id : $user_id),
                        'codigo_id' => new MongoDB\BSON\ObjectId($codigo_id_conv)
                    ]);
                    $resultado_ctx['codigo_completado'] = $completado_doc ? true : false;
                }
                
                echo json_encode($resultado_ctx);
            } catch (Throwable $e) {
                log_error("Error en get_interacciones_codigo: " . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
            }
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

        case 'toggle_codigo_completado':
             $codigo_id = $_REQUEST['codigo_id'] ?? '';
             $usuario_referido_id = $_REQUEST['usuario_referido_id'] ?? '';
             $completado = isset($_REQUEST['completado']) && ($_REQUEST['completado'] === 'true' || $_REQUEST['completado'] === '1');
             $beneficio = isset($_REQUEST['beneficio']) ? (float)$_REQUEST['beneficio'] : 0;

             if (empty($codigo_id) || empty($usuario_referido_id)) {
                 echo json_encode(['success' => false, 'error' => 'Parámetros insuficientes']);
                 exit;
             }

             try {
                 $db = createConnection();
                 $collection = $db->selectCollection('codigos_completados');
                 
                 if ($completado) {
                     // Insertar o actualizar
                     $collection->updateOne(
                         [
                             'owner_id' => new MongoDB\BSON\ObjectId($user_id),
                             'viewer_id' => new MongoDB\BSON\ObjectId($usuario_referido_id),
                             'codigo_id' => new MongoDB\BSON\ObjectId($codigo_id)
                         ],
                         [
                             '$set' => [
                                 'completado' => true,
                                 'fecha_completado' => new MongoDB\BSON\UTCDateTime(),
                                 'beneficio' => $beneficio
                             ]
                         ],
                         ['upsert' => true]
                     );
                 } else {
                     // Eliminar
                     $collection->deleteOne([
                         'owner_id' => new MongoDB\BSON\ObjectId($user_id),
                         'viewer_id' => new MongoDB\BSON\ObjectId($usuario_referido_id),
                         'codigo_id' => new MongoDB\BSON\ObjectId($codigo_id)
                     ]);
                 }
                 echo json_encode(['success' => true]);
             } catch (Throwable $e) {
                 log_error("Error en toggle_codigo_completado: " . $e->getMessage());
                 echo json_encode(['success' => false, 'error' => $e->getMessage()]);
             }
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
            
        case 'archive_conversation':
            $conversacion_id = $_REQUEST['conversacion_id'] ?? '';
            if (empty($conversacion_id)) {
                echo json_encode(['success' => false, 'error' => 'conversacion_id requerido']);
                exit;
            }
            $result = archivarConversacion($user_id, $conversacion_id);
            echo json_encode(['success' => $result]);
            break;

        case 'unarchive_conversation':
            $conversacion_id = $_REQUEST['conversacion_id'] ?? '';
            if (empty($conversacion_id)) {
                echo json_encode(['success' => false, 'error' => 'conversacion_id requerido']);
                exit;
            }
            $result = desarchivarConversacion($user_id, $conversacion_id);
            echo json_encode(['success' => $result]);
            break;

        case 'pin_conversation':
            $conversacion_id = $_REQUEST['conversacion_id'] ?? '';
            if (empty($conversacion_id)) {
                echo json_encode(['success' => false, 'error' => 'conversacion_id requerido']);
                exit;
            }
            $result = fijarConversacion($user_id, $conversacion_id);
            if (!$result) {
                echo json_encode(['success' => false, 'error' => 'No se pudo fijar. Máximo 3 conversaciones fijadas.']);
                exit;
            }
            echo json_encode(['success' => true]);
            break;

        case 'unpin_conversation':
            $conversacion_id = $_REQUEST['conversacion_id'] ?? '';
            if (empty($conversacion_id)) {
                echo json_encode(['success' => false, 'error' => 'conversacion_id requerido']);
                exit;
            }
            $result = desfijarConversacion($user_id, $conversacion_id);
            echo json_encode(['success' => $result]);
            break;

        case 'delete_conversation':
            $conversacion_id = $_REQUEST['conversacion_id'] ?? '';
            if (empty($conversacion_id)) {
                echo json_encode(['success' => false, 'error' => 'conversacion_id requerido']);
                exit;
            }
            $result = eliminarConversacion($user_id, $conversacion_id);
            echo json_encode(['success' => $result]);
            break;

        case 'subscribe_push':
            $subscription = $_REQUEST['subscription'] ?? '';
            if (is_string($subscription)) {
                $subscription = json_decode($subscription, true);
            }
            if (empty($subscription) || empty($subscription['endpoint'])) {
                echo json_encode(['success' => false, 'error' => 'Subscription inválida']);
                exit;
            }
            $result = guardarPushSubscription($user_id, $subscription);
            echo json_encode(['success' => $result]);
            break;

        case 'unsubscribe_push':
            $endpoint = $_REQUEST['endpoint'] ?? '';
            if (empty($endpoint)) {
                echo json_encode(['success' => false, 'error' => 'Endpoint requerido']);
                exit;
            }
            $result = eliminarPushSubscription($user_id, $endpoint);
            echo json_encode(['success' => $result]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    log_error("Error en chat_api.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
?>

