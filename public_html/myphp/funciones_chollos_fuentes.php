<?php

// Incluir funciones de conexión a MongoDB
if (!function_exists('createConnection')) {
    include_once __DIR__ . '/funciones.php';
}

/******************************************************
 *  FUNCIONES PARA GESTIÓN DE FUENTES DE CHOLLOS
 * ***************************************************/

/**
 * Obtiene la colección de fuentes de chollos
 */
function getCollectionChollosFuentes() {
    $db = createConnection();
    if (!$db) {
        return null;
    }

    try {
        $collection_fuentes = $db->selectCollection('chollos_fuentes');
        return $collection_fuentes;
    } catch (Throwable $e) {
        error_log("Error al obtener colección de fuentes: " . $e->getMessage());
        return null;
    }
}

/**
 * Crea una nueva fuente de chollos
 */
function crearFuente($datos) {
    $collection = getCollectionChollosFuentes();
    if (!$collection) {
        return ['success' => false, 'error' => 'Error de conexión'];
    }

    try {
        // Validar campos requeridos
        if (empty($datos['nombre']) || empty($datos['tipo']) || empty($datos['url'])) {
            return ['success' => false, 'error' => 'Nombre, tipo y URL son obligatorios'];
        }

        $documento = [
            'nombre' => $datos['nombre'],
            'tipo' => $datos['tipo'], // telegram, rss, api, etc.
            'url' => $datos['url'],
            'configuracion' => $datos['configuracion'] ?? [],
            'activo' => isset($datos['activo']) ? (bool)$datos['activo'] : true,
            'ultima_sincronizacion' => null,
            'mensajes_procesados' => 0,
            'mensajes_totales' => 0,
            'ultimo_mensaje_id' => null,
            'fecha_creacion' => new MongoDB\BSON\UTCDateTime(),
            'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
        ];

        $resultado = $collection->insertOne($documento);
        
        if ($resultado->getInsertedId()) {
            return ['success' => true, 'id' => (string)$resultado->getInsertedId()];
        } else {
            return ['success' => false, 'error' => 'Error al insertar fuente'];
        }
    } catch (Throwable $e) {
        error_log("Error al crear fuente: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno: ' . $e->getMessage()];
    }
}

/**
 * Obtiene fuentes con filtros
 */
function obtenerFuentes($filtros = []) {
    $collection = getCollectionChollosFuentes();
    if (!$collection) {
        return [];
    }

    try {
        $query = [];

        // Filtro por tipo
        if (!empty($filtros['tipo'])) {
            $query['tipo'] = $filtros['tipo'];
        }

        // Filtro por estado activo
        if (isset($filtros['activo'])) {
            $query['activo'] = (bool)$filtros['activo'];
        }

        // Filtro por búsqueda en nombre
        if (!empty($filtros['busqueda'])) {
            $query['nombre'] = ['$regex' => $filtros['busqueda'], '$options' => 'i'];
        }

        $opciones = [
            'sort' => ['fecha_creacion' => -1] // Más recientes primero
        ];

        // Paginación
        if (isset($filtros['limite'])) {
            $opciones['limit'] = intval($filtros['limite']);
        }
        if (isset($filtros['skip'])) {
            $opciones['skip'] = intval($filtros['skip']);
        }

        $cursor = $collection->find($query, $opciones);
        $fuentes = [];

        foreach ($cursor as $doc) {
            $fuentes[] = [
                'id' => (string)$doc['_id'],
                'nombre' => $doc['nombre'] ?? '',
                'tipo' => $doc['tipo'] ?? '',
                'url' => $doc['url'] ?? '',
                'configuracion' => $doc['configuracion'] ?? [],
                'activo' => $doc['activo'] ?? true,
                'ultima_sincronizacion' => isset($doc['ultima_sincronizacion']) && $doc['ultima_sincronizacion'] instanceof MongoDB\BSON\UTCDateTime 
                    ? $doc['ultima_sincronizacion']->toDateTime()->format('Y-m-d H:i:s') 
                    : null,
                'mensajes_procesados' => $doc['mensajes_procesados'] ?? 0,
                'mensajes_totales' => $doc['mensajes_totales'] ?? 0,
                'ultimo_mensaje_id' => $doc['ultimo_mensaje_id'] ?? null,
                'fecha_creacion' => isset($doc['fecha_creacion']) && $doc['fecha_creacion'] instanceof MongoDB\BSON\UTCDateTime
                    ? $doc['fecha_creacion']->toDateTime()->format('Y-m-d H:i:s')
                    : '',
                'fecha_actualizacion' => isset($doc['fecha_actualizacion']) && $doc['fecha_actualizacion'] instanceof MongoDB\BSON\UTCDateTime
                    ? $doc['fecha_actualizacion']->toDateTime()->format('Y-m-d H:i:s')
                    : ''
            ];
        }

        return $fuentes;
    } catch (Throwable $e) {
        error_log("Error al obtener fuentes: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene una fuente por ID
 */
function obtenerFuentePorId($id) {
    $collection = getCollectionChollosFuentes();
    if (!$collection) {
        return null;
    }

    try {
        // Validar y crear ObjectId
        try {
            $objectId = new MongoDB\BSON\ObjectId($id);
        } catch (Exception $e) {
            return null;
        }

        $doc = $collection->findOne(['_id' => $objectId]);

        if (!$doc) {
            return null;
        }

        return [
            'id' => (string)$doc['_id'],
            'nombre' => $doc['nombre'] ?? '',
            'tipo' => $doc['tipo'] ?? '',
            'url' => $doc['url'] ?? '',
            'configuracion' => $doc['configuracion'] ?? [],
            'activo' => $doc['activo'] ?? true,
            'ultima_sincronizacion' => isset($doc['ultima_sincronizacion']) && $doc['ultima_sincronizacion'] instanceof MongoDB\BSON\UTCDateTime
                ? $doc['ultima_sincronizacion']->toDateTime()->format('Y-m-d H:i:s')
                : null,
            'mensajes_procesados' => $doc['mensajes_procesados'] ?? 0,
            'mensajes_totales' => $doc['mensajes_totales'] ?? 0,
            'ultimo_mensaje_id' => $doc['ultimo_mensaje_id'] ?? null,
            'fecha_creacion' => isset($doc['fecha_creacion']) && $doc['fecha_creacion'] instanceof MongoDB\BSON\UTCDateTime
                ? $doc['fecha_creacion']->toDateTime()->format('Y-m-d H:i:s')
                : '',
            'fecha_actualizacion' => isset($doc['fecha_actualizacion']) && $doc['fecha_actualizacion'] instanceof MongoDB\BSON\UTCDateTime
                ? $doc['fecha_actualizacion']->toDateTime()->format('Y-m-d H:i:s')
                : ''
        ];
    } catch (Throwable $e) {
        error_log("Error al obtener fuente por ID: " . $e->getMessage());
        return null;
    }
}

/**
 * Actualiza una fuente
 */
function actualizarFuente($id, $datos) {
    $collection = getCollectionChollosFuentes();
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

        if (isset($datos['nombre'])) $update['nombre'] = $datos['nombre'];
        if (isset($datos['tipo'])) $update['tipo'] = $datos['tipo'];
        if (isset($datos['url'])) $update['url'] = $datos['url'];
        if (isset($datos['configuracion'])) $update['configuracion'] = $datos['configuracion'];
        if (isset($datos['activo'])) $update['activo'] = (bool)$datos['activo'];

        $resultado = $collection->updateOne(
            ['_id' => $objectId],
            ['$set' => $update]
        );

        if ($resultado->getMatchedCount() > 0) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'Fuente no encontrada'];
        }
    } catch (Throwable $e) {
        error_log("Error al actualizar fuente: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno: ' . $e->getMessage()];
    }
}

/**
 * Elimina una fuente
 */
function eliminarFuente($id) {
    $collection = getCollectionChollosFuentes();
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
            return ['success' => false, 'error' => 'Fuente no encontrada'];
        }
    } catch (Throwable $e) {
        error_log("Error al eliminar fuente: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno: ' . $e->getMessage()];
    }
}

/**
 * Actualiza la última sincronización de una fuente
 */
function actualizarUltimaSincronizacion($fuente_id, $mensajes_procesados = 0, $ultimo_mensaje_id = null) {
    $collection = getCollectionChollosFuentes();
    if (!$collection) {
        return ['success' => false, 'error' => 'Error de conexión'];
    }

    try {
            try {
                $objectId = new MongoDB\BSON\ObjectId($fuente_id);
            } catch (Exception $e) {
                return ['success' => false, 'error' => 'ID no válido'];
            }

        $update = [
            'ultima_sincronizacion' => new MongoDB\BSON\UTCDateTime(),
            'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
        ];

        if ($mensajes_procesados > 0) {
            $update['$inc'] = ['mensajes_procesados' => $mensajes_procesados];
        }

        if ($ultimo_mensaje_id !== null) {
            $update['ultimo_mensaje_id'] = $ultimo_mensaje_id;
        }

        // Si hay $inc, necesitamos hacer dos operaciones o combinar
        if (isset($update['$inc'])) {
            $inc_update = ['$inc' => $update['$inc']];
            unset($update['$inc']);
            $resultado = $collection->updateOne(
                ['_id' => $objectId],
                ['$set' => $update, '$inc' => $inc_update['$inc']]
            );
        } else {
            $resultado = $collection->updateOne(
                ['_id' => $objectId],
                ['$set' => $update]
            );
        }

        if ($resultado->getMatchedCount() > 0) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'Fuente no encontrada'];
        }
    } catch (Throwable $e) {
        error_log("Error al actualizar última sincronización: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno: ' . $e->getMessage()];
    }
}

/**
 * Obtiene fuentes activas por tipo
 */
function obtenerFuentesActivas($tipo = null) {
    $filtros = ['activo' => true];
    if ($tipo !== null) {
        $filtros['tipo'] = $tipo;
    }
    return obtenerFuentes($filtros);
}

/**
 * Obtiene estadísticas de fuentes
 */
function obtenerEstadisticasFuentes() {
    $collection = getCollectionChollosFuentes();
    if (!$collection) {
        return [];
    }

    try {
        $total = $collection->countDocuments([]);
        $activas = $collection->countDocuments(['activo' => true]);
        $inactivas = $collection->countDocuments(['activo' => false]);
        
        $por_tipo = [];
        $tipos = ['telegram', 'rss', 'api', 'manual'];
        foreach ($tipos as $tipo) {
            $por_tipo[$tipo] = $collection->countDocuments(['tipo' => $tipo, 'activo' => true]);
        }

        return [
            'total' => $total,
            'activas' => $activas,
            'inactivas' => $inactivas,
            'por_tipo' => $por_tipo
        ];
    } catch (Throwable $e) {
        error_log("Error al obtener estadísticas de fuentes: " . $e->getMessage());
        return [];
    }
}




