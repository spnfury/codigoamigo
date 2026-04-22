<?php

/**
 * Funciones para manejar enlaces de Amazon y añadir ID de afiliado
 */

// ID de afiliado de Amazon (se usa el de config/ai_config.php si está definido)
if (!defined('AMAZON_AFFILIATE_ID')) {
define('AMAZON_AFFILIATE_ID', 'spnfuryy-21');
}

/**
 * Detecta si una URL es un acortador que apunta a Amazon pero necesita expansión
 */
function esAcortadorAmazon($url) {
    if (empty($url)) return false;
    // Solo acortadores que EXCLUSIVAMENTE o MAYORITARIAMENTE apuntan a Amazon
    $acortadores = ['amzn.to', 'amzn.com', 'amzn.eu', 'a.co', 'amz.tf', 'chollo.biz', 'ganga.ad'];
    $url_lower = strtolower($url);
    foreach ($acortadores as $acortador) {
        if (strpos($url_lower, $acortador) !== false) return true;
    }
    return false;
}

/**
 * Detecta si una URL es de Amazon (dominio real)
 */
function esDominioAmazon($url) {
    if (empty($url)) return false;
    $dominios = ['amazon.es', 'amazon.com', 'amazon.co.uk', 'amazon.de', 'amazon.fr', 'amazon.it'];
    $url_lower = strtolower($url);
    foreach ($dominios as $dominio) {
        if (strpos($url_lower, $dominio) !== false) return true;
    }
    return false;
}

function esEnlaceAmazon($url) {
    return esDominioAmazon($url) || esAcortadorAmazon($url);
}

/**
 * Extrae el ASIN de una URL de Amazon
 */
function extraerASIN($url) {
    if (!esEnlaceAmazon($url)) {
        return null;
    }

    // Patrones para extraer ASIN (ordenados por especificidad)
    $patrones = [
        '/\/dp\/([A-Z0-9]{10})/i',
        '/\/gp\/product\/([A-Z0-9]{10})/i',
        '/\/product\/([A-Z0-9]{10})/i',
        '/\/dp\/product\/([A-Z0-9]{10})/i',
        '/\/exec\/obidos\/ASIN\/([A-Z0-9]{10})/i',
        '/\/o\/ASIN\/([A-Z0-9]{10})/i',
        '/[?\&]asin=([A-Z0-9]{10})/i',
        '/[?\&]ASIN=([A-Z0-9]{10})/i',
        '/\/([A-Z0-9]{10})(?:\/|\?|$)/i'
    ];

    foreach ($patrones as $patron) {
        if (preg_match($patron, $url, $matches)) {
            return strtoupper($matches[1]);
        }
    }

    return null;
}

/**
 * Expande un acortador de URL de Amazon de forma robusta
 */
function expandirAcortadorAmazon($url) {
    if (empty($url)) return $url;
    
    $acortadores = ['amzn.to', 'amzn.com', 'amzn.eu', 'a.co', 'amz.tf', 'chollo.biz', 'ganga.ad', 't.co', 'bit.ly', 'tinyurl.com', 'tiny.one'];
    $url_lower = strtolower($url);
    
    $es_acortador_objetivo = false;
    foreach ($acortadores as $acortador) {
        if (strpos($url_lower, $acortador) !== false) {
            $es_acortador_objetivo = true;
            break;
        }
    }

    if (!$es_acortador_objetivo) return $url;

    // --- PRIMER INTENTO: NORMAL (GET) ---
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    // FORZAR HTTP/1.1 para evitar PROTOCOL_ERROR en algunos servidores (como ganga.ad)
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36');
    
    // SOLUCIÓN PARA ganga.ad y similares: Forzar HTTP/1.1 y desactivar reuso de conexión si hay errores de stream/eof
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
    
    curl_exec($ch);
    $final_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($final_url && $final_url !== $url && esEnlaceAmazon($final_url)) {
        return $final_url;
    }

    // --- SEGUNDO INTENTO: HEAD RÁPIDO ---
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 4);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_HEADER, true);

    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    if ($info['http_code'] >= 300 && $info['http_code'] < 400 && !empty($info['redirect_url'])) {
        if (esEnlaceAmazon($info['redirect_url'])) {
            return $info['redirect_url'];
        }
    }
    
    // --- TERCER INTENTO: file_get_contents (MÁS RESILIENTE A CIERTOS SSL/HTTP2) ---
    try {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36\r\n",
                'follow_location' => 1,
                'max_redirects' => 5,
                'timeout' => 5,
                'protocol_version' => 1.1
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        $headers_raw = @get_headers($url, 1, $ctx);
        if ($headers_raw) {
            // Buscar Location en los headers (puede haber varios si hubo redirects)
            if (isset($headers_raw['Location'])) {
                $locs = (array)$headers_raw['Location'];
                $last_loc = end($locs);
                if (esEnlaceAmazon($last_loc)) {
                    return $last_loc;
                }
            }
        }
    } catch (Exception $e) {}
    
    return $url; // Fallback al original
}

/**
 * Expande un acortador con caché en base de datos
 */
function expandirAcortadorConCache($url, $chollo_id = null) {
    if ($chollo_id) {
        if (!function_exists('getCollectionChollos')) { require_once __DIR__ . '/funciones_chollos.php'; }
        $collection = getCollectionChollos();
        if ($collection) {
            try {
                $chollo = $collection->findOne(['_id' => new MongoDB\BSON\ObjectId($chollo_id)]);
                if ($chollo && isset($chollo['enlace_expandido']) && isset($chollo['fecha_expansion'])) {
                    $fecha_expansion = $chollo['fecha_expansion']->toDateTime();
                    if ((time() - $fecha_expansion->getTimestamp()) < (86400 * 7)) {
                        return $chollo['enlace_expandido'];
                    }
                }
            } catch (Exception $e) {}
        }
    }
    
    $url_expandida = expandirAcortadorAmazon($url);
    
    // --- NUEVO: SI LA EXPANSIÓN FALLÓ (siguió siendo el mismo URL), PROBAMOS MATCH POR TÍTULO ---
    if ($url_expandida === $url && $chollo_id) {
        if (!function_exists('getCollectionChollos')) { require_once __DIR__ . '/funciones_chollos.php'; }
        $collection = getCollectionChollos();
        try {
            $chollo_actual = $collection->findOne(['_id' => new MongoDB\BSON\ObjectId($chollo_id)]);
            if ($chollo_actual && !empty($chollo_actual['titulo'])) {
                $titulo = $chollo_actual['titulo'];
                
                // 1. Intento por match exacto (Fácil)
                $similar = $collection->findOne([
                    'titulo' => $titulo,
                    'asin' => ['$exists' => true, '$ne' => null]
                ], ['projection' => ['asin' => 1, 'enlace_expandido' => 1]]);
                
                // 2. Intento por match difuso (Palabras clave)
                if (!$similar) {
                    $clean_title = preg_replace('/[^a-zA-Z0-9 ]/', '', $titulo);
                    $words = explode(' ', $clean_title);
                    $keywords = [];
                    $ignored_words = ['oferta', 'chollo', 'descuento', 'amazon', 'pro', 'con', 'del', 'los', 'las', 'unos', 'unas'];
                    foreach ($words as $w) {
                        $w_lower = strtolower($w);
                        if (strlen($w) > 3 && !in_array($w_lower, $ignored_words)) {
                            $keywords[] = $w;
                        }
                    }
                    
                    if (count($keywords) >= 1) {
                        $regex = (count($keywords) >= 2) 
                            ? '.*' . preg_quote($keywords[0]) . '.*' . preg_quote($keywords[1]) . '.*'
                            : '.*' . preg_quote($keywords[0]) . '.*';
                            
                        $similar = $collection->findOne([
                            'titulo' => ['$regex' => $regex, '$options' => 'i'],
                            'asin' => ['$exists' => true, '$ne' => null]
                        ], ['projection' => ['asin' => 1, 'enlace_expandido' => 1]]);
                    }
                }
                
                if ($similar && !empty($similar['enlace_expandido'])) {
                    $url_expandida = $similar['enlace_expandido'];
                }
            }
        } catch (Exception $e) {}
    }
    
    if ($chollo_id && $url_expandida !== $url && esEnlaceAmazon($url_expandida)) {
        if (!function_exists('getCollectionChollos')) { require_once __DIR__ . '/funciones_chollos.php'; }
        $collection = getCollectionChollos();
        if ($collection) {
            try {
                $asin_extraido = extraerASIN($url_expandida);
                $update_data = [
                    'enlace' => $url_expandida, // Actualizar campo principal para que se vea bien en el panel
                    'enlace_expandido' => $url_expandida,
                    'fecha_expansion' => new MongoDB\BSON\UTCDateTime()
                ];
                if ($asin_extraido) { $update_data['asin'] = $asin_extraido; }
                $collection->updateOne(['_id' => new MongoDB\BSON\ObjectId($chollo_id)], ['$set' => $update_data]);
            } catch (Exception $e) {}
        }
    }
    return $url_expandida;
}

/**
 * Convierte un enlace de Amazon añadiendo el ID de afiliado
 */
function convertirEnlaceAmazon($url, $affiliate_id = null) {
    if (empty($url)) return $url;
    
    // SI ES UN ACORTADOR, NO AÑADIMOS EL TAG (estaría mal puesto)
    if (esAcortadorAmazon($url)) {
        return $url;
    }

    if (!esDominioAmazon($url)) return $url;

    if (empty($affiliate_id)) {
        $affiliate_id = defined('AMAZON_AFFILIATE_ID') ? AMAZON_AFFILIATE_ID : 'spnfuryy-21';
    }

    // SI YA TIENE NUESTRO TAG Y NINGUNO MÁS, NO HACER NADA
    // Pero si tiene otro tag, debemos sobrescribirlo. 
    // La forma más segura es usar regex para asegurar que el tag final es el correcto.
    
    if (strpos($url, 'tag=') !== false) {
        // Reemplazar CUALQUIER tag existente (gng-21 o cualquier otro)
        $url = preg_replace('/tag=[^&#]+/', 'tag=' . $affiliate_id, $url);
        return $url;
    }

    // Si no tiene tag, añadirlo
    $separator = (strpos($url, '?') !== false) ? '&' : '?';
    $url .= $separator . 'tag=' . $affiliate_id;

    return $url;
}

/**
 * Convierte un enlace de Amazon de forma garantizada (utilizada en redirects)
 */
function convertirEnlaceAmazonGarantizado($url, $chollo_id = null) {
    if (!esEnlaceAmazon($url)) return $url;

    // 1. Intentar expandir (si el servidor lo permite)
    $expandida = expandirAcortadorConCache($url, $chollo_id);
    
    // 2. Si se expandió a Amazon, aplicamos tag al expandido
    if ($expandida !== $url && esEnlaceAmazon($expandida)) {
        return convertirEnlaceAmazon($expandida);
    }
    
    // 3. Si no se expandió (o falló), aplicar tag al original como fallback
    return convertirEnlaceAmazon($url);
}

/**
 * Resto de funciones auxiliares...
 */
function convertirEnlaceAmazonConPAAPI($url, $nombre_producto = null, $affiliate_id = null) {
    return convertirEnlaceAmazon($url, $affiliate_id);
}
