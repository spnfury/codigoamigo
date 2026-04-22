<?php

/**
 * Funciones para el sistema de votación de chollos
 */

// Incluir funciones de conexión a MongoDB
if (!function_exists('createConnection')) {
    include_once __DIR__ . '/funciones.php';
}
if (!function_exists('getCollectionChollos')) {
    include_once __DIR__ . '/funciones_chollos.php';
}

/**
 * Obtiene la colección de votos de chollos
 */
function getCollectionCholloVotos() {
    $db = createConnection();
    if (!$db) {
        return null;
    }

    try {
        return $db->selectCollection('chollo_votos');
    } catch (Throwable $e) {
        error_log("Error al obtener colección de votos: " . $e->getMessage());
        return null;
    }
}

/**
 * Vota un chollo (positivo o negativo)
 * @param string $chollo_id ID del chollo
 * @param string $usuario_id ID del usuario
 * @param string $tipo 'positivo' o 'negativo'
 * @return array ['success' => bool, 'temperatura' => int, 'voto_actual' => string]
 */
function votarChollo($chollo_id, $usuario_id, $tipo) {
    $collection_votos = getCollectionCholloVotos();
    $collection_chollos = getCollectionChollos();
    
    if (!$collection_votos || !$collection_chollos) {
        return ['success' => false, 'error' => 'Error de conexión'];
    }

    try {
        $chollo_oid = new MongoDB\BSON\ObjectId($chollo_id);
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
            'chollo_id' => $chollo_oid,
            'usuario_id' => $usuario_oid
        ]);

        if ($voto_existente) {
            // Si el voto es el mismo, eliminarlo (toggle)
            if ($voto_existente['tipo'] === $tipo) {
                $collection_votos->deleteOne([
                    'chollo_id' => $chollo_oid,
                    'usuario_id' => $usuario_oid
                ]);
                
                // Actualizar contador en chollo
                $campo = $tipo === 'positivo' ? 'votos_positivos' : 'votos_negativos';
                $collection_chollos->updateOne(
                    ['_id' => $chollo_oid],
                    ['$inc' => [$campo => -1]]
                );
                
                $temperatura = actualizarTemperaturaChollo($chollo_id);
                return [
                    'success' => true,
                    'temperatura' => $temperatura,
                    'voto_actual' => null
                ];
            } else {
                // Cambiar el voto
                $collection_votos->updateOne(
                    [
                        'chollo_id' => $chollo_oid,
                        'usuario_id' => $usuario_oid
                    ],
                    [
                        '$set' => [
                            'tipo' => $tipo,
                            'fecha' => new MongoDB\BSON\UTCDateTime()
                        ]
                    ]
                );
                
                // Actualizar contadores en chollo
                $campo_incrementar = $tipo === 'positivo' ? 'votos_positivos' : 'votos_negativos';
                $campo_decrementar = $tipo === 'positivo' ? 'votos_negativos' : 'votos_positivos';
                
                $collection_chollos->updateOne(
                    ['_id' => $chollo_oid],
                    [
                        '$inc' => [
                            $campo_incrementar => 1,
                            $campo_decrementar => -1
                        ]
                    ]
                );
                
                $temperatura = actualizarTemperaturaChollo($chollo_id);
                return [
                    'success' => true,
                    'temperatura' => $temperatura,
                    'voto_actual' => $tipo
                ];
            }
        } else {
            // Crear nuevo voto
            $collection_votos->insertOne([
                'chollo_id' => $chollo_oid,
                'usuario_id' => $usuario_oid,
                'tipo' => $tipo,
                'fecha' => new MongoDB\BSON\UTCDateTime()
            ]);
            
            // Actualizar contador en chollo
            $campo = $tipo === 'positivo' ? 'votos_positivos' : 'votos_negativos';
            $collection_chollos->updateOne(
                ['_id' => $chollo_oid],
                [
                    '$inc' => [$campo => 1]
                ]
            );
            
            $temperatura = actualizarTemperaturaChollo($chollo_id);
            return [
                'success' => true,
                'temperatura' => $temperatura,
                'voto_actual' => $tipo
            ];
        }
    } catch (Throwable $e) {
        error_log("Error al votar chollo: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno'];
    }
}

/**
 * Obtiene el voto del usuario para un chollo
 * @param string $chollo_id ID del chollo
 * @param string $usuario_id ID del usuario
 * @return string|null 'positivo', 'negativo' o null
 */
function obtenerVotoUsuario($chollo_id, $usuario_id) {
    $collection = getCollectionCholloVotos();
    if (!$collection) {
        return null;
    }

    try {
        $chollo_oid = new MongoDB\BSON\ObjectId($chollo_id);
        $usuario_oid = new MongoDB\BSON\ObjectId($usuario_id);
        
        $voto = $collection->findOne([
            'chollo_id' => $chollo_oid,
            'usuario_id' => $usuario_oid
        ]);
        
        return $voto ? $voto['tipo'] : null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Actualiza la temperatura de un chollo
 * @param string $chollo_id ID del chollo
 * @return int Temperatura calculada
 */
function actualizarTemperaturaChollo($chollo_id) {
    $collection = getCollectionChollos();
    if (!$collection) {
        return 0;
    }

    try {
        $chollo_oid = new MongoDB\BSON\ObjectId($chollo_id);
        $chollo = $collection->findOne(['_id' => $chollo_oid]);
        
        if (!$chollo) {
            return 0;
        }
        
        $votos_positivos = $chollo['votos_positivos'] ?? 0;
        $votos_negativos = $chollo['votos_negativos'] ?? 0;
        $temperatura = $votos_positivos - $votos_negativos;
        
        $collection->updateOne(
            ['_id' => $chollo_oid],
            ['$set' => ['temperatura' => $temperatura]]
        );
        
        return $temperatura;
    } catch (Exception $e) {
        error_log("Error al actualizar temperatura: " . $e->getMessage());
        return 0;
    }
}

/**
 * Obtiene los chollos más calientes (más votados)
 * @param int $limite Número de chollos a obtener
 * @return array Lista de chollos
 */
function obtenerChollosMasCalientes($limite = 5) {
    $collection = getCollectionChollos();
    if (!$collection) {
        return [];
    }

    try {
        $cursor = $collection->find(
            ['estado' => 1],
            [
                'sort' => ['temperatura' => -1],
                'limit' => $limite
            ]
        );
        
        $chollos = [];
        foreach ($cursor as $doc) {
            // Convertir categoría
            $categoria = $doc['categoria'] ?? 'general';
            if (is_object($categoria)) {
                if (method_exists($categoria, 'toArray')) {
                    $categoria = $categoria->toArray();
                } else {
                    $categoria = (array)$categoria;
                }
            }
            if (!is_array($categoria)) {
                $categoria = [$categoria];
            }
            $categoria = array_map(function($cat) {
                return is_object($cat) ? (string)$cat : $cat;
            }, array_values($categoria));
            
            $chollos[] = [
                'id' => (string)$doc['_id'],
                'titulo' => $doc['titulo'] ?? '',
                'descripcion' => $doc['descripcion'] ?? '',
                'precio_original' => $doc['precio_original'] ?? null,
                'precio_descuento' => $doc['precio_descuento'] ?? null,
                'porcentaje_descuento' => $doc['porcentaje_descuento'] ?? null,
                'enlace' => $doc['enlace'] ?? '',
                'imagen' => $doc['imagen'] ?? '',
                'categoria' => $categoria,
                'temperatura' => $doc['temperatura'] ?? 0,
                'votos_positivos' => $doc['votos_positivos'] ?? 0,
                'votos_negativos' => $doc['votos_negativos'] ?? 0,
                'total_comentarios' => $doc['total_comentarios'] ?? 0,
                'clicks' => $doc['clicks'] ?? 0
            ];
        }
        
        return $chollos;
    } catch (Throwable $e) {
        error_log("Error al obtener chollos más calientes: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene estadísticas de votos de un chollo
 * @param string $chollo_id ID del chollo
 * @return array ['votos_positivos' => int, 'votos_negativos' => int, 'temperatura' => int]
 */
function obtenerEstadisticasVotosChollo($chollo_id) {
    $collection = getCollectionChollos();
    if (!$collection) {
        return ['votos_positivos' => 0, 'votos_negativos' => 0, 'temperatura' => 0];
    }

    try {
        $chollo_oid = new MongoDB\BSON\ObjectId($chollo_id);
        $chollo = $collection->findOne(['_id' => $chollo_oid]);
        
        if (!$chollo) {
            return ['votos_positivos' => 0, 'votos_negativos' => 0, 'temperatura' => 0];
        }
        
        return [
            'votos_positivos' => $chollo['votos_positivos'] ?? 0,
            'votos_negativos' => $chollo['votos_negativos'] ?? 0,
            'temperatura' => $chollo['temperatura'] ?? 0
        ];
    } catch (Exception $e) {
        return ['votos_positivos' => 0, 'votos_negativos' => 0, 'temperatura' => 0];
    }
}

/**
 * Obtiene los chollos más calientes de las últimas 24h
 * @param int $limite Número de chollos a obtener
 * @return array Lista de chollos
 */
function obtenerChollosMasCalientes24h($limite = 5, $categoria = null) {
    $collection = getCollectionChollos();
    if (!$collection) {
        return [];
    }

    try {
        // Calcular fecha hace 24h
        $fecha_24h = new MongoDB\BSON\UTCDateTime((time() - 24 * 3600) * 1000);
        
        $query = [
            'estado' => 1,
            'fecha_creacion' => ['$gte' => $fecha_24h]
        ];

        // Filtro por categoría if provided
        if (!empty($categoria)) {
            $query['$or'] = [
                ['categoria' => $categoria],
                ['categoria' => ['$in' => [$categoria]]]
            ];
        }
        
        $cursor = $collection->find(
            $query,
            [
                'sort' => ['temperatura' => -1],
                'limit' => $limite
            ]
        );
        
        $chollos = [];
        foreach ($cursor as $doc) {
            // Procesar categoría
            $categoria = $doc['categoria'] ?? 'general';
            if (is_object($categoria)) {
                if (method_exists($categoria, 'toArray')) {
                    $categoria = $categoria->toArray();
                } else {
                    $categoria = (array)$categoria;
                }
            }
            if (!is_array($categoria)) {
                $categoria = [$categoria];
            }
            $categoria = array_map(function($cat) {
                return is_object($cat) ? (string)$cat : $cat;
            }, array_values($categoria));
            
            $chollos[] = [
                'id' => (string)$doc['_id'],
                'titulo' => $doc['titulo'] ?? '',
                'imagen' => $doc['imagen'] ?? '',
                'categoria' => $categoria,
                'temperatura' => $doc['temperatura'] ?? 0,
                'precio_descuento' => $doc['precio_descuento'] ?? null,
            ];
        }
        
        return $chollos;
    } catch (Throwable $e) {
        error_log("Error al obtener chollos calientes 24h: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene los chollos más populares de las últimas 24h (por clicks)
 * @param int $limite Número de chollos a obtener
 * @return array Lista de chollos
 */
function obtenerChollosMasPopulares24h($limite = 5, $categoria = null) {
    $collection = getCollectionChollos();
    if (!$collection) {
        return [];
    }

    try {
        // Calcular fecha hace 24h
        $fecha_24h = new MongoDB\BSON\UTCDateTime((time() - 24 * 3600) * 1000);
        
        $query = [
            'estado' => 1,
            'fecha_creacion' => ['$gte' => $fecha_24h]
        ];

        // Filtro por categoría if provided
        if (!empty($categoria)) {
            $query['$or'] = [
                ['categoria' => $categoria],
                ['categoria' => ['$in' => [$categoria]]]
            ];
        }
        
        $cursor = $collection->find(
            $query,
            [
                'sort' => ['clicks' => -1],
                'limit' => $limite
            ]
        );
        
        $chollos = [];
        foreach ($cursor as $doc) {
            // Procesar categoría
            $categoria = $doc['categoria'] ?? 'general';
            if (is_object($categoria)) {
                if (method_exists($categoria, 'toArray')) {
                    $categoria = $categoria->toArray();
                } else {
                    $categoria = (array)$categoria;
                }
            }
            if (!is_array($categoria)) {
                $categoria = [$categoria];
            }
            $categoria = array_map(function($cat) {
                return is_object($cat) ? (string)$cat : $cat;
            }, array_values($categoria));
            
            $chollos[] = [
                'id' => (string)$doc['_id'],
                'titulo' => $doc['titulo'] ?? '',
                'imagen' => $doc['imagen'] ?? '',
                'categoria' => $categoria,
                'clicks' => $doc['clicks'] ?? 0,
                'precio_descuento' => $doc['precio_descuento'] ?? null,
            ];
        }
        
        return $chollos;
    } catch (Throwable $e) {
        error_log("Error al obtener chollos populares 24h: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene los chollos más calientes de los últimos 7 días
 * @param int $limite Número de chollos a obtener
 * @param string|null $categoria Categoría opcional para filtrar
 * @return array Lista de chollos
 */
function obtenerChollosMasCalientes7d($limite = 5, $categoria = null) {
    $collection = getCollectionChollos();
    if (!$collection) {
        return [];
    }

    try {
        // Calcular fecha hace 7 días
        $fecha_7d = new MongoDB\BSON\UTCDateTime((time() - 7 * 24 * 3600) * 1000);
        
        $query = [
            'estado' => 1,
            'fecha_creacion' => ['$gte' => $fecha_7d]
        ];

        // Filtro por categoría if provided
        if (!empty($categoria)) {
            $query['$or'] = [
                ['categoria' => $categoria],
                ['categoria' => ['$in' => [$categoria]]]
            ];
        }
        
        $cursor = $collection->find(
            $query,
            [
                'sort' => ['temperatura' => -1],
                'limit' => $limite
            ]
        );
        
        $chollos = [];
        foreach ($cursor as $doc) {
            // Procesar categoría
            $categoria = $doc['categoria'] ?? 'general';
            if (is_object($categoria)) {
                if (method_exists($categoria, 'toArray')) {
                    $categoria = $categoria->toArray();
                } else {
                    $categoria = (array)$categoria;
                }
            }
            if (!is_array($categoria)) {
                $categoria = [$categoria];
            }
            $categoria = array_map(function($cat) {
                return is_object($cat) ? (string)$cat : $cat;
            }, array_values($categoria));
            
            $chollos[] = [
                'id' => (string)$doc['_id'],
                'titulo' => $doc['titulo'] ?? '',
                'imagen' => $doc['imagen'] ?? '',
                'categoria' => $categoria,
                'temperatura' => $doc['temperatura'] ?? 0,
                'precio_descuento' => $doc['precio_descuento'] ?? null,
            ];
        }
        
        return $chollos;
    } catch (Throwable $e) {
        error_log("Error al obtener chollos calientes 7d: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene los chollos más populares de los últimos 7 días (por clicks)
 * @param int $limite Número de chollos a obtener
 * @param string|null $categoria Categoría opcional para filtrar
 * @return array Lista de chollos
 */
function obtenerChollosMasPopulares7d($limite = 5, $categoria = null) {
    $collection = getCollectionChollos();
    if (!$collection) {
        return [];
    }

    try {
        // Calcular fecha hace 7 días
        $fecha_7d = new MongoDB\BSON\UTCDateTime((time() - 7 * 24 * 3600) * 1000);
        
        $query = [
            'estado' => 1,
            'fecha_creacion' => ['$gte' => $fecha_7d]
        ];

        // Filtro por categoría if provided
        if (!empty($categoria)) {
            $query['$or'] = [
                ['categoria' => $categoria],
                ['categoria' => ['$in' => [$categoria]]]
            ];
        }
        
        $cursor = $collection->find(
            $query,
            [
                'sort' => ['clicks' => -1],
                'limit' => $limite
            ]
        );
        
        $chollos = [];
        foreach ($cursor as $doc) {
            // Procesar categoría
            $categoria = $doc['categoria'] ?? 'general';
            if (is_object($categoria)) {
                if (method_exists($categoria, 'toArray')) {
                    $categoria = $categoria->toArray();
                } else {
                    $categoria = (array)$categoria;
                }
            }
            if (!is_array($categoria)) {
                $categoria = [$categoria];
            }
            $categoria = array_map(function($cat) {
                return is_object($cat) ? (string)$cat : $cat;
            }, array_values($categoria));
            
            $chollos[] = [
                'id' => (string)$doc['_id'],
                'titulo' => $doc['titulo'] ?? '',
                'imagen' => $doc['imagen'] ?? '',
                'categoria' => $categoria,
                'clicks' => $doc['clicks'] ?? 0,
                'precio_descuento' => $doc['precio_descuento'] ?? null,
            ];
        }
        
        return $chollos;
    } catch (Throwable $e) {
        error_log("Error al obtener chollos populares 7d: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene los chollos más calientes de los últimos 30 días
 * @param int $limite Número de chollos a obtener
 * @param string|null $categoria Categoría opcional para filtrar
 * @return array Lista de chollos
 */
function obtenerChollosMasCalientes30d($limite = 5, $categoria = null) {
    $collection = getCollectionChollos();
    if (!$collection) {
        return [];
    }

    try {
        // Calcular fecha hace 30 días
        $fecha_30d = new MongoDB\BSON\UTCDateTime((time() - 30 * 24 * 3600) * 1000);
        
        $query = [
            'estado' => 1,
            'fecha_creacion' => ['$gte' => $fecha_30d]
        ];

        // Filtro por categoría if provided
        if (!empty($categoria)) {
            $query['$or'] = [
                ['categoria' => $categoria],
                ['categoria' => ['$in' => [$categoria]]]
            ];
        }
        
        $cursor = $collection->find(
            $query,
            [
                'sort' => ['temperatura' => -1],
                'limit' => $limite
            ]
        );
        
        $chollos = [];
        foreach ($cursor as $doc) {
            // Procesar categoría
            $categoria = $doc['categoria'] ?? 'general';
            if (is_object($categoria)) {
                if (method_exists($categoria, 'toArray')) {
                    $categoria = $categoria->toArray();
                } else {
                    $categoria = (array)$categoria;
                }
            }
            if (!is_array($categoria)) {
                $categoria = [$categoria];
            }
            $categoria = array_map(function($cat) {
                return is_object($cat) ? (string)$cat : $cat;
            }, array_values($categoria));
            
            $chollos[] = [
                'id' => (string)$doc['_id'],
                'titulo' => $doc['titulo'] ?? '',
                'imagen' => $doc['imagen'] ?? '',
                'categoria' => $categoria,
                'temperatura' => $doc['temperatura'] ?? 0,
                'precio_descuento' => $doc['precio_descuento'] ?? null,
            ];
        }
        
        return $chollos;
    } catch (Throwable $e) {
        error_log("Error al obtener chollos calientes 30d: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene los chollos más populares de los últimos 30 días (por clicks)
 * @param int $limite Número de chollos a obtener
 * @param string|null $categoria Categoría opcional para filtrar
 * @return array Lista de chollos
 */
function obtenerChollosMasPopulares30d($limite = 5, $categoria = null) {
    $collection = getCollectionChollos();
    if (!$collection) {
        return [];
    }

    try {
        // Calcular fecha hace 30 días
        $fecha_30d = new MongoDB\BSON\UTCDateTime((time() - 30 * 24 * 3600) * 1000);
        
        $query = [
            'estado' => 1,
            'fecha_creacion' => ['$gte' => $fecha_30d]
        ];

        // Filtro por categoría if provided
        if (!empty($categoria)) {
            $query['$or'] = [
                ['categoria' => $categoria],
                ['categoria' => ['$in' => [$categoria]]]
            ];
        }
        
        $cursor = $collection->find(
            $query,
            [
                'sort' => ['clicks' => -1],
                'limit' => $limite
            ]
        );
        
        $chollos = [];
        foreach ($cursor as $doc) {
            // Procesar categoría
            $categoria = $doc['categoria'] ?? 'general';
            if (is_object($categoria)) {
                if (method_exists($categoria, 'toArray')) {
                    $categoria = $categoria->toArray();
                } else {
                    $categoria = (array)$categoria;
                }
            }
            if (!is_array($categoria)) {
                $categoria = [$categoria];
            }
            $categoria = array_map(function($cat) {
                return is_object($cat) ? (string)$cat : $cat;
            }, array_values($categoria));
            
            $chollos[] = [
                'id' => (string)$doc['_id'],
                'titulo' => $doc['titulo'] ?? '',
                'imagen' => $doc['imagen'] ?? '',
                'categoria' => $categoria,
                'clicks' => $doc['clicks'] ?? 0,
                'precio_descuento' => $doc['precio_descuento'] ?? null,
            ];
        }
        
        return $chollos;
    } catch (Throwable $e) {
        error_log("Error al obtener chollos populares 30d: " . $e->getMessage());
        return [];
    }
}
