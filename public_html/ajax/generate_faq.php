<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones_chollos_groq.php';
require_once __DIR__ . '/../myphp/funciones_marca.php';

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

$brand_slug = $_POST['brand'] ?? '';
$question = $_POST['question'] ?? '';

if (empty($brand_slug) || empty($question)) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

// Obtener info básica de la marca para contexto
$marca_info = get_brand_info($brand_slug);
$brand_name = $marca_info['nombre'] ?? ucfirst($brand_slug);

// Prompt para Groq
$prompt = "Eres un experto en atención al cliente y SEO para una web de códigos de descuento.
Genera una respuesta corta y útil (máximo 50-60 palabras) para la siguiente pregunta frecuente sobre la marca '$brand_name'.
Pregunta: $question
Respuesta directa y amigable. No menciones 'lo siento' ni que eres una IA. Si no sabes la respuesta exacta, da un consejo general sobre cómo ahorrar en esa marca.
Devuelve SOLO el texto de la respuesta, nada más.";

$resultado_ia = reescribirTextoGroq($prompt, 'general');

if ($resultado_ia['success']) {
    echo json_encode([
        'success' => true,
        'question' => $question,
        'answer' => trim($resultado_ia['texto_reescrito']),
        'brand' => $brand_slug
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error AI: ' . ($resultado_ia['error'] ?? 'Desconocido')]);
}
