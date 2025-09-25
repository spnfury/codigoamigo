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
?>
