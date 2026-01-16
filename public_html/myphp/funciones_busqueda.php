<?php
// Funciones de búsqueda simplificadas para CodigoAmigo.com

function procesar_termino_busqueda($termino) {
    // Limpiar y procesar el término de búsqueda
    $termino = trim($termino);
    $termino = preg_replace('/\s+/', ' ', $termino); // Eliminar espacios múltiples
    $termino = htmlspecialchars($termino, ENT_QUOTES, 'UTF-8'); // Sanitizar
    return $termino;
}

function crear_filtro_marcas_mejorado($terminos_busqueda) {
    // Crear filtro para búsqueda en marcas
    $array_filtro = array("estado" => 0);
    
    if (!empty($terminos_busqueda)) {
        $array_filtro['$or'] = array(
            array("nombre" => new MongoDB\BSON\Regex($terminos_busqueda, 'i')),
            array("nombre_clave" => new MongoDB\BSON\Regex($terminos_busqueda, 'i')),
            array("categoria" => new MongoDB\BSON\Regex($terminos_busqueda, 'i'))
        );
    }
    
    return $array_filtro;
}

function buscar_marcas_mejorado($array_filtro) {
    // Buscar marcas con el filtro proporcionado
    try {
        $collection = getCollectionMarcas();
        $cursor = $collection->find($array_filtro);
        return $cursor->toArray();
    } catch (Exception $e) {
        return array();
    }
}

function crear_filtro_codigos_mejorado($terminos_busqueda) {
    // Crear filtro para búsqueda en códigos
    $array_filtro = array("estado" => 0);
    
    if (!empty($terminos_busqueda)) {
        $array_filtro['$or'] = array(
            array("marca" => new MongoDB\BSON\Regex($terminos_busqueda, 'i')),
            array("descripcion" => new MongoDB\BSON\Regex($terminos_busqueda, 'i')),
            array("categoria" => new MongoDB\BSON\Regex($terminos_busqueda, 'i'))
        );
    }
    
    return $array_filtro;
}

function record_search_term($termino) {
    // Registrar término de búsqueda en la base de datos
    try {
        // Usar la colección de logs como alternativa
        $collection = getCollectionLogs();
        
        // Crear documento con estadísticas de búsqueda
        $search_doc = array(
            'type' => 'search_term',
            'term' => strtolower(trim($termino)),
            'date' => new MongoDB\BSON\UTCDateTime(),
            'timestamp' => time(),
            'day' => date('Y-m-d'),
            'week' => date('Y-W'),
            'month' => date('Y-m'),
            'year' => date('Y')
        );
        
        // Insertar el documento
        $collection->insertOne($search_doc);
        
        return true;
    } catch (Exception $e) {
        // Log del error si es necesario
        error_log("Error registrando búsqueda: " . $e->getMessage());
        return false;
    }
}

function get_search_statistics() {
    // Obtener estadísticas de búsquedas
    try {
        $collection = getCollectionLogs();
        
        // Estadísticas diarias (últimos 7 días)
        $daily_stats = array();
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $count = $collection->countDocuments(array('type' => 'search_term', 'day' => $date));
            $daily_stats[] = array(
                'date' => $date,
                'count' => $count,
                'formatted_date' => date('d/m', strtotime($date))
            );
        }
        
        // Estadísticas semanales (últimas 4 semanas)
        $weekly_stats = array();
        for ($i = 3; $i >= 0; $i--) {
            $week = date('Y-W', strtotime("-{$i} weeks"));
            $count = $collection->countDocuments(array('type' => 'search_term', 'week' => $week));
            $week_start = date('d/m', strtotime(date('Y') . 'W' . sprintf('%02d', $week) . '1'));
            $week_end = date('d/m', strtotime(date('Y') . 'W' . sprintf('%02d', $week) . '7'));
            $weekly_stats[] = array(
                'week' => $week,
                'count' => $count,
                'period' => $week_start . ' - ' . $week_end
            );
        }
        
        // Estadísticas mensuales (últimos 6 meses)
        $monthly_stats = array();
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $count = $collection->countDocuments(array('type' => 'search_term', 'month' => $month));
            $monthly_stats[] = array(
                'month' => $month,
                'count' => $count,
                'formatted_month' => date('M Y', strtotime($month . '-01'))
            );
        }
        
        // Términos más buscados (últimos 30 días)
        $pipeline = array(
            array('$match' => array(
                'type' => 'search_term',
                'timestamp' => array('$gte' => time() - (30 * 24 * 60 * 60))
            )),
            array('$group' => array(
                '_id' => '$term',
                'count' => array('$sum' => 1)
            )),
            array('$sort' => array('count' => -1)),
            array('$limit' => 10)
        );
        
        $top_terms = $collection->aggregate($pipeline)->toArray();
        
        return array(
            'daily' => $daily_stats,
            'weekly' => $weekly_stats,
            'monthly' => $monthly_stats,
            'top_terms' => $top_terms,
            'total_today' => $daily_stats[6]['count'],
            'total_this_week' => array_sum(array_column($weekly_stats, 'count')),
            'total_this_month' => array_sum(array_column($monthly_stats, 'count'))
        );
        
    } catch (Exception $e) {
        error_log("Error obteniendo estadísticas de búsqueda: " . $e->getMessage());
        return array(
            'daily' => array(),
            'weekly' => array(),
            'monthly' => array(),
            'top_terms' => array(),
            'total_today' => 0,
            'total_this_week' => 0,
            'total_this_month' => 0
        );
    }
}

function get_related_searches($current_term, $limit = 8) {
    // Obtener búsquedas relacionadas basadas en:
    // 1. Búsquedas populares (excluyendo la actual)
    // 2. Marcas relacionadas con el término
    try {
        $collection = getCollectionLogs();
        $related_searches = array();
        
        // Obtener términos populares excluyendo el actual
        $pipeline = array(
            array('$match' => array(
                'type' => 'search_term',
                'timestamp' => array('$gte' => time() - (30 * 24 * 60 * 60)),
                'term' => array('$ne' => strtolower(trim($current_term)))
            )),
            array('$group' => array(
                '_id' => '$term',
                'count' => array('$sum' => 1)
            )),
            array('$sort' => array('count' => -1)),
            array('$limit' => $limit)
        );
        
        $popular_terms = $collection->aggregate($pipeline)->toArray();
        
        foreach ($popular_terms as $term) {
            if (isset($term['_id']) && !empty($term['_id'])) {
                $related_searches[] = array(
                    'term' => $term['_id'],
                    'count' => $term['count']
                );
            }
        }
        
        // Si no hay suficientes, buscar marcas relacionadas
        if (count($related_searches) < $limit) {
            try {
                $collection_marcas = getCollectionMarcas();
                $regex = new MongoDB\BSON\Regex($current_term, 'i');
                
                $marcas = $collection_marcas->find(
                    array(
                        'estado' => 0,
                        '$or' => array(
                            array('nombre' => $regex),
                            array('categoria' => $regex)
                        )
                    ),
                    array(
                        'limit' => $limit - count($related_searches),
                        'sort' => array('nombre' => 1)
                    )
                )->toArray();
                
                foreach ($marcas as $marca) {
                    $marca_nombre = strtolower(trim($marca['nombre'] ?? ''));
                    if (!empty($marca_nombre) && $marca_nombre !== strtolower(trim($current_term))) {
                        // Verificar que no esté ya en la lista
                        $exists = false;
                        foreach ($related_searches as $existing) {
                            if (strtolower($existing['term']) === $marca_nombre) {
                                $exists = true;
                                break;
                            }
                        }
                        
                        if (!$exists) {
                            $related_searches[] = array(
                                'term' => $marca['nombre'],
                                'count' => 0 // No tiene contador de búsquedas
                            );
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Error obteniendo marcas relacionadas: " . $e->getMessage());
            }
        }
        
        return $related_searches;
        
    } catch (Exception $e) {
        error_log("Error obteniendo búsquedas relacionadas: " . $e->getMessage());
        return array();
    }
}

function get_popular_searches_by_period($period = 'today', $limit = 8) {
    // Obtener búsquedas populares por período específico
    // $period puede ser: 'today', 'week', 'month'
    try {
        $collection = getCollectionLogs();
        
        // Determinar el filtro de tiempo según el período
        $time_filter = array();
        switch ($period) {
            case 'today':
                $time_filter = array('day' => date('Y-m-d'));
                break;
            case 'week':
                $time_filter = array('week' => date('Y-W'));
                break;
            case 'month':
                $time_filter = array('month' => date('Y-m'));
                break;
            default:
                $time_filter = array('timestamp' => array('$gte' => time() - (30 * 24 * 60 * 60)));
        }
        
        // Pipeline de agregación
        $pipeline = array(
            array('$match' => array_merge(
                array('type' => 'search_term'),
                $time_filter
            )),
            array('$group' => array(
                '_id' => '$term',
                'count' => array('$sum' => 1)
            )),
            array('$sort' => array('count' => -1)),
            array('$limit' => $limit)
        );
        
        $popular_terms = $collection->aggregate($pipeline)->toArray();
        
        $results = array();
        foreach ($popular_terms as $term) {
            if (isset($term['_id']) && !empty($term['_id'])) {
                $results[] = array(
                    'term' => $term['_id'],
                    'count' => $term['count']
                );
            }
        }
        
        return $results;
        
    } catch (Exception $e) {
        error_log("Error obteniendo búsquedas populares por período: " . $e->getMessage());
        return array();
    }
}

function get_popular_searches_all_periods($limit_per_period = 8) {
    // Obtener búsquedas populares para todos los períodos
    return array(
        'today' => get_popular_searches_by_period('today', $limit_per_period),
        'week' => get_popular_searches_by_period('week', $limit_per_period),
        'month' => get_popular_searches_by_period('month', $limit_per_period)
    );
}
?>
