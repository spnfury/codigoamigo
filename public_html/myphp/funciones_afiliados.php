<?php

// Incluir funciones de conexión a MongoDB
if (!function_exists('createConnection')) {
    include_once __DIR__ . '/funciones.php';
}

// Incluir funciones de códigos para getCollectionCodigos
if (!function_exists('getCollectionCodigos')) {
    include_once __DIR__ . '/funciones_codigo.php';
}

/******************************************************
 *  FUNCIONES PARA GESTIÓN DE URLs DE AFILIADOS
 * ***************************************************/

/**
 * Obtiene la colección de URLs de afiliados
 */
function getCollectionAfiliados() {
    $db = createConnection();
    if (!$db) {
        return null;
    }

    try {
        $collection_afiliados = $db->selectCollection('afiliados_urls');
        return $collection_afiliados;
    } catch (Throwable $e) {
        error_log("Error al obtener colección de afiliados: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtiene la colección de ingresos de afiliados
 */
function getCollectionIngresosAfiliados() {
    $db = createConnection();
    if (!$db) {
        return null;
    }

    try {
        $collection_ingresos = $db->selectCollection('afiliados_ingresos');
        return $collection_ingresos;
    } catch (Throwable $e) {
        error_log("Error al obtener colección de ingresos afiliados: " . $e->getMessage());
        return null;
    }
}

/**
 * Añade una nueva URL de afiliado para un usuario
 */
function agregarUrlAfiliado($usuario_id, $url, $nombre_plataforma, $descripcion = '') {
    $collection = getCollectionAfiliados();
    if (!$collection) {
        return ['success' => false, 'error' => 'Error de conexión'];
    }

    try {
        $documento = [
            'usuario_id' => $usuario_id,
            'url' => $url,
            'nombre_plataforma' => $nombre_plataforma,
            'descripcion' => $descripcion,
            'marcas' => [], // Array de marcas asociadas
            'activo' => true,
            'fecha_creacion' => new MongoDB\BSON\UTCDateTime(),
            'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
        ];

        $resultado = $collection->insertOne($documento);
        
        if ($resultado->getInsertedId()) {
            return ['success' => true, 'id' => (string)$resultado->getInsertedId(), 'url_id' => (string)$resultado->getInsertedId()];
        } else {
            return ['success' => false, 'error' => 'Error al insertar URL'];
        }
    } catch (Throwable $e) {
        error_log("Error al agregar URL afiliado: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno: ' . $e->getMessage()];
    }
}

/**
 * Obtiene todas las URLs de afiliados de un usuario
 */
function obtenerUrlsAfiliadosUsuario($usuario_id) {
    $collection = getCollectionAfiliados();
    if (!$collection) {
        return ['success' => false, 'error' => 'Error de conexión'];
    }

    try {
        $cursor = $collection->find([
            'usuario_id' => $usuario_id,
            'activo' => true
        ], [
            'sort' => ['fecha_creacion' => -1]
        ]);

        $urls = [];
        foreach ($cursor as $documento) {
            $urls[] = [
                '_id' => $documento['_id'],
                'url' => $documento['url'],
                'nombre_plataforma' => $documento['nombre_plataforma'],
                'descripcion' => $documento['descripcion'],
                'marcas' => $documento['marcas'] ?? [],
                'fecha_creacion' => $documento['fecha_creacion']->toDateTime()->format('Y-m-d H:i:s')
            ];
        }

        return ['success' => true, 'urls' => $urls];
    } catch (Throwable $e) {
        error_log("Error al obtener URLs afiliados: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno: ' . $e->getMessage()];
    }
}

/**
 * Añade una marca a una URL de afiliado
 */
function agregarMarcaUrlAfiliado($url_id, $marca_nombre, $marca_id = null) {
    $collection = getCollectionAfiliados();
    if (!$collection) {
        return ['success' => false, 'error' => 'Error de conexión'];
    }

    try {
        $marca = [
            'nombre' => $marca_nombre,
            'marca_id' => $marca_id,
            'fecha_asociacion' => new MongoDB\BSON\UTCDateTime()
        ];

        $resultado = $collection->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($url_id)],
            [
                '$push' => ['marcas' => $marca],
                '$set' => ['fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()]
            ]
        );

        if ($resultado->getModifiedCount() > 0) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'URL no encontrada'];
        }
    } catch (Throwable $e) {
        error_log("Error al agregar marca a URL: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno: ' . $e->getMessage()];
    }
}

/**
 * Registra ingresos de una URL de afiliado
 */
function registrarIngresosAfiliado($url_id, $usuario_id, $monto, $periodo, $captura_path = null, $notas = '') {
    $collection = getCollectionIngresosAfiliados();
    if (!$collection) {
        return ['success' => false, 'error' => 'Error de conexión'];
    }

    try {
        $documento = [
            'url_id' => new MongoDB\BSON\ObjectId($url_id),
            'usuario_id' => $usuario_id,
            'monto' => floatval($monto),
            'periodo' => $periodo, // ej: "2025-01", "Enero 2025"
            'captura_path' => $captura_path,
            'notas' => $notas,
            'fecha_registro' => new MongoDB\BSON\UTCDateTime(),
            'fecha_periodo' => new MongoDB\BSON\UTCDateTime(strtotime($periodo . '-01'))
        ];

        $resultado = $collection->insertOne($documento);
        
        if ($resultado->getInsertedId()) {
            return ['success' => true, 'id' => (string)$resultado->getInsertedId()];
        } else {
            return ['success' => false, 'error' => 'Error al registrar ingresos'];
        }
    } catch (Throwable $e) {
        error_log("Error al registrar ingresos: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno: ' . $e->getMessage()];
    }
}

/**
 * Obtiene ingresos de un usuario por URL de afiliado
 */
function obtenerIngresosAfiliadosUsuario($usuario_id, $url_id = null) {
    $collection = getCollectionIngresosAfiliados();
    if (!$collection) {
        return ['success' => false, 'error' => 'Error de conexión'];
    }

    try {
        $filtro = ['usuario_id' => $usuario_id];
        if ($url_id) {
            $filtro['url_id'] = new MongoDB\BSON\ObjectId($url_id);
        }

        $cursor = $collection->find($filtro, [
            'sort' => ['fecha_periodo' => -1]
        ]);

        $ingresos = [];
        foreach ($cursor as $documento) {
            $ingresos[] = [
                'id' => $documento['_id'],
                'url_id' => $documento['url_id'],
                'monto' => $documento['monto'],
                'periodo' => $documento['periodo'],
                'captura_path' => $documento['captura_path'] ?? null,
                'notas' => $documento['notas'] ?? '',
                'fecha_registro' => $documento['fecha_registro']->toDateTime()->format('Y-m-d H:i:s')
            ];
        }

        return ['success' => true, 'ingresos' => $ingresos];
    } catch (Throwable $e) {
        error_log("Error al obtener ingresos: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno: ' . $e->getMessage()];
    }
}

/**
 * Obtiene estadísticas de ingresos de un usuario
 */
function obtenerEstadisticasIngresosUsuario($usuario_id) {
    $collection = getCollectionIngresosAfiliados();
    if (!$collection) {
        return ['success' => false, 'error' => 'Error de conexión'];
    }

    try {
        // Pipeline de agregación para estadísticas
        $pipeline = [
            ['$match' => ['usuario_id' => $usuario_id]],
            [
                '$group' => [
                    '_id' => null,
                    'total_ingresos' => ['$sum' => '$monto'],
                    'promedio_mensual' => ['$avg' => '$monto'],
                    'total_registros' => ['$sum' => 1],
                    'ingreso_maximo' => ['$max' => '$monto'],
                    'ingreso_minimo' => ['$min' => '$monto']
                ]
            ]
        ];

        $cursor = $collection->aggregate($pipeline);
        $estadisticas = iterator_to_array($cursor);

        // Obtener número de URLs activas
        $collection_urls = getCollectionAfiliados();
        $urls_activas = $collection_urls->countDocuments([
            'usuario_id' => $usuario_id,
            'activo' => true
        ]);

        if (empty($estadisticas)) {
            return ['success' => true, 'estadisticas' => [
                'total_ingresos' => 0,
                'promedio_mensual' => 0,
                'total_registros' => 0,
                'urls_activas' => $urls_activas,
                'ingreso_maximo' => 0,
                'ingreso_minimo' => 0
            ]];
        }

        $estadisticas[0]['urls_activas'] = $urls_activas;
        return ['success' => true, 'estadisticas' => $estadisticas[0]];
    } catch (Throwable $e) {
        error_log("Error al obtener estadísticas: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno: ' . $e->getMessage()];
    }
}

/**
 * Elimina una URL de afiliado (soft delete)
 */
function eliminarUrlAfiliado($url_id, $usuario_id) {
    $collection = getCollectionAfiliados();
    if (!$collection) {
        return ['success' => false, 'error' => 'Error de conexión'];
    }

    try {
        $resultado = $collection->updateOne(
            [
                '_id' => new MongoDB\BSON\ObjectId($url_id),
                'usuario_id' => $usuario_id
            ],
            [
                '$set' => [
                    'activo' => false,
                    'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
                ]
            ]
        );

        if ($resultado->getModifiedCount() > 0) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'URL no encontrada'];
        }
    } catch (Throwable $e) {
        error_log("Error al eliminar URL: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno: ' . $e->getMessage()];
    }
}

/**
 * Actualiza una URL de afiliado
 */
function actualizarUrlAfiliado($url_id, $usuario_id, $datos) {
    $collection = getCollectionAfiliados();
    if (!$collection) {
        return ['success' => false, 'error' => 'Error de conexión'];
    }

    try {
        $update_data = [
            'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
        ];

        if (isset($datos['url'])) $update_data['url'] = $datos['url'];
        if (isset($datos['nombre_plataforma'])) $update_data['nombre_plataforma'] = $datos['nombre_plataforma'];
        if (isset($datos['descripcion'])) $update_data['descripcion'] = $datos['descripcion'];

        $resultado = $collection->updateOne(
            [
                '_id' => new MongoDB\BSON\ObjectId($url_id),
                'usuario_id' => $usuario_id
            ],
            ['$set' => $update_data]
        );

        if ($resultado->getModifiedCount() > 0) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'URL no encontrada'];
        }
    } catch (Throwable $e) {
        error_log("Error al actualizar URL: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno: ' . $e->getMessage()];
    }
}

/**
 * Obtiene códigos sin URL de afiliado asignada para un usuario (agrupados por marca)
 */
function obtenerCodigosSinAfiliado($usuario_id) {
    $collection_codigos = getCollectionCodigos();
    if (!$collection_codigos) {
        return ['success' => false, 'error' => 'Error de conexión'];
    }

    try {
        // Aceptar id de usuario como String u ObjectId
        $idFilter = ['$in' => [$usuario_id]];
        try { $idFilter['$in'][] = new MongoDB\BSON\ObjectId($usuario_id); } catch (Throwable $e) {}

        $cursor = $collection_codigos->find([
            'id_usuario' => $idFilter,
            'estado' => 0,
            '$or' => [
                ['url_afiliado' => ['$exists' => false]],
                ['url_afiliado' => null],
                ['url_afiliado' => '']
            ]
        ], [
            'sort' => ['fecha_publicacion' => -1]
        ]);

        // Preparar colección de marcas para fallback de imagen
        $db = createConnection();
        $collection_marcas = $db ? $db->selectCollection('marcas') : null;

        $marcas_agrupadas = [];
        foreach ($cursor as $documento) {
            $marca_clave = $documento['marca'] ?? 'sin_marca';
            
            if (!isset($marcas_agrupadas[$marca_clave])) {
                $urlImagen = $documento['url_imagen'] ?? '';
                if (empty($urlImagen) && $collection_marcas) {
                    try {
                        $marca_bd = $collection_marcas->findOne(['nombre_clave' => $marca_clave]);
                        if ($marca_bd && !empty($marca_bd['imagen'])) {
                            $urlImagen = $marca_bd['imagen'];
                        }
                    } catch (Throwable $e) {
                        // ignorar y dejar urlImagen vacío
                    }
                }

                $marcas_agrupadas[$marca_clave] = [
                    'nombre_marca' => $documento['marca'] ?? 'Sin Marca',
                    'url_imagen' => $urlImagen,
                    'total_codigos' => 0,
                    'codigos' => []
                ];
            }
            
            $marcas_agrupadas[$marca_clave]['codigos'][] = [
                '_id' => (string)$documento['_id'],
                'codigo' => $documento['codigo'] ?? '',
                'marca' => $documento['marca'] ?? '',
                'descripcion' => $documento['descripcion'] ?? '',
                'descuento' => $documento['descuento'] ?? '',
                'fecha_publicacion' => $documento['fecha_publicacion'] ?? '',
                'totalclicks' => $documento['totalclicks'] ?? 0,
                'url_imagen' => $documento['url_imagen'] ?? ''
            ];
            
            $marcas_agrupadas[$marca_clave]['total_codigos']++;
        }

        return ['success' => true, 'marcas' => $marcas_agrupadas];
    } catch (Throwable $e) {
        error_log("Error al obtener códigos sin afiliado: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno: ' . $e->getMessage()];
    }
}

/**
 * Obtiene códigos sin URL de afiliado asignada para una marca específica
 */
function obtenerCodigosSinAfiliadoPorMarca($usuario_id, $marca_clave) {
    $collection_codigos = getCollectionCodigos();
    if (!$collection_codigos) {
        return ['success' => false, 'error' => 'Error de conexión'];
    }

    try {
        // Aceptar id de usuario como String u ObjectId
        $idFilter = ['$in' => [$usuario_id]];
        try { $idFilter['$in'][] = new MongoDB\BSON\ObjectId($usuario_id); } catch (Throwable $e) {}

        $cursor = $collection_codigos->find([
            'id_usuario' => $idFilter,
            'estado' => 0,
            'marca' => $marca_clave,
            '$or' => [
                ['url_afiliado' => ['$exists' => false]],
                ['url_afiliado' => null],
                ['url_afiliado' => '']
            ]
        ], [
            'sort' => ['fecha_publicacion' => -1]
        ]);

        $codigos = [];
        foreach ($cursor as $documento) {
            $codigos[] = [
                '_id' => (string)$documento['_id'],
                'codigo' => $documento['codigo'] ?? '',
                'marca' => $documento['marca'] ?? '',
                'descripcion' => $documento['descripcion'] ?? '',
                'descuento' => $documento['descuento'] ?? '',
                'fecha_publicacion' => $documento['fecha_publicacion'] ?? '',
                'totalclicks' => $documento['totalclicks'] ?? 0,
                'url_imagen' => $documento['url_imagen'] ?? ''
            ];
        }

        return ['success' => true, 'codigos' => $codigos];
    } catch (Throwable $e) {
        error_log("Error al obtener códigos sin afiliado por marca: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno: ' . $e->getMessage()];
    }
}

/**
 * Asigna múltiples códigos a una URL de afiliado
 */
function asignarMultiplesCodigosAAfiliado($codigos_ids, $url_afiliado_id, $usuario_id) {
    $collection_codigos = getCollectionCodigos();
    $collection_afiliados = getCollectionAfiliados();
    
    if (!$collection_codigos || !$collection_afiliados) {
        return ['success' => false, 'error' => 'Error de conexión'];
    }

    try {
        // Verificar que la URL de afiliado pertenece al usuario
        $afiliado = $collection_afiliados->findOne([
            '_id' => new MongoDB\BSON\ObjectId($url_afiliado_id),
            'usuario_id' => $usuario_id,
            'activo' => true
        ]);

        if (!$afiliado) {
            return ['success' => false, 'error' => 'URL de afiliado no encontrada o no válida'];
        }

        $codigos_asignados = 0;
        $errores = [];

        foreach ($codigos_ids as $codigo_id) {
            // Verificar que el código pertenece al usuario
            $idFilter = ['$in' => [$usuario_id]];
            try { $idFilter['$in'][] = new MongoDB\BSON\ObjectId($usuario_id); } catch (Throwable $e) {}

            $codigo = $collection_codigos->findOne([
                '_id' => new MongoDB\BSON\ObjectId($codigo_id),
                'id_usuario' => $idFilter,
                'estado' => 0
            ]);

            if ($codigo) {
                // Actualizar el código con la URL de afiliado
                $resultado = $collection_codigos->updateOne(
                    ['_id' => new MongoDB\BSON\ObjectId($codigo_id)],
                    [
                        '$set' => [
                            'url_afiliado' => (string)$url_afiliado_id,
                            'fecha_asignacion_afiliado' => new MongoDB\BSON\UTCDateTime(),
                            'fecha_modificacion' => date('Y-m-d H:i:s')
                        ]
                    ]
                );

                if ($resultado->getModifiedCount() > 0) {
                    $codigos_asignados++;
                }
            } else {
                $errores[] = "Código {$codigo_id} no encontrado o no válido";
            }
        }

        if ($codigos_asignados > 0) {
            return [
                'success' => true, 
                'mensaje' => "{$codigos_asignados} códigos asignados correctamente",
                'codigos_asignados' => $codigos_asignados,
                'errores' => $errores
            ];
        } else {
            return ['success' => false, 'error' => 'No se pudo asignar ningún código'];
        }
    } catch (Throwable $e) {
        error_log("Error al asignar múltiples códigos: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno: ' . $e->getMessage()];
    }
}

/**
 * Obtiene estadísticas de afiliados con códigos asignados
 */
function obtenerEstadisticasAfiliadosConCodigos($usuario_id) {
    $collection_afiliados = getCollectionAfiliados();
    $collection_codigos = getCollectionCodigos();
    
    if (!$collection_afiliados || !$collection_codigos) {
        return ['success' => false, 'error' => 'Error de conexión'];
    }

    try {
        // Obtener URLs de afiliados del usuario
        $cursor_afiliados = $collection_afiliados->find([
            'usuario_id' => $usuario_id,
            'activo' => true
        ]);

        $afiliados_con_codigos = [];
        foreach ($cursor_afiliados as $afiliado) {
            // Contar códigos asignados a esta URL
            $idFilter = ['$in' => [$usuario_id]];
            try { $idFilter['$in'][] = new MongoDB\BSON\ObjectId($usuario_id); } catch (Throwable $e) {}

            $count_codigos = $collection_codigos->countDocuments([
                'id_usuario' => $idFilter,
                'estado' => 0,
                'url_afiliado' => (string)$afiliado['_id']
            ]);

            if ($count_codigos > 0) {
                $afiliados_con_codigos[] = [
                    '_id' => $afiliado['_id'],
                    'nombre_plataforma' => $afiliado['nombre_plataforma'],
                    'url' => $afiliado['url'],
                    'descripcion' => $afiliado['descripcion'],
                    'total_codigos' => $count_codigos,
                    'fecha_creacion' => $afiliado['fecha_creacion']->toDateTime()->format('Y-m-d H:i:s')
                ];
            }
        }

        return ['success' => true, 'afiliados' => $afiliados_con_codigos];
    } catch (Throwable $e) {
        error_log("Error al obtener estadísticas afiliados: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno: ' . $e->getMessage()];
    }
}

/**
 * Asigna un código a una URL de afiliado
 */
function asignarCodigoAAfiliado($codigo_id, $url_afiliado_id, $usuario_id) {
    $collection_codigos = getCollectionCodigos();
    $collection_afiliados = getCollectionAfiliados();
    
    if (!$collection_codigos || !$collection_afiliados) {
        return ['success' => false, 'error' => 'Error de conexión'];
    }

    try {
        // Limpiar y validar codigo_id
        $codigo_id = trim((string)$codigo_id);
        if (empty($codigo_id) || !MongoDB\BSON\ObjectId::isValid($codigo_id)) {
            return ['success' => false, 'error' => 'ID de código no válido: ' . $codigo_id];
        }
        
        // Limpiar y validar url_afiliado_id
        $url_afiliado_id = trim((string)$url_afiliado_id);
        if (empty($url_afiliado_id) || !MongoDB\BSON\ObjectId::isValid($url_afiliado_id)) {
            return ['success' => false, 'error' => 'ID de URL de afiliado no válido: ' . $url_afiliado_id];
        }
        
        // Verificar que el código pertenece al usuario
        $idFilter = ['$in' => [$usuario_id]];
        try { $idFilter['$in'][] = new MongoDB\BSON\ObjectId($usuario_id); } catch (Throwable $e) {}

        $codigo = $collection_codigos->findOne([
            '_id' => new MongoDB\BSON\ObjectId($codigo_id),
            'id_usuario' => $idFilter,
            'estado' => 0
        ]);

        if (!$codigo) {
            return ['success' => false, 'error' => 'Código no encontrado o no válido'];
        }

        // Verificar que la URL de afiliado pertenece al usuario
        $afiliado = $collection_afiliados->findOne([
            '_id' => new MongoDB\BSON\ObjectId($url_afiliado_id),
            'usuario_id' => $usuario_id,
            'activo' => true
        ]);

        if (!$afiliado) {
            return ['success' => false, 'error' => 'URL de afiliado no encontrada o no válida'];
        }

        // Actualizar el código con la URL de afiliado
        $resultado = $collection_codigos->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($codigo_id)],
            [
                '$set' => [
                    'url_afiliado' => (string)$url_afiliado_id,
                    'fecha_asignacion_afiliado' => new MongoDB\BSON\UTCDateTime(),
                    'fecha_modificacion' => date('Y-m-d H:i:s')
                ]
            ]
        );

        if ($resultado->getModifiedCount() > 0) {
            return ['success' => true, 'mensaje' => 'Código asignado correctamente'];
        } else {
            return ['success' => false, 'error' => 'No se pudo asignar el código'];
        }
    } catch (Throwable $e) {
        error_log("Error al asignar código a afiliado: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno: ' . $e->getMessage()];
    }
}

/**
 * Desasigna un código de una URL de afiliado
 */
function desasignarCodigoDeAfiliado($codigo_id, $usuario_id) {
    $collection_codigos = getCollectionCodigos();
    
    if (!$collection_codigos) {
        return ['success' => false, 'error' => 'Error de conexión'];
    }

    try {
        // Verificar que el código pertenece al usuario
        $idFilter = ['$in' => [$usuario_id]];
        try { $idFilter['$in'][] = new MongoDB\BSON\ObjectId($usuario_id); } catch (Throwable $e) {}

        $codigo = $collection_codigos->findOne([
            '_id' => new MongoDB\BSON\ObjectId($codigo_id),
            'id_usuario' => $idFilter,
            'estado' => 0
        ]);

        if (!$codigo) {
            return ['success' => false, 'error' => 'Código no encontrado o no válido'];
        }

        // Remover la asignación del código
        $resultado = $collection_codigos->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($codigo_id)],
            [
                '$unset' => [
                    'url_afiliado' => '',
                    'fecha_asignacion_afiliado' => ''
                ],
                '$set' => [
                    'fecha_modificacion' => date('Y-m-d H:i:s')
                ]
            ]
        );

        if ($resultado->getModifiedCount() > 0) {
            return ['success' => true, 'mensaje' => 'Código desasignado correctamente'];
        } else {
            return ['success' => false, 'error' => 'No se pudo desasignar el código'];
        }
    } catch (Throwable $e) {
        error_log("Error al desasignar código de afiliado: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno: ' . $e->getMessage()];
    }
}

/**
 * Obtiene códigos asignados a una URL de afiliado específica
 */
function obtenerCodigosAsignadosAAfiliado($url_afiliado_id, $usuario_id) {
    $collection_codigos = getCollectionCodigos();
    
    if (!$collection_codigos) {
        return ['success' => false, 'error' => 'Error de conexión'];
    }

    try {
        $cursor = $collection_codigos->find([
            'id_usuario' => $usuario_id,
            'estado' => 0,
            'url_afiliado' => (string)$url_afiliado_id
        ], [
            'sort' => ['fecha_asignacion_afiliado' => -1]
        ]);

        $codigos = [];
        foreach ($cursor as $documento) {
            $codigos[] = [
                '_id' => $documento['_id'],
                'codigo' => $documento['codigo'] ?? '',
                'marca' => $documento['marca'] ?? '',
                'descripcion' => $documento['descripcion'] ?? '',
                'descuento' => $documento['descuento'] ?? '',
                'totalclicks' => $documento['totalclicks'] ?? 0,
                'fecha_asignacion_afiliado' => isset($documento['fecha_asignacion_afiliado']) ? 
                    $documento['fecha_asignacion_afiliado']->toDateTime()->format('Y-m-d H:i:s') : ''
            ];
        }

        return ['success' => true, 'codigos' => $codigos];
    } catch (Throwable $e) {
        error_log("Error al obtener códigos asignados: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno: ' . $e->getMessage()];
    }
}

?>
