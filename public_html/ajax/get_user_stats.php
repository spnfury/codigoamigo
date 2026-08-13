<?php
/**
 * AJAX endpoint: devuelve estadísticas de un usuario por su _id de MongoDB.
 * Responde en JSON. Sin autenticación requerida (datos públicos).
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: max-age=300');

$doc_root = $_SERVER['DOCUMENT_ROOT'];

// 1. Funciones de colecciones de usuarios y códigos
if (!function_exists('getCollectionUsuarios')) {
    require_once $doc_root . '/myphp/funciones_usuario.php';
}
if (!function_exists('getCollectionCodigos')) {
    require_once $doc_root . '/myphp/funciones_codigo.php';
}
// 2. Funciones base: createConnection, getObjectUser, etc.
if (!function_exists('createConnection')) {
    require_once $doc_root . '/myphp/funciones.php';
}

$user_id = trim($_GET['user_id'] ?? '');

if (empty($user_id)) {
    echo json_encode(['ok' => false, 'error' => 'missing user_id']);
    exit;
}

// Convertir string a ObjectId de MongoDB
try {
    $obj_id = new MongoDB\BSON\ObjectId($user_id);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => 'invalid user_id: ' . $e->getMessage()]);
    exit;
}

try {
    // Obtener usuario
    $collection_usuarios = getCollectionUsuarios();
    $usuario = $collection_usuarios->findOne(['_id' => $obj_id]);

    if (!$usuario) {
        echo json_encode(['ok' => false, 'error' => 'user_not_found']);
        exit;
    }

    $collection_codigos = getCollectionCodigos();

    // --- Códigos activos del usuario ---
    // id_usuario puede ser string o ObjectId según cuándo se insertó el código
    $total_codigos_str = (int) $collection_codigos->countDocuments(['id_usuario' => $user_id, 'estado' => 0]);
    $total_codigos_obj = (int) $collection_codigos->countDocuments(['id_usuario' => $obj_id, 'estado' => 0]);
    $total_codigos = max($total_codigos_str, $total_codigos_obj);

    // --- Comentarios ---
    $total_comentarios = 0;
    try {
        $db = createConnection();
        if ($db) {
            $c1 = (int) $db->comentarios->countDocuments(['id_usuario' => $user_id]);
            $c2 = (int) $db->comentarios->countDocuments(['id_usuario' => $obj_id]);
            $total_comentarios = max($c1, $c2);
        }
    } catch (Exception $e) { /* silencioso */ }

    // --- Likes / votos positivos recibidos ---
    $total_likes = 0;
    try {
        foreach ([$user_id, $obj_id] as $uid_k) {
            $res = $collection_codigos->aggregate([
                ['$match' => ['id_usuario' => $uid_k, 'estado' => 0]],
                ['$group' => ['_id' => null, 'likes' => ['$sum' => ['$ifNull' => ['$votos_positivos', 0]]]]]
            ])->toArray();
            if (!empty($res)) {
                $v = (int)($res[0]['likes'] ?? 0);
                if ($v > 0) { $total_likes = $v; break; }
            }
        }
    } catch (Exception $e) { /* silencioso */ }

    // --- Promedio temperatura ---
    $promedio_temp = '-°';
    try {
        foreach ([$user_id, $obj_id] as $uid_k) {
            $res = $collection_codigos->aggregate([
                ['$match' => ['id_usuario' => $uid_k, 'estado' => 0]],
                ['$group' => ['_id' => null, 'avg' => ['$avg' => ['$ifNull' => ['$temperatura', 0]]]]]
            ])->toArray();
            if (!empty($res) && isset($res[0]['avg'])) {
                $v = round((float)$res[0]['avg'], 1);
                if ($v > 0) { $promedio_temp = $v . '°'; break; }
            }
        }
    } catch (Exception $e) { /* silencioso */ }

    // --- Fecha de registro ---
    $fecha_registro = 'Miembro de la comunidad';
    if (isset($usuario['fecha_registro'])) {
        $fr = $usuario['fecha_registro'];
        $ts = null;
        if ($fr instanceof MongoDB\BSON\UTCDateTime) {
            $ts = (int)($fr->toDateTime()->format('U'));
        } elseif (is_numeric($fr)) {
            $ts = (int)$fr;
        }
        if ($ts) {
            $diff = time() - $ts;
            if ($diff < 86400)          $fecha_registro = 'Hoy';
            elseif ($diff < 2592000)    $fecha_registro = 'Hace ' . floor($diff / 86400) . ' días';
            elseif ($diff < 31536000)   $fecha_registro = 'Hace ' . floor($diff / 2592000) . ' meses';
            else                        $fecha_registro = 'Hace ' . floor($diff / 31536000) . ' años';
        }
    }

    $is_vip = !empty($usuario['is_vip']);
    
    // Check follow status
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    $is_following = false;
    if (!empty($_SESSION['user_id'])) {
        if (!function_exists('es_favorito')) {
            require_once $doc_root . '/myphp/funciones_favoritos.php';
        }
        $is_following = es_favorito($_SESSION['user_id'], $user_id, 'usuario');
    }

    echo json_encode([
        'ok'                => true,
        'username'          => $usuario['username'] ?? '',
        'img'               => $usuario['img'] ?? $usuario['avatar'] ?? '',
        'total_codigos'     => $total_codigos,
        'total_comentarios' => $total_comentarios,
        'total_likes'       => $total_likes,
        'promedio_temp'     => $promedio_temp,
        'fecha_registro'    => $fecha_registro,
        'is_vip'            => $is_vip,
        'is_following'      => $is_following,
    ]);

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
