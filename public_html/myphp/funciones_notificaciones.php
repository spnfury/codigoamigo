<?php
include_once __DIR__ . '/../inc/logger.php';

if (!function_exists('createConnection')) {
    include_once __DIR__ . '/funciones.php';
}

function getCollectionNotificaciones() {
    $db = createConnection();
    if (!$db) return null;
    return $db->selectCollection('notificaciones');
}

/**
 * Crea una nueva notificación
 */
function crear_notificacion($usuario_id, $tipo, $datos) {
    $collection = getCollectionNotificaciones();
    if (!$collection) return false;

    // convertir usuario_id a string si viene como objeto
    if ($usuario_id instanceof MongoDB\BSON\ObjectId) {
        $usuario_id = (string)$usuario_id;
    }

    $notificacion = [
        'usuario_id' => $usuario_id, // El receptor de la notificación
        'tipo' => $tipo, // 'respuesta_comentario', 'nuevo_chollo_seguido', etc.
        'datos' => $datos, // Array con info relevante (id_chollo, id_comentario, autor_respuesta, etc.)
        'leido' => false,
        'fecha_creacion' => new MongoDB\BSON\UTCDateTime(),
        'borrado' => false
    ];

    try {
        $result = $collection->insertOne($notificacion);
        return $result->getInsertedId();
    } catch (Exception $e) {
        log_error("Error creando notificación: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene notificaciones de un usuario
 */
function obtener_notificaciones_usuario($usuario_id, $limit = 20) {
    $collection = getCollectionNotificaciones();
    if (!$collection) return [];

    $filter = [
        'usuario_id' => (string)$usuario_id,
        'borrado' => false
    ];

    $options = [
        'sort' => ['fecha_creacion' => -1],
        'limit' => $limit
    ];

    try {
        $cursor = $collection->find($filter, $options);
        return iterator_to_array($cursor);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Cuenta notificaciones no leídas
 */
function contar_notificaciones_no_leidas($usuario_id) {
    $collection = getCollectionNotificaciones();
    if (!$collection) return 0;

    try {
        return $collection->countDocuments([
            'usuario_id' => (string)$usuario_id,
            'leido' => false,
            'borrado' => false
        ]);
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Marca una notificación como leída
 */
function marcar_notificacion_leida($notificacion_id, $usuario_id) {
    $collection = getCollectionNotificaciones();
    if (!$collection) return false;

    try {
        $result = $collection->updateOne(
            [
                '_id' => new MongoDB\BSON\ObjectId($notificacion_id),
                'usuario_id' => (string)$usuario_id
            ],
            ['$set' => ['leido' => true]]
        );
        return $result->getModifiedCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Marca todas las notificaciones como leídas
 */
function marcar_todas_leidas($usuario_id) {
    $collection = getCollectionNotificaciones();
    if (!$collection) return false;

    try {
        $result = $collection->updateMany(
            [
                'usuario_id' => (string)$usuario_id,
                'leido' => false
            ],
            ['$set' => ['leido' => true]]
        );
        return $result->getModifiedCount();
    } catch (Exception $e) {
        return 0;
    }
}
?>
