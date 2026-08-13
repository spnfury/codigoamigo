<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../inc/includes.php';
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

$brand_slug = $_POST['brand'] ?? '';
$question = $_POST['question'] ?? '';
$answer = $_POST['answer'] ?? '';

if (empty($brand_slug) || empty($question) || empty($answer)) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

try {
    // Verificar que la marca existe
    $marca_doc = get_object_marca('nombre_clave', $brand_slug);
    if (!$marca_doc) {
        throw new Exception("Marca no encontrada");
    }

    // Calcular orden (ponerlo al final)
    $collection_faqs = getCollectionFAQs();
    $count = $collection_faqs->countDocuments(['marca_clave' => $brand_slug]);
    
    // Crear la FAQ en la colección dedicada usando la función helper
    // crearFAQ($marca_clave, $titulo, $respuesta, $orden = 0)
    $resultId = crearFAQ($brand_slug, $question, $answer, $count + 1);

    if ($resultId) {
        echo json_encode(['success' => true, 'message' => 'FAQ guardada correctamente en el sistema de FAQs']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al crear la FAQ']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
