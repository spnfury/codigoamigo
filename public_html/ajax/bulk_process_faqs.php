<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones_chollos_groq.php';
require_once __DIR__ . '/../myphp/funciones_marca.php';
require_once __DIR__ . '/../myphp/funciones_faq.php';

// Verificar permisos (Whitelist de admins)
$admin_ids = [
    "58bd851da54e295b8b52f702", //thevega82@gmail.com
    "5e78170e6b68e6519b7c5df2", //edna
    "639899bc6321ee0d0e4010d2", //aron
    "5c8a10ce2f55c86d6e707d82"  //jose
];
if (!isset($_SESSION["user_id"]) || !in_array($_SESSION["user_id"], $admin_ids)) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$items = $_POST['items'] ?? []; // Array de {brand, question}
if (empty($items) || !is_array($items)) {
    echo json_encode(['success' => false, 'error' => 'No hay items para procesar']);
    exit;
}

$results = [];
$processed_count = 0;

foreach ($items as $item) {
    $brand_slug = $item['brand'] ?? '';
    $question = $item['question'] ?? '';

    if (empty($brand_slug) || empty($question)) continue;

    try {
        // 1. Obtener info de marca
        $marca_info = get_brand_info($brand_slug);
        $brand_name = $marca_info['nombre'] ?? ucfirst($brand_slug);

        // 2. Generar con IA
        $prompt = "Eres un experto en atención al cliente y SEO para una web de códigos de descuento.
Genera una respuesta corta y útil (máximo 50-60 palabras) para la siguiente pregunta frecuente sobre la marca '$brand_name'.
Pregunta: $question
Respuesta directa y amigable. No menciones 'lo siento' ni que eres una IA. Si no sabes la respuesta exacta, da un consejo general sobre cómo ahorrar en esa marca.
Devuelve SOLO el texto de la respuesta, nada más.";

        $resultado_ia = reescribirTextoGroq($prompt, 'general');
        
        if ($resultado_ia['success']) {
            $answer = trim($resultado_ia['texto_reescrito']);
            
            // 3. Guardar en DB
            $collection_faqs = getCollectionFAQs();
            $count = $collection_faqs->countDocuments(['marca_clave' => $brand_slug]);
            $resultId = crearFAQ($brand_slug, $question, $answer, $count + 1);
            
            if ($resultId) {
                $results[] = [
                    'question' => $question,
                    'brand' => $brand_slug,
                    'success' => true
                ];
                $processed_count++;
            } else {
                $results[] = ['question' => $question, 'brand' => $brand_slug, 'success' => false, 'error' => 'Error al guardar DB'];
            }
        } else {
            $results[] = ['question' => $question, 'brand' => $brand_slug, 'success' => false, 'error' => 'Error IA: ' . ($resultado_ia['error'] ?? 'Unknown')];
        }
    } catch (Exception $e) {
        $results[] = ['question' => $question, 'brand' => $brand_slug, 'success' => false, 'error' => $e->getMessage()];
    }
    
    // Pequeño delay para no saturar rate limits si hay muchos
    usleep(200000); 
}

echo json_encode([
    'success' => true,
    'processed' => $processed_count,
    'results' => $results
]);
