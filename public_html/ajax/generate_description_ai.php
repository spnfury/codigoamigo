<?php
/**
 * AJAX Endpoint: Generar descripción con IA
 * 
 * VIP users: unlimited use
 * Non-VIP users: blocked, shown VIP upgrade modal
 */

session_start();
header('Content-Type: application/json');

// Incluir dependencias
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';
require_once __DIR__ . '/../config/ai_config.php';

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Verificar sesión de usuario
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'error' => 'Debes iniciar sesión para usar esta función',
        'require_login' => true
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Obtener parámetros
$marca = isset($_POST['marca']) ? trim($_POST['marca']) : '';
$beneficio = isset($_POST['beneficio']) ? trim($_POST['beneficio']) : '';
$tipo_beneficio = isset($_POST['tipo_beneficio']) ? trim($_POST['tipo_beneficio']) : 'euros';

if (empty($marca)) {
    echo json_encode(['success' => false, 'error' => 'Debes seleccionar una marca primero']);
    exit;
}

// Verificar si es usuario VIP
$is_vip = es_usuario_vip($user_id);

// Si no es VIP, mostrar modal de ventajas VIP directamente
if (!$is_vip) {
    echo json_encode([
        'success' => false,
        'show_vip_modal' => true,
        'is_vip' => false,
        'message' => 'La función de Completar con IA es exclusiva para usuarios VIP. ¡Hazte VIP para disfrutar de todas las ventajas!'
    ]);
    exit;
}

// Generar descripción con IA usando Groq
try {
    $beneficio_texto = '';
    if (!empty($beneficio)) {
        $beneficio_texto = $beneficio . ($tipo_beneficio === 'porcentaje' ? '%' : '€');
    }
    
    $prompt = "Genera una descripción breve y atractiva (máximo 150 palabras) para un código de descuento de la marca {$marca}";
    if (!empty($beneficio_texto)) {
        $prompt .= " que ofrece un beneficio de {$beneficio_texto}";
    }
    $prompt .= ". La descripción debe:
- Ser persuasiva y generar interés
- Destacar los beneficios para el usuario
- Ser natural y no parecer spam
- NO incluir el código de descuento (será añadido automáticamente)
- Estar en español
- Invitar a usar el código sin prometer cosas que no se pueden garantizar

Solo devuelve la descripción, sin explicaciones adicionales ni comillas.";

    // Rotación de claves + fallback de modelo: el modelo por defecto (8b-instant)
    // agota su límite de tokens por minuto con facilidad (bulks/concurrencia) y
    // devolvía 429 directo al usuario. Probamos cada clave y, si todas fallan,
    // caemos a llama-3.3-70b-versatile (cuota TPM separada).
    $keys    = (defined('GROQ_API_KEYS') && !empty(GROQ_API_KEYS)) ? GROQ_API_KEYS : [GROQ_API_KEY];
    $modelos = array_values(array_unique([GROQ_MODEL, 'llama-3.3-70b-versatile']));
    $timeout = defined('AI_TIMEOUT') ? AI_TIMEOUT : 30;

    $description  = null;
    $ultimo_http  = 0;
    $ultimo_resp  = '';
    foreach ($modelos as $modelo) {
        foreach ($keys as $key) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => GROQ_API_URL,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $key,
                ],
                CURLOPT_POSTFIELDS     => json_encode([
                    'model'    => $modelo,
                    'messages' => [
                        ['role' => 'system', 'content' => 'Eres un experto en marketing digital que escribe descripciones persuasivas para códigos de descuento. Siempre escribes en español de España.'],
                        ['role' => 'user',   'content' => $prompt],
                    ],
                    'max_tokens'  => 500,
                    'temperature' => 0.7,
                ]),
            ]);
            $response  = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $ultimo_http = $http_code;
            $ultimo_resp = $response;

            if ($http_code === 200) {
                $result = json_decode($response, true);
                $contenido = $result['choices'][0]['message']['content'] ?? null;
                if ($contenido !== null && trim($contenido) !== '') {
                    $description = trim($contenido);
                    break 2; // éxito
                }
            }
            // 429 (rate limit) o 5xx: rotar a la siguiente clave / siguiente modelo
        }
    }

    if ($description === null) {
        if (function_exists('log_error')) {
            log_error("[generate_description_ai] Groq agotado tras rotación. HTTP $ultimo_http: " . substr((string)$ultimo_resp, 0, 300));
        }
        echo json_encode(['success' => false, 'error' => 'Error al generar la descripción. Inténtalo de nuevo.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'description' => $description,
        'is_vip' => true,
        'show_vip_modal' => false
    ]);

} catch (Exception $e) {
    if (function_exists('log_error')) {
        log_error("[generate_description_ai] Excepción: " . $e->getMessage());
    }
    echo json_encode(['success' => false, 'error' => 'Error interno. Inténtalo de nuevo.']);
}
