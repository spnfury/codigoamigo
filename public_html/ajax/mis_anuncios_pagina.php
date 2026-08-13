<?php
/**
 * AJAX endpoint: paginación infinita "Mis anuncios".
 *
 * GET /ajax/mis_anuncios_pagina.php?estado=todos&p=2
 * Returns JSON: { html, has_more, page, total }
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

require_once __DIR__ . '/../inc/conexion.php';
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_codigo.php';
require_once __DIR__ . '/../myphp/funciones_marca.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';
@include_once __DIR__ . '/../myphp/_super_landing_functions.php';
@require_once __DIR__ . '/../myphp/links.php';

try {
    $estado_tab = $_GET['estado'] ?? 'todos';
    $estados_validos = ['todos', 'activos', 'caducados', 'desactivados', 'inactivos', 'destacados'];
    if (!in_array($estado_tab, $estados_validos, true)) {
        $estado_tab = 'todos';
    }
    $pagina = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
    $por_pagina = 24;
    $skip = ($pagina - 1) * $por_pagina;

    $user_oid = new MongoDB\BSON\ObjectId($_SESSION['user_id']);
    $collection_codigos = getCollectionCodigos();

    $array_filtro = ['id_usuario' => $user_oid];
    switch ($estado_tab) {
        case 'activos':
            $array_filtro['$or'] = [['estado' => 0], ['estado' => ['$exists' => false]]];
            break;
        case 'caducados':
            $array_filtro['estado'] = -3;
            break;
        case 'desactivados':
            $array_filtro['estado'] = -2;
            break;
        case 'inactivos':
            $array_filtro['estado'] = -1;
            break;
        case 'destacados':
            $array_filtro['$and'] = [
                ['$or' => [['estado' => 0], ['estado' => ['$exists' => false]]]],
                ['destacado' => ['$gt' => 0]],
            ];
            break;
    }

    // Búsqueda libre: marca / descripcion / codigo (case-insensitive)
    $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
    if ($q !== '') {
        $regex_str = preg_quote($q, '/');
        $regex = new MongoDB\BSON\Regex($regex_str, 'i');
        $or_clauses = [
            ['marca'       => $regex],
            ['descripcion' => $regex],
            ['codigo'      => $regex],
        ];
        if (isset($array_filtro['$or'])) {
            $array_filtro['$and'] = [
                ['$or' => $array_filtro['$or']],
                ['$or' => $or_clauses],
            ];
            unset($array_filtro['$or']);
        } else {
            $array_filtro['$or'] = $or_clauses;
        }
    }

    $total_filtrado = $collection_codigos->count($array_filtro);
    $cursor = $collection_codigos->find($array_filtro, [
        'sort'  => ['estado' => -1, 'destacado' => -1, 'fecha_publicacion' => -1],
        'skip'  => $skip,
        'limit' => $por_pagina,
    ]);
    $listado_codigos = iterator_to_array($cursor);

    // Potencial — necesario para chips de mensaje masivo
    $potencial_data = function_exists('obtener_potencial_completo_usuario')
        ? obtener_potencial_completo_usuario($_SESSION['user_id'])
        : ['per_code' => []];

    ob_start();
    foreach ($listado_codigos as $codigo) {
        include __DIR__ . '/../public/_mis_anuncios_card.php';
    }
    $html = ob_get_clean();

    $cargados = $skip + count($listado_codigos);
    $has_more = $cargados < $total_filtrado;

    echo json_encode([
        'success'  => true,
        'html'     => $html,
        'page'     => $pagina,
        'has_more' => $has_more,
        'total'    => $total_filtrado,
        'cargados' => $cargados,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    log_error('mis_anuncios_pagina: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error servidor']);
}
