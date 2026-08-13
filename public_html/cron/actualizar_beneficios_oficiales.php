<?php
/**
 * Cron: Auto-detección de beneficio oficial de marcas usando scraping + Groq AI
 * 
 * Estrategia dual:
 *   1. Intenta descargar la web de la marca y analizar el texto
 *   2. Si falla el scraping, pregunta a Groq usando su conocimiento interno
 * 
 * Uso:
 *   php cron/actualizar_beneficios_oficiales.php                         → todas las marcas
 *   php cron/actualizar_beneficios_oficiales.php --marca=octopus-energy  → una marca
 *   php cron/actualizar_beneficios_oficiales.php --dry-run               → solo muestra
 *   php cron/actualizar_beneficios_oficiales.php --force                 → sobrescribe manuales
 *   php cron/actualizar_beneficios_oficiales.php --all                   → incluye marcas sin URL
 */

$doc_root = '/home/admin/web/codigoamigo.com/public_html';
require_once $doc_root . '/inc/includes.php';
require_once $doc_root . '/inc/conexion.php';
require_once $doc_root . '/config/ai_config.php';

// Parsear argumentos
$args = $argv ?? [];
$dry_run = in_array('--dry-run', $args);
$force = in_array('--force', $args);
$all_brands = in_array('--all', $args);
$marca_filtro = null;
foreach ($args as $arg) {
    if (strpos($arg, '--marca=') === 0) {
        $marca_filtro = substr($arg, 8);
    }
}

echo "🔍 Auto-detección de beneficio oficial\n";
echo $dry_run ? "   Modo: DRY-RUN\n" : "   Modo: APLICAR\n";
if ($marca_filtro) echo "   Marca: {$marca_filtro}\n";
if ($all_brands) echo "   Incluye marcas sin URL\n";
echo "\n";

// Conectar a la BD
$db = createConnection();
$collection_marcas = $db->selectCollection('marcas');

// Filtro de marcas
$filtro = [];
if (!$all_brands && !$marca_filtro) {
    // Por defecto, solo marcas con URL
    $filtro['$or'] = [
        ['url' => ['$exists' => true, '$ne' => '']],
        ['url_register' => ['$exists' => true, '$ne' => '']]
    ];
}
if ($marca_filtro) {
    // Buscar por nombre_clave flexible (contiene el texto)
    $filtro['nombre_clave'] = ['$regex' => $marca_filtro, '$options' => 'i'];
}

$marcas = $collection_marcas->find($filtro, ['sort' => ['nombre' => 1], 'limit' => 100]);
$marcas_array = iterator_to_array($marcas);

echo "📋 Marcas encontradas: " . count($marcas_array) . "\n\n";

$stats = ['procesadas' => 0, 'detectadas' => 0, 'actualizadas' => 0, 'sin_beneficio' => 0, 'errores' => 0, 'saltadas_manual' => 0, 'fallback_ia' => 0];

foreach ($marcas_array as $marca) {
    $nombre = $marca['nombre'] ?? $marca['nombre_clave'] ?? 'desconocido';
    $nombre_clave = $marca['nombre_clave'] ?? '';
    $url = $marca['url'] ?? $marca['url_register'] ?? '';
    $categoria = $marca['categoria'] ?? '';
    
    // Saltar si tiene valor manual y no estamos forzando
    $bo_actual = $marca['beneficio_oficial'] ?? null;
    $origen_actual = $bo_actual['origen'] ?? null;
    
    if (!$force && $origen_actual === 'manual' && !empty($bo_actual['cantidad'])) {
        $stats['saltadas_manual']++;
        echo "⏭️  {$nombre} — Beneficio manual ({$bo_actual['cantidad']}" . (($bo_actual['tipo'] ?? 'euros') === 'euros' ? '€' : '%') . "), saltando\n";
        continue;
    }
    
    $stats['procesadas']++;
    echo "━━━ {$nombre}" . ($url ? " ({$url})" : " [sin URL]") . " ━━━\n";
    
    // ESTRATEGIA 1: Intentar scraping si hay URL
    $texto_web = null;
    $metodo = 'fallback_ia';
    
    if (!empty($url)) {
        $html = descargarPaginaMarca($url);
        if ($html) {
            $texto_web = limpiarHTMLaTexto($html);
            if (strlen($texto_web) >= 100) {
                echo "  📄 Texto extraído: " . strlen($texto_web) . " chars\n";
                $metodo = 'scraping';
            } else {
                echo "  ⚠️  Texto extraído muy corto (" . strlen($texto_web) . " chars), usando fallback IA\n";
                $texto_web = null;
            }
        } else {
            echo "  ⚠️  No se pudo descargar, usando fallback IA\n";
        }
    }
    
    // ESTRATEGIA 2: Si no hay texto web, usar conocimiento de la IA
    if ($metodo === 'fallback_ia') {
        $stats['fallback_ia']++;
    }
    
    $resultado_ia = detectarBeneficioConIA($nombre, $categoria, $texto_web);
    
    if (!$resultado_ia['encontrado']) {
        $stats['sin_beneficio']++;
        echo "  ℹ️  No se detectó beneficio de referidos\n";
        if (!empty($resultado_ia['razon'])) {
            echo "      Razón: {$resultado_ia['razon']}\n";
        }
        echo "\n";
        
        // Rate limiting
        usleep(1500000); // 1.5s entre peticiones
        continue;
    }
    
    $stats['detectadas']++;
    $cantidad = $resultado_ia['cantidad'];
    $tipo = $resultado_ia['tipo'];
    $texto_promo = $resultado_ia['texto_promo'];
    $confianza = $resultado_ia['confianza'];
    $unidad = ($tipo === 'euros') ? '€' : '%';
    
    echo "  ✅ Detectado: {$cantidad}{$unidad} (confianza: {$confianza}, método: {$metodo})\n";
    if ($texto_promo) echo "      Texto: {$texto_promo}\n";
    
    // Comparar con valor actual
    if ($bo_actual && !empty($bo_actual['cantidad'])) {
        $diff = floatval($cantidad) - floatval($bo_actual['cantidad']);
        if ($diff != 0) {
            echo "  ⚠️  Cambio: anterior={$bo_actual['cantidad']}, nuevo={$cantidad}\n";
        } else {
            echo "  ✔️  Sin cambios\n";
        }
    }
    
    // Guardar si no es dry-run y confianza no es baja
    if (!$dry_run && $confianza !== 'low') {
        $update_data = [
            'beneficio_oficial' => [
                'cantidad' => floatval($cantidad),
                'tipo' => $tipo,
                'texto' => $texto_promo,
                'origen' => 'auto_ia',
                'confianza' => $confianza,
                'metodo' => $metodo,
                'actualizado' => date('Y-m-d H:i:s'),
                'url_fuente' => $url ?: null
            ]
        ];
        
        $collection_marcas->updateOne(
            ['_id' => $marca['_id']],
            ['$set' => $update_data]
        );
        
        $stats['actualizadas']++;
        echo "  💾 Guardado en BD\n";
    } elseif ($confianza === 'low') {
        echo "  ⚠️  Confianza baja, no se guarda\n";
    }
    
    echo "\n";
    
    // Rate limiting: Groq free tier = 30 req/min
    usleep(2500000); // 2.5s entre peticiones
}

// Resumen
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 RESUMEN\n";
echo "   Procesadas:        {$stats['procesadas']}\n";
echo "   Detectadas:        {$stats['detectadas']}\n";
echo "   Actualizadas BD:   {$stats['actualizadas']}\n";
echo "   Sin beneficio:     {$stats['sin_beneficio']}\n";
echo "   Errores:           {$stats['errores']}\n";
echo "   Fallback IA:       {$stats['fallback_ia']}\n";
echo "   Saltadas (manual): {$stats['saltadas_manual']}\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";


// ═══════════════════════════════════════════════════════════════
// FUNCIONES
// ═══════════════════════════════════════════════════════════════

/**
 * Descarga el HTML de una URL de marca
 */
function descargarPaginaMarca($url) {
    if (!preg_match('/^https?:\/\//', $url)) {
        $url = 'https://' . $url;
    }
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: es-ES,es;q=0.9,en;q=0.8',
            'Cache-Control: no-cache',
            'Sec-Fetch-Dest: document',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-Site: none',
            'Sec-Fetch-User: ?1',
        ],
        CURLOPT_ENCODING => '',
    ]);
    
    $html = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "  cURL error: {$error}\n";
        return null;
    }
    
    if ($http_code >= 400) {
        echo "  HTTP {$http_code}\n";
        return null;
    }
    
    return $html;
}

/**
 * Limpia HTML a texto plano legible, limitado a ~3000 chars
 */
function limpiarHTMLaTexto($html) {
    $html = preg_replace('/<script[^>]*>.*?<\/script>/si', ' ', $html);
    $html = preg_replace('/<style[^>]*>.*?<\/style>/si', ' ', $html);
    $html = preg_replace('/<svg[^>]*>.*?<\/svg>/si', ' ', $html);
    $html = preg_replace('/<noscript[^>]*>.*?<\/noscript>/si', ' ', $html);
    $html = preg_replace('/<nav[^>]*>.*?<\/nav>/si', ' ', $html);
    $html = preg_replace('/<footer[^>]*>.*?<\/footer>/si', ' ', $html);
    
    $texto = strip_tags($html);
    $texto = preg_replace('/\s+/', ' ', $texto);
    $texto = html_entity_decode($texto, ENT_QUOTES, 'UTF-8');
    $texto = trim($texto);
    
    if (strlen($texto) > 3000) {
        $texto = substr($texto, 0, 3000) . '...';
    }
    
    return $texto;
}

/**
 * Usa Groq AI para detectar el beneficio de referidos
 * Si $texto_web es null, usa el conocimiento interno de la IA
 */
function detectarBeneficioConIA($nombre_marca, $categoria = '', $texto_web = null) {
    $api_key = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
    
    if (empty($api_key)) {
        return ['encontrado' => false, 'razon' => 'API key no configurada'];
    }
    
    if ($texto_web) {
        // Modo scraping: analizar texto de la web
        $prompt = <<<PROMPT
Analiza el siguiente texto de la web de "{$nombre_marca}" y busca información sobre su programa de referidos, invitaciones, o códigos amigo.

Necesito saber EXACTAMENTE cuánto dinero o descuento obtiene un nuevo usuario al registrarse con un código de referido/invitación/amigo.

Responde SOLO con JSON válido, sin texto adicional:
{
  "encontrado": true o false,
  "cantidad": número sin símbolo de moneda,
  "tipo": "euros" o "porcentaje",
  "texto_promo": "descripción corta máx 50 chars",
  "confianza": "high" o "medium" o "low"
}

Si no encuentras información sobre programa de referidos, pon encontrado como false.

IMPORTANTE: 
- Solo busca beneficios de PROGRAMAS DE REFERIDOS/INVITACIÓN, no descuentos generales
- Si hay varios beneficios, usa el del nuevo usuario invitado
- La cantidad debe ser EXACTA, no estimaciones
- Contexto: el mercado es España (euros)

Texto de la web:
{$texto_web}
PROMPT;
    } else {
        // Modo fallback: usar conocimiento de la IA
        $cat_context = $categoria ? " (categoría: {$categoria})" : '';
        $prompt = <<<PROMPT
¿Tiene la marca "{$nombre_marca}"{$cat_context} un programa de referidos, invitaciones, o códigos amigo en España?

Si lo tiene, ¿cuánto dinero o descuento obtiene un nuevo usuario al registrarse con un código de referido?

Responde SOLO con JSON válido, sin texto adicional:
{
  "encontrado": true o false,
  "cantidad": número sin símbolo de moneda,
  "tipo": "euros" o "porcentaje",
  "texto_promo": "descripción corta máx 50 chars",
  "confianza": "high" si estás seguro, "medium" si es probable, "low" si no estás seguro
}

IMPORTANTE:
- Solo busca PROGRAMAS DE REFERIDOS/INVITACIÓN activos en España
- No inventes datos, si no estás seguro pon confianza "low" o encontrado false
- La cantidad debe ser el beneficio REAL que ofrece la marca actualmente
- Si la marca no tiene programa de referidos, pon encontrado false
PROMPT;
    }

    $data = [
        'model' => defined('GROQ_MODEL') ? GROQ_MODEL : 'llama-3.1-8b-instant',
        'messages' => [
            [
                'role' => 'user', 
                'content' => $prompt
            ]
        ],
        'max_tokens' => 300,
        'temperature' => 0.1
    ];
    
    // Groq con rotación de claves + fallback de modelo (ver groq_request en ai_config).
    // Reemplaza el curl single-key + reintento manual 429: el helper rota las claves
    // del pool y cae a 70b-versatile si todas dan 429.
    $__g = groq_request($data);
    $response  = $__g['body'];
    $http_code = $__g['http'];

    if ($http_code !== 200) {
        echo "  Groq HTTP {$http_code}\n";
        return ['encontrado' => false, 'razon' => 'Error API: HTTP ' . $http_code];
    }
    
    $result = json_decode($response, true);
    $content = $result['choices'][0]['message']['content'] ?? '';
    $content = trim($content);
    
    // Limpiar posibles backticks de markdown
    $content = preg_replace('/^```json\s*/i', '', $content);
    $content = preg_replace('/\s*```$/i', '', $content);
    $content = trim($content);
    
    $json = json_decode($content, true);
    
    if (!$json || !is_array($json)) {
        echo "  ⚠️  Respuesta IA no parseable: " . substr($content, 0, 200) . "\n";
        return ['encontrado' => false, 'razon' => 'Respuesta no parseable'];
    }
    
    if (empty($json['encontrado'])) {
        return ['encontrado' => false, 'razon' => $json['texto_promo'] ?? 'No encontrado por la IA'];
    }
    
    // Validar que la cantidad sea razonable (> 0 y < 1000)
    $cantidad = floatval($json['cantidad'] ?? 0);
    if ($cantidad <= 0 || $cantidad > 1000) {
        return ['encontrado' => false, 'razon' => "Cantidad sospechosa: {$cantidad}"];
    }
    
    return [
        'encontrado' => true,
        'cantidad' => $cantidad,
        'tipo' => $json['tipo'] ?? 'euros',
        'texto_promo' => $json['texto_promo'] ?? '',
        'confianza' => $json['confianza'] ?? 'medium'
    ];
}
