<?php
/**
 * Funciones para generar títulos de páginas de marca mejorados
 */

/**
 * Obtiene el ahorro del último código destacado o publicado para una marca
 */
function get_ultimo_ahorro_marca($marca_nombre_clave, $lista_codigos) {
    // Primero buscar códigos destacados ordenados por fecha de publicación
    $codigos_destacados = [];
    $codigos_normales = [];
    
    foreach ($lista_codigos as $codigo) {
        if (is_object($codigo)) { 
            $codigo = (array)$codigo; 
        }
        
        // Verificar si es destacado
        $is_destacado = isset($codigo['destacado']) && $codigo['destacado'] > 0;
        
        if ($is_destacado) {
            $codigos_destacados[] = $codigo;
        } else {
            $codigos_normales[] = $codigo;
        }
    }
    
    // Ordenar códigos destacados por fecha de publicación (más reciente primero)
    usort($codigos_destacados, function($a, $b) {
        $fecha_a = isset($a['fecha_publicacion']) ? strtotime($a['fecha_publicacion']) : 0;
        $fecha_b = isset($b['fecha_publicacion']) ? strtotime($b['fecha_publicacion']) : 0;
        return $fecha_b - $fecha_a;
    });
    
    // Ordenar códigos normales por fecha de publicación (más reciente primero)
    usort($codigos_normales, function($a, $b) {
        $fecha_a = isset($a['fecha_publicacion']) ? strtotime($a['fecha_publicacion']) : 0;
        $fecha_b = isset($b['fecha_publicacion']) ? strtotime($b['fecha_publicacion']) : 0;
        return $fecha_b - $fecha_a;
    });
    
    // Priorizar códigos destacados, si no hay, usar códigos normales
    $codigos_a_revisar = !empty($codigos_destacados) ? $codigos_destacados : $codigos_normales;
    
    if (empty($codigos_a_revisar)) {
        return null;
    }
    
    // Tomar el primer código (más reciente)
    $ultimo_codigo = $codigos_a_revisar[0];
    
    // Extraer información del beneficio
    $num_beneficio = isset($ultimo_codigo['num_beneficio']) ? $ultimo_codigo['num_beneficio'] : 0;
    $tipo_descuento = isset($ultimo_codigo['tipo_descuento']) ? $ultimo_codigo['tipo_descuento'] : '';
    
    if ($num_beneficio <= 0) {
        return null;
    }
    
    // Generar string de ahorro según el tipo
    switch ($tipo_descuento) {
        case 'euros':
            return "【 ahorra " . $num_beneficio . "€ 】";
        case '% de descuento':
            return " " . $num_beneficio . " % DESC.";
        case 'minutos gratis':
            return " " . $num_beneficio . " MIN. GRATIS";
        default:
            // Si no hay tipo específico, asumir euros
            return "【 ahorra " . $num_beneficio . "€ 】";
    }
}

/**
 * Genera el título mejorado para páginas de marca
 */
function generate_titulo_marca_mejorado($marca, $lista_codigos) {
    // Obtener el ahorro del último código destacado o publicado
    $nombre_clave = isset($marca['nombre_clave']) ? $marca['nombre_clave'] : '';
    $ahorro_ultimo = get_ultimo_ahorro_marca($nombre_clave, $lista_codigos);
    
    // Obtener mes y año actual
    $meses_es = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    
    $fecha_actual = new DateTime();
    $mes_actual = $meses_es[(int)$fecha_actual->format('n')];
    $año_actual = $fecha_actual->format('Y');
    $string_fecha = " " . $mes_actual . " " . $año_actual;
    
    // Determinar el prefijo según la marca
    $pre_txt = "Cupones descuento ";
    
    $nombre_clave = isset($marca['nombre_clave']) ? $marca['nombre_clave'] : '';
    
    if ($nombre_clave) {
        switch ($nombre_clave) {
            case 'bookingcom':
                $pre_txt = "Códigos Descuento ";
                break;
            case 'fever':
                $pre_txt = "Cupones ";
                break;
            case 'glovo':
                $pre_txt = "Cupones Descuento ";
                break;
            case 'jazztel':
                $pre_txt = "Planes amigo ";
                break;
            case 'playfulbet':
                $pre_txt = "Cupones ";
                break;
            case 'cheerz':
                $pre_txt = "Códigos Descuento ";
                break;
            case 'n26':
            case 'revolut':
            case 'qonto':
                $pre_txt = "Códigos Promocionales ";
                break;
            case 'mitto':
                $pre_txt = "Códigos Invitación ";
                break;
            case 'traderepublic':
            case 'scalable-capital':
            case 'degiro':
                $pre_txt = "Código Invitación ";
                break;
            case 'backmarket':
            case 'wallapop':
            case 'vinted':
                $pre_txt = "Código amigo ";
                break;
            case 'yego':
                $pre_txt = "Primer trayecto gratis y Código Promocional ";
                break;
        }
    }
    
    // Construir el título
    $nombre_marca = isset($marca['nombre']) ? $marca['nombre'] : 'Marca';
    $titulo = $pre_txt . $nombre_marca;
    
    if ($ahorro_ultimo) {
        $titulo .= $ahorro_ultimo;
    }
    
    $titulo .= $string_fecha;
    
    return $titulo;
}

/**
 * Genera la descripción mejorada para páginas de marca
 */
function generate_descripcion_marca_mejorada($marca, $lista_codigos, $numero_codigos) {
    // Obtener el ahorro del último código destacado o publicado
    $nombre_clave = isset($marca['nombre_clave']) ? $marca['nombre_clave'] : '';
    $ahorro_ultimo = get_ultimo_ahorro_marca($nombre_clave, $lista_codigos);
    
    // Obtener mes y año actual
    $meses_es = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    
    $fecha_actual = new DateTime();
    $mes_actual = $meses_es[(int)$fecha_actual->format('n')];
    $año_actual = $fecha_actual->format('Y');
    $string_fecha = $mes_actual . " " . $año_actual;
    
    // Construir la descripción
    $nombre_marca = isset($marca['nombre']) ? $marca['nombre'] : 'la marca';
    
    if ($ahorro_ultimo) {
        $descripcion = trim($ahorro_ultimo) . " con tu código amigo " . $nombre_marca . ". Códigos descuento y cupones descuento válidos para " . $string_fecha . " ✅ - ¡Aprovecha y gana dinero";
    } else {
        $descripcion = "Códigos de amigo y cupones descuento de " . $nombre_marca . ". " . $numero_codigos . " códigos verificados válidos para " . $string_fecha . " ✅ - ¡Aprovecha y gana dinero";
    }
    
    // Enriquecer con top keywords transaccionales de SEO si están disponibles
    if (!function_exists('get_brand_keywords_by_type')) {
        $kw_file = __DIR__ . '/funciones_keywords_marca.php';
        if (file_exists($kw_file)) {
            include_once $kw_file;
        }
    }
    
    if (function_exists('get_brand_keywords_by_type')) {
        $top_kws = get_brand_keywords_by_type($nombre_clave, 'transactional', 3);
        if (!empty($top_kws)) {
            // Extraer solo las keywords que no sean redundantes con el nombre de marca
            $kw_terms = [];
            $brand_lower = mb_strtolower($nombre_marca);
            foreach ($top_kws as $kw) {
                $kw_text = $kw['kw'] ?? '';
                // Solo añadir keywords que no sean simplemente "codigo descuento [marca]" (ya está implícito)
                $clean_kw = str_replace($brand_lower, '', mb_strtolower($kw_text));
                $clean_kw = trim(preg_replace('/\s+/', ' ', $clean_kw));
                if (mb_strlen($clean_kw) > 3 && !in_array($clean_kw, ['codigo descuento', 'cupones', 'codigo amigo'])) {
                    $kw_terms[] = trim($kw_text);
                }
            }
            if (!empty($kw_terms)) {
                // Solo añadir la cola de keywords si cabe en el snippet (~155 chars);
                // si no, se omite entera para no dejar "Búsquedas:" colgando.
                $tail = ". Búsquedas: " . implode(', ', array_slice($kw_terms, 0, 2));
                if (mb_strlen($descripcion . $tail) <= 155) {
                    $descripcion .= $tail;
                }
            }
        }
    }

    // Cap de seguridad a ~155 chars (Google trunca el snippet ahí). Corta en
    // frontera de palabra y elimina cualquier "Búsquedas:" parcial residual.
    $descripcion = trim($descripcion);
    if (mb_strlen($descripcion) > 155) {
        $cut = mb_substr($descripcion, 0, 155);
        $sp = mb_strrpos($cut, ' ');
        if ($sp !== false && $sp > 120) {
            $cut = mb_substr($cut, 0, $sp);
        }
        $descripcion = $cut;
    }
    // Quitar cola "Búsquedas..." si quedó incompleta tras el recorte
    $descripcion = preg_replace('/\.?\s*Búsquedas:.*$/u', '', $descripcion);
    $descripcion = rtrim(trim($descripcion), " ,.-");

    return $descripcion;
}
?>
