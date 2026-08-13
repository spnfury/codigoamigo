<?php
/**
 * API de Mantenimiento de Enlaces de Amazon
 * Permite listar enlaces no expandidos y actualizarlos masivamente.
 */

require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones_chollos.php';
require_once __DIR__ . '/../myphp/funciones_chollos_amazon.php';

// Seguridad: Solo administradores (ajusta según tu sistema de sesión)
// session_start();
// if (!isset($_SESSION['usuario_admin'])) {
//     header('Content-Type: application/json');
//     echo json_encode(['success' => false, 'error' => 'No autorizado']);
//     exit;
// }

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

$collection = getCollectionChollos();

// -------------------------------------------------------------------------
// ACCIÓN: Obtener enlaces rotos/no expandidos
// -------------------------------------------------------------------------
if ($action === 'get_broken') {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    
    // Buscamos enlaces de ganga.ad o chollo.biz que no tengan ASIN o enlace_expandido
    $query = [
        'enlace' => [
            '$regex' => 'ganga\.ad|chollo\.biz',
            '$options' => 'i'
        ],
        '$or' => [
            ['enlace_expandido' => ['$exists' => false]],
            ['enlace_expandido' => null],
            ['asin' => ['$exists' => false]],
            ['asin' => null]
        ]
    ];
    
    $cursor = $collection->find($query, [
        'limit' => $limit,
        'projection' => [
            'titulo' => 1,
            'enlace' => 1
        ]
    ]);
    
    $links = [];
    foreach ($cursor as $doc) {
        $links[] = [
            'id' => (string)$doc['_id'],
            'titulo' => $doc['titulo'] ?? 'Sin título',
            'enlace' => $doc['enlace']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'count' => count($links),
        'links' => $links
    ]);
    exit;
}

// -------------------------------------------------------------------------
// ACCIÓN: Actualizar enlace expandido
// -------------------------------------------------------------------------
if ($action === 'update') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = $data['id'] ?? '';
    $expanded_url = $data['enlace_expandido'] ?? '';
    $asin = $data['asin'] ?? '';
    
    // Si enviaron ASIN, bien. Si no, intentamos extraerlo del enlace expandido.
    if (!$asin) {
        $asin = extraerASIN($expanded_url);
    }

    // --- NUEVO: FALLBACK POR TÍTULO (MAESTRO) ---
    // Si aún no tenemos ASIN, buscamos en la DB otros chollos con títulos similares
    if (!$asin) {
        $chollo_actual = $collection->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
        if ($chollo_actual && !empty($chollo_actual['titulo'])) {
            $titulo = $chollo_actual['titulo'];
            
            // 1. Intento por match exacto (Fácil)
            $similar = $collection->findOne([
                'titulo' => $titulo,
                'asin' => ['$exists' => true, '$ne' => null]
            ], ['projection' => ['asin' => 1, 'enlace_expandido' => 1]]);
            
            // 2. Intento por match difuso (Palabras clave)
            if (!$similar) {
                // Limpiar título para buscar palabras clave
                $clean_title = preg_replace('/[^a-zA-Z0-9 ]/', '', $titulo);
                $words = explode(' ', $clean_title);
                $keywords = [];
                $ignored_words = ['oferta', 'chollo', 'descuento', 'amazon', 'pro', 'con', 'del', 'los', 'las', 'unos', 'unas'];
                foreach ($words as $w) {
                    $w_lower = strtolower($w);
                    if (strlen($w) > 3 && !in_array($w_lower, $ignored_words)) {
                        $keywords[] = $w;
                    }
                }
                
                // Si tenemos keywords, buscamos otro que tenga al menos las keywords más importantes
                if (count($keywords) >= 1) {
                    // Si tenemos al menos 2 keywords, usamos las 2 primeras importantes
                    if (count($keywords) >= 2) {
                        $regex = '.*' . preg_quote($keywords[0]) . '.*' . preg_quote($keywords[1]) . '.*';
                    } else {
                        $regex = '.*' . preg_quote($keywords[0]) . '.*';
                    }
                    $similar = $collection->findOne([
                        'titulo' => ['$regex' => $regex, '$options' => 'i'],
                        'asin' => ['$exists' => true, '$ne' => null]
                    ], ['projection' => ['asin' => 1, 'enlace_expandido' => 1]]);
                }
            }
            
            if ($similar) {
                $asin = $similar['asin'];
                if (empty($expanded_url)) {
                    $expanded_url = $similar['enlace_expandido'];
                }
            }
        }
    }

    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
        exit;
    }
    
    // Si después de todo no hay expanded_url, usamos el original del chollo (fallido)
    if (empty($expanded_url)) {
        $chollo_actual = $collection->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
        if ($chollo_actual && !empty($chollo_actual['enlace'])) {
            $expanded_url = $chollo_actual['enlace'];
        }
    }
    
    // Asegurar que el tag spnfuryy-21 esté presente en el enlace final
    if (!empty($expanded_url) && strpos($expanded_url, 'amazon.') !== false) {
        $expanded_url = convertirEnlaceAmazon($expanded_url);
    }

    // Si no enviaron ASIN, intentamos extraerlo
    if (!$asin) {
        $asin = extraerASIN($expanded_url);
    }

    $updateData = [
        'enlace_expandido' => $expanded_url,
        'fecha_expansion' => new MongoDB\BSON\UTCDateTime()
    ];
    
    if ($asin) {
        $updateData['asin'] = $asin;
    }
    
    // También actualizamos el 'enlace' original si ya tenemos el expandido final con tag
    $updateData['enlace'] = $expanded_url;

    $result = $collection->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($id)],
        ['$set' => $updateData]
    );

    if ($result->getModifiedCount() > 0) {
        // Registrar el click cuando se resuelve/actualiza vía mantenimiento o interstitial.
        // NOTA: Si viene de resolve_amazon.php, el click YA fue registrado en app_with_mongo.php.
        // Solo registramos aquí si es una actualización real de datos que no viene del flujo normal de redirección.
        // registrarClickChollo($id, ['resolution_method' => 'api_maintenance_update']);
    }

    echo json_encode([
        'success' => ($result->getMatchedCount() > 0 || $result->getModifiedCount() > 0),
        'id' => $id,
        'asin' => $asin,
        'enlace' => $expanded_url
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Acción no válida']);
