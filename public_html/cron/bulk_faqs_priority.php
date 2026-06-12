<?php
/**
 * Genera FAQs en bulk para marcas prioritarias por SEO.
 * Criterio: marcas con >100 impresiones GSC y posición >20, con 0 FAQs.
 * Uso: php cron/bulk_faqs_priority.php [--limit=N] [--marca=slug]
 */

define('CRON_MODE', true);
require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones_marca.php';
require_once __DIR__ . '/../myphp/funciones_faq.php';
require_once __DIR__ . '/../config/ai_config.php';

// Parseamos args CLI
$opts = getopt('', ['limit:', 'marca:', 'min-faqs:']);
$limit    = isset($opts['limit'])    ? (int)$opts['limit']    : 10;
$solo_marca = $opts['marca']         ?? null;
$min_faqs  = isset($opts['min-faqs']) ? (int)$opts['min-faqs'] : 3;

// Marcas prioritarias (alto volumen GSC, posición >20) ordenadas por impresiones desc
$marcas_prioritarias = [
    'goikogrill','ubereats','goiko','shein','justeat','autodoc','zooplus',
    'showroomprive','miravia','temu','sprinter','prozis','glovo','hsnstorecom',
    'veepee','privalia','elcorteingles','sklum','edreams','bolt','grover',
    'iqos','fever','uber','acciona','pccomponentes','backmarket','hm','wetaca',
    'thefork','tripcom','fnac','airbnb','hpinstantink','hostinger','expedia',
    'amazonprime','cabify','orange','zumub','freenowmytaxi','amazon','n26',
    'taxdown','bookingcom','cinesa','alsa','ing','myinvestor','tulotero',
    'endesa','divain','unidays','traderepublic','digi','betfury','abanca',
    'naturgy','freecash','coinmaster','patasbox','beruby','clipclaps','wise',
    'homeexchange','smartmeapp','nutrimarket','voi','abonoteatro','octopusenergy',
    'remitly','cepsagow','repsol-waylet','bancosabadell','smartickmatematicas',
];

// llama-3.3-70b-versatile tiene cuota TPD separada de llama-3.1-8b-instant
define('FAQ_GROQ_MODEL', 'llama-3.3-70b-versatile');

function groq_call(string $prompt): ?string {
    $keys = defined('GROQ_API_KEYS') ? GROQ_API_KEYS : [GROQ_API_KEY];
    $data = json_encode([
        'model'       => FAQ_GROQ_MODEL,
        'messages'    => [['role' => 'user', 'content' => $prompt]],
        'max_tokens'  => 3000,
        'temperature' => 0.7,
    ]);
    foreach ($keys as $key) {
        $ch = curl_init(GROQ_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $key,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200) {
            $result = json_decode($response, true);
            return $result['choices'][0]['message']['content'] ?? null;
        }
        // 429 = rate limit — probar siguiente clave
        if ($http_code === 429) {
            log_info("[bulk_faqs] Clave agotada (429), rotando a siguiente...");
            continue;
        }
        log_error("[bulk_faqs] Error Groq HTTP $http_code: " . substr($response, 0, 300));
        return null;
    }
    log_error("[bulk_faqs] Todas las claves Groq agotadas.");
    return null;
}

function generar_y_guardar_faqs(string $slug, string $nombre): int {
    $prompt_template = defined('FAQ_PROMPT_TEMPLATE')
        ? FAQ_PROMPT_TEMPLATE
        : 'Genera 8-10 preguntas frecuentes sobre {MARCA_NOMBRE}, especialmente relacionadas con códigos de descuento, promociones, registro y uso de la plataforma. Responde en español y formato JSON con estructura: [{"pregunta": "...", "respuesta": "..."}]';
    $prompt   = str_replace('{MARCA_NOMBRE}', $nombre, $prompt_template);
    $content  = groq_call($prompt);

    if ($content === null) {
        return 0;
    }

    // Extraer array JSON — Groq a veces añade texto explicativo antes/después
    if (preg_match('/\[.+\]/s', $content, $m)) {
        $content = $m[0];
    }
    $faqs = json_decode($content, true);
    // Groq a veces devuelve objeto raíz {key: [...]}
    if (!is_array($faqs) || isset($faqs['pregunta'])) {
        $raw = json_decode($content, true);
        if (is_array($raw)) {
            if (isset($raw['pregunta'])) {
                $faqs = [$raw]; // objeto único → array
            } else {
                foreach ($raw as $v) {
                    if (is_array($v) && isset($v[0]['pregunta'])) { $faqs = $v; break; }
                }
            }
        }
    }
    // Rescate: respuesta truncada por max_tokens — recortar al último objeto completo
    if (!is_array($faqs) || empty($faqs)) {
        $pos = strrpos($content, '},');
        if ($pos === false) $pos = strrpos($content, '}');
        if ($pos !== false) {
            $recortado = substr($content, 0, $pos + 1) . ']';
            $inicio = strpos($recortado, '[');
            if ($inicio !== false) {
                $faqs = json_decode(substr($recortado, $inicio), true);
            }
        }
    }
    if (!is_array($faqs) || empty($faqs)) {
        log_error("[bulk_faqs] JSON inválido para $slug: " . substr($content, -400));
        return 0;
    }

    $col     = getCollectionFAQs();
    $guardadas = 0;
    foreach ($faqs as $i => $faq) {
        $pregunta  = $faq['pregunta'] ?? '';
        $respuesta = $faq['respuesta'] ?? '';
        if (empty($pregunta) || empty($respuesta)) continue;
        $orden = $col->countDocuments(['marca_clave' => $slug]) + 1;
        $id    = crearFAQ($slug, $pregunta, $respuesta, $orden);
        if ($id) $guardadas++;
    }
    return $guardadas;
}

// Filtrar si se pasó --marca
if ($solo_marca) {
    $marcas_a_procesar = [$solo_marca];
} else {
    // Solo marcas con menos de $min_faqs FAQs
    $col_faqs = getCollectionFAQs();
    $marcas_a_procesar = [];
    foreach ($marcas_prioritarias as $slug) {
        $count = $col_faqs->countDocuments(['marca_clave' => $slug]);
        if ($count < $min_faqs) {
            $marcas_a_procesar[] = $slug;
            if (count($marcas_a_procesar) >= $limit) break;
        }
    }
}

$total_marcas   = count($marcas_a_procesar);
$total_faqs_ok  = 0;
$total_faqs_err = 0;

log_info("[bulk_faqs] Inicio. Marcas a procesar: $total_marcas (limit=$limit, min_faqs=$min_faqs)");
echo "=== Generador FAQs bulk ===\n";
echo "Marcas a procesar: $total_marcas\n\n";

foreach ($marcas_a_procesar as $slug) {
    $marca_doc = get_object_marca('clave', $slug);
    $nombre    = $marca_doc['nombre'] ?? ucwords(str_replace(['-','_'], ' ', $slug));

    $col_faqs        = getCollectionFAQs();
    $faqs_existentes = $col_faqs->countDocuments(['marca_clave' => $slug]);

    if ($faqs_existentes >= $min_faqs) {
        echo "  [$slug] Ya tiene $faqs_existentes FAQs — skip\n";
        continue;
    }

    echo "[$slug] ($nombre) — generando FAQs vía Groq (" . FAQ_GROQ_MODEL . ")...\n";

    $n = generar_y_guardar_faqs($slug, $nombre);
    if ($n > 0) {
        echo "  ✓ $n FAQs generadas y guardadas\n";
        $total_faqs_ok += $n;
    } else {
        echo "  ✗ Error generando FAQs\n";
        $total_faqs_err++;
    }
    echo "\n";
    // Delay entre marcas para no saturar rate limits
    sleep(3);
}

$resumen = "Fin. FAQs generadas: $total_faqs_ok | Errores: $total_faqs_err | Marcas procesadas: $total_marcas";
log_info("[bulk_faqs] $resumen");
echo "=== $resumen ===\n";
