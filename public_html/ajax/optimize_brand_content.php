<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones_chollos_groq.php';
require_once __DIR__ . '/../myphp/funciones_marca.php';
require_once __DIR__ . '/../myphp/funciones_modern.php';

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
$keyword = $_POST['keyword'] ?? '';

if (empty($brand_slug)) {
    echo json_encode(['success' => false, 'error' => 'Marca no proporcionada']);
    exit;
}

// 1. Obtener info actual de la marca
$marca_info = get_brand_info($brand_slug);
if (!$marca_info || empty($marca_info['nombre'])) {
    // Si no existe, intentar buscar por nombre_clave directamente
    $marca_doc = get_object_marca('nombre_clave', $brand_slug);
    if (!$marca_doc) {
        echo json_encode(['success' => false, 'error' => 'Marca no encontrada en DB']);
        exit;
    }
} else {
    $marca_doc = get_object_marca('nombre_clave', $brand_slug);
}

$brand_name = $marca_doc['nombre'] ?? ucfirst($brand_slug);
$current_h1 = $marca_doc['h1'] ?? 'Códigos Amigo, Referidos y Descuentos ' . $brand_name;
$current_h2 = $marca_doc['h2'] ?? 'Listado de Códigos Amigo y Cupones ' . $brand_name;

// 2. Preparar el prompt con reemplazos
// Como reescribirTextoGroq solo concatena, vamos a pasar el texto 'pre-procesado' si podemos,
// o mejor aún, modificamos temporalmente el prompt o pasamos uno custom.
// En este caso, como ya definí 'seo_full_optimizer' en el array, voy a hacer el reemplazo aquí.

$prompts = [
    'seo_full_optimizer' => "Eres un experto en SEO y Copywriting. Tu tarea es optimizar una página de códigos de descuento. 
        Keyword principal: {keyword}. 
        Marca: {brand}.
        Actual H1: {h1}.
        Actual H2: {h2}.
        
        Debes devolver un JSON con la siguiente estructura:
        {
            \"h1\": \"Nuevo H1 atractivo con la keyword\",
            \"h2\": \"Nuevo H2 persuasivo\",
            \"descripcion\": \"Meta descripción optimizada (máx 155 caracteres)\",
            \"seo_que_es\": \"Un párrafo largo (mínimo 100 palabras) explicando qué es la marca y sus beneficios para el SEO\",
            \"seo_tips\": \"3 consejos rápidos para ahorrar en esta marca\"
        }
        El tono debe ser profesional y enfocado al ahorro. Devuelve EXCLUSIVAMENTE el JSON, sin texto adicional."
];

$final_prompt = str_replace(
    ['{keyword}', '{brand}', '{h1}', '{h2}'],
    [$keyword, $brand_name, $current_h1, $current_h2],
    $prompts['seo_full_optimizer']
);

// Llamamos a Groq usando 'general' pero pasándole nuestro prompt ya construido como 'texto'
// O mejor, llamamos a una versión modificada de reescribirTextoGroq si fuera posible.
// Como no quiero tocar funciones core, voy a usar un pequeño truco:
$resultado_ia = reescribirTextoGroq($final_prompt, 'general');

if (!$resultado_ia['success']) {
    echo json_encode($resultado_ia);
    exit;
}

$json_content = $resultado_ia['texto_reescrito'];
// Limpiar posibles bloques de código triple backtick
$json_content = preg_replace('/```json|```/', '', $json_content);
$data_optimized = json_decode(trim($json_content), true);

if (!$data_optimized || !isset($data_optimized['h1'])) {
    echo json_encode(['success' => false, 'error' => 'Error al parsear respuesta IA', 'raw' => $json_content]);
    exit;
}

// 3. Normalizar datos (asegurar que sean strings para evitar errores en el template)
foreach(['h1', 'h2', 'descripcion', 'seo_que_es', 'seo_tips'] as $field) {
    if (isset($data_optimized[$field]) && is_array($data_optimized[$field])) {
        $data_optimized[$field] = implode("\n", $data_optimized[$field]);
    }
}

// 4. Actualizar MongoDB
try {
    $collection = getCollectionMarcas();
    $updateResult = $collection->updateOne(
        ['nombre_clave' => $brand_slug],
        ['$set' => [
            'h1' => $data_optimized['h1'] ?? '',
            'h2' => $data_optimized['h2'] ?? '',
            'descripcion' => $data_optimized['descripcion'] ?? '',
            'seo_que_es' => $data_optimized['seo_que_es'] ?? '',
            'seo_tips' => $data_optimized['seo_tips'] ?? '',
            'ultima_optimizacion_ia' => date('Y-m-d H:i:s')
        ]]
    );

    if ($updateResult->getMatchedCount() > 0) {
        $modified = $updateResult->getModifiedCount() > 0;
        echo json_encode([
            'success' => true, 
            'message' => $modified ? '🚀 Web actualizada con éxito' : '✅ La web ya tiene el mejor contenido SEO',
            'modified' => $modified,
            'data' => $data_optimized
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se encontró la marca en la base de datos para actualizar']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
