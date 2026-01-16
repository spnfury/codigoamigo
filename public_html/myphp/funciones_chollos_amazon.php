<?php

/**
 * Funciones para manejar enlaces de Amazon y añadir ID de afiliado
 */

// ID de afiliado de Amazon (se usa el de config/ai_config.php si está definido)
if (!defined('AMAZON_AFFILIATE_ID')) {
define('AMAZON_AFFILIATE_ID', 'spnfuryy-21');
}

/**
 * Detecta si una URL es de Amazon
 */
function esEnlaceAmazon($url) {
    if (empty($url)) {
        return false;
    }

    $dominios_amazon = [
        'amazon.es', 'amazon.com', 'amazon.co.uk', 'amazon.de', 'amazon.fr', 'amazon.it',
        'amazon.com.mx', 'amazon.ca', 'amazon.com.au', 'amazon.in', 'amazon.jp', 'amazon.cn'
    ];
    
    // También detectar acortadores de Amazon y propios
    $acortadores_amazon = [
        'amzn.to', 'amzn.com', 'amzn.eu', 'a.co', 'amz.tf', 'chollo.biz', 'ganga.ad'
    ];

    $url_lower = strtolower($url);
    
    // Verificar dominios de Amazon
    foreach ($dominios_amazon as $dominio) {
        if (strpos($url_lower, $dominio) !== false) {
            return true;
        }
    }
    
    // Verificar acortadores de Amazon
    foreach ($acortadores_amazon as $acortador) {
        if (strpos($url_lower, $acortador) !== false) {
            return true;
        }
    }

    return false;
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
    
    $acortadores = ['amzn.to', 'amzn.com', 'amzn.eu', 'a.co', 'amz.tf', 'chollo.biz', 'ganga.ad'];
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 8); // Reducimos timeout
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36');
    
    curl_exec($ch);
    $final_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($final_url && $final_url !== $url && esEnlaceAmazon($final_url)) {
        return $final_url;
    }

    // --- SEGUNDO INTENTO: HEAD RÁPIDO ---
    // Si falló el GET, intentamos un HEAD ultra-rápido para ver si al menos obtenemos Location
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // No seguir, queremos ver el Location nosotros si acaso
    curl_setopt($ch, CURLOPT_TIMEOUT, 4);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
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
    
    if ($chollo_id && $url_expandida !== $url && esEnlaceAmazon($url_expandida)) {
        if (!function_exists('getCollectionChollos')) { require_once __DIR__ . '/funciones_chollos.php'; }
        $collection = getCollectionChollos();
        if ($collection) {
            try {
                $asin_extraido = extraerASIN($url_expandida);
                $update_data = [
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
    if (!esEnlaceAmazon($url)) return $url;

    if (empty($affiliate_id)) {
        $affiliate_id = defined('AMAZON_AFFILIATE_ID') ? AMAZON_AFFILIATE_ID : 'spnfuryy-21';
    }

    // SI YA TIENE EL TAG, NO HACER NADA (Evita bucles y sobreprocesamiento)
    if (strpos($url, 'tag=' . $affiliate_id) !== false) return $url;

    // Parsear la URL para añadir el tag
    $parsed = parse_url($url);
    if (!$parsed) return $url;

    $query = [];
    if (isset($parsed['query'])) {
        parse_str($parsed['query'], $query);
    }

    $query['tag'] = $affiliate_id;

    $nueva_url = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
    if (isset($parsed['path'])) { $nueva_url .= $parsed['path']; }
    if (!empty($query)) { $nueva_url .= '?' . http_build_query($query); }
    if (isset($parsed['fragment'])) { $nueva_url .= '#' . $parsed['fragment']; }

    return $nueva_url;
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
