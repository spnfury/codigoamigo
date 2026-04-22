<?php

// Funciones para la sección de Servicios Amazon
// Requiere inc/conexion.php estar incluido previamente
require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/librerias/Mobile_Detect.php';

function getCollectionAffiliateLinks() {
    $db = createConnection();
    return $db->selectCollection('affiliate_links');
}

function getCollectionAffiliateClicks() {
    $db = createConnection();
    return $db->selectCollection('affiliate_clicks');
}

function getAmazonServiceBySlug($slug) {
    $collection = getCollectionAffiliateLinks();
    return $collection->findOne(['slug' => $slug, 'active' => true]);
}

/**
 * Obtiene todos los servicios activos ordenados por 'order'
 */
function getAllAmazonServices() {
    $collection = getCollectionAffiliateLinks();
    $options = ['sort' => ['order' => 1]];
    return $collection->find(['active' => true], $options);
}

/**
 * Registra un clic en un servicio
 */
function logAmazonServiceClick($slug, $referrer = '', $page_origin = '') {
    $collection = getCollectionAffiliateClicks();
    
    // Bot Detection
    if (!function_exists('isBot')) {
        require_once __DIR__ . '/bot_detection.php';
    }
    if (isBot()) {
        return true; 
    }
    
    // Intentar obtener IP y User Agent de forma segura
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $device = 'desktop';
    
    // Detección simple de dispositivo (si Mobile_Detect está disponible, usarlo mejor)
    if (class_exists('Mobile_Detect')) {
        $detect = new Mobile_Detect();
        $device = $detect->isMobile() ? 'mobile' : 'desktop';
    } else {
        if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $userAgent)) {
            $device = 'mobile';
        }
    }

    $data = [
        'slug' => $slug,
        'session_id' => session_id(),
        'referrer' => $referrer, // De dónde viene el usuario antes de llegar a nuestra web (si aplica)
        'page_origin' => $page_origin, // Página interna donde estaba el enlace
        'device' => $device,
        'country' => $_SERVER["HTTP_CF_IPCOUNTRY"] ?? 'unknown', // Si usa Cloudflare
        'ip_hash' => hash('sha256', $ip), // IP hash for privacy
        'user_agent' => $userAgent,
        'created_at' => new MongoDB\BSON\UTCDateTime()
    ];

    try {
        $collection->insertOne($data);
        return true;
    } catch (Exception $e) {
        // Silenciar error de log para no interrumpir redirect
        return false;
    }
}

/**
 * Métrica: Vistas de la sección /amazon
 */
function logAmazonSectionView() {
    // Reutilizamos la tabla de clicks con un slug especial o creamos una tabla de views separada.
    // Para simplificar, usamos un slug reservado 'view_section_amazon'
    return logAmazonServiceClick('view_section_amazon', $_SERVER['HTTP_REFERER'] ?? '', 'direct_access');
}
?>
