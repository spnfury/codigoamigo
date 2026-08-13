<?php
/**
 * Clickout de códigos: registra el clic saliente y redirige.
 *
 * Uso: /salir.php?codigo={id}
 *
 * - Registra el clic en `clicks_salida` (marca, código, referer, bot) y suma
 *   `clicks_salida` en el propio código → datos reales de tráfico saliente
 *   por marca para negociar altas en redes de afiliación.
 * - Si AffiliationService tiene un programa activo para la marca, redirige
 *   al enlace afiliado; si no (estado actual: colección vacía), redirige a
 *   la URL original del código. El usuario no nota diferencia.
 */

include_once __DIR__ . '/inc/logger.php';
require_once __DIR__ . '/inc/conexion.php';
require_once __DIR__ . '/myphp/sistemas/afiliacion/AffiliationService.php';

use CodigoAmigo\Systems\Affiliation\AffiliationService;

$codigo_id = trim($_GET['codigo'] ?? '');

// Validar antes de construir el ObjectId (bots con query fuzzing)
if (!preg_match('/^[a-f\d]{24}$/i', $codigo_id)) {
    header('Location: /', true, 302);
    exit;
}

try {
    $db = createConnection();
    $col_codigos = $db->selectCollection('codigos');
    $codigo = $col_codigos->findOne(['_id' => new MongoDB\BSON\ObjectId($codigo_id)]);
} catch (Throwable $e) {
    log_error('salir.php: error consultando código: ' . $e->getMessage());
    $codigo = null;
}

// El enlace saliente vive en el campo `codigo` cuando el código es un enlace
// de referido (p.ej. https://revolut.com/referral/...); `url` casi nunca se usa.
$url_destino = '';
foreach (['codigo', 'url'] as $campo) {
    $v = trim((string)($codigo[$campo] ?? ''));
    if (preg_match('#^https?://#i', $v)) {
        $url_destino = $v;
        break;
    }
}

if (!$codigo || $url_destino === '') {
    // Código de texto (no enlace) o inexistente: a la ficha de su marca si la hay
    $marca_fallback = $codigo['marca'] ?? '';
    header('Location: ' . ($marca_fallback ? '/de-' . rawurlencode($marca_fallback) : '/'), true, 302);
    exit;
}

$marca = $codigo['marca'] ?? '';

// Bot heurístico simple: no ensuciar los datos con crawlers
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$es_bot = ($ua === '') || (bool)preg_match('/bot|crawl|spider|slurp|curl|wget|python|scrapy|headless/i', $ua);

// 1. Registrar clic saliente (analytics propio, independiente de afiliación)
if (!$es_bot) {
    try {
        $db->selectCollection('clicks_salida')->insertOne([
            'codigo_id' => $codigo['_id'],
            'marca'     => $marca,
            'url'       => $url_destino,
            'referer'   => substr((string)($_SERVER['HTTP_REFERER'] ?? ''), 0, 300),
            'fecha'     => new MongoDB\BSON\UTCDateTime(),
        ]);
        $col_codigos->updateOne(['_id' => $codigo['_id']], ['$inc' => ['clicks_salida' => 1]]);
    } catch (Throwable $e) {
        log_warning('salir.php: no se pudo registrar clic: ' . $e->getMessage());
    }
}

// 2. Enlace afiliado si existe programa para la marca (hoy: colección vacía → null)
try {
    $service = AffiliationService::getInstance();
    $program = $marca ? $service->getBestLinkForBrand($marca) : null;
    if ($program && !empty($program['url'])) {
        if (!$es_bot) {
            $net_id = isset($program['network_id']) ? (string)$program['network_id'] : 'unknown';
            $service->recordClick($marca, $net_id, $program['program_id'] ?? null);
        }
        $url_destino = $program['url'];
    }
} catch (Throwable $e) {
    log_warning('salir.php: AffiliationService falló, uso URL original: ' . $e->getMessage());
}

header('Location: ' . $url_destino, true, 302);
exit;
