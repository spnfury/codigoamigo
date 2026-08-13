<?php
include_once __DIR__ . '/../inc/logger.php';
/**
 * Funciones para gestionar favoritos de códigos
 */

// Incluir funciones necesarias
if (!function_exists('getCollectionFavoritos')) {
    include_once __DIR__ . '/funciones_usuario.php';
}
if (!function_exists('getCollectionCodigos')) {
    include_once __DIR__ . '/funciones_codigo.php';
}

/**
 * Añade un código a favoritos
 */
if (!function_exists('añadir_favorito')) {
function añadir_favorito($usuario_id, $codigo_id, $tipo = 'codigo') {
    if (empty($usuario_id) || empty($codigo_id)) {
        return ['success' => false, 'message' => 'Datos incompletos'];
    }

    try {
        $collection_favoritos = getCollectionFavoritos();
        
        // Verificar existencia según tipo
        if ($tipo === 'chollo') {
            if (!function_exists('getCollectionChollos')) {
                include_once __DIR__ . '/funciones_chollos.php';
            }
            $collection_chollos = getCollectionChollos();
            $item = $collection_chollos->findOne(['_id' => new MongoDB\BSON\ObjectId($codigo_id)]);
            $msg_not_found = 'Chollo no encontrado';
            $msg_success = 'Chollo guardado en favoritos';
            $msg_exists = 'Este chollo ya está en tus favoritos';
        } elseif ($tipo === 'usuario') {
            $collection_usuarios = getCollectionUsuarios();
            $item = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($codigo_id)]);
            $msg_not_found = 'Usuario no encontrado';
            $msg_success = 'Has empezado a seguir a este usuario';
            $msg_exists = 'Ya sigues a este usuario';
        } else {
            $collection_codigos = getCollectionCodigos();
            $item = $collection_codigos->findOne(['_id' => new MongoDB\BSON\ObjectId($codigo_id)]);
            $msg_not_found = 'Código no encontrado';
            $msg_success = 'Código añadido a favoritos';
            $msg_exists = 'Este código ya está en tus favoritos';
        }

        if (!$item) {
            return ['success' => false, 'message' => $msg_not_found];
        }

        // Asegurar que usamos ObjectId si es posible para usuario_id
        $uid_obj = $usuario_id;
        try {
            if (is_string($usuario_id) && strlen($usuario_id) === 24) {
                 $uid_obj = new MongoDB\BSON\ObjectId($usuario_id);
            }
        } catch(Exception $e) {}

        // Verificar si ya está en favoritos
        $query = [
            'usuario_id' => $uid_obj,
            'codigo_id' => new MongoDB\BSON\ObjectId($codigo_id)
        ];
        // Solo verificamos tipo si no es el default 'codigo' para compatibilidad backward
        if ($tipo !== 'codigo') {
            $query['tipo'] = $tipo;
        }

        $favorito_existente = $collection_favoritos->findOne($query);

        if ($favorito_existente) {
            return ['success' => false, 'message' => $msg_exists];
        }

        // Añadir a favoritos
        $collection_favoritos->insertOne([
            'usuario_id' => $uid_obj,
            'codigo_id' => new MongoDB\BSON\ObjectId($codigo_id),
            'tipo' => $tipo,
            'fecha_creacion' => date('Y-m-d H:i:s'),
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ]);
        
        // Si es un chollo, incrementar la temperatura
        if ($tipo === 'chollo') {
            try {
                if (!isset($collection_chollos)) {
                    if (!function_exists('getCollectionChollos')) {
                        include_once __DIR__ . '/funciones_chollos.php';
                    }
                    $collection_chollos = getCollectionChollos();
                }
                
                $collection_chollos->updateOne(
                    ['_id' => new MongoDB\BSON\ObjectId($codigo_id)],
                    ['$inc' => ['temperatura' => 1]]
                );
            } catch (Exception $e) {
                // Silencioso: no fallar la acción principal si falla el incremento
                log_error("Error incrementando temperatura al añadir favorito: " . $e->getMessage());
            }
        }

        return ['success' => true, 'message' => $msg_success];
    } catch (Exception $e) {
        if (function_exists('log_error')) {
            log_error("Error añadiendo favorito", ['error' => $e->getMessage(), 'usuario_id' => $usuario_id, 'codigo_id' => $codigo_id]);
        }
        return ['success' => false, 'message' => 'Error al añadir a favoritos'];
    }
}
}


/**
 * Elimina un código de favoritos
 */
if (!function_exists('eliminar_favorito')) {
function eliminar_favorito($usuario_id, $codigo_id, $tipo = 'codigo') {
    if (empty($usuario_id) || empty($codigo_id)) {
        return ['success' => false, 'message' => 'Datos incompletos'];
    }

    try {
        $collection_favoritos = getCollectionFavoritos();

        // Asegurar IDs como ObjectId
        $uid_obj = $usuario_id;
        try {
            if (is_string($usuario_id) && strlen($usuario_id) === 24) {
                 $uid_obj = new MongoDB\BSON\ObjectId($usuario_id);
            }
        } catch(Exception $e) {}

        $query = [
            'usuario_id' => $uid_obj,
            'codigo_id' => new MongoDB\BSON\ObjectId($codigo_id)
        ];
        
        if ($tipo !== 'codigo') {
            $query['tipo'] = $tipo;
        }

        $result = $collection_favoritos->deleteOne($query);

        $msg_success = 'Elemento eliminado de favoritos';
        $msg_not_found = 'Elemento no encontrado en favoritos';
        
        switch ($tipo) {
            case 'chollo':
                $msg_success = 'Chollo eliminado de favoritos';
                $msg_not_found = 'El chollo no estaba en tus favoritos';
                break;
            case 'usuario':
                $msg_success = 'Has dejado de seguir a este usuario';
                $msg_not_found = 'No seguías a este usuario';
                break;
            case 'codigo':
            default:
                $msg_success = 'Código eliminado de favoritos';
                $msg_not_found = 'El código no estaba en tus favoritos';
                break;
        }

        if ($result->getDeletedCount() > 0) {
            return ['success' => true, 'message' => $msg_success];
        } else {
            return ['success' => false, 'message' => $msg_not_found];
        }
    } catch (Exception $e) {
        if (function_exists('log_error')) {
            log_error("Error eliminando favorito", ['error' => $e->getMessage(), 'usuario_id' => $usuario_id, 'codigo_id' => $codigo_id]);
        }
        return ['success' => false, 'message' => 'Error al eliminar de favoritos'];
    }
}
}


/**
 * Verifica si un código está en favoritos
 */
if (!function_exists('es_favorito')) {
function es_favorito($usuario_id, $codigo_id, $tipo = 'codigo') {
    if (empty($usuario_id) || empty($codigo_id)) {
        return false;
    }

    try {
        $collection_favoritos = getCollectionFavoritos();

        // Asegurar IDs como ObjectId
        $uid_obj = $usuario_id;
        try {
            if (is_string($usuario_id) && strlen($usuario_id) === 24) {
                 $uid_obj = new MongoDB\BSON\ObjectId($usuario_id);
            }
        } catch(Exception $e) {}

        $query = [
            'usuario_id' => $uid_obj,
            'codigo_id' => new MongoDB\BSON\ObjectId($codigo_id)
        ];
        
        if ($tipo !== 'codigo') {
            $query['tipo'] = $tipo;
        }

        $favorito = $collection_favoritos->findOne($query);

        return $favorito !== null;
    } catch (Exception $e) {
        return false;
    }
}
}

/**
 * Obtiene todos los favoritos de un usuario
 */
/**
 * Obtiene todos los favoritos de un usuario
 * @param string $usuario_id
 * @param string $tipo 'codigo', 'chollo' o null (todos - no recomendado para grids mixtos aun)
 * @param int $limit
 * @param int $skip
 */
function obtener_favoritos_usuario($usuario_id, $tipo = 'codigo', $limit = 50, $skip = 0) {
    if (empty($usuario_id)) {
        return [];
    }

    try {
        $collection_favoritos = getCollectionFavoritos();
        
        // Asegurar IDs como ObjectId
        $uid_obj = $usuario_id;
        try {
            if (is_string($usuario_id) && strlen($usuario_id) === 24) {
                 $uid_obj = new MongoDB\BSON\ObjectId($usuario_id);
            }
        } catch(Exception $e) {}

        // Filtro base
        $filter = ['usuario_id' => $uid_obj];
        
        // Filtrar por tipo si se especifica (default 'codigo' para compatibilidad)
        // Si en DB no hay campo 'tipo', asumimos que es 'codigo'
        if ($tipo === 'codigo') {
            $filter['$or'] = [
                ['tipo' => 'codigo'],
                ['tipo' => ['$exists' => false]]
            ];
        } elseif ($tipo) {
            $filter['tipo'] = $tipo;
        }

        // Obtener IDs
        $favoritos_cursor = $collection_favoritos->find(
            $filter,
            [
                'sort' => ['created_at' => -1],
                'limit' => $limit,
                'skip' => $skip
            ]
        );

        $ids = [];
        $favoritos_data = []; // Para mantener orden o metadatos si fuera necesario
        foreach ($favoritos_cursor as $favorito) {
            $oid = new MongoDB\BSON\ObjectId($favorito['codigo_id']);
            $ids[] = $oid;
            $favoritos_data[(string)$oid] = $favorito;
        }

        if (empty($ids)) {
            return [];
        }

        // Obtener los objetos reales
        if ($tipo === 'chollo') {
            if (!function_exists('getCollectionChollos')) {
                include_once __DIR__ . '/funciones_chollos.php';
            }
            $collection = getCollectionChollos();
            // Para chollos, necesitamos query normal
            $cursor = $collection->find(['_id' => ['$in' => $ids]]);
        } elseif ($tipo === 'usuario') {
            $collection = getCollectionUsuarios();
            $cursor = $collection->find(['_id' => ['$in' => $ids]]);
        } else {
            // Default codigos
            $collection = getCollectionCodigos();
             $cursor = $collection->find([
                '_id' => ['$in' => $ids],
                'estado' => 0 // Solo códigos activos
            ]);
        }

        $items = [];
        foreach ($cursor as $doc) {
            $item = iterator_to_array($doc);
            $item['id'] = (string)$item['_id'];
            // Inyectar info de favorito (fecha agregada etc) si se quisiera
            $items[] = $item;
        }

        return $items;
    } catch (Exception $e) {
        if (function_exists('log_error')) {
            log_error("Error obteniendo favoritos", ['error' => $e->getMessage(), 'usuario_id' => $usuario_id]);
        }
        return [];
    }
}

/**
 * Obtiene el número total de favoritos de un usuario
 */
function contar_favoritos_usuario($usuario_id, $tipo = 'codigo') {
    if (empty($usuario_id)) {
        return 0;
    }

    try {
        $collection_favoritos = getCollectionFavoritos();
        
        // Asegurar IDs como ObjectId
        $uid_obj = $usuario_id;
        try {
            if (is_string($usuario_id) && strlen($usuario_id) === 24) {
                 $uid_obj = new MongoDB\BSON\ObjectId($usuario_id);
            }
        } catch(Exception $e) {}

        $filter = ['usuario_id' => $uid_obj];
        
        if ($tipo === 'codigo') {
            $filter['$or'] = [
                ['tipo' => 'codigo'],
                ['tipo' => ['$exists' => false]]
            ];
        } elseif ($tipo) {
            $filter['tipo'] = $tipo;
        }
        
        return $collection_favoritos->countDocuments($filter);
    } catch (Exception $e) {
        return 0;
    }
}

?>

