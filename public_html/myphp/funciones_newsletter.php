<?php
/**
 * Funciones para gestión de newsletters
 */

// Incluir funciones necesarias
if (!function_exists('createConnection')) {
    include_once __DIR__ . '/funciones.php';
}

if (!function_exists('getCollectionUsuarios')) {
    include_once __DIR__ . '/funciones_usuario.php';
}

if (!function_exists('enviarNewsletterBrevoAPI')) {
    include_once __DIR__ . '/brevo_api.php';
}

/**
 * Obtiene la colección de newsletters
 */
function getCollectionNewsletters() {
    $db = createConnection();
    if (!$db) {
        return null;
    }
    
    try {
        return $db->selectCollection('newsletters');
    } catch (Throwable $e) {
        error_log("Error al obtener colección newsletters: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtiene la colección de cola de newsletters
 */
function getCollectionNewsletterQueue() {
    $db = createConnection();
    if (!$db) {
        return null;
    }
    
    try {
        return $db->selectCollection('newsletter_queue');
    } catch (Throwable $e) {
        error_log("Error al obtener colección newsletter_queue: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtiene la colección de estadísticas de newsletters
 */
function getCollectionNewsletterStats() {
    $db = createConnection();
    if (!$db) {
        return null;
    }
    
    try {
        return $db->selectCollection('newsletter_stats');
    } catch (Throwable $e) {
        error_log("Error al obtener colección newsletter_stats: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtiene usuarios activos para newsletters
 * 
 * @param array $filtros Filtros adicionales (opcional)
 * @return array Lista de usuarios activos
 */
function obtenerUsuariosActivos($filtros = []) {
    
    $collection_usuarios = getCollectionUsuarios();
    if (!$collection_usuarios) {
        return [];
    }
    
    // Filtros base: usuarios activos con email existente y no vacío
    // Nota: Validamos el formato del email en PHP después, ya que el regex de MongoDB puede fallar
    $filtros_base = [
        'estado' => 1, // Usuarios activos
        'mail' => ['$exists' => true, '$ne' => '', '$type' => 'string'] // Email existe, no está vacío y es string
    ];
    
    // Combinar filtros
    $filtros_finales = array_merge($filtros_base, $filtros);
    
    try {
        // Obtener cursor sin cargar todo en memoria
        $usuarios_cursor = $collection_usuarios->find($filtros_finales, [
            'projection' => ['mail' => 1, 'username' => 1, '_id' => 1]
        ]);
        
        // Procesar usuarios uno por uno para evitar problemas de memoria
        // Nota: Esta función retorna un array, pero para newsletters grandes
        // se recomienda usar crearNewsletterConCursor() que procesa directamente
        $usuarios_validos = [];
        $contador = 0;
        
        foreach ($usuarios_cursor as $usuario) {
            $contador++;
            $email = trim($usuario['mail'] ?? '');
            
            // Validar formato de email con filter_var (más confiable)
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $usuarios_validos[] = $usuario;
            }
            
            // Limitar a 50,000 usuarios para evitar problemas de memoria
            // Si hay más, se recomienda usar segmentación
            if ($contador >= 50000) {
                error_log("Advertencia: Se limitó la búsqueda a 50,000 usuarios para evitar problemas de memoria. Use segmentación para más usuarios.");
                break;
            }
        }
        
        return $usuarios_validos;
    } catch (Throwable $e) {
        error_log("Error al obtener usuarios activos: " . $e->getMessage());
        return [];
    }
}

/**
 * Crea una nueva newsletter y genera la cola de destinatarios
 * 
 * @param array $datos Datos de la newsletter
 * @return array Resultado ['success' => bool, 'newsletter_id' => string, 'total_destinatarios' => int, 'error' => string]
 */
function crearNewsletter($datos) {
    
    $resultado = [
        'success' => false,
        'newsletter_id' => null,
        'total_destinatarios' => 0,
        'error' => ''
    ];
    
    // Validar datos requeridos
    if (empty($datos['titulo']) || empty($datos['asunto']) || empty($datos['contenido_html'])) {
        $resultado['error'] = 'Faltan datos requeridos';
        return $resultado;
    }
    
    $collection_newsletters = getCollectionNewsletters();
    $collection_queue = getCollectionNewsletterQueue();
    
    if (!$collection_newsletters || !$collection_queue) {
        $resultado['error'] = 'Error de conexión a la base de datos';
        return $resultado;
    }
    
    try {
        // Obtener usuarios activos directamente desde MongoDB sin cargar todo en memoria
        $filtros_usuarios = $datos['segmentacion'] ?? [];
        $collection_usuarios = getCollectionUsuarios();
        
        if (!$collection_usuarios) {
            $resultado['error'] = 'Error de conexión a la base de datos';
            return $resultado;
        }
        
        // Filtros base para usuarios activos
        $filtros_base = [
            'estado' => 1,
            'mail' => ['$exists' => true, '$ne' => '', '$type' => 'string']
        ];
        $filtros_finales = array_merge($filtros_base, $filtros_usuarios);
        
        // Contar usuarios primero (sin cargar en memoria)
        $total_destinatarios = 0;
        $usuarios_cursor = $collection_usuarios->find($filtros_finales, [
            'projection' => ['mail' => 1, 'username' => 1, '_id' => 1]
        ]);
        
        // Contar usuarios válidos procesando el cursor
        foreach ($usuarios_cursor as $usuario) {
            $email = trim($usuario['mail'] ?? '');
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $total_destinatarios++;
            }
        }
        
        if ($total_destinatarios === 0) {
            $resultado['error'] = 'No hay usuarios activos que cumplan los criterios de segmentación';
            return $resultado;
        }
        
        // Añadir enlace de Telegram si es contenido sobre chollos
        $contenido_html = añadirEnlaceTelegramChollos($datos['contenido_html']);
        
        // Crear documento de newsletter
        $newsletter_data = [
            'titulo' => $datos['titulo'],
            'asunto' => $datos['asunto'],
            'contenido_html' => $contenido_html,
            'contenido_texto' => $datos['contenido_texto'] ?? strip_tags($contenido_html),
            'segmentacion' => $datos['segmentacion'] ?? [],
            'estado' => 'programada', // borrador, programada, enviando, completada
            'fecha_creacion' => new MongoDB\BSON\UTCDateTime(),
            'fecha_programada' => isset($datos['fecha_programada']) ? new MongoDB\BSON\UTCDateTime(strtotime($datos['fecha_programada']) * 1000) : new MongoDB\BSON\UTCDateTime(),
            'fecha_inicio_envio' => null,
            'fecha_fin_envio' => null,
            'total_destinatarios' => $total_destinatarios,
            'total_enviados' => 0,
            'total_errores' => 0,
            'total_abiertos' => 0,
            'total_clics' => 0,
            'creado_por' => $datos['creado_por'] ?? null
        ];
        
        // Insertar newsletter
        $insert_result = $collection_newsletters->insertOne($newsletter_data);
        $newsletter_id = (string)$insert_result->getInsertedId();
        
        // Crear cola de envíos procesando directamente desde el cursor (sin cargar todo en memoria)
        $batch_size = 500; // Insertar 500 usuarios a la vez
        $queue_items = [];
        $contador = 0;
        
        // Obtener cursor nuevamente para procesar
        $usuarios_cursor = $collection_usuarios->find($filtros_finales, [
            'projection' => ['mail' => 1, 'username' => 1, '_id' => 1]
        ]);
        
        foreach ($usuarios_cursor as $usuario) {
            $email = trim($usuario['mail'] ?? '');
            
            // Validar formato de email
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $queue_items[] = [
                    'newsletter_id' => $newsletter_id,
                    'usuario_email' => $email,
                    'usuario_nombre' => $usuario['username'] ?? 'Usuario',
                    'usuario_id' => (string)$usuario['_id'],
                    'estado' => 'pendiente',
                    'intentos' => 0,
                    'fecha_creacion' => new MongoDB\BSON\UTCDateTime(),
                    'fecha_envio' => null,
                    'error_message' => null,
                    'brevo_message_id' => null
                ];
                
                $contador++;
                
                // Insertar en lotes para evitar problemas de memoria
                if (count($queue_items) >= $batch_size) {
                    $collection_queue->insertMany($queue_items);
                    $queue_items = []; // Limpiar array
                }
            }
        }
        
        // Insertar los últimos items si quedan
        if (!empty($queue_items)) {
            $collection_queue->insertMany($queue_items);
        }
        
        $resultado['success'] = true;
        $resultado['newsletter_id'] = $newsletter_id;
        $resultado['total_destinatarios'] = $total_destinatarios;
        
        error_log("Newsletter creada: $newsletter_id con $total_destinatarios destinatarios");
        
    } catch (Throwable $e) {
        $resultado['error'] = 'Error al crear newsletter: ' . $e->getMessage();
        error_log("Error al crear newsletter: " . $e->getMessage());
    }
    
    return $resultado;
}

/**
 * Añade el enlace de Telegram de chollos al contenido HTML si es una newsletter sobre chollos
 * 
 * @param string $html_content Contenido HTML
 * @return string Contenido HTML con enlace de Telegram añadido si corresponde
 */
function añadirEnlaceTelegramChollos($html_content) {
    // Detectar si es contenido sobre chollos (búsqueda case-insensitive)
    $es_chollos = (
        stripos($html_content, 'chollos') !== false ||
        stripos($html_content, 'chollo') !== false ||
        stripos($html_content, 'oferta') !== false
    );
    
    // Verificar si ya existe el enlace de Telegram
    $ya_tiene_telegram = (
        stripos($html_content, 't.me/cholloscodigoamigo') !== false ||
        stripos($html_content, 'telegram') !== false && stripos($html_content, 'chollos') !== false
    );
    
    if ($es_chollos && !$ya_tiene_telegram) {
        // Crear sección con enlace de Telegram
        $enlace_telegram = '
        <div style="margin: 30px 0; padding: 20px; background: linear-gradient(135deg, #0088cc 0%, #0066aa 100%); border-radius: 10px; text-align: center;">
            <h3 style="color: white; margin: 0 0 15px 0; font-size: 1.5em;">
                📱 ¡Síguenos en Telegram!
            </h3>
            <p style="color: white; margin: 0 0 20px 0; font-size: 1.1em;">
                Recibe los mejores chollos y ofertas directamente en tu móvil
            </p>
            <a href="https://t.me/cholloscodigoamigo" 
               target="_blank" 
               rel="noopener noreferrer"
               style="display: inline-block; background: white; color: #0088cc; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; font-size: 1.1em; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: transform 0.3s ease;">
                📲 Únete a nuestro canal de Telegram
            </a>
        </div>';
        
        // Insertar antes del cierre del body o al final del contenido
        if (stripos($html_content, '</body>') !== false) {
            $html_content = str_ireplace('</body>', $enlace_telegram . '</body>', $html_content);
        } else {
            $html_content .= $enlace_telegram;
        }
    }
    
    return $html_content;
}

/**
 * Edita una newsletter existente
 * 
 * @param string $newsletter_id ID de la newsletter
 * @param array $datos Datos a actualizar
 * @return array Resultado ['success' => bool, 'error' => string]
 */
function editarNewsletter($newsletter_id, $datos) {
    
    $resultado = [
        'success' => false,
        'error' => ''
    ];
    
    $collection_newsletters = getCollectionNewsletters();
    
    if (!$collection_newsletters) {
        $resultado['error'] = 'Error de conexión a la base de datos';
        return $resultado;
    }
    
    try {
        // Validar ID
        try {
            $objectId = new MongoDB\BSON\ObjectId($newsletter_id);
        } catch (Exception $e) {
            $resultado['error'] = 'ID de newsletter no válido';
            return $resultado;
        }
        
        // Verificar que la newsletter existe
        $newsletter = $collection_newsletters->findOne(['_id' => $objectId]);
        if (!$newsletter) {
            $resultado['error'] = 'Newsletter no encontrada';
            return $resultado;
        }
        
        // No permitir editar newsletters que están enviando
        if (isset($newsletter['estado']) && $newsletter['estado'] === 'enviando') {
            $resultado['error'] = 'No se puede editar una newsletter que está en proceso de envío. Cancélala primero.';
            return $resultado;
        }
        
        // Preparar datos de actualización
        $update_data = [
            '$set' => [
                'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
            ]
        ];
        
        if (isset($datos['titulo'])) {
            $update_data['$set']['titulo'] = $datos['titulo'];
        }
        
        if (isset($datos['asunto'])) {
            $update_data['$set']['asunto'] = $datos['asunto'];
        }
        
        if (isset($datos['contenido_html'])) {
            // Añadir enlace de Telegram si es contenido sobre chollos
            $contenido_html = añadirEnlaceTelegramChollos($datos['contenido_html']);
            $update_data['$set']['contenido_html'] = $contenido_html;
        }
        
        if (isset($datos['contenido_texto'])) {
            $update_data['$set']['contenido_texto'] = $datos['contenido_texto'];
        } elseif (isset($datos['contenido_html'])) {
            // Generar texto automáticamente si no se proporciona
            $update_data['$set']['contenido_texto'] = strip_tags($datos['contenido_html']);
        }
        
        // Actualizar newsletter
        $update_result = $collection_newsletters->updateOne(
            ['_id' => $objectId],
            $update_data
        );
        
        if ($update_result->getModifiedCount() > 0 || $update_result->getMatchedCount() > 0) {
            $resultado['success'] = true;
            error_log("Newsletter editada: $newsletter_id");
        } else {
            $resultado['error'] = 'No se realizaron cambios';
        }
        
    } catch (Throwable $e) {
        $resultado['error'] = 'Error al editar newsletter: ' . $e->getMessage();
        error_log("Error al editar newsletter: " . $e->getMessage());
    }
    
    return $resultado;
}

/**
 * Reactiva una newsletter (cambia estado y regenera cola si es necesario)
 * 
 * @param string $newsletter_id ID de la newsletter
 * @param bool $regenerar_cola Si regenerar la cola de envíos (solo para emails no enviados)
 * @return array Resultado ['success' => bool, 'error' => string, 'total_destinatarios' => int]
 */
function reactivarNewsletter($newsletter_id, $regenerar_cola = false) {
    
    $resultado = [
        'success' => false,
        'error' => '',
        'total_destinatarios' => 0
    ];
    
    $collection_newsletters = getCollectionNewsletters();
    $collection_queue = getCollectionNewsletterQueue();
    $collection_usuarios = getCollectionUsuarios();
    
    if (!$collection_newsletters || !$collection_queue || !$collection_usuarios) {
        $resultado['error'] = 'Error de conexión a la base de datos';
        return $resultado;
    }
    
    try {
        // Validar ID
        try {
            $objectId = new MongoDB\BSON\ObjectId($newsletter_id);
        } catch (Exception $e) {
            $resultado['error'] = 'ID de newsletter no válido';
            return $resultado;
        }
        
        // Verificar que la newsletter existe
        $newsletter = $collection_newsletters->findOne(['_id' => $objectId]);
        if (!$newsletter) {
            $resultado['error'] = 'Newsletter no encontrada';
            return $resultado;
        }
        
        // No permitir reactivar newsletters que están enviando
        if (isset($newsletter['estado']) && $newsletter['estado'] === 'enviando') {
            $resultado['error'] = 'No se puede reactivar una newsletter que está en proceso de envío. Cancélala primero.';
            return $resultado;
        }
        
        // Si se solicita regenerar la cola, eliminar emails no enviados y crear nuevos
        if ($regenerar_cola) {
            // Eliminar emails pendientes, cancelados y errores (mantener los enviados)
            $collection_queue->deleteMany([
                'newsletter_id' => $newsletter_id,
                'estado' => ['$in' => ['pendiente', 'cancelado', 'error']]
            ]);
            
            // Obtener usuarios activos
            $filtros_base = [
                'estado' => 1,
                'mail' => ['$exists' => true, '$ne' => '', '$type' => 'string']
            ];
            $filtros_finales = array_merge($filtros_base, $newsletter['segmentacion'] ?? []);
            
            // Obtener emails ya enviados para no duplicar
            $emails_enviados = $collection_queue->find(
                [
                    'newsletter_id' => $newsletter_id,
                    'estado' => 'enviado'
                ],
                ['projection' => ['usuario_email' => 1]]
            )->toArray();
            
            $emails_enviados_list = array_map(function($item) {
                return $item['usuario_email'] ?? '';
            }, $emails_enviados);
            
            // Contar y crear nueva cola
            $total_destinatarios = 0;
            $batch_size = 500;
            $queue_items = [];
            
            $usuarios_cursor = $collection_usuarios->find($filtros_finales, [
                'projection' => ['mail' => 1, 'username' => 1, '_id' => 1]
            ]);
            
            foreach ($usuarios_cursor as $usuario) {
                $email = trim($usuario['mail'] ?? '');
                
                // Validar formato de email y que no esté ya enviado
                if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL) && !in_array($email, $emails_enviados_list)) {
                    $queue_items[] = [
                        'newsletter_id' => $newsletter_id,
                        'usuario_email' => $email,
                        'usuario_nombre' => $usuario['username'] ?? 'Usuario',
                        'usuario_id' => (string)$usuario['_id'],
                        'estado' => 'pendiente',
                        'intentos' => 0,
                        'fecha_creacion' => new MongoDB\BSON\UTCDateTime(),
                        'fecha_envio' => null,
                        'error_message' => null,
                        'brevo_message_id' => null
                    ];
                    
                    $total_destinatarios++;
                    
                    if (count($queue_items) >= $batch_size) {
                        $collection_queue->insertMany($queue_items);
                        $queue_items = [];
                    }
                }
            }
            
            if (!empty($queue_items)) {
                $collection_queue->insertMany($queue_items);
            }
            
            $resultado['total_destinatarios'] = $total_destinatarios;
        }
        
        // Actualizar estado a programada
        $collection_newsletters->updateOne(
            ['_id' => $objectId],
            [
                '$set' => [
                    'estado' => 'programada',
                    'fecha_reactivacion' => new MongoDB\BSON\UTCDateTime()
                ],
                '$unset' => [
                    'fecha_cancelacion' => ''
                ]
            ]
        );
        
        $resultado['success'] = true;
        error_log("Newsletter reactivada: $newsletter_id");
        
    } catch (Throwable $e) {
        $resultado['error'] = 'Error al reactivar newsletter: ' . $e->getMessage();
        error_log("Error al reactivar newsletter: " . $e->getMessage());
    }
    
    return $resultado;
}

/**
 * Procesa la cola de newsletters (máximo 300 emails)
 * 
 * @param int $limite_diario Límite diario de emails (default: 300)
 * @return array Resultado del procesamiento
 */
function procesarColaNewsletter($limite_diario = 300) {
    
    $resultado = [
        'procesados' => 0,
        'enviados' => 0,
        'errores' => 0,
        'limite_alcanzado' => false,
        'newsletters_completadas' => []
    ];
    
    $collection_queue = getCollectionNewsletterQueue();
    $collection_newsletters = getCollectionNewsletters();
    $collection_stats = getCollectionNewsletterStats();
    
    if (!$collection_queue || !$collection_newsletters) {
        $resultado['error'] = 'Error de conexión a la base de datos';
        return $resultado;
    }
    
    try {
        // Obtener contador de emails enviados hoy
        $emails_enviados_hoy = obtenerEmailsEnviadosHoy();
        $disponibles = max(0, $limite_diario - $emails_enviados_hoy);
        
        if ($disponibles <= 0) {
            $resultado['limite_alcanzado'] = true;
            error_log("Límite diario alcanzado. Emails enviados hoy: $emails_enviados_hoy / $limite_diario");
            return $resultado;
        }
        
        // Obtener emails pendientes (limitado a los disponibles, excluyendo cancelados)
        $pendientes = $collection_queue->find(
            [
                'estado' => 'pendiente'
            ],
            [
                'sort' => ['fecha_creacion' => 1],
                'limit' => $disponibles
            ]
        )->toArray();
        
        if (empty($pendientes)) {
            error_log("No hay emails pendientes en la cola");
            return $resultado;
        }
        
        // Procesar cada email
        foreach ($pendientes as $item) {
            $resultado['procesados']++;
            
            // Obtener datos de la newsletter
            $newsletter = $collection_newsletters->findOne(['_id' => new MongoDB\BSON\ObjectId($item['newsletter_id'])]);
            
            if (!$newsletter) {
                // Marcar como error si no existe la newsletter
                $collection_queue->updateOne(
                    ['_id' => $item['_id']],
                    [
                        '$set' => [
                            'estado' => 'error',
                            'error_message' => 'Newsletter no encontrada',
                            'intentos' => $item['intentos'] + 1
                        ]
                    ]
                );
                $resultado['errores']++;
                continue;
            }
            
            // Si es el primer envío, actualizar estado de newsletter
            if ($newsletter['estado'] === 'programada') {
                $collection_newsletters->updateOne(
                    ['_id' => new MongoDB\BSON\ObjectId($item['newsletter_id'])],
                    [
                        '$set' => [
                            'estado' => 'enviando',
                            'fecha_inicio_envio' => new MongoDB\BSON\UTCDateTime()
                        ]
                    ]
                );
            }
            
            // Añadir enlace de Telegram si es contenido sobre chollos (por si acaso no se añadió antes)
            $contenido_html = añadirEnlaceTelegramChollos($newsletter['contenido_html']);
            
            // Añadir tracking al contenido HTML
            $contenido_con_tracking = añadirTrackingNewsletter(
                $contenido_html,
                $item['newsletter_id'],
                $item['usuario_email']
            );
            
            // Enviar email usando Brevo API
            $tags = ['newsletter_id' => $item['newsletter_id']];
            $envio_result = enviarNewsletterBrevoAPI(
                $item['usuario_email'],
                $item['usuario_nombre'],
                $newsletter['asunto'],
                $contenido_con_tracking,
                $newsletter['contenido_texto'],
                FROM_EMAIL,
                FROM_NAME,
                $tags
            );
            
            if ($envio_result['success']) {
                // Marcar como enviado
                $collection_queue->updateOne(
                    ['_id' => $item['_id']],
                    [
                        '$set' => [
                            'estado' => 'enviado',
                            'fecha_envio' => new MongoDB\BSON\UTCDateTime(),
                            'brevo_message_id' => $envio_result['message_id']
                        ]
                    ]
                );
                
                // Actualizar contador de newsletter
                $collection_newsletters->updateOne(
                    ['_id' => new MongoDB\BSON\ObjectId($item['newsletter_id'])],
                    ['$inc' => ['total_enviados' => 1]]
                );
                
                // Registrar email enviado hoy
                registrarEmailEnviadoHoy();
                
                $resultado['enviados']++;
            } else {
                // Marcar como error (con reintentos)
                $intentos = $item['intentos'] + 1;
                $estado_error = ($intentos >= 3) ? 'error' : 'pendiente'; // Máximo 3 intentos
                
                $collection_queue->updateOne(
                    ['_id' => $item['_id']],
                    [
                        '$set' => [
                            'estado' => $estado_error,
                            'error_message' => $envio_result['error'],
                            'intentos' => $intentos
                        ]
                    ]
                );
                
                if ($estado_error === 'error') {
                    $collection_newsletters->updateOne(
                        ['_id' => new MongoDB\BSON\ObjectId($item['newsletter_id'])],
                        ['$inc' => ['total_errores' => 1]]
                    );
                }
                
                $resultado['errores']++;
            }
        }
        
        // Verificar si alguna newsletter se completó
        $newsletters_activas = $collection_newsletters->find(['estado' => 'enviando'])->toArray();
        foreach ($newsletters_activas as $newsletter) {
            $pendientes_count = $collection_queue->countDocuments([
                'newsletter_id' => (string)$newsletter['_id'],
                'estado' => 'pendiente'
            ]);
            
            if ($pendientes_count === 0) {
                // Marcar como completada
                $collection_newsletters->updateOne(
                    ['_id' => $newsletter['_id']],
                    [
                        '$set' => [
                            'estado' => 'completada',
                            'fecha_fin_envio' => new MongoDB\BSON\UTCDateTime()
                        ]
                    ]
                );
                
                $resultado['newsletters_completadas'][] = (string)$newsletter['_id'];
            }
        }
        
    } catch (Throwable $e) {
        error_log("Error al procesar cola de newsletters: " . $e->getMessage());
        $resultado['error'] = $e->getMessage();
    }
    
    return $resultado;
}

/**
 * Obtiene estadísticas de una newsletter
 * 
 * @param string $newsletter_id ID de la newsletter
 * @return array Estadísticas
 */
function obtenerEstadisticasNewsletter($newsletter_id) {
    
    $resultado = [
        'total_destinatarios' => 0,
        'total_enviados' => 0,
        'total_errores' => 0,
        'total_pendientes' => 0,
        'porcentaje_completado' => 0
    ];
    
    $collection_newsletters = getCollectionNewsletters();
    $collection_queue = getCollectionNewsletterQueue();
    
    if (!$collection_newsletters || !$collection_queue) {
        return $resultado;
    }
    
    try {
        $newsletter = $collection_newsletters->findOne(['_id' => new MongoDB\BSON\ObjectId($newsletter_id)]);
        
        if ($newsletter) {
            $resultado['total_destinatarios'] = $newsletter['total_destinatarios'] ?? 0;
            $resultado['total_enviados'] = $newsletter['total_enviados'] ?? 0;
            $resultado['total_errores'] = $newsletter['total_errores'] ?? 0;
            
            $pendientes = $collection_queue->countDocuments([
                'newsletter_id' => $newsletter_id,
                'estado' => 'pendiente'
            ]);
            
            $resultado['total_pendientes'] = $pendientes;
            
            if ($resultado['total_destinatarios'] > 0) {
                $resultado['porcentaje_completado'] = round(($resultado['total_enviados'] / $resultado['total_destinatarios']) * 100, 2);
            }
        }
    } catch (Throwable $e) {
        error_log("Error al obtener estadísticas de newsletter: " . $e->getMessage());
    }
    
    return $resultado;
}

/**
 * Obtiene los emails de la cola de una newsletter específica
 * 
 * @param string $newsletter_id ID de la newsletter
 * @param int $limit Límite de resultados (0 = sin límite)
 * @param int $skip Número de resultados a saltar (para paginación)
 * @param string $estado Filtrar por estado (pendiente, enviado, error) - opcional
 * @return array Lista de emails con su estado
 */
function obtenerEmailsNewsletter($newsletter_id, $limit = 0, $skip = 0, $estado = null) {
    
    $collection_queue = getCollectionNewsletterQueue();
    if (!$collection_queue) {
        return [];
    }
    
    try {
        $filtro = ['newsletter_id' => $newsletter_id];
        if ($estado) {
            $filtro['estado'] = $estado;
        }
        
        $opciones = [
            'sort' => ['fecha_creacion' => -1]
        ];
        
        if ($limit > 0) {
            $opciones['limit'] = $limit;
        }
        
        if ($skip > 0) {
            $opciones['skip'] = $skip;
        }
        
        $emails = $collection_queue->find($filtro, $opciones)->toArray();
        
        // Formatear los resultados
        $resultado = [];
        foreach ($emails as $email) {
            $resultado[] = [
                '_id' => (string)$email['_id'],
                'usuario_email' => $email['usuario_email'] ?? '',
                'usuario_nombre' => $email['usuario_nombre'] ?? '',
                'usuario_id' => $email['usuario_id'] ?? '',
                'estado' => $email['estado'] ?? 'pendiente',
                'intentos' => $email['intentos'] ?? 0,
                'fecha_creacion' => isset($email['fecha_creacion']) ? $email['fecha_creacion']->toDateTime()->format('d/m/Y H:i:s') : '',
                'fecha_envio' => isset($email['fecha_envio']) && $email['fecha_envio'] ? $email['fecha_envio']->toDateTime()->format('d/m/Y H:i:s') : null,
                'error_message' => $email['error_message'] ?? null,
                'brevo_message_id' => $email['brevo_message_id'] ?? null
            ];
        }
        
        return $resultado;
    } catch (Throwable $e) {
        error_log("Error al obtener emails de newsletter: " . $e->getMessage());
        return [];
    }
}

/**
 * Cuenta los emails de una newsletter por estado
 * 
 * @param string $newsletter_id ID de la newsletter
 * @return array Conteos por estado
 */
function contarEmailsNewsletterPorEstado($newsletter_id) {
    
    $collection_queue = getCollectionNewsletterQueue();
    if (!$collection_queue) {
        return [
            'pendiente' => 0,
            'enviado' => 0,
            'error' => 0,
            'cancelado' => 0,
            'total' => 0
        ];
    }
    
    try {
        $pendiente = $collection_queue->countDocuments([
            'newsletter_id' => $newsletter_id,
            'estado' => 'pendiente'
        ]);
        
        $enviado = $collection_queue->countDocuments([
            'newsletter_id' => $newsletter_id,
            'estado' => 'enviado'
        ]);
        
        $error = $collection_queue->countDocuments([
            'newsletter_id' => $newsletter_id,
            'estado' => 'error'
        ]);
        
        $cancelado = $collection_queue->countDocuments([
            'newsletter_id' => $newsletter_id,
            'estado' => 'cancelado'
        ]);
        
        return [
            'pendiente' => $pendiente,
            'enviado' => $enviado,
            'error' => $error,
            'cancelado' => $cancelado,
            'total' => $pendiente + $enviado + $error + $cancelado
        ];
    } catch (Throwable $e) {
        error_log("Error al contar emails por estado: " . $e->getMessage());
        return [
            'pendiente' => 0,
            'enviado' => 0,
            'error' => 0,
            'cancelado' => 0,
            'total' => 0
        ];
    }
}

/**
 * Obtiene el número de emails enviados hoy
 * 
 * @return int Número de emails enviados hoy
 */
function obtenerEmailsEnviadosHoy() {
    
    $collection_queue = getCollectionNewsletterQueue();
    if (!$collection_queue) {
        return 0;
    }
    
    try {
        $hoy_inicio = new MongoDB\BSON\UTCDateTime(strtotime('today') * 1000);
        $hoy_fin = new MongoDB\BSON\UTCDateTime(strtotime('tomorrow') * 1000);
        
        $count = $collection_queue->countDocuments([
            'estado' => 'enviado',
            'fecha_envio' => [
                '$gte' => $hoy_inicio,
                '$lt' => $hoy_fin
            ]
        ]);
        
        return $count;
    } catch (Throwable $e) {
        error_log("Error al obtener emails enviados hoy: " . $e->getMessage());
        return 0;
    }
}

/**
 * Registra un email enviado hoy (para contador diario)
 * Esta función se llama después de cada envío exitoso
 */
function registrarEmailEnviadoHoy() {
    // El contador se calcula dinámicamente con obtenerEmailsEnviadosHoy()
    // No necesitamos una colección separada para esto
}

/**
 * Añade tracking de aperturas y clics al contenido HTML del newsletter
 * 
 * @param string $html_content Contenido HTML original
 * @param string $newsletter_id ID de la newsletter
 * @param string $usuario_email Email del destinatario
 * @return string HTML con tracking añadido
 */
function añadirTrackingNewsletter($html_content, $newsletter_id, $usuario_email) {
    
    // URL base para tracking
    $base_url = 'https://www.codigoamigo.com';
    
    // Generar token único para este envío
    $token = base64_encode($newsletter_id . '|' . $usuario_email . '|' . time());
    
    // Añadir pixel de tracking de apertura (imagen invisible de 1x1)
    $tracking_pixel = '<img src="' . $base_url . '/track/newsletter/open.php?t=' . urlencode($token) . '" width="1" height="1" style="display:none;" alt="" />';
    
    // Insertar el pixel antes del cierre del body, o al final si no hay body
    if (stripos($html_content, '</body>') !== false) {
        $html_content = str_ireplace('</body>', $tracking_pixel . '</body>', $html_content);
    } else {
        $html_content .= $tracking_pixel;
    }
    
    // Reemplazar todos los enlaces con URLs de tracking
    $html_content = preg_replace_callback(
        '/<a\s+([^>]*href=["\'])([^"\']+)(["\'][^>]*)>/i',
        function($matches) use ($base_url, $token) {
            $url_original = $matches[2];
            
            // No trackear enlaces internos de tracking ni mailto
            if (strpos($url_original, '/track/') !== false || 
                strpos($url_original, 'mailto:') === 0 ||
                strpos($url_original, '#') === 0) {
                return $matches[0];
            }
            
            // Crear URL de tracking
            $url_tracking = $base_url . '/track/newsletter/click.php?t=' . urlencode($token) . '&url=' . urlencode($url_original);
            
            return '<a ' . $matches[1] . $url_tracking . $matches[3] . '>';
        },
        $html_content
    );
    
    return $html_content;
}

/**
 * Registra una apertura de newsletter
 * 
 * @param string $token Token de tracking
 * @return bool Éxito de la operación
 */
function registrarAperturaNewsletter($token) {
    
    try {
        $datos = decodificarTokenTracking($token);
        if (!$datos) {
            return false;
        }
        
        $collection_stats = getCollectionNewsletterStats();
        if (!$collection_stats) {
            return false;
        }
        
        // Verificar si ya se registró esta apertura (evitar duplicados)
        $existe = $collection_stats->findOne([
            'newsletter_id' => $datos['newsletter_id'],
            'usuario_email' => $datos['usuario_email'],
            'tipo' => 'apertura'
        ]);
        
        if ($existe) {
            return true; // Ya registrada
        }
        
        // Registrar nueva apertura
        $collection_stats->insertOne([
            'newsletter_id' => $datos['newsletter_id'],
            'usuario_email' => $datos['usuario_email'],
            'tipo' => 'apertura',
            'fecha' => new MongoDB\BSON\UTCDateTime(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        // Actualizar contador en la newsletter
        $collection_newsletters = getCollectionNewsletters();
        if ($collection_newsletters) {
            $collection_newsletters->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($datos['newsletter_id'])],
                ['$inc' => ['total_abiertos' => 1]]
            );
        }
        
        return true;
        
    } catch (Throwable $e) {
        error_log("Error al registrar apertura de newsletter: " . $e->getMessage());
        return false;
    }
}

/**
 * Registra un clic en un enlace del newsletter
 * 
 * @param string $token Token de tracking
 * @param string $url_original URL original del enlace
 * @return string URL a redirigir
 */
function registrarClicNewsletter($token, $url_original) {
    
    try {
        $datos = decodificarTokenTracking($token);
        if (!$datos) {
            return $url_original; // Si falla, redirigir a URL original
        }
        
        $collection_stats = getCollectionNewsletterStats();
        if ($collection_stats) {
            // Registrar clic
            $collection_stats->insertOne([
                'newsletter_id' => $datos['newsletter_id'],
                'usuario_email' => $datos['usuario_email'],
                'tipo' => 'clic',
                'url' => $url_original,
                'fecha' => new MongoDB\BSON\UTCDateTime(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            
            // Actualizar contador en la newsletter
            $collection_newsletters = getCollectionNewsletters();
            if ($collection_newsletters) {
                $collection_newsletters->updateOne(
                    ['_id' => new MongoDB\BSON\ObjectId($datos['newsletter_id'])],
                    ['$inc' => ['total_clics' => 1]]
                );
            }
        }
        
    } catch (Throwable $e) {
        error_log("Error al registrar clic de newsletter: " . $e->getMessage());
    }
    
    return $url_original;
}

/**
 * Decodifica el token de tracking
 * 
 * @param string $token Token codificado
 * @return array|false Datos decodificados o false si falla
 */
function decodificarTokenTracking($token) {
    try {
        if (empty($token)) {
            return false;
        }
        
        $decoded = base64_decode($token, true); // strict mode
        if ($decoded === false) {
            return false;
        }
        
        $parts = explode('|', $decoded);
        
        if (count($parts) >= 2) {
            return [
                'newsletter_id' => $parts[0],
                'usuario_email' => $parts[1],
                'timestamp' => $parts[2] ?? null
            ];
        }
        
        return false;
    } catch (Throwable $e) {
        error_log("Error al decodificar token: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene estadísticas detalladas de una newsletter (con aperturas y clics)
 * 
 * @param string $newsletter_id ID de la newsletter
 * @return array Estadísticas completas
 */
function obtenerEstadisticasCompletasNewsletter($newsletter_id) {
    
    $stats = obtenerEstadisticasNewsletter($newsletter_id);
    
    $collection_stats = getCollectionNewsletterStats();
    if ($collection_stats) {
        try {
            // Contar aperturas únicas
            $aperturas = $collection_stats->countDocuments([
                'newsletter_id' => $newsletter_id,
                'tipo' => 'apertura'
            ]);
            
            // Contar clics únicos
            $clics = $collection_stats->countDocuments([
                'newsletter_id' => $newsletter_id,
                'tipo' => 'clic'
            ]);
            
            $stats['total_abiertos'] = $aperturas;
            $stats['total_clics'] = $clics;
            
            // Calcular tasas
            if ($stats['total_enviados'] > 0) {
                $stats['tasa_apertura'] = round(($aperturas / $stats['total_enviados']) * 100, 2);
                $stats['tasa_clics'] = round(($clics / $stats['total_enviados']) * 100, 2);
            } else {
                $stats['tasa_apertura'] = 0;
                $stats['tasa_clics'] = 0;
            }
            
        } catch (Throwable $e) {
            error_log("Error al obtener estadísticas completas: " . $e->getMessage());
        }
    }
    
    return $stats;
}

/**
 * Cancela la cola de envío de una newsletter (marca pendientes como cancelados)
 * 
 * @param string $newsletter_id ID de la newsletter
 * @return array Resultado ['success' => bool, 'cancelados' => int, 'error' => string]
 */
function cancelarColaNewsletter($newsletter_id) {
    
    $resultado = [
        'success' => false,
        'cancelados' => 0,
        'error' => ''
    ];
    
    $collection_newsletters = getCollectionNewsletters();
    $collection_queue = getCollectionNewsletterQueue();
    
    if (!$collection_newsletters || !$collection_queue) {
        $resultado['error'] = 'Error de conexión a la base de datos';
        return $resultado;
    }
    
    try {
        // Validar ID
        try {
            $objectId = new MongoDB\BSON\ObjectId($newsletter_id);
        } catch (Exception $e) {
            $resultado['error'] = 'ID de newsletter no válido';
            return $resultado;
        }
        
        // Verificar que la newsletter existe
        $newsletter = $collection_newsletters->findOne(['_id' => $objectId]);
        if (!$newsletter) {
            $resultado['error'] = 'Newsletter no encontrada';
            return $resultado;
        }
        
        // Marcar todos los emails pendientes como cancelados
        $update_result = $collection_queue->updateMany(
            [
                'newsletter_id' => $newsletter_id,
                'estado' => 'pendiente'
            ],
            [
                '$set' => [
                    'estado' => 'cancelado',
                    'fecha_cancelacion' => new MongoDB\BSON\UTCDateTime()
                ]
            ]
        );
        
        $cancelados = $update_result->getModifiedCount();
        
        // Actualizar estado de la newsletter si estaba enviando
        if (isset($newsletter['estado']) && $newsletter['estado'] === 'enviando') {
            $collection_newsletters->updateOne(
                ['_id' => $objectId],
                [
                    '$set' => [
                        'estado' => 'cancelada',
                        'fecha_cancelacion' => new MongoDB\BSON\UTCDateTime()
                    ]
                ]
            );
        }
        
        $resultado['success'] = true;
        $resultado['cancelados'] = $cancelados;
        
        error_log("Cola cancelada para newsletter $newsletter_id: $cancelados emails cancelados");
        
    } catch (Throwable $e) {
        $resultado['error'] = 'Error al cancelar cola: ' . $e->getMessage();
        error_log("Error al cancelar cola de newsletter: " . $e->getMessage());
    }
    
    return $resultado;
}

/**
 * Elimina una newsletter y su cola asociada
 * 
 * @param string $newsletter_id ID de la newsletter
 * @return array Resultado ['success' => bool, 'error' => string]
 */
function eliminarNewsletter($newsletter_id) {
    
    $resultado = [
        'success' => false,
        'error' => ''
    ];
    
    $collection_newsletters = getCollectionNewsletters();
    $collection_queue = getCollectionNewsletterQueue();
    $collection_stats = getCollectionNewsletterStats();
    
    if (!$collection_newsletters) {
        $resultado['error'] = 'Error de conexión a la base de datos';
        return $resultado;
    }
    
    try {
        // Validar ID
        try {
            $objectId = new MongoDB\BSON\ObjectId($newsletter_id);
        } catch (Exception $e) {
            $resultado['error'] = 'ID de newsletter no válido';
            return $resultado;
        }
        
        // Verificar que la newsletter existe
        $newsletter = $collection_newsletters->findOne(['_id' => $objectId]);
        if (!$newsletter) {
            $resultado['error'] = 'Newsletter no encontrada';
            return $resultado;
        }
        
        // No permitir eliminar newsletters que están enviando
        if (isset($newsletter['estado']) && $newsletter['estado'] === 'enviando') {
            $resultado['error'] = 'No se puede eliminar una newsletter que está en proceso de envío. Espera a que termine o cancélala primero.';
            return $resultado;
        }
        
        // Eliminar cola de envíos asociada
        if ($collection_queue) {
            $collection_queue->deleteMany(['newsletter_id' => $newsletter_id]);
        }
        
        // Eliminar estadísticas asociadas
        if ($collection_stats) {
            $collection_stats->deleteMany(['newsletter_id' => $newsletter_id]);
        }
        
        // Eliminar la newsletter
        $delete_result = $collection_newsletters->deleteOne(['_id' => $objectId]);
        
        if ($delete_result->getDeletedCount() > 0) {
            $resultado['success'] = true;
            error_log("Newsletter eliminada: $newsletter_id");
        } else {
            $resultado['error'] = 'No se pudo eliminar la newsletter';
        }
        
    } catch (Throwable $e) {
        $resultado['error'] = 'Error al eliminar newsletter: ' . $e->getMessage();
        error_log("Error al eliminar newsletter: " . $e->getMessage());
    }
    
    return $resultado;
}

?>

