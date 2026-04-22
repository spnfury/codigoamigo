<?php

/**
 * Funciones generales para la gestión de chollos
 */

// Incluir funciones de conexión a MongoDB
if (!function_exists('createConnection')) {
    include_once __DIR__ . '/funciones.php';
}

// Incluir configuración de IA
if (file_exists(__DIR__ . '/../config/ai_config.php')) {
    include_once __DIR__ . '/../config/ai_config.php';
}

/**
 * Obtiene la colección de chollos
 */
function getCollectionChollos() {
    $db = createConnection();
    if (!$db) {
        return null;
    }

    try {
        $collection = $db->selectCollection('chollos');
        return $collection;
    } catch (Throwable $e) {
        error_log("Error al obtener colección de chollos: " . $e->getMessage());
        return null;
    }
}

/**
 * Crea un nuevo chollo en la base de datos
 */
function crearChollo($datos) {
    $collection = getCollectionChollos();
    if (!$collection) {
        return ['success' => false, 'error' => 'Error de conexión'];
    }

    try {
        // Validar campos requeridos
        if (empty($datos['titulo']) || empty($datos['enlace'])) {
            return ['success' => false, 'error' => 'Título y enlace son obligatorios'];
        }

        $documento = [
            'titulo' => $datos['titulo'],
            'descripcion' => $datos['descripcion'] ?? '',
            'precio_original' => isset($datos['precio_original']) ? floatval($datos['precio_original']) : null,
            'precio_descuento' => isset($datos['precio_descuento']) ? floatval($datos['precio_descuento']) : null,
            'porcentaje_descuento' => isset($datos['porcentaje_descuento']) ? intval($datos['porcentaje_descuento']) : null,
            'enlace' => $datos['enlace'],
            'enlace_original' => $datos['enlace_original'] ?? $datos['enlace'],
            'asin' => $datos['asin'] ?? null,
            'imagen' => $datos['imagen'] ?? '',
            'categoria' => $datos['categoria'] ?? ['General'],
            'fecha_inicio' => $datos['fecha_inicio'] ?? new MongoDB\BSON\UTCDateTime(),
            'fecha_fin' => $datos['fecha_fin'] ?? null,
            'fuente' => $datos['fuente'] ?? 'manual',
            'fuente_id' => isset($datos['fuente_id']) ? new MongoDB\BSON\ObjectId($datos['fuente_id']) : null,
            'mensaje_original_id' => $datos['mensaje_original_id'] ?? null,
            'estado' => $datos['estado'] ?? 1,
            'texto_reescrito' => $datos['texto_reescrito'] ?? false,
            'fecha_creacion' => new MongoDB\BSON\UTCDateTime(),
            'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime(),
            'clicks' => 0,
            'publicado_telegram' => false,
            'votos_positivos' => 0,
            'votos_negativos' => 0,
            'temperatura' => 0,
            'total_comentarios' => 0
        ];

        $resultado = $collection->insertOne($documento);
        
        if ($resultado->getInsertedId()) {
            
            // --- NOTIFICACIÓN A SEGUIDORES (Si el chollo tiene un autor) ---
            try {
                if (isset($documento['fuente_id']) && $documento['fuente_id'] instanceof MongoDB\BSON\ObjectId) {
                    if (!function_exists('enviarEmailNotificacionPublicacionUsuario')) {
                        include_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/email_helper.php';
                    }
                    if (!function_exists('getCollectionFavoritos')) {
                        include_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/funciones_favoritos.php';
                    }
                    if (!function_exists('getCollectionUsuarios')) {
                        include_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/funciones_usuario.php';
                    }
                    
                    $collection_favoritos = getCollectionFavoritos();
                    $collection_usuarios = getCollectionUsuarios();
                    
                    $autor = $collection_usuarios->findOne(['_id' => $documento['fuente_id']]);
                    if ($autor) {
                        $autor_nombre = $autor['username'] ?? 'Un usuario';
                        
                        $seguidores = $collection_favoritos->find([
                            'codigo_id' => $documento['fuente_id'],
                            'tipo' => 'usuario'
                        ]);
                        
                        // Generar slug de categoría
                        $cat_slug = 'general';
                        if (!empty($documento['categoria']) && is_array($documento['categoria'])) {
                            $cat_slug = strtolower(str_replace(' ', '-', $documento['categoria'][0]));
                        } elseif (!empty($documento['categoria']) && is_string($documento['categoria'])) {
                            $cat_slug = strtolower(str_replace(' ', '-', $documento['categoria']));
                        }
                        
                        $url_chollo = "https://www.malprecio.com/chollos/" . $cat_slug . "/" . (string)$resultado->getInsertedId();
                        
                        foreach ($seguidores as $seg) {
                            $seguidor = $collection_usuarios->findOne(['_id' => $seg['usuario_id']]);
                            if ($seguidor && !empty($seguidor['mail'])) {
                                enviarEmailNotificacionPublicacionUsuario(
                                    $seguidor['mail'],
                                    $seguidor['username'] ?? 'Usuario',
                                    $autor_nombre,
                                    'chollo',
                                    $documento['titulo'],
                                    $url_chollo,
                                    $documento['imagen'] ?? null
                                );
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Error al notificar a seguidores sobre nuevo chollo: " . $e->getMessage());
            }
            // --- FIN NOTIFICACIÓN ---

            return ['success' => true, 'id' => (string)$resultado->getInsertedId()];
        } else {
            return ['success' => false, 'error' => 'Error al insertar chollo'];
        }
    } catch (Throwable $e) {
        error_log("Error al crear chollo: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno: ' . $e->getMessage()];
    }
}

/**
 * Obtiene chollos con filtros y paginación
 */
function obtenerChollos($filtros = []) {
    $collection = getCollectionChollos();
    if (!$collection) {
        return [];
    }

    try {
        $query = [];

        // Filtro por estado
        if (isset($filtros['estado'])) {
            $query['estado'] = intval($filtros['estado']);
        } else {
            $query['estado'] = 1; // Por defecto solo activos
        }

        // Filtro por categoría (es un array en BD)
        if (!empty($filtros['categoria'])) {
            $query['categoria'] = $filtros['categoria'];
        }

        // Filtro por fuente
        if (!empty($filtros['fuente'])) {
            $query['fuente'] = $filtros['fuente'];
        }

        // Búsqueda por texto
        if (!empty($filtros['q'])) {
            $query['$or'] = [
                ['titulo' => ['$regex' => $filtros['q'], '$options' => 'i']],
                ['descripcion' => ['$regex' => $filtros['q'], '$options' => 'i']]
            ];
        }

        // Opciones de consulta
        $opciones = [
            'sort' => ['fecha_creacion' => -1]
        ];

        // Paginación
        if (isset($filtros['limite'])) {
            $opciones['limit'] = intval($filtros['limite']);
        }
        if (isset($filtros['skip'])) {
            $opciones['skip'] = intval($filtros['skip']);
        }

        $cursor = $collection->find($query, $opciones);
        $chollos = [];

        foreach ($cursor as $doc) {
            $chollos[] = [
                'id' => (string)$doc['_id'],
                'titulo' => $doc['titulo'] ?? '',
                'descripcion' => $doc['descripcion'] ?? '',
                'precio_original' => $doc['precio_original'] ?? null,
                'precio_descuento' => $doc['precio_descuento'] ?? null,
                'porcentaje_descuento' => $doc['porcentaje_descuento'] ?? null,
                'enlace' => $doc['enlace'] ?? '',
                'enlace_original' => $doc['enlace_original'] ?? '',
                'asin' => $doc['asin'] ?? null,
                'imagen' => $doc['imagen'] ?? '',
                'categoria' => $doc['categoria'] ?? ['General'],
                'fecha_inicio' => isset($doc['fecha_inicio']) && $doc['fecha_inicio'] instanceof MongoDB\BSON\UTCDateTime ? $doc['fecha_inicio']->toDateTime()->format('Y-m-d H:i:s') : '',
                'fuente' => $doc['fuente'] ?? 'manual',
                'estado' => $doc['estado'] ?? 1,
                'fecha_creacion' => isset($doc['fecha_creacion']) && $doc['fecha_creacion'] instanceof MongoDB\BSON\UTCDateTime ? $doc['fecha_creacion']->toDateTime()->format('Y-m-d H:i:s') : '',
                'clicks' => $doc['clicks'] ?? 0,
                'publicado_telegram' => $doc['publicado_telegram'] ?? false,
                'votos_positivos' => $doc['votos_positivos'] ?? 0,
                'votos_negativos' => $doc['votos_negativos'] ?? 0,
                'temperatura' => $doc['temperatura'] ?? 0,
                'total_comentarios' => $doc['total_comentarios'] ?? 0
            ];
        }

        return $chollos;
    } catch (Throwable $e) {
        error_log("Error al obtener chollos: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene un chollo por su ID
 */
function obtenerCholloPorId($id, $incrementar_clicks = false) {
    $collection = getCollectionChollos();
    if (!$collection) {
        return null;
    }

    try {
        try {
            $objectId = new MongoDB\BSON\ObjectId($id);
        } catch (Exception $e) {
            return null;
        }

        if ($incrementar_clicks) {
            $doc = $collection->findOneAndUpdate(
                ['_id' => $objectId],
                ['$inc' => ['clicks' => 1]],
                ['returnDocument' => MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER]
            );
        } else {
            $doc = $collection->findOne(['_id' => $objectId]);
        }

        if (!$doc) {
            return null;
        }

        // Convertir categoría a array si es string (compatibilidad legacy)
        $categoria = $doc['categoria'] ?? ['General'];
        if (!is_array($categoria)) {
            $categoria = [$categoria];
        }

        return [
            'id' => (string)$doc['_id'],
            'titulo' => $doc['titulo'] ?? '',
            'descripcion' => $doc['descripcion'] ?? '',
            'precio_original' => $doc['precio_original'] ?? null,
            'precio_descuento' => $doc['precio_descuento'] ?? null,
            'porcentaje_descuento' => $doc['porcentaje_descuento'] ?? null,
            'enlace' => $doc['enlace'] ?? '',
            'enlace_original' => $doc['enlace_original'] ?? '',
            'asin' => $doc['asin'] ?? null,
            'imagen' => $doc['imagen'] ?? '',
            'categoria' => $categoria,
            'fecha_inicio' => $doc['fecha_inicio'] ?? '',
            'fecha_fin' => $doc['fecha_fin'] ?? null,
            'fuente' => $doc['fuente'] ?? 'manual',
            'fuente_id' => isset($doc['fuente_id']) ? (string)$doc['fuente_id'] : null,
            'mensaje_original_id' => $doc['mensaje_original_id'] ?? null,
            'estado' => $doc['estado'] ?? 1,
            'texto_reescrito' => $doc['texto_reescrito'] ?? false,
            'fecha_creacion' => isset($doc['fecha_creacion']) ? $doc['fecha_creacion']->toDateTime()->format('Y-m-d H:i:s') : '',
            'clicks' => ($doc['clicks'] ?? 0) + ($incrementar_clicks ? 1 : 0),
            'publicado_telegram' => $doc['publicado_telegram'] ?? false,
            'votos_positivos' => $doc['votos_positivos'] ?? 0,
            'votos_negativos' => $doc['votos_negativos'] ?? 0,
            'temperatura' => $doc['temperatura'] ?? 0,
            'total_comentarios' => $doc['total_comentarios'] ?? 0
        ];
    } catch (Throwable $e) {
        error_log("Error al obtener chollo por ID: " . $e->getMessage());
        return null;
    }
}

/**
 * Actualiza un chollo
 */
function actualizarChollo($id, $datos) {
    $collection = getCollectionChollos();
    if (!$collection) {
        return ['success' => false, 'error' => 'Error de conexión'];
    }

    try {
        try {
            $objectId = new MongoDB\BSON\ObjectId($id);
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'ID no válido'];
        }

        $update = [
            'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
        ];

        if (isset($datos['titulo'])) $update['titulo'] = $datos['titulo'];
        if (isset($datos['descripcion'])) $update['descripcion'] = $datos['descripcion'];
        if (isset($datos['precio_original'])) $update['precio_original'] = floatval($datos['precio_original']);
        if (isset($datos['precio_descuento'])) $update['precio_descuento'] = floatval($datos['precio_descuento']);
        if (isset($datos['porcentaje_descuento'])) $update['porcentaje_descuento'] = intval($datos['porcentaje_descuento']);
        if (isset($datos['enlace'])) $update['enlace'] = $datos['enlace'];
        if (isset($datos['imagen'])) $update['imagen'] = $datos['imagen'];
        if (isset($datos['categoria'])) {
            // Aceptar tanto string como array
            if (is_array($datos['categoria'])) {
                $update['categoria'] = $datos['categoria'];
            } else {
                $update['categoria'] = [$datos['categoria']]; // Convertir string a array
            }
        }
        if (isset($datos['fecha_inicio'])) $update['fecha_inicio'] = $datos['fecha_inicio'];
        if (isset($datos['fecha_fin'])) $update['fecha_fin'] = $datos['fecha_fin'];
        if (isset($datos['fuente'])) $update['fuente'] = $datos['fuente'];
        if (isset($datos['fuente_id'])) {
            try {
                $update['fuente_id'] = new MongoDB\BSON\ObjectId($datos['fuente_id']);
            } catch (Exception $e) {
                $update['fuente_id'] = null;
            }
        }
        if (isset($datos['mensaje_original_id'])) $update['mensaje_original_id'] = $datos['mensaje_original_id'];
        if (isset($datos['estado'])) $update['estado'] = intval($datos['estado']);
        if (isset($datos['texto_reescrito'])) $update['texto_reescrito'] = $datos['texto_reescrito'];

        $resultado = $collection->updateOne(
            ['_id' => $objectId],
            ['$set' => $update]
        );

        if ($resultado->getMatchedCount() > 0) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'Chollo no encontrado'];
        }
    } catch (Throwable $e) {
        error_log("Error al actualizar chollo: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno: ' . $e->getMessage()];
    }
}

/**
 * Elimina un chollo
 */
function eliminarChollo($id) {
    $collection = getCollectionChollos();
    if (!$collection) {
        return ['success' => false, 'error' => 'Error de conexión'];
    }

    try {
        try {
            $objectId = new MongoDB\BSON\ObjectId($id);
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'ID no válido'];
        }

        $resultado = $collection->deleteOne(['_id' => $objectId]);

        if ($resultado->getDeletedCount() > 0) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'Chollo no encontrado'];
        }
    } catch (Throwable $e) {
        error_log("Error al eliminar chollo: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno: ' . $e->getMessage()];
    }
}

/**
 * Obtiene categorías disponibles
 */
function obtenerCategoriasChollos() {
    return [
        'amazon' => 'Amazon',
        'electronica' => 'Electrónica',
        'moda' => 'Moda',
        'hogar' => 'Hogar',
        'deportes' => 'Deportes',
        'libros' => 'Libros',
        'videojuegos' => 'Videojuegos',
        'viajes' => 'Viajes',
        'general' => 'General'
    ];
}

/**
 * Obtiene estadísticas de chollos
 */
function obtenerEstadisticasChollos() {
    $collection = getCollectionChollos();
    if (!$collection) {
        return [];
    }

    try {
        $total = $collection->countDocuments([]);
        $activos = $collection->countDocuments(['estado' => 1]);
        $inactivos = $collection->countDocuments(['estado' => 0]);
        
        $por_categoria = [];
        $categorias = obtenerCategoriasChollos();
        foreach ($categorias as $key => $nombre) {
            $por_categoria[$key] = $collection->countDocuments(['categoria' => $key, 'estado' => 1]);
        }

        return [
            'total' => $total,
            'activos' => $activos,
            'inactivos' => $inactivos,
            'por_categoria' => $por_categoria
        ];
    } catch (Throwable $e) {
        error_log("Error al obtener estadísticas de chollos: " . $e->getMessage());
        return [];
    }
}

/**
 * Genera la URL acortada para un chollo
 */
function generarUrlAcortadaChollo($chollo_id) {
    return '/chollo/' . $chollo_id;
}

/**
 * Registra un click en un chollo con información detallada
 */
function registrarClickChollo($chollo_id, $datos_adicionales = []) {
    $collection = getCollectionChollos();
    if (!$collection) {
        return false;
    }

    try {
        $objectId = new MongoDB\BSON\ObjectId($chollo_id);
        
        // Bot Detection
        if (!function_exists('isBot')) {
            require_once __DIR__ . '/bot_detection.php';
        }
        if (isBot()) {
            return true; // Don't log, but pretend success
        }

        // Incrementar contador de clicks en el chollo
        $collection->updateOne(
            ['_id' => $objectId],
            ['$inc' => ['clicks' => 1]]
        );
        
        // Registrar click detallado en colección de historial
        $collection_historial = getCollectionHistorialChollos();
        if ($collection_historial) {
            $click_data = [
                'chollo_id' => $objectId,
                'fecha' => new MongoDB\BSON\UTCDateTime(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'referer' => $_SERVER['HTTP_REFERER'] ?? '',
                'url' => $_SERVER['REQUEST_URI'] ?? ''
            ];
            
            // Añadir datos adicionales si se proporcionan
            if (!empty($datos_adicionales)) {
                error_log("DEBUG registrarClickChollo: Datos adicionales antes de merge: " . json_encode($datos_adicionales));
                $click_data = array_merge($click_data, $datos_adicionales);
                error_log("DEBUG registrarClickChollo: Referer después de merge: " . ($click_data['referer'] ?? 'NULL'));
            } else {
                error_log("DEBUG registrarClickChollo: No datos adicionales");
            }
            
            // Añadir user_id si hay sesión
            if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
                try {
                    $click_data['user_id'] = new MongoDB\BSON\ObjectId($_SESSION['user_id']);
                } catch (Exception $e) {
                    // Ignorar si el ID no es válido
                }
            }
            
            $collection_historial->insertOne($click_data);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error al registrar click de chollo: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene la colección de historial de clicks de chollos
 */
function getCollectionHistorialChollos() {
    $db = createConnection();
    if (!$db) {
        return null;
    }

    try {
        $collection = $db->selectCollection('chollos_clicks');
        return $collection;
    } catch (Throwable $e) {
        error_log("Error al obtener colección de historial de chollos: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtiene estadísticas detalladas de clicks de un chollo
 */
function obtenerEstadisticasChollo($chollo_id) {
    $collection_historial = getCollectionHistorialChollos();
    if (!$collection_historial) {
        return [
            'total_clicks' => 0,
            'clicks_hoy' => 0,
            'clicks_semana' => 0,
            'clicks_mes' => 0,
            'clicks_por_dia' => [],
            'ultimos_clicks' => []
        ];
    }

    try {
        $objectId = new MongoDB\BSON\ObjectId($chollo_id);
        
        // Total de clicks
        $total_clicks = $collection_historial->countDocuments(['chollo_id' => $objectId]);
        
        // Clicks hoy
        $fecha_hoy = new MongoDB\BSON\UTCDateTime(strtotime('today') * 1000);
        $clicks_hoy = $collection_historial->countDocuments([
            'chollo_id' => $objectId,
            'fecha' => ['$gte' => $fecha_hoy]
        ]);
        
        // Clicks esta semana
        $fecha_semana = new MongoDB\BSON\UTCDateTime((time() - 7*24*60*60) * 1000);
        $clicks_semana = $collection_historial->countDocuments([
            'chollo_id' => $objectId,
            'fecha' => ['$gte' => $fecha_semana]
        ]);
        
        // Clicks este mes
        $fecha_mes = new MongoDB\BSON\UTCDateTime((time() - 30*24*60*60) * 1000);
        $clicks_mes = $collection_historial->countDocuments([
            'chollo_id' => $objectId,
            'fecha' => ['$gte' => $fecha_mes]
        ]);
        
        // Clicks por día (últimos 30 días)
        $fecha_30_dias = new MongoDB\BSON\UTCDateTime((time() - 30*24*60*60) * 1000);
        $pipeline = [
            ['$match' => [
                'chollo_id' => $objectId,
                'fecha' => ['$gte' => $fecha_30_dias]
            ]],
            ['$group' => [
                '_id' => [
                    '$dateToString' => [
                        'format' => '%Y-%m-%d',
                        'date' => '$fecha'
                    ]
                ],
                'clicks' => ['$sum' => 1]
            ]],
            ['$sort' => ['_id' => 1]]
        ];
        
        $clicks_por_dia = [];
        $result = $collection_historial->aggregate($pipeline);
        foreach ($result as $doc) {
            $clicks_por_dia[] = [
                'fecha' => $doc['_id'],
                'clicks' => $doc['clicks']
            ];
        }
        
        // Últimos clicks
        $ultimos_clicks = [];
        $ultimos = $collection_historial->find(
            ['chollo_id' => $objectId],
            ['sort' => ['fecha' => -1], 'limit' => 10]
        );
        
        foreach ($ultimos as $click) {
            $ultimos_clicks[] = [
                'fecha' => isset($click['fecha']) ? $click['fecha']->toDateTime()->format('Y-m-d H:i:s') : '',
                'ip' => $click['ip'] ?? 'unknown',
                'user_agent' => $click['user_agent'] ?? 'unknown',
                'referer' => $click['referer'] ?? ''
            ];
        }
        
        return [
            'total_clicks' => $total_clicks,
            'clicks_hoy' => $clicks_hoy,
            'clicks_semana' => $clicks_semana,
            'clicks_mes' => $clicks_mes,
            'clicks_por_dia' => $clicks_por_dia,
            'ultimos_clicks' => $ultimos_clicks
        ];
    } catch (Exception $e) {
        error_log("Error al obtener estadísticas de chollo: " . $e->getMessage());
        return [
            'total_clicks' => 0,
            'clicks_hoy' => 0,
            'clicks_semana' => 0,
            'clicks_mes' => 0,
            'clicks_por_dia' => [],
            'ultimos_clicks' => []
        ];
    }
}

/**
 * Renderiza el componente de votación para un chollo (Versión Premium Consistente)
 * @param array $chollo Datos del chollo
 * @param string $size 'small', 'medium', 'large' (mantenido por compatibilidad, pero usa estilo premium)
 * @return string HTML del componente de votación
 */
function renderCholloVoting($chollo, $size = 'medium') {
    $temperatura = $chollo['temperatura'] ?? 0;
    $chollo_id = $chollo['id'] ?? '';
    
    // Determinar color de temperatura
    $temp_color = '#fff'; // Default
    if ($temperatura >= 50) {
        $temp_color = '#ff5252';
    } elseif ($temperatura < 0) {
        $temp_color = '#81d4fa';
    }
    
    // Verificar si el usuario ha votado (esto requiere acceso a sesión o info pasada)
    // Para listados estáticos, se carga via JS si hay usuario logueado
    // Las clases 'active' se añadirán via JS
    
    $voto_up_class = '';
    $voto_down_class = '';
    
    return <<<HTML
    <div class="chollo-voting-premium" data-chollo-id="{$chollo_id}">
        <button class="vote-btn-premium vote-down-premium {$voto_down_class}" title="No me gusta">
            <i class="fas fa-arrow-down" aria-hidden="true"></i>
        </button>
        <div class="temp-premium" style="color: {$temp_color};">
            {$temperatura}°
        </div>
        <button class="vote-btn-premium vote-up-premium {$voto_up_class}" title="Me gusta">
            <i class="fas fa-arrow-up" aria-hidden="true"></i>
        </button>
    </div>
HTML;
}

/**
 * Renderiza el widget de chollos más calientes y populares (24h)
 * @return string HTML del widget
 */
function renderHotDealsWidget($categoria = null) {
    // Asegurar que las funciones de votos estén disponibles
    if (!function_exists('obtenerChollosMasCalientes24h')) {
        include_once __DIR__ . '/funciones_chollos_votos.php';
    }
    
    // Obtener datos iniciales (24h)
    $calientes = obtenerChollosMasCalientes24h(5, $categoria);
    $populares = obtenerChollosMasPopulares24h(5, $categoria);
    
    // Función auxiliar para renderizar items
    $renderItem = function($chollo, $tipo, $posicion = '') {
        $categoria = is_array($chollo['categoria']) ? $chollo['categoria'][0] : $chollo['categoria'];
        
        // Generar slug SEO-friendly
        if (!function_exists('categoriaToSlug')) {
            include_once __DIR__ . '/funciones_chollos_helpers.php';
        }
        $categoria_slug = categoriaToSlug($categoria);
        
        $url = 'https://www.malprecio.com/chollos/' . $categoria_slug . '/' . $chollo['id'];
        $imagen = $chollo['imagen'] ?: 'https://via.placeholder.com/80x80?text=Chollo';
        $precio = $chollo['precio_descuento'] ? number_format((float)$chollo['precio_descuento'], 2, ',', '.') . '€' : '';
        $titulo = htmlspecialchars($chollo['titulo']);
        
        $meta = '';
        if ($tipo === 'caliente') {
            $meta = '<span class="deal-badge hot"><i class="fas fa-fire"></i> ' . $chollo['temperatura'] . '°</span>';
        } else {
            $meta = '<span class="deal-badge popular"><i class="fas fa-eye"></i> ' . $chollo['clicks'] . '</span>';
        }
        
        $posicion_html = $posicion ? '<div class="deal-rank-badge">' . $posicion . 'º</div>' : '';
        
        return <<<HTML
        <a href="{$url}" class="deal-item-row">
            <div class="deal-item-image">
                <img src="{$imagen}" alt="{$titulo}" loading="lazy">
                {$posicion_html}
            </div>
            <div class="deal-item-content">
                <div class="deal-item-title">{$titulo}</div>
                <div class="deal-item-meta">
                    <span class="deal-item-price">{$precio}</span>
                    <div class="deal-meta-info">
                        {$meta}
                    </div>
                </div>
            </div>
        </a>
HTML;
    };
    
    // Si ambas listas están vacías, no mostrar el widget
    if (empty($calientes) && empty($populares)) {
        return '';
    }
    
    // Definir sufijo para títulos
    $suffix = $categoria ? " en " . htmlspecialchars($categoria) : "";
    $categoria_encoded = htmlspecialchars($categoria ?? '');

    $htmlCalientes = '';
    if (!empty($calientes)) {
        $itemsHtml = '';
        foreach ($calientes as $i => $c) {
            $itemsHtml .= $renderItem($c, 'caliente', $i + 1);
        }
        $htmlCalientes = <<<HTML
        <div class="deals-column" data-tipo="calientes" data-categoria="{$categoria_encoded}">
            <div class="deals-column-header">
                <div class="deals-tabs">
                    <button class="deal-tab active" data-periodo="24h">24h</button>
                    <button class="deal-tab" data-periodo="7d">7d</button>
                    <button class="deal-tab" data-periodo="30d">30d</button>
                </div>
                <h3><i class="fas fa-fire"></i> Los más calientes{$suffix}</h3>
            </div>
            <div class="deals-content">
                {$itemsHtml}
            </div>
        </div>
HTML;
    }
    
    $htmlPopulares = '';
    if (!empty($populares)) {
        $itemsHtml = '';
        foreach ($populares as $i => $c) {
            $itemsHtml .= $renderItem($c, 'popular', $i + 1);
        }
        $htmlPopulares = <<<HTML
        <div class="deals-column" data-tipo="populares" data-categoria="{$categoria_encoded}">
            <div class="deals-column-header">
                <div class="deals-tabs">
                    <button class="deal-tab active" data-periodo="24h">24h</button>
                    <button class="deal-tab" data-periodo="7d">7d</button>
                    <button class="deal-tab" data-periodo="30d">30d</button>
                </div>
                <h3><i class="fas fa-chart-line"></i> Los más populares{$suffix}</h3>
            </div>
            <div class="deals-content">
                {$itemsHtml}
            </div>
        </div>
HTML;
    }
    
    // Estilo dinámico para grid (si solo hay una columna, que ocupe todo)
    $gridStyle = '';
    if (empty($htmlCalientes) || empty($htmlPopulares)) {
        $gridStyle = 'grid-template-columns: 1fr;';
    }

    return <<<HTML
    <script src="/js/chollos-tabs.js"></script>
    <style>
        .deals-widget-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 40px;
            {$gridStyle}
        }
        
        .deals-column {
            background: #1E1E1E;
            border-radius: 12px;
            padding: 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            border: 1px solid #2a2a2a;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .deals-column-header {
            background: #252525;
            padding: 15px 20px;
            border-bottom: 1px solid #333;
        }
        
        .deals-tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 12px;
            background: rgba(255,255,255,0.05);
            padding: 3px;
            border-radius: 8px;
            width: fit-content;
        }
        
        .deal-tab {
            padding: 6px 15px;
            border: none;
            background: transparent;
            color: #aaa;
            font-weight: 600;
            font-size: 0.85em;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        
        .deal-tab:hover {
            color: #E30613;
        }
        
        .deal-tab.active {
            background: #E30613;
            color: white;
            box-shadow: 0 2px 6px rgba(227, 6, 19, 0.3);
        }
        
        .deals-column-header h3 {
            font-size: 1.15em;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #fff;
            font-weight: 700;
        }
        
        .deals-column-header h3 i {
            color: #E30613;
        }
        
        .deals-content {
            padding: 10px;
            flex-grow: 1;
        }
        
        .deal-item-row {
            display: flex;
            gap: 12px;
            padding: 10px;
            border-radius: 10px;
            transition: all 0.2s ease;
            text-decoration: none;
            color: #fff;
            margin-bottom: 5px;
            border: 1px solid transparent;
            background: rgba(255,255,255,0.02);
        }
        
        .deal-item-row:hover {
            background: #2a2a2a;
            transform: translateX(3px);
            border-color: #333;
        }
        
        .deal-item-image {
            width: 70px;
            height: 70px;
            flex-shrink: 0;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
            padding: 4px;
            position: relative;
        }
        
        .deal-item-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .deal-rank-badge {
            position: absolute;
            top: 0;
            left: 0;
            background: #E30613;
            color: white;
            font-size: 0.7em;
            font-weight: 800;
            padding: 2px 6px;
            border-bottom-right-radius: 6px;
            box-shadow: 1px 1px 3px rgba(0,0,0,0.3);
        }
        
        .deal-item-title {
            font-size: 0.9em;
            font-weight: 600;
            color: #fff !important;
            margin-bottom: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.3;
        }
        
        .deal-item-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.85em;
        }
        
        .deal-item-price {
            font-weight: 800;
            color: #E30613;
            font-size: 1.05em;
        }

        .deal-meta-info {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .deal-badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: 0.8em;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 10px;
        }
        
        .deal-badge.hot {
            color: #E30613;
            background: rgba(227, 6, 19, 0.1);
        }
        
        .deal-badge.popular {
            color: #0088cc;
            background: rgba(0, 136, 204, 0.1);
        }

        @media (max-width: 768px) {
            .deals-widget-container {
                grid-template-columns: 1fr;
                gap: 20px;
                padding: 0;
            }

            .deals-column-header {
                padding: 15px 18px;
            }

            .deals-content {
                padding: 15px;
            }

            .deal-item-row {
                padding: 12px;
                gap: 15px;
                margin-bottom: 10px;
            }
            
            .deal-item-image {
                width: 80px;
                height: 80px;
            }

            .deal-item-title {
                font-size: 0.95em;
                line-height: 1.4;
            }

            .deal-item-meta {
                font-size: 0.9em;
            }
        }
    </style>
    <div class="deals-widget-container">
        {$htmlCalientes}
        {$htmlPopulares}
    </div>
HTML;
}

/**
 * Renderiza la sección de comentarios para un chollo
 * @param string $chollo_id ID del chollo
 * @param array $usuario_actual Datos del usuario actual (opcional)
 * @return string HTML de la sección de comentarios
 */
function renderCholloComments($chollo_id, $usuario_actual = null) {
    $user_avatar = '';
    $user_placeholder = '';
    $username_display = 'Invitado';
    
    if ($usuario_actual) {
        $username_display = $usuario_actual['username'];
        if (!empty($usuario_actual['img'])) {
            $user_avatar = '<img src="' . htmlspecialchars($usuario_actual['img']) . '" alt="' . htmlspecialchars($usuario_actual['username']) . '" class="comment-user-avatar">';
        } else {
            $initials = substr($usuario_actual['username'], 0, 2);
            $user_placeholder = '<div class="comment-user-avatar-placeholder">' . strtoupper($initials) . '</div>';
        }
    } else {
        $user_placeholder = '<div class="comment-user-avatar-placeholder guest-avatar"><i class="fas fa-user"></i></div>';
    }
    
    $comment_form = <<<HTML
    <div class="comment-form-container collapsed" id="comment-form-wrapper">
        <div class="comment-form-avatar">
            {$user_avatar}{$user_placeholder}
        </div>
        <div class="comment-form-main">
            <form id="comment-form">
                <div class="comment-textarea-wrapper">
                    <textarea class="comment-textarea" placeholder="¿Qué tienes en mente?" required></textarea>
                    <div class="comment-form-toolbar">
                        <div class="toolbar-left">
                            <button type="button" class="toolbar-btn" title="Cita"><i class="fas fa-quote-right"></i></button>
                            <button type="button" class="toolbar-btn" title="Emoji"><i class="far fa-smile"></i></button>
                            <button type="button" class="toolbar-btn" title="Enlace"><i class="fas fa-link"></i></button>
                            <button type="button" class="toolbar-btn" title="Imagen"><i class="far fa-image"></i></button>
                        </div>
                        <div class="toolbar-right">
                            <button type="submit" class="comment-btn-submit" disabled>
                                <i class="fas fa-paper-plane"></i> Comentar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
HTML;
    
    return <<<HTML
    <div class="chollo-comments-section" id="comentarios">
        <div class="comments-header">
            <h2>
                <span class="comments-count">0 comentarios</span> 
                <span class="comments-sort-label">Ordenados por</span>
                <div class="comments-sort">
                    <select id="comments-sort">
                        <option value="nuevos" selected>Nuevos primero</option>
                        <option value="antiguos">Antiguos primero</option>
                        <option value="populares">Más populares</option>
                    </select>
                </div>
            </h2>
        </div>
        
        {$comment_form}
        
        <div id="comments-list" class="comments-list">
            <div class="comments-loading">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
        </div>
    </div>
HTML;
}

/**
 * Renderiza el widget compacto de chollos calientes para mostrar en todas las páginas
 * @return string HTML del widget compacto
 */
function renderCompactHotDealsWidget($categoria = null) {
    // Asegurar que las funciones de votos estén disponibles
    if (!function_exists('obtenerChollosMasCalientes24h')) {
        include_once __DIR__ . '/funciones_chollos_votos.php';
    }
    
    // Cache simple por sesión o tiempo si no hay categoría
    static $cached_data = null;
    static $cache_time = 0; // Keep cache_time for time-based invalidation
    
    // If $categoria is provided, or cache is empty, or cache is expired, re-fetch
    if ($cached_data === null || !empty($categoria) || (time() - $cache_time) > 600) {
        $cached_data = obtenerChollosMasCalientes24h(10, $categoria); // Changed limit to 10 as per instruction
        $cache_time = time();
    }
    
    $calientes = $cached_data;
    
    // Si no hay chollos, no mostrar nada
    if (empty($calientes)) {
        return '';
    }
    
    // Función auxiliar para renderizar items
    $renderItem = function($chollo) {
        $categoria = is_array($chollo['categoria']) ? $chollo['categoria'][0] : $chollo['categoria'];
        
        // Generar slug SEO-friendly
        if (!function_exists('categoriaToSlug')) {
            include_once __DIR__ . '/funciones_chollos_helpers.php';
        }
        $categoria_slug = categoriaToSlug($categoria);
        
        $url = 'https://www.malprecio.com/chollos/' . $categoria_slug . '/' . $chollo['id'];
        $imagen = $chollo['imagen'] ?: 'https://via.placeholder.com/60x60?text=Chollo';
        $precio = $chollo['precio_descuento'] ? number_format((float)$chollo['precio_descuento'], 2, ',', '.') . '€' : '';
        $titulo = htmlspecialchars($chollo['titulo']);
        $temperatura = $chollo['temperatura'] ?? 0;
        $descuento = $chollo['porcentaje_descuento'] ?? 0;
        
        // Clase de temperatura
        $temp_class = $temperatura >= 100 ? 'super-hot' : ($temperatura >= 50 ? 'hot' : 'warm');
        
        // Badge de descuento
        $discount_badge = ($descuento > 0) ? '<div class="hot-deal-discount">-' . $descuento . '%</div>' : '';
        
        return <<<HTML
        <a href="{$url}" class="hot-deal-item" data-chollo-id="{$chollo['id']}">
            <div class="hot-deal-image">
                <img src="{$imagen}" alt="{$titulo}" loading="lazy">
                {$discount_badge}
            </div>
            <div class="hot-deal-content">
                <div class="hot-deal-title">{$titulo}</div>
                <div class="hot-deal-meta">
                    <span class="hot-deal-temp {$temp_class}">
                        <i class="fas fa-fire"></i> {$temperatura}°
                    </span>
                    <span class="hot-deal-price">{$precio}</span>
                </div>
            </div>
        </a>
HTML;
    };
    
    // Construir lista HTML
    $htmlItems = '';
    foreach ($calientes as $c) {
        $htmlItems .= $renderItem($c);
    }
    
    return <<<HTML
    <style>
        .hot-deals-widget-global {
            position: relative;
            z-index: 10;
            background: linear-gradient(135deg, rgba(227, 6, 19, 0.95) 0%, rgba(255, 140, 66, 0.95) 100%);
            backdrop-filter: blur(10px);
            border-bottom: 3px solid rgba(227, 6, 19, 0.3);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            margin-bottom: 30px;
            margin-top: 20px;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .hot-deals-widget-global.minimized {
            transform: translateY(-100%);
            opacity: 0;
            pointer-events: none;
            max-height: 0;
            margin: 0;
            padding: 0;
            border: none;
        }
        
        .hot-deals-widget-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .hot-deals-header {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            font-weight: 700;
            font-size: 1.1em;
            white-space: nowrap;
            flex-shrink: 0;
        }
        
        .hot-deals-header i {
            font-size: 1.3em;
            animation: pulse-fire 1.5s ease-in-out infinite;
        }
        
        @keyframes pulse-fire {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        
        .hot-deals-list {
            display: flex;
            flex: 1;
            overflow-x: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
        }
        
        .hot-deals-list::-webkit-scrollbar {
            height: 6px;
        }
        
        .hot-deals-list::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }
        
        .hot-deals-list::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }
        
        .hot-deals-list::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
        
        .hot-deal-item {
            display: flex;
            gap: 12px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            padding: 10px;
            min-width: 280px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .hot-deal-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.2);
            border-color: rgba(227, 6, 19, 0.5);
            text-decoration: none;
        }
        
        .hot-deal-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
            background: #f8f9fa;
            position: relative;
        }
 
        .hot-deal-discount {
            position: absolute;
            top: 2px;
            right: 2px;
            background: #E30613;
            color: white;
            font-size: 0.7em;
            font-weight: 800;
            padding: 1px 4px;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .hot-deal-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .hot-deal-content {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 5px;
        }
        
        .hot-deal-title {
            font-size: 0.9em;
            font-weight: 600;
            color: #333;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.3;
        }
        
        .hot-deal-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.85em;
            font-weight: 700;
        }
 
        .hot-deal-price {
            color: #E30613;
        }
        
        .hot-deal-temp {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 800;
        }
        
        .hot-deal-temp.super-hot {
            background: linear-gradient(135deg, #ff0000, #E30613);
            color: white;
            box-shadow: 0 2px 8px rgba(255, 0, 0, 0.3);
        }
        
        .hot-deal-temp.hot {
            background: linear-gradient(135deg, #E30613, #E30613);
            color: white;
            box-shadow: 0 2px 8px rgba(227, 6, 19, 0.3);
        }
        
        .hot-deal-temp.warm {
            background: linear-gradient(135deg, #E30613, #ffcc00);
            color: #333;
        }
        
        .hot-deal-temp i {
            font-size: 1.1em;
        }
        
        .hot-deals-toggle {
            display: none;
        }
        
        .hot-deals-toggle:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }
        
        .hot-deals-toggle i {
            font-size: 1.1em;
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            .hot-deals-widget-global {
                margin: 15px 10px;
            }
            
            .hot-deals-widget-container {
                padding: 8px 12px;
                gap: 8px;
            }
            
            .hot-deals-header {
                font-size: 0.85em;
            }
            
            .hot-deals-header span {
                display: none;
            }
            
            .hot-deal-item {
                min-width: 220px;
                flex-direction: row;
                gap: 8px;
                padding: 6px;
                background: white;
            }
            
            .hot-deal-image {
                width: 50px;
                height: 50px;
                border-radius: 6px;
            }
            
            .hot-deal-title {
                font-size: 0.82em;
                -webkit-line-clamp: 2;
                line-height: 1.2;
            }
            
            .hot-deal-meta {
                gap: 4px;
                font-size: 0.8em;
            }
 
            .hot-deal-temp {
                padding: 1px 6px;
                font-size: 0.9em;
            }
        }
        
        @media (max-width: 480px) {
            .hot-deals-list {
                gap: 8px;
            }
            
            .hot-deal-item {
                min-width: 200px;
            }
            
            .hot-deals-header i {
                font-size: 1.1em;
            }
 
            .hot-deals-toggle {
                width: 30px;
                height: 30px;
            }
        }
    </style>
    
    <div class="hot-deals-widget-global" id="hotDealsWidget">
        <div class="hot-deals-widget-container">
            <div class="hot-deals-header">
                <i class="fas fa-fire"></i>
                <span>TOP CHOLLOS</span>
            </div>
            <div class="hot-deals-list" id="hotDealsList">
                {$htmlItems}
            </div>
            <button class="hot-deals-toggle" id="hotDealsToggle" title="Minimizar">
                <i class="fas fa-chevron-up"></i>
            </button>
        </div>
    </div>
    
    <script>
    (function() {
        // Track clicks en chollos
        const dealItems = document.querySelectorAll('.hot-deal-item');
        dealItems.forEach(function(item) {
            item.addEventListener('click', function() {
                const cholloId = this.getAttribute('data-chollo-id');
                // Registrar click (opcional)
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'click', {
                        'event_category': 'Hot Deals Widget',
                        'event_label': cholloId
                    });
                }
            });
        });
    })();
    </script>
HTML;
}

/**
 * Renderiza el slider de social proof con comentarios recientes
 * Crea un efecto de marquee con los últimos comentarios para mostrar actividad
 * @param int $limite Número de comentarios a obtener
 * @return string HTML del slider
 */
function renderSocialProofSlider($limite = 12) {
    // Asegurar funciones de comentarios disponibles
    if (!function_exists('obtenerUltimosComentariosGlobales')) {
        include_once __DIR__ . '/funciones_chollos_comentarios.php';
    }
    if (!function_exists('categoriaToSlug')) {
        include_once __DIR__ . '/funciones_chollos_helpers.php';
    }
    
    $comentarios = obtenerUltimosComentariosGlobales($limite);
    
    if (empty($comentarios)) {
        return '';
    }
    
    // Función para tiempo relativo
    $tiempoRelativo = function($fecha) {
        $ahora = new DateTime();
        $comentarioFecha = new DateTime($fecha);
        $diff = $ahora->diff($comentarioFecha);
        
        if ($diff->d > 0) {
            return $diff->d == 1 ? 'hace 1 día' : 'hace ' . $diff->d . ' días';
        } elseif ($diff->h > 0) {
            return $diff->h == 1 ? 'hace 1 hora' : 'hace ' . $diff->h . ' horas';
        } elseif ($diff->i > 0) {
            return $diff->i == 1 ? 'hace 1 min' : 'hace ' . $diff->i . ' min';
        } else {
            return 'ahora mismo';
        }
    };
    
    // Generar tarjetas HTML
    $cardsHtml = '';
    foreach ($comentarios as $c) {
        $nombre = htmlspecialchars($c['usuario_nombre']);
        $avatar = !empty($c['usuario_img']) ? $c['usuario_img'] : 'https://ui-avatars.com/api/?name=' . urlencode($c['usuario_nombre']) . '&background=random&size=40';
        $texto = htmlspecialchars(mb_substr($c['comentario'], 0, 80) . (mb_strlen($c['comentario']) > 80 ? '...' : ''));
        $chollo = htmlspecialchars(mb_substr($c['chollo_titulo'], 0, 40) . (mb_strlen($c['chollo_titulo']) > 40 ? '...' : ''));
        $tiempo = $tiempoRelativo($c['fecha']);
        $categoria_slug = categoriaToSlug($c['chollo_categoria']);
        $url = 'https://www.malprecio.com/chollos/' . $categoria_slug . '/' . $c['chollo_id'];
        
        $cardsHtml .= <<<HTML
        <a href="{$url}" class="sp-card">
            <div class="sp-card-header">
                <img src="{$avatar}" alt="{$nombre}" class="sp-avatar" onerror="this.src='https://ui-avatars.com/api/?name={$nombre}&background=random&size=40'">
                <div class="sp-user-info">
                    <span class="sp-username">{$nombre}</span>
                    <span class="sp-time">{$tiempo}</span>
                </div>
            </div>
            <div class="sp-comment">"{$texto}"</div>
            <div class="sp-chollo">
                <i class="fas fa-tag"></i> {$chollo}
            </div>
        </a>
HTML;
    }
    
    // Duplicar para efecto infinito
    $duplicatedCards = $cardsHtml . $cardsHtml;
    
    return <<<HTML
    <style>
        .social-proof-slider {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border-radius: 16px;
            padding: 20px 0;
            margin: 30px 0;
            overflow: hidden;
            position: relative;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        
        .sp-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 20px;
            padding: 0 20px;
        }
        
        .sp-header h3 {
            margin: 0;
            color: #fff;
            font-size: 1.2em;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sp-header i {
            color: #E30613;
            animation: pulse-icon 2s ease-in-out infinite;
        }
        
        .sp-live-badge {
            background: #E30613;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75em;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
            animation: pulse-badge 2s ease-in-out infinite;
        }
        
        .sp-live-dot {
            width: 8px;
            height: 8px;
            background: #fff;
            border-radius: 50%;
            animation: blink 1s ease-in-out infinite;
        }
        
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        
        @keyframes pulse-icon {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.15); }
        }
        
        @keyframes pulse-badge {
            0%, 100% { box-shadow: 0 0 0 0 rgba(227, 6, 19, 0.4); }
            50% { box-shadow: 0 0 0 8px rgba(227, 6, 19, 0); }
        }
        
        .sp-track-wrapper {
            position: relative;
            overflow: hidden;
        }
        
        .sp-track-wrapper::before,
        .sp-track-wrapper::after {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            width: 80px;
            z-index: 10;
            pointer-events: none;
        }
        
        .sp-track-wrapper::before {
            left: 0;
            background: linear-gradient(to right, #1a1a2e 0%, transparent 100%);
        }
        
        .sp-track-wrapper::after {
            right: 0;
            background: linear-gradient(to left, #16213e 0%, transparent 100%);
        }
        
        .sp-track {
            display: flex;
            gap: 20px;
            animation: scroll-left 40s linear infinite;
            width: fit-content;
        }
        
        .sp-track:hover {
            animation-play-state: paused;
        }
        
        @keyframes scroll-left {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        
        .sp-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 16px;
            min-width: 280px;
            max-width: 280px;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            gap: 12px;
            backdrop-filter: blur(10px);
        }
        
        .sp-card:hover {
            background: rgba(255,255,255,0.1);
            transform: translateY(-5px) scale(1.02);
            border-color: #E30613;
            box-shadow: 0 10px 30px rgba(227, 6, 19, 0.2);
        }
        
        .sp-card-header {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sp-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(227, 6, 19, 0.5);
        }
        
        .sp-user-info {
            display: flex;
            flex-direction: column;
        }
        
        .sp-username {
            color: #fff;
            font-weight: 600;
            font-size: 0.9em;
        }
        
        .sp-time {
            color: rgba(255,255,255,0.5);
            font-size: 0.75em;
        }
        
        .sp-comment {
            color: rgba(255,255,255,0.8);
            font-size: 0.9em;
            line-height: 1.4;
            font-style: italic;
        }
        
        .sp-chollo {
            color: #E30613;
            font-size: 0.8em;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            padding-top: 8px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .sp-chollo i {
            font-size: 0.9em;
        }
        
        @media (max-width: 768px) {
            .social-proof-slider {
                margin: 20px -10px;
                border-radius: 0;
            }
            
            .sp-header h3 {
                font-size: 1em;
            }
            
            .sp-card {
                min-width: 250px;
                max-width: 250px;
                padding: 14px;
            }
            
            .sp-track-wrapper::before,
            .sp-track-wrapper::after {
                width: 40px;
            }
        }
    </style>
    
    <div class="social-proof-slider">
        <div class="sp-header">
            <h3>
                <i class="far fa-comments"></i>
                La comunidad está hablando
            </h3>
            <span class="sp-live-badge">
                <span class="sp-live-dot"></span>
                EN VIVO
            </span>
        </div>
        
        <div class="sp-track-wrapper">
            <div class="sp-track">
                {$duplicatedCards}
            </div>
        </div>
    </div>
HTML;
}

/**
 * Imprime un grid de chollos con el diseño de tarjetas moderno
 * @param array $chollos Array de chollos a mostrar
 * @param int $columnas Número de columnas (para clase CSS)
 */
function imprimir_grid_chollos($chollos, $columnas = 3) {
    if (empty($chollos)) {
        echo '<p style="text-align: center; color: #888; padding: 40px;">No hay chollos disponibles.</p>';
        return;
    }
    
    // Asegurar funciones helper disponibles
    if (!function_exists('categoriaToSlug')) {
        include_once __DIR__ . '/funciones_chollos_helpers.php';
    }
    
    $grid_class = 'chollos-grid';
    if ($columnas >= 3) {
        $grid_class .= ' chollos-grid-3';
    }
    
    echo '<div class="' . $grid_class . '">';
    
    foreach ($chollos as $chollo) {
        // Obtener datos del chollo
        $id = $chollo['id'] ?? '';
        $titulo = $chollo['titulo'] ?? 'Sin título';
        $descripcion = $chollo['descripcion'] ?? '';
        $imagen = $chollo['imagen'] ?? '';
        $precio_original = $chollo['precio_original'] ?? null;
        $precio_descuento = $chollo['precio_descuento'] ?? null;
        $porcentaje_descuento = $chollo['porcentaje_descuento'] ?? null;
        $enlace = $chollo['enlace'] ?? '#';
        $clicks = $chollo['clicks'] ?? 0;
        $temperatura = $chollo['temperatura'] ?? 0;
        $fecha_creacion = $chollo['fecha_creacion'] ?? '';
        
        // Manejar categoría (puede ser string o array)
        $categoria = $chollo['categoria'] ?? 'general';
        if (is_array($categoria)) {
            $categoria_slug = categoriaToSlug($categoria[0] ?? 'general');
        } else {
            $categoria_slug = categoriaToSlug($categoria);
        }
        
        // URL del detalle del chollo
        $url_detalle = 'https://www.malprecio.com/chollos/' . $categoria_slug . '/' . $id;
        
        // Formatear fecha
        $fecha_formateada = '';
        if ($fecha_creacion) {
            $timestamp = strtotime($fecha_creacion);
            if ($timestamp) {
                $ahora = time();
                $diff = $ahora - $timestamp;
                
                if ($diff < 3600) {
                    $mins = floor($diff / 60);
                    $fecha_formateada = 'Hace ' . ($mins > 0 ? $mins . ' min' : 'ahora');
                } elseif ($diff < 86400) {
                    $horas = floor($diff / 3600);
                    $fecha_formateada = 'Hace ' . $horas . ' h';
                } else {
                    $dias = floor($diff / 86400);
                    $fecha_formateada = $dias === 1 ? 'Ayer' : 'Hace ' . $dias . ' días';
                }
            }
        }
        
        // Color de temperatura
        $temp_color = '#888';
        if ($temperatura >= 100) {
            $temp_color = '#ff5252';
        } elseif ($temperatura >= 50) {
            $temp_color = '#ff9800';
        } elseif ($temperatura > 0) {
            $temp_color = '#4caf50';
        } elseif ($temperatura < 0) {
            $temp_color = '#81d4fa';
        }
        
        ?>
        <div class="chollo-card" data-chollo-id="<?php echo htmlspecialchars($id); ?>">
            <a href="<?php echo htmlspecialchars($url_detalle); ?>" class="chollo-image-link">
                <div class="chollo-image">
                    <?php if (!empty($imagen)): ?>
                        <img src="<?php echo htmlspecialchars($imagen); ?>" 
                             alt="<?php echo htmlspecialchars($titulo); ?>"
                             loading="lazy"
                             onerror="this.src='/img/no-image-placeholder.png'">
                    <?php else: ?>
                        <img src="/img/no-image-placeholder.png" alt="Sin imagen">
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($porcentaje_descuento) && $porcentaje_descuento > 0): ?>
                    <span class="chollo-badge">-<?php echo intval($porcentaje_descuento); ?>%</span>
                <?php elseif ($temperatura >= 100): ?>
                    <span class="chollo-badge chollo-badge-hot"><i class="fas fa-fire"></i> HOT</span>
                <?php endif; ?>
            </a>
            
            <div class="chollo-content">
                <!-- Voting Widget -->
                <?php 
                if (function_exists('renderCholloVoting')) {
                    echo '<div class="chollo-voting-wrapper">' . renderCholloVoting($chollo, 'small') . '</div>';
                }
                ?>
                
                <!-- Title -->
                <a href="<?php echo htmlspecialchars($url_detalle); ?>" class="chollo-title-link">
                    <h3 class="chollo-title"><?php echo htmlspecialchars($titulo); ?></h3>
                </a>
                
                <!-- Description (truncated) -->
                <?php if (!empty($descripcion)): ?>
                    <p class="chollo-description"><?php echo htmlspecialchars(mb_substr($descripcion, 0, 120)) . (mb_strlen($descripcion) > 120 ? '...' : ''); ?></p>
                <?php endif; ?>
                
                <!-- Prices -->
                <div class="chollo-prices">
                    <?php if ($precio_descuento !== null): ?>
                        <span class="chollo-price-discount"><?php echo number_format($precio_descuento, 2, ',', '.'); ?>€</span>
                    <?php endif; ?>
                    
                    <?php if ($precio_original !== null && $precio_original > $precio_descuento): ?>
                        <span class="chollo-price-original"><?php echo number_format($precio_original, 2, ',', '.'); ?>€</span>
                    <?php endif; ?>
                </div>
                
                <!-- Meta -->
                <div class="chollo-meta">
                    <?php if ($clicks > 0): ?>
                        <span class="chollo-meta-item">
                            <i class="fas fa-eye"></i>
                            <span class="chollo-clicks"><?php echo number_format($clicks); ?></span>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($fecha_formateada): ?>
                        <span class="chollo-meta-item">
                            <i class="fas fa-clock"></i>
                            <span class="chollo-date"><?php echo $fecha_formateada; ?></span>
                        </span>
                    <?php endif; ?>
                </div>
                
                <!-- Buttons -->
                <div class="chollo-buttons">
                    <a href="<?php echo htmlspecialchars($enlace); ?>" 
                       class="chollo-button chollo-button-primary" 
                       target="_blank" 
                       rel="nofollow noopener"
                       onclick="if(typeof registrarClickChollo === 'function') registrarClickChollo('<?php echo $id; ?>')">
                        <i class="fas fa-external-link-alt"></i> Ir a Oferta
                    </a>
                    <a href="<?php echo htmlspecialchars($url_detalle); ?>" class="chollo-button chollo-button-secondary">
                        <i class="fas fa-info-circle"></i> Ver Más
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    
    echo '</div>';
}

/**
 * Genera paginación moderna estilo Google
 */
if (!function_exists('generate_modern_pagination')) {
function generate_modern_pagination($total_items, $current_page, $items_per_page, $base = 'chollos') {
    $total_pages = ceil($total_items / $items_per_page);
    
    if ($total_pages <= 1) {
        return '';
    }
    
    $html = '<nav class="pagination-modern" style="display: flex; justify-content: center; gap: 8px; margin: 40px 0; flex-wrap: wrap;">';
    
    // Construir URL base preservando parámetros GET
    $url_params = $_GET;
    unset($url_params['page']);
    $query_string = http_build_query($url_params);
    $base_url = '/' . $base . ($query_string ? '?' . $query_string . '&' : '?');
    
    // Botón anterior
    if ($current_page > 1) {
        $html .= '<a href="' . $base_url . 'page=' . ($current_page - 1) . '" class="page-link" style="padding: 10px 16px; background: #2a2a2a; color: #fff; border-radius: 8px; text-decoration: none; border: 1px solid #444;">
            <i class="fas fa-chevron-left"></i> Anterior
        </a>';
    }
    
    // Números de página
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    if ($start > 1) {
        $html .= '<a href="' . $base_url . 'page=1" class="page-link" style="padding: 10px 14px; background: #2a2a2a; color: #fff; border-radius: 8px; text-decoration: none; border: 1px solid #444;">1</a>';
        if ($start > 2) {
            $html .= '<span style="padding: 10px; color: #888;">...</span>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $is_active = ($i == $current_page);
        $style = $is_active 
            ? 'padding: 10px 14px; background: #E30613; color: #fff; border-radius: 8px; text-decoration: none; font-weight: 700;'
            : 'padding: 10px 14px; background: #2a2a2a; color: #fff; border-radius: 8px; text-decoration: none; border: 1px solid #444;';
        
        $html .= '<a href="' . $base_url . 'page=' . $i . '" class="page-link" style="' . $style . '">' . $i . '</a>';
    }
    
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            $html .= '<span style="padding: 10px; color: #888;">...</span>';
        }
        $html .= '<a href="' . $base_url . 'page=' . $total_pages . '" class="page-link" style="padding: 10px 14px; background: #2a2a2a; color: #fff; border-radius: 8px; text-decoration: none; border: 1px solid #444;">' . $total_pages . '</a>';
    }
    
    // Botón siguiente
    if ($current_page < $total_pages) {
        $html .= '<a href="' . $base_url . 'page=' . ($current_page + 1) . '" class="page-link" style="padding: 10px 16px; background: #2a2a2a; color: #fff; border-radius: 8px; text-decoration: none; border: 1px solid #444;">
            Siguiente <i class="fas fa-chevron-right"></i>
        </a>';
    }
    
    $html .= '</nav>';
    
    return $html;
}
} // end function_exists('generate_modern_pagination') guard

/**
 * Footer simple para Malprecio
 */
function get_footer_malprecio() {
    ?>
    <footer style="background: #1a1a1a; color: #888; padding: 40px 20px; text-align: center; border-top: 1px solid #333; margin-top: 50px;">
        <div style="max-width: 1200px; margin: 0 auto;">
            <div style="margin-bottom: 20px;">
                <a href="/" style="font-size: 1.5rem; font-weight: 800; color: #E30613; text-decoration: none;">MalPrecio</a>
            </div>
            <p>&copy; <?php echo date('Y'); ?> MalPrecio.com - Todos los derechos reservados.</p>
            <div style="margin-top: 20px; display: flex; justify-content: center; gap: 20px;">
                <a href="/privacidad" style="color: inherit; text-decoration: none;">Privacidad</a>
                <a href="/legal" style="color: inherit; text-decoration: none;">Aviso Legal</a>
                <a href="/contacto" style="color: inherit; text-decoration: none;">Contacto</a>
            </div>
        </div>
    </footer>
    </body>
    </html>
    <?php
}
