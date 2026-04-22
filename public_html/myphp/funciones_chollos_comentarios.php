<?php

/**
 * Funciones para el sistema de comentarios de chollos
 */

// Incluir funciones de conexión a MongoDB
if (!function_exists('createConnection')) {
    include_once __DIR__ . '/funciones.php';
}

if (!function_exists('get_object_user')) {
    include_once __DIR__ . '/funciones_usuario.php';
}

if (!function_exists('getCollectionChollos')) {
    include_once __DIR__ . '/funciones_chollos.php';
}

/**
 * Obtiene la colección de comentarios de chollos
 */
function getCollectionCholloComentarios() {
    $db = createConnection();
    if (!$db) {
        return null;
    }

    try {
        return $db->selectCollection('chollo_comentarios');
    } catch (Throwable $e) {
        error_log("Error al obtener colección de comentarios: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtiene la colección de votos de comentarios
 */
function getCollectionComentarioVotos() {
    $db = createConnection();
    if (!$db) {
        return null;
    }

    try {
        return $db->selectCollection('comentario_votos');
    } catch (Throwable $e) {
        error_log("Error al obtener colección de votos de comentarios: " . $e->getMessage());
        return null;
    }
}

/**
 * Crea un nuevo comentario
 * @param array $datos ['chollo_id', 'usuario_id', 'comentario', 'padre_id' (opcional)]
 * @return array ['success' => bool, 'id' => string]
 */
function crearComentario($datos) {
    $collection_comentarios = getCollectionCholloComentarios();
    $collection_chollos = getCollectionChollos();
    
    if (!$collection_comentarios || !$collection_chollos) {
        return ['success' => false, 'error' => 'Error de conexión'];
    }

    // Validar campos requeridos
    if (empty($datos['chollo_id']) || empty($datos['usuario_id']) || empty($datos['comentario'])) {
        return ['success' => false, 'error' => 'Campos requeridos faltantes'];
    }

    try {
        $chollo_oid = new MongoDB\BSON\ObjectId($datos['chollo_id']);
        $usuario_oid = new MongoDB\BSON\ObjectId($datos['usuario_id']);
        
        // Obtener datos del usuario
        $usuario = get_object_user('_id', $usuario_oid);
        if (!$usuario) {
            return ['success' => false, 'error' => 'Usuario no encontrado'];
        }

        $documento = [
            'chollo_id' => $chollo_oid,
            'usuario_id' => $usuario_oid,
            'usuario_nombre' => $usuario['username'] ?? 'Usuario',
            'usuario_img' => $usuario['img'] ?? '',
            'comentario' => trim($datos['comentario']),
            'padre_id' => null,
            'votos_positivos' => 0,
            'votos_negativos' => 0,
            'fecha' => new MongoDB\BSON\UTCDateTime(),
            'editado' => false,
            'fecha_edicion' => null
        ];

        // Si es una respuesta a otro comentario
        if (!empty($datos['padre_id'])) {
            try {
                $documento['padre_id'] = new MongoDB\BSON\ObjectId($datos['padre_id']);
            } catch (Exception $e) {
                // Ignorar si el padre_id no es válido
            }
        }

        $resultado = $collection_comentarios->insertOne($documento);
        
        if ($resultado->getInsertedId()) {
            // Incrementar contador de comentarios en el chollo
            $collection_chollos->updateOne(
                ['_id' => $chollo_oid],
                ['$inc' => ['total_comentarios' => 1]]
            );

            // GESTIÓN DE NOTIFICACIONES
            if (!empty($datos['padre_id'])) {
                try {
                    // Incluir funciones necesarias
                    if (!function_exists('crear_notificacion')) {
                        include_once __DIR__ . '/funciones_notificaciones.php';
                    }
                    if (!function_exists('categoriaToSlug')) {
                        include_once __DIR__ . '/funciones_chollos_helpers.php';
                    }

                    // Buscar comentario padre para saber a quién notificar
                    $padre = $collection_comentarios->findOne(['_id' => new MongoDB\BSON\ObjectId($datos['padre_id'])]);
                    
                    if ($padre && (string)$padre['usuario_id'] !== (string)$usuario_oid) {
                        // Buscar chollo
                        $chollo = $collection_chollos->findOne(['_id' => $chollo_oid]);
                        
                        if ($chollo) {
                            // Determinar slug de categoría correctamente
                            $categoria = 'general';
                            if (isset($chollo['categoria'])) {
                                if (is_object($chollo['categoria']) && method_exists($chollo['categoria'], 'getArrayCopy')) {
                                    $cats = $chollo['categoria']->getArrayCopy();
                                } else {
                                    $cats = $chollo['categoria'];
                                }
                                
                                if (is_array($cats) && !empty($cats)) {
                                    $categoria = $cats[0];
                                } elseif (is_string($cats)) {
                                    $categoria = $cats;
                                }
                            }
                            
                            $slug_categoria = categoriaToSlug($categoria);
                            
                            crear_notificacion(
                                (string)$padre['usuario_id'],
                                'respuesta_comentario',
                                [
                                    'chollo_id' => (string)$chollo_oid,
                                    'chollo_slug' => $slug_categoria . '/' . (string)$chollo_oid, // Formato slug completo: categoria/id
                                    'chollo_titulo' => $chollo['titulo'],
                                    'comentario_id' => (string)$resultado->getInsertedId(),
                                    'autor_respuesta' => $usuario['username'] ?? 'Alguien'
                                ]
                            );
                        }
                    }
                } catch (Throwable $e) {
                    error_log("Error creando notificación: " . $e->getMessage());
                }
            }
            
            return [
                'success' => true,
                'id' => (string)$resultado->getInsertedId()
            ];
        } else {
            return ['success' => false, 'error' => 'Error al crear comentario'];
        }
    } catch (Throwable $e) {
        error_log("Error al crear comentario: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno'];
    }
}

/**
 * Obtiene los comentarios de un chollo
 * @param string $chollo_id ID del chollo
 * @param string $orden 'antiguos', 'nuevos', 'votados'
 * @return array Lista de comentarios organizados jerárquicamente
 */
function obtenerComentarios($chollo_id, $orden = 'antiguos') {
    $collection = getCollectionCholloComentarios();
    if (!$collection) {
        return [];
    }

    try {
        $chollo_oid = new MongoDB\BSON\ObjectId($chollo_id);
        
        // Determinar el orden
        $sort = ['fecha' => 1]; // Antiguos primero por defecto
        if ($orden === 'nuevos') {
            $sort = ['fecha' => -1];
        } elseif ($orden === 'votados') {
            $sort = ['votos_positivos' => -1, 'fecha' => -1];
        }
        
        $cursor = $collection->find(
            ['chollo_id' => $chollo_oid],
            ['sort' => $sort]
        );
        
        $comentarios = [];
        $comentarios_por_id = [];
        
        foreach ($cursor as $doc) {
            $comentario = [
                'id' => (string)$doc['_id'],
                'chollo_id' => (string)$doc['chollo_id'],
                'usuario_id' => (string)$doc['usuario_id'],
                'usuario_nombre' => $doc['usuario_nombre'] ?? 'Usuario',
                'usuario_img' => $doc['usuario_img'] ?? '',
                'comentario' => $doc['comentario'] ?? '',
                'padre_id' => isset($doc['padre_id']) ? (string)$doc['padre_id'] : null,
                'votos_positivos' => $doc['votos_positivos'] ?? 0,
                'votos_negativos' => $doc['votos_negativos'] ?? 0,
                'fecha' => isset($doc['fecha']) ? $doc['fecha']->toDateTime()->format('Y-m-d H:i:s') : '',
                'editado' => $doc['editado'] ?? false,
                'fecha_edicion' => isset($doc['fecha_edicion']) && $doc['fecha_edicion'] ? $doc['fecha_edicion']->toDateTime()->format('Y-m-d H:i:s') : null,
                'respuestas' => []
            ];
            
            $comentarios_por_id[$comentario['id']] = $comentario;
        }
        
        // Organizar jerárquicamente
        foreach ($comentarios_por_id as $id => &$comentario) {
            if ($comentario['padre_id'] && isset($comentarios_por_id[$comentario['padre_id']])) {
                $comentarios_por_id[$comentario['padre_id']]['respuestas'][] = &$comentario;
            } else {
                $comentarios[] = &$comentario;
            }
        }
        
        return $comentarios;
    } catch (Throwable $e) {
        error_log("Error al obtener comentarios: " . $e->getMessage());
        return [];
    }
}

/**
 * Vota un comentario
 * @param string $comentario_id ID del comentario
 * @param string $usuario_id ID del usuario
 * @param string $tipo 'positivo' o 'negativo'
 * @return array ['success' => bool, 'votos' => array]
 */
function votarComentario($comentario_id, $usuario_id, $tipo) {
    $collection_votos = getCollectionComentarioVotos();
    $collection_comentarios = getCollectionCholloComentarios();
    
    if (!$collection_votos || !$collection_comentarios) {
        return ['success' => false, 'error' => 'Error de conexión'];
    }

    try {
        $comentario_oid = new MongoDB\BSON\ObjectId($comentario_id);
        $usuario_oid = new MongoDB\BSON\ObjectId($usuario_id);
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'ID no válido'];
    }

    if (!in_array($tipo, ['positivo', 'negativo'])) {
        return ['success' => false, 'error' => 'Tipo de voto no válido'];
    }

    try {
        // Verificar si el usuario ya votó
        $voto_existente = $collection_votos->findOne([
            'comentario_id' => $comentario_oid,
            'usuario_id' => $usuario_oid
        ]);

        if ($voto_existente) {
            // Si el voto es el mismo, eliminarlo (toggle)
            if ($voto_existente['tipo'] === $tipo) {
                $collection_votos->deleteOne([
                    'comentario_id' => $comentario_oid,
                    'usuario_id' => $usuario_oid
                ]);
                
                $campo = $tipo === 'positivo' ? 'votos_positivos' : 'votos_negativos';
                $collection_comentarios->updateOne(
                    ['_id' => $comentario_oid],
                    ['$inc' => [$campo => -1]]
                );
            } else {
                // Cambiar el voto
                $collection_votos->updateOne(
                    [
                        'comentario_id' => $comentario_oid,
                        'usuario_id' => $usuario_oid
                    ],
                    [
                        '$set' => [
                            'tipo' => $tipo,
                            'fecha' => new MongoDB\BSON\UTCDateTime()
                        ]
                    ]
                );
                
                $campo_incrementar = $tipo === 'positivo' ? 'votos_positivos' : 'votos_negativos';
                $campo_decrementar = $tipo === 'positivo' ? 'votos_negativos' : 'votos_positivos';
                
                $collection_comentarios->updateOne(
                    ['_id' => $comentario_oid],
                    [
                        '$inc' => [
                            $campo_incrementar => 1,
                            $campo_decrementar => -1
                        ]
                    ]
                );
            }
        } else {
            // Crear nuevo voto
            $collection_votos->insertOne([
                'comentario_id' => $comentario_oid,
                'usuario_id' => $usuario_oid,
                'tipo' => $tipo,
                'fecha' => new MongoDB\BSON\UTCDateTime()
            ]);
            
            $campo = $tipo === 'positivo' ? 'votos_positivos' : 'votos_negativos';
            $collection_comentarios->updateOne(
                ['_id' => $comentario_oid],
                ['$inc' => [$campo => 1]]
            );
        }
        
        // Obtener votos actualizados
        $comentario = $collection_comentarios->findOne(['_id' => $comentario_oid]);
        
        return [
            'success' => true,
            'votos' => [
                'positivos' => $comentario['votos_positivos'] ?? 0,
                'negativos' => $comentario['votos_negativos'] ?? 0
            ]
        ];
    } catch (Throwable $e) {
        error_log("Error al votar comentario: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno'];
    }
}

/**
 * Edita un comentario
 * @param string $comentario_id ID del comentario
 * @param string $usuario_id ID del usuario
 * @param string $texto Nuevo texto del comentario
 * @return array ['success' => bool]
 */
function editarComentario($comentario_id, $usuario_id, $texto) {
    $collection = getCollectionCholloComentarios();
    if (!$collection) {
        return ['success' => false, 'error' => 'Error de conexión'];
    }

    try {
        $comentario_oid = new MongoDB\BSON\ObjectId($comentario_id);
        $usuario_oid = new MongoDB\BSON\ObjectId($usuario_id);
        
        // Verificar que el comentario pertenece al usuario
        $comentario = $collection->findOne([
            '_id' => $comentario_oid,
            'usuario_id' => $usuario_oid
        ]);
        
        if (!$comentario) {
            return ['success' => false, 'error' => 'Comentario no encontrado o no autorizado'];
        }
        
        $resultado = $collection->updateOne(
            ['_id' => $comentario_oid],
            [
                '$set' => [
                    'comentario' => trim($texto),
                    'editado' => true,
                    'fecha_edicion' => new MongoDB\BSON\UTCDateTime()
                ]
            ]
        );
        
        return ['success' => $resultado->getModifiedCount() > 0];
    } catch (Throwable $e) {
        error_log("Error al editar comentario: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno'];
    }
}

/**
 * Elimina un comentario
 * @param string $comentario_id ID del comentario
 * @param string $usuario_id ID del usuario
 * @return array ['success' => bool]
 */
function eliminarComentario($comentario_id, $usuario_id) {
    $collection_comentarios = getCollectionCholloComentarios();
    $collection_chollos = getCollectionChollos();
    
    if (!$collection_comentarios || !$collection_chollos) {
        return ['success' => false, 'error' => 'Error de conexión'];
    }

    try {
        $comentario_oid = new MongoDB\BSON\ObjectId($comentario_id);
        $usuario_oid = new MongoDB\BSON\ObjectId($usuario_id);
        
        // Verificar que el comentario pertenece al usuario
        $comentario = $collection_comentarios->findOne([
            '_id' => $comentario_oid,
            'usuario_id' => $usuario_oid
        ]);
        
        if (!$comentario) {
            return ['success' => false, 'error' => 'Comentario no encontrado o no autorizado'];
        }
        
        // Eliminar el comentario
        $resultado = $collection_comentarios->deleteOne(['_id' => $comentario_oid]);
        
        if ($resultado->getDeletedCount() > 0) {
            // Eliminar RESPUESTAS (Casada)
            // Buscamos hijos y los borramos (recursive? por ahora solo 1 nivel es usado en el sistema)
            $collection_comentarios->deleteMany(['padre_id' => $comentario_oid]);

            // Decrementar contador de comentarios en el chollo
            $collection_chollos->updateOne(
                ['_id' => $comentario['chollo_id']],
                ['$inc' => ['total_comentarios' => -1]]
            );
            
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'No se pudo eliminar el comentario'];
        }
    } catch (Throwable $e) {
        error_log("Error al eliminar comentario: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno'];
    }
}

/**
 * Obtiene el voto del usuario para un comentario
 * @param string $comentario_id ID del comentario
 * @param string $usuario_id ID del usuario
 * @return string|null 'positivo', 'negativo' o null
 */
function obtenerVotoUsuarioComentario($comentario_id, $usuario_id) {
    $collection = getCollectionComentarioVotos();
    if (!$collection) {
        return null;
    }

    try {
        $comentario_oid = new MongoDB\BSON\ObjectId($comentario_id);
        $usuario_oid = new MongoDB\BSON\ObjectId($usuario_id);
        
        $voto = $collection->findOne([
            'comentario_id' => $comentario_oid,
            'usuario_id' => $usuario_oid
        ]);
        
        return $voto ? $voto['tipo'] : null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Obtiene los últimos comentarios globales de todos los chollos
 * @param int $limite Número de comentarios a obtener
 * @return array
 */
function obtenerUltimosComentariosGlobales($limite = 5) {
    $collection = getCollectionCholloComentarios();
    if (!$collection) return [];
    
    try {
        $pipeline = [
            ['$sort' => ['fecha' => -1]],
            ['$limit' => $limite],
            ['$lookup' => [
                'from' => 'chollos',
                'localField' => 'chollo_id',
                'foreignField' => '_id',
                'as' => 'chollo'
            ]],
            ['$unwind' => '$chollo']
        ];
        
        $cursor = $collection->aggregate($pipeline);
        $comentarios = [];
        
        foreach ($cursor as $doc) {
            $comentarios[] = [
                'usuario_nombre' => $doc['usuario_nombre'] ?? 'Usuario',
                'usuario_img' => $doc['usuario_img'] ?? '',
                'comentario' => $doc['comentario'],
                'fecha' => isset($doc['fecha']) ? $doc['fecha']->toDateTime()->format('Y-m-d H:i:s') : '',
                'chollo_titulo' => $doc['chollo']['titulo'],
                'chollo_slug' => isset($doc['chollo']['slug']) ? $doc['chollo']['slug'] : '',
                'chollo_id' => (string)$doc['chollo']['_id'],
                'chollo_categoria' => (function($cat) {
                    if (is_object($cat) && method_exists($cat, 'getArrayCopy')) {
                        $cat = $cat->getArrayCopy();
                    }
                    if (is_array($cat)) {
                        return isset($cat[0]) ? $cat[0] : 'general';
                    }
                    return (string)$cat ?: 'general';
                })($doc['chollo']['categoria'] ?? 'general')
            ];
        }
        
        return $comentarios;
    } catch (Throwable $e) {
        error_log("Error obteniendo últimos comentarios globales: " . $e->getMessage());
        return [];
    }
}
