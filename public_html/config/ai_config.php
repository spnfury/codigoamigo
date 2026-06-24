<?php

/**
 * Configuración de APIs externas (IA, Telegram, Amazon, YouTube).
 *
 * Las claves se cargan desde:
 *   1. Variables de entorno (FPM pool, systemd, getenv)
 *   2. Fallback a /private/api_secrets.php (fuera de git)
 *
 * Para rotar una clave: editar /private/api_secrets.php (chmod 600).
 */

// Bootstrap: cargar private/api_secrets.php SIEMPRE para rellenar las keys
// que no estén ya en el entorno (cada línea del private file usa ?? para no sobrescribir).
$secrets_path = dirname(__DIR__, 2) . '/private/api_secrets.php';
if (is_readable($secrets_path)) {
    require_once $secrets_path;
}

// Helper: prefiere $_ENV → getenv → fallback
if (!function_exists('_api_env')) {
    function _api_env($key, $default = '') {
        if (!empty($_ENV[$key])) return $_ENV[$key];
        $v = getenv($key);
        return ($v !== false && $v !== '') ? $v : $default;
    }
}

// Perplexity AI
define('PERPLEXITY_API_KEY', _api_env('PERPLEXITY_API_KEY', 'pplx-your-api-key-here'));
define('PERPLEXITY_API_URL', 'https://api.perplexity.ai/chat/completions');
define('PERPLEXITY_MODEL', 'llama-3.1-sonar-small-128k-online');

// Groq AI
define('GROQ_API_KEY',   _api_env('GROQ_API_KEY'));
define('GROQ_API_KEY_2', _api_env('GROQ_API_KEY_2', ''));
define('GROQ_API_KEYS',  array_filter([GROQ_API_KEY, GROQ_API_KEY_2])); // pool de claves para rotación
define('GROQ_API_URL',   'https://api.groq.com/openai/v1/chat/completions');
define('GROQ_MODEL',     'llama-3.1-8b-instant');

// Configuración general de IA
define('AI_MAX_TOKENS', 2000);
define('AI_TEMPERATURE', 0.7);
define('AI_TIMEOUT', 30);

// Prompts FAQs
define('FAQ_PROMPT_TEMPLATE', 'Genera 8-10 preguntas frecuentes sobre {MARCA_NOMBRE}, especialmente relacionadas con códigos de descuento, promociones, registro y uso de la plataforma. Responde en español y formato JSON con estructura: [{"pregunta": "...", "respuesta": "..."}]');

// Fallback
define('AI_FALLBACK_ENABLED', true);
define('AI_FALLBACK_PROVIDER', 'groq');

// Prompts Chollos
define('CHOLLO_PROMPT_TITULO', 'Reescribe este título de chollo de forma atractiva y SEO-friendly, manteniendo la información clave del producto y el descuento. Máximo 80 caracteres. Solo devuelve el título reescrito, sin explicaciones:');
define('CHOLLO_PROMPT_DESCRIPCION', 'Reescribe esta descripción de chollo de forma atractiva, destacando las ventajas y beneficios del producto. Usa un tono persuasivo pero honesto. Máximo 200 palabras. Solo devuelve la descripción reescrita, sin explicaciones:');
define('CHOLLO_PROMPT_GENERAL', 'Reescribe este texto de chollo de forma atractiva y profesional, manteniendo toda la información importante sobre el producto y la oferta. Solo devuelve el texto reescrito, sin explicaciones:');

// Prompts Marcas
define('MARCA_PROMPT_DESCRIPCION', 'Genera una descripción corta y atractiva (máximo 150 palabras) para la marca {NOMBRE_MARCA} en la categoría {CATEGORIA}. Debe ser SEO-friendly y destacar los beneficios principales. Solo devuelve la descripción, sin explicaciones:');
define('MARCA_PROMPT_DESCRIPCION_LARGA', 'Genera una descripción larga y detallada (mínimo 500 palabras) para la marca {NOMBRE_MARCA} en la categoría {CATEGORIA}. Incluye información sobre la empresa, servicios, beneficios y por qué usar códigos de descuento. Solo devuelve la descripción, sin explicaciones:');
define('MARCA_PROMPT_VENTAJA', 'Genera una ventaja principal atractiva y concisa (máximo 20 palabras) para la marca {NOMBRE_MARCA} en la categoría {CATEGORIA}. Debe ser un beneficio específico y convincente. Solo devuelve la ventaja, sin explicaciones:');

// Telegram
define('TELEGRAM_BOT_TOKEN', _api_env('TELEGRAM_BOT_TOKEN'));
define('TELEGRAM_CHOLLOS_CHAT_ID_SALIDA', _api_env('TELEGRAM_CHOLLOS_CHAT_ID_SALIDA'));
define('TELEGRAM_CHOLLOS_CHAT_ID_ENTRADA', _api_env('TELEGRAM_CHOLLOS_CHAT_ID_ENTRADA'));
define('TELEGRAM_ADMIN_CHAT_ID', _api_env('TELEGRAM_ADMIN_CHAT_ID'));
define('TELEGRAM_SYNC_TOKEN', _api_env('TELEGRAM_SYNC_TOKEN'));

// Amazon Affiliate / PA-API
if (!defined('AMAZON_AFFILIATE_ID')) {
    define('AMAZON_AFFILIATE_ID', _api_env('AMAZON_AFFILIATE_ID', 'spnfuryy-21'));
}
if (!defined('AMAZON_PAAPI_ACCESS_KEY')) {
    define('AMAZON_PAAPI_ACCESS_KEY', _api_env('AMAZON_PAAPI_ACCESS_KEY'));
}
if (!defined('AMAZON_PAAPI_SECRET_KEY')) {
    define('AMAZON_PAAPI_SECRET_KEY', _api_env('AMAZON_PAAPI_SECRET_KEY'));
}
if (!defined('AMAZON_PAAPI_REGION')) {
    define('AMAZON_PAAPI_REGION', _api_env('AMAZON_PAAPI_REGION', 'es'));
}
if (!defined('AMAZON_PAAPI_HOST')) {
    define('AMAZON_PAAPI_HOST', _api_env('AMAZON_PAAPI_HOST', 'webservices.amazon.es'));
}

// YouTube
if (!defined('YOUTUBE_API_KEY')) {
    define('YOUTUBE_API_KEY', _api_env('YOUTUBE_API_KEY'));
}
if (!defined('YOUTUBE_API_KEYS')) {
    define('YOUTUBE_API_KEYS', [
        _api_env('YOUTUBE_API_KEY'),
    ]);
}

/**
 * Llamada robusta a la API de Groq con rotación de claves y fallback de modelo.
 *
 * Recibe el payload ya construido (mismo formato que la API: model, messages,
 * max_tokens, etc.) y prueba cada clave del pool (GROQ_API_KEYS); si todas
 * fallan con 429/5xx, reintenta con llama-3.3-70b-versatile (cuota TPM
 * separada del modelo 8b-instant). El modelo 8b agota su límite de tokens por
 * minuto con facilidad bajo carga, así que sin esto un 429 puntual rompe la
 * funcionalidad de cara al usuario.
 *
 * @param array    $data    Payload Groq. Si no trae 'model' se usa GROQ_MODEL.
 * @param int|null $timeout Segundos (def. AI_TIMEOUT).
 * @return array ['ok'=>bool, 'http'=>int, 'body'=>string, 'content'=>?string]
 */
if (!function_exists('groq_request')) {
    function groq_request(array $data, $timeout = null) {
        $timeout = $timeout ?? (defined('AI_TIMEOUT') ? AI_TIMEOUT : 30);
        $url     = defined('GROQ_API_URL') ? GROQ_API_URL : 'https://api.groq.com/openai/v1/chat/completions';
        $keys    = (defined('GROQ_API_KEYS') && !empty(GROQ_API_KEYS))
            ? GROQ_API_KEYS
            : [defined('GROQ_API_KEY') ? GROQ_API_KEY : ''];

        $base_model = $data['model'] ?? (defined('GROQ_MODEL') ? GROQ_MODEL : 'llama-3.1-8b-instant');
        $modelos    = array_values(array_unique([$base_model, 'llama-3.3-70b-versatile']));

        $last_http = 0;
        $last_body = '';
        foreach ($modelos as $modelo) {
            $data['model'] = $modelo;
            $payload = json_encode($data);
            foreach ($keys as $key) {
                if (empty($key)) continue;
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL            => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_TIMEOUT        => $timeout,
                    CURLOPT_HTTPHEADER     => [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $key,
                    ],
                    CURLOPT_POSTFIELDS     => $payload,
                ]);
                $body  = curl_exec($ch);
                $http  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $cerr  = curl_error($ch);
                curl_close($ch);

                $last_http = $http;
                $last_body = (string)$body;

                if ($http === 200) {
                    $json    = json_decode((string)$body, true);
                    $content = $json['choices'][0]['message']['content'] ?? null;
                    return ['ok' => true, 'http' => 200, 'body' => (string)$body, 'content' => $content];
                }
                // 429 (rate limit), 5xx o error de red → rotar clave / probar fallback.
                // Otros 4xx también se reintentan con el siguiente modelo por si es
                // un modelo retirado; el coste extra solo ocurre en fallo total.
                if ($cerr && function_exists('log_error')) {
                    log_error("[groq_request] cURL error ($modelo): $cerr");
                }
            }
        }
        return ['ok' => false, 'http' => $last_http, 'body' => $last_body, 'content' => null];
    }
}
