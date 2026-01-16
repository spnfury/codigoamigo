<?php

/**
 * FUNCIONES PARA GESTIÓN DE SUSCRIPCIONES PREMIUM Y PROMOCIONES
 */

// Incluir funciones de conexión a MongoDB
if (!function_exists('createConnection')) {
    include_once __DIR__ . '/funciones.php';
}

if (!function_exists('getCollectionUsuarios')) {
    include_once __DIR__ . '/funciones_usuario.php';
}

/******************************************************
 *  COLECCIONES
 * ***************************************************/

function getCollectionPromociones() {
    $db = createConnection();
    if (!$db) {
        return null;
    }

    try {
        $collection = $db->selectCollection('promociones_codigos');
        return $collection;
    } catch (Throwable $e) {
        error_log("Error al obtener colección de promociones: " . $e->getMessage());
        return null;
    }
}

/******************************************************
 *  FUNCIONES DE SUSCRIPCIÓN PREMIUM
 * ***************************************************/

/**
 * Verifica si un usuario tiene suscripción premium activa
 */
function esUsuarioPremium($usuario_id) {
    if (empty($usuario_id)) {
        return false;
    }

    try {
        $collection = getCollectionUsuarios();
        if (!$collection) {
            return false;
        }

        $usuario_id_obj = is_string($usuario_id) ? new \MongoDB\BSON\ObjectId($usuario_id) : $usuario_id;
        
        $usuario = $collection->findOne(['_id' => $usuario_id_obj]);
        
        if (!$usuario) {
            return false;
        }

        // Verificar si tiene suscripción premium activa
        if (isset($usuario['suscripcion_premium']) && is_array($usuario['suscripcion_premium'])) {
            $suscripcion = $usuario['suscripcion_premium'];
            
            // Verificar que esté activa
            if (isset($suscripcion['activa']) && $suscripcion['activa'] === true) {
                // Verificar que no haya expirado
                if (isset($suscripcion['fecha_vencimiento'])) {
                    $fecha_vencimiento = $suscripcion['fecha_vencimiento'];
                    
                    // Si es UTCDateTime, convertir a timestamp
                    if ($fecha_vencimiento instanceof \MongoDB\BSON\UTCDateTime) {
                        $fecha_vencimiento_ts = $fecha_vencimiento->toDateTime()->getTimestamp();
                    } else {
                        $fecha_vencimiento_ts = is_numeric($fecha_vencimiento) ? $fecha_vencimiento : strtotime($fecha_vencimiento);
                    }
                    
                    // Si la fecha de vencimiento es futura, está activa
                    return $fecha_vencimiento_ts > time();
                }
                
                // Si no tiene fecha de vencimiento pero está marcada como activa, considerar activa
                return true;
            }
        }

        // Compatibilidad con campo antiguo pro_user
        if (isset($usuario['pro_user']) && $usuario['pro_user'] == 1) {
            return true;
        }

        return false;
    } catch (Throwable $e) {
        error_log("Error al verificar usuario premium: " . $e->getMessage());
        return false;
    }
}

/**
 * Activa la suscripción premium de un usuario
 */
function activarSuscripcionPremium($usuario_id, $stripe_subscription_id, $meses = 1) {
    if (empty($usuario_id) || empty($stripe_subscription_id)) {
        return false;
    }

    try {
        $collection = getCollectionUsuarios();
        if (!$collection) {
            return false;
        }

        $usuario_id_obj = is_string($usuario_id) ? new \MongoDB\BSON\ObjectId($usuario_id) : $usuario_id;
        
        $fecha_inicio = new \MongoDB\BSON\UTCDateTime();
        $fecha_vencimiento = new \MongoDB\BSON\UTCDateTime((time() + ($meses * 30 * 24 * 60 * 60)) * 1000);

        $updateResult = $collection->updateOne(
            ['_id' => $usuario_id_obj],
            [
                '$set' => [
                    'suscripcion_premium' => [
                        'activa' => true,
                        'fecha_inicio' => $fecha_inicio,
                        'fecha_vencimiento' => $fecha_vencimiento,
                        'stripe_subscription_id' => $stripe_subscription_id,
                        'precio_mensual' => 40
                    ],
                    'pro_user' => 1 // Mantener compatibilidad
                ]
            ]
        );

        return $updateResult->getModifiedCount() > 0 || $updateResult->getMatchedCount() > 0;
    } catch (Throwable $e) {
        error_log("Error al activar suscripción premium: " . $e->getMessage());
        return false;
    }
}

/**
 * Desactiva la suscripción premium de un usuario
 */
function desactivarSuscripcionPremium($usuario_id) {
    if (empty($usuario_id)) {
        return false;
    }

    try {
        $collection = getCollectionUsuarios();
        if (!$collection) {
            return false;
        }

        $usuario_id_obj = is_string($usuario_id) ? new \MongoDB\BSON\ObjectId($usuario_id) : $usuario_id;
        
        $updateResult = $collection->updateOne(
            ['_id' => $usuario_id_obj],
            [
                '$set' => [
                    'suscripcion_premium.activa' => false,
                    'pro_user' => 0
                ]
            ]
        );

        return $updateResult->getModifiedCount() > 0 || $updateResult->getMatchedCount() > 0;
    } catch (Throwable $e) {
        error_log("Error al desactivar suscripción premium: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene la información de suscripción premium de un usuario
 */
function obtenerSuscripcionPremium($usuario_id) {
    if (empty($usuario_id)) {
        return null;
    }

    try {
        $collection = getCollectionUsuarios();
        if (!$collection) {
            return null;
        }

        $usuario_id_obj = is_string($usuario_id) ? new \MongoDB\BSON\ObjectId($usuario_id) : $usuario_id;
        
        $usuario = $collection->findOne(
            ['_id' => $usuario_id_obj],
            ['projection' => ['suscripcion_premium' => 1]]
        );

        return $usuario['suscripcion_premium'] ?? null;
    } catch (Throwable $e) {
        error_log("Error al obtener suscripción premium: " . $e->getMessage());
        return null;
    }
}

/******************************************************
 *  FUNCIONES DE PROMOCIONES
 * ***************************************************/

/**
 * Obtiene la promoción activa de un código
 */
function obtenerPromocionActiva($codigo_id) {
    if (empty($codigo_id)) {
        return null;
    }

    try {
        $collection = getCollectionPromociones();
        if (!$collection) {
            return null;
        }

        $codigo_id_obj = is_string($codigo_id) ? new \MongoDB\BSON\ObjectId($codigo_id) : $codigo_id;
        $ahora = new \MongoDB\BSON\UTCDateTime();

        $promocion = $collection->findOne([
            'codigo_id' => $codigo_id_obj,
            'activa' => true,
            'fecha_inicio' => ['$lte' => $ahora],
            'fecha_fin' => ['$gte' => $ahora]
        ], ['sort' => ['fecha_creacion' => -1]]);

        return $promocion;
    } catch (Throwable $e) {
        error_log("Error al obtener promoción activa: " . $e->getMessage());
        return null;
    }
}

/**
 * Crea una promoción temporal para un código
 */
function crearPromocionCodigo($codigo_id, $precio_promocional, $fecha_fin, $usuario_id) {
    if (empty($codigo_id) || empty($precio_promocional) || empty($fecha_fin) || empty($usuario_id)) {
        return ['success' => false, 'error' => 'Datos incompletos'];
    }

    try {

        // Obtener el código para validar que pertenece al usuario
        if (!function_exists('getCodeByID')) {
            include_once __DIR__ . '/funciones.php';
        }

        $codigo_id_obj = is_string($codigo_id) ? new \MongoDB\BSON\ObjectId($codigo_id) : $codigo_id;
        $codigo = getCodeByID($codigo_id_obj);

        if (!$codigo) {
            return ['success' => false, 'error' => 'Código no encontrado'];
        }

        // Validar que el código pertenece al usuario
        $codigo_user_id = is_object($codigo['id_usuario']) ? (string)$codigo['id_usuario'] : $codigo['id_usuario'];
        $usuario_id_str = is_object($usuario_id) ? (string)$usuario_id : $usuario_id;

        if ($codigo_user_id !== $usuario_id_str) {
            return ['success' => false, 'error' => 'No tienes permiso para crear promociones en este código'];
        }

        // Validar que no existe una promoción activa
        $promocion_existente = obtenerPromocionActiva($codigo_id);
        if ($promocion_existente) {
            return ['success' => false, 'error' => 'Ya existe una promoción activa para este código'];
        }

        // Validar que el precio promocional es menor que el original
        $precio_original = floatval($codigo['num_beneficio'] ?? 0);
        $precio_promocional_float = floatval($precio_promocional);

        if ($precio_promocional_float >= $precio_original) {
            return ['success' => false, 'error' => 'El precio promocional debe ser menor que el precio original'];
        }

        // Validar fecha de fin
        $fecha_fin_ts = is_numeric($fecha_fin) ? $fecha_fin : strtotime($fecha_fin);
        if ($fecha_fin_ts <= time()) {
            return ['success' => false, 'error' => 'La fecha de fin debe ser futura'];
        }

        // Crear la promoción
        $collection = getCollectionPromociones();
        if (!$collection) {
            return ['success' => false, 'error' => 'Error de conexión'];
        }

        $usuario_id_obj = is_string($usuario_id) ? new \MongoDB\BSON\ObjectId($usuario_id) : $usuario_id;

        $promocion = [
            'codigo_id' => $codigo_id_obj,
            'usuario_id' => $usuario_id_obj,
            'precio_original' => $precio_original,
            'precio_promocional' => $precio_promocional_float,
            'tipo_beneficio' => $codigo['tipo_descuento'] ?? 'euros',
            'fecha_inicio' => new \MongoDB\BSON\UTCDateTime(),
            'fecha_fin' => new \MongoDB\BSON\UTCDateTime($fecha_fin_ts * 1000),
            'activa' => true,
            'fecha_creacion' => new \MongoDB\BSON\UTCDateTime()
        ];

        $result = $collection->insertOne($promocion);

        if ($result->getInsertedId()) {
            return ['success' => true, 'promocion_id' => (string)$result->getInsertedId()];
        }

        return ['success' => false, 'error' => 'Error al crear la promoción'];
    } catch (Throwable $e) {
        error_log("Error al crear promoción: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno: ' . $e->getMessage()];
    }
}

/**
 * Obtiene todas las promociones de un usuario
 */
function obtenerPromocionesUsuario($usuario_id, $solo_activas = false) {
    if (empty($usuario_id)) {
        return [];
    }

    try {
        $collection = getCollectionPromociones();
        if (!$collection) {
            return [];
        }

        $usuario_id_obj = is_string($usuario_id) ? new \MongoDB\BSON\ObjectId($usuario_id) : $usuario_id;
        
        $filtro = ['usuario_id' => $usuario_id_obj];
        
        if ($solo_activas) {
            $ahora = new \MongoDB\BSON\UTCDateTime();
            $filtro['activa'] = true;
            $filtro['fecha_inicio'] = ['$lte' => $ahora];
            $filtro['fecha_fin'] = ['$gte' => $ahora];
        }

        $promociones = $collection->find($filtro, [
            'sort' => ['fecha_creacion' => -1]
        ])->toArray();

        return $promociones;
    } catch (Throwable $e) {
        error_log("Error al obtener promociones de usuario: " . $e->getMessage());
        return [];
    }
}

/**
 * Marca promociones expiradas como inactivas
 */
function validarPromocionesExpiradas() {
    try {
        $collection = getCollectionPromociones();
        if (!$collection) {
            return 0;
        }

        $ahora = new \MongoDB\BSON\UTCDateTime();

        $result = $collection->updateMany(
            [
                'activa' => true,
                'fecha_fin' => ['$lt' => $ahora]
            ],
            [
                '$set' => ['activa' => false]
            ]
        );

        return $result->getModifiedCount();
    } catch (Throwable $e) {
        error_log("Error al validar promociones expiradas: " . $e->getMessage());
        return 0;
    }
}

/**
 * Elimina una promoción (solo el propietario)
 */
function eliminarPromocion($promocion_id, $usuario_id) {
    if (empty($promocion_id) || empty($usuario_id)) {
        return false;
    }

    try {
        $collection = getCollectionPromociones();
        if (!$collection) {
            return false;
        }

        $promocion_id_obj = is_string($promocion_id) ? new \MongoDB\BSON\ObjectId($promocion_id) : $promocion_id;
        $usuario_id_obj = is_string($usuario_id) ? new \MongoDB\BSON\ObjectId($usuario_id) : $usuario_id;

        $result = $collection->deleteOne([
            '_id' => $promocion_id_obj,
            'usuario_id' => $usuario_id_obj
        ]);

        return $result->getDeletedCount() > 0;
    } catch (Throwable $e) {
        error_log("Error al eliminar promoción: " . $e->getMessage());
        return false;
    }
}

/**
 * Calcula los días restantes de una promoción
 */
function diasRestantesPromocion($promocion) {
    if (!$promocion || !isset($promocion['fecha_fin'])) {
        return 0;
    }

    $fecha_fin = $promocion['fecha_fin'];
    
    if ($fecha_fin instanceof \MongoDB\BSON\UTCDateTime) {
        $fecha_fin_ts = $fecha_fin->toDateTime()->getTimestamp();
    } else {
        $fecha_fin_ts = is_numeric($fecha_fin) ? $fecha_fin : strtotime($fecha_fin);
    }

    $dias = ceil(($fecha_fin_ts - time()) / (24 * 60 * 60));
    return max(0, $dias);
}

/**
 * Obtiene el precio a mostrar para un código (con promoción si existe)
 * Retorna: ['precio_original' => X, 'precio_promocional' => Y, 'tiene_promocion' => bool, 'promocion' => obj]
 */
function obtenerPrecioCodigoConPromocion($codigo_id, $precio_original = null) {
    if (empty($codigo_id)) {
        return [
            'precio_original' => $precio_original ?? 0,
            'precio_promocional' => null,
            'tiene_promocion' => false,
            'promocion' => null,
            'dias_restantes' => 0
        ];
    }

    $promocion = obtenerPromocionActiva($codigo_id);
    
    if ($promocion) {
        return [
            'precio_original' => $promocion['precio_original'] ?? $precio_original ?? 0,
            'precio_promocional' => $promocion['precio_promocional'] ?? null,
            'tiene_promocion' => true,
            'promocion' => $promocion,
            'dias_restantes' => diasRestantesPromocion($promocion),
            'tipo_beneficio' => $promocion['tipo_beneficio'] ?? 'euros'
        ];
    }

    return [
        'precio_original' => $precio_original ?? 0,
        'precio_promocional' => null,
        'tiene_promocion' => false,
        'promocion' => null,
        'dias_restantes' => 0
    ];
}

/**
 * Genera HTML para mostrar precio con promoción (precio tachado + precio promocional)
 */
function generarHTMLPrecioConPromocion($codigo_id, $precio_original, $tipo_descuento = 'euros', $mostrar_badge = true) {
    $precio_info = obtenerPrecioCodigoConPromocion($codigo_id, $precio_original);
    
    $html = '';
    
    if ($precio_info['tiene_promocion'] && $precio_info['precio_promocional'] !== null) {
        // Hay promoción activa
        if ($mostrar_badge) {
            $dias = $precio_info['dias_restantes'];
            $html .= '<div class="promocion-badge">';
            if ($dias > 0) {
                $html .= '<span class="promo-text">PROMO</span>';
                $html .= '<span class="promo-days">' . $dias . ' día' . ($dias > 1 ? 's' : '') . '</span>';
            } else {
                $html .= '<span class="promo-text">OFERTA</span>';
            }
            $html .= '</div>';
        }
        
        $tipo = $precio_info['tipo_beneficio'] ?? $tipo_descuento;
        $simbolo = ($tipo === 'porcentaje' || strpos(strtolower($tipo), '%') !== false) ? '%' : '€';
        
        $html .= '<div class="precio-con-promocion">';
        // Precio promocional destacado
        $html .= '<span class="precio-promocional">' . number_format((float)$precio_info['precio_promocional'], 2, ',', '.') . $simbolo . '</span>';
        // Precio original tachado
        $html .= '<span class="precio-original-tachado">' . number_format((float)$precio_info['precio_original'], 2, ',', '.') . $simbolo . '</span>';
        $html .= '</div>';
    } else {
        // Sin promoción - precio normal
        $simbolo = (strpos(strtolower($tipo_descuento), '%') !== false) ? '%' : '€';
        $html .= '<div class="precio-normal">';
        $html .= '<span class="precio-standard">' . number_format((float)$precio_original, 2, ',', '.') . $simbolo . '</span>';
        $html .= '</div>';
    }
    
    return $html;
}

?>


