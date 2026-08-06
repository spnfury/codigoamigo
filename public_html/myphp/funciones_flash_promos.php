<?php
include_once __DIR__ . '/../inc/logger.php';

/**
 * Obtiene la colección de flash_promos
 */
function getCollectionFlashPromos() {
    $db = createConnection();
    if (!$db) {
        return null;
    }

    try {
        $collection = $db->selectCollection('flash_promos');
        return $collection;
    } catch (Throwable $e) {
        log_error("Error al obtener colección de flash_promos: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtiene las promociones flash activas para una marca
 * @param string $marca_clave El nombre clave de la marca
 * @return array Lista de promociones flash
 */
function obtenerFlashPromosPorMarca($marca_clave) {
    $collection = getCollectionFlashPromos();
    if (!$collection) {
        return [];
    }

    try {
        $fecha_actual = new MongoDB\BSON\UTCDateTime();
        $query = [
            'marca_clave' => $marca_clave,
            'activo' => true,
            '$or' => [
                ['fecha_expiracion' => null],
                ['fecha_expiracion' => ['$gte' => $fecha_actual]]
            ]
        ];

        $opciones = [
            'sort' => ['prioridad' => -1, 'fecha_creacion' => -1]
        ];

        $cursor = $collection->find($query, $opciones);
        $promos = [];

        foreach ($cursor as $doc) {
            $promos[] = [
                'id' => (string)$doc['_id'],
                'titulo' => $doc['titulo'] ?? '',
                'descripcion' => $doc['descripcion'] ?? '',
                'beneficio' => $doc['beneficio'] ?? '',
                'url_promo' => $doc['url_promo'] ?? '',
                'verificado' => $doc['verificado'] ?? false,
                'fecha_expiracion' => isset($doc['fecha_expiracion']) ? $doc['fecha_expiracion']->toDateTime()->format('Y-m-d H:i:s') : null,
                'tipo' => $doc['tipo'] ?? 'flash_promo'
            ];
        }

        return $promos;
    } catch (Throwable $e) {
        log_error("Error al obtener flash promos: " . $e->getMessage());
        return [];
    }
}
