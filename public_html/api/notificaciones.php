<?php
require_once __DIR__ . '/../myphp/funciones_notificaciones.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'contar':
        $count = contar_notificaciones_no_leidas($user_id);
        echo json_encode(['success' => true, 'count' => $count]);
        break;

    case 'listar':
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $raw_notificaciones = obtener_notificaciones_usuario($user_id, $limit);
        
        // Formatear para el frontend
        $notificaciones = [];
        foreach ($raw_notificaciones as $n) {
            $fecha = $n['fecha_creacion']->toDateTime();
            
            // Construir mensaje y enlace según tipo
            $mensaje = '';
            $enlace = '#';
            $icono = 'fas fa-bell';
            
            if ($n['tipo'] === 'respuesta_comentario') {
                $autor = $n['datos']['autor_respuesta'] ?? 'Alguien';
                $mensaje = "<strong>{$autor}</strong> respondió a tu comentario";
                // Determinar slug válido
                $slug = $n['datos']['chollo_slug'] ?? '';
                $chollo_id = $n['datos']['chollo_id'] ?? '';
                
                // Si el slug es inválido o antiguo ('view'), intentar reconstruirlo
                if (empty($slug) || $slug === 'view') {
                    if (!empty($chollo_id)) {
                        $slug = 'general/' . $chollo_id;
                    } else {
                        $slug = 'general/view';
                    }
                }
                
                // Asegurar que no tenga /extra al principio
                $slug = ltrim($slug, '/');
                
                $enlace = "/chollos/" . $slug . "#comment-" . ($n['datos']['comentario_id'] ?? '');
                $icono = 'fas fa-reply';
            } elseif ($n['tipo'] === 'nuevo_viewer') {
                $marca = htmlspecialchars($n['datos']['marca'] ?? 'tu código');
                $beneficio = (int)($n['datos']['beneficio'] ?? 0);
                $is_vip = $n['datos']['is_vip'] ?? false;
                
                if ($is_vip) {
                    $viewer_name = htmlspecialchars($n['datos']['viewer_username'] ?? 'Alguien');
                    $mensaje = "<strong>{$viewer_name}</strong> ha visto tu código de <strong>{$marca}</strong>. Potencial: <strong>{$beneficio}€</strong>";
                    $enlace = "/public/mis_viewers.php";
                } else {
                    $mensaje = "¡Alguien está interesado en tu código de <strong>{$marca}</strong>! Potencial: <strong>{$beneficio}€</strong>. <span style='color:#ffd700;font-weight:bold;'>Hazte VIP</span> para contactarle";
                    $enlace = "/public/mis_viewers.php";
                }
                $icono = 'fas fa-eye';
            } elseif ($n['tipo'] === 'nuevo_mensaje') {
                $is_vip_destinatario = $n['datos']['is_vip'] ?? false;
                $is_vip_remitente = $n['datos']['is_vip_remitente'] ?? null;
                $de_username = htmlspecialchars($n['datos']['de_username'] ?? 'Alguien');
                $de_usuario_id = $n['datos']['de_usuario_id'] ?? '';
                
                // Para notificaciones antiguas sin is_vip_remitente, verificar dinámicamente
                if ($is_vip_remitente === null && !empty($de_usuario_id)) {
                    if (!function_exists('es_usuario_vip')) {
                        require_once __DIR__ . '/../myphp/funciones_usuario.php';
                    }
                    $is_vip_remitente = es_usuario_vip($de_usuario_id);
                }
                
                if ($is_vip_destinatario || $is_vip_remitente) {
                    // El destinatario es VIP, o el remitente es VIP (el no-VIP puede leer y responder)
                    $preview = isset($n['datos']['preview']) && $n['datos']['preview'] ? htmlspecialchars($n['datos']['preview']) : '';
                    $mensaje = "<strong>{$de_username}</strong> te envió un mensaje" . ($preview ? ": \"{$preview}...\"" : "");
                    $enlace = "/public/chat_usuario.php?open_chat=" . $de_usuario_id;
                } else {
                    $mensaje = "<strong>{$de_username}</strong> te ha enviado un mensaje. <span style='color:#ffd700;font-weight:bold;'>Hazte VIP</span> para leerlo y ganar dinero";
                    $enlace = "/public/mis_viewers.php";
                }
                $icono = 'fas fa-envelope';
            }
            
            $notificaciones[] = [
                'id' => (string)$n['_id'],
                'mensaje' => $mensaje,
                'enlace' => $enlace,
                'icono' => $icono,
                'leido' => $n['leido'],
                'fecha' => $fecha->format('c'), // ISO 8601
                'fecha_relativa' => time_elapsed_string($fecha->format('Y-m-d H:i:s'))
            ];
        }
        
        echo json_encode(['success' => true, 'notificaciones' => $notificaciones]);
        break;

    case 'marcar_leida':
        $notificacion_id = $_POST['id'] ?? '';
        if ($notificacion_id) {
            $result = marcar_notificacion_leida($notificacion_id, $user_id);
            echo json_encode(['success' => $result]);
        } else {
            echo json_encode(['success' => false, 'error' => 'ID requerido']);
        }
        break;
        
    case 'marcar_todas':
        $count = marcar_todas_leidas($user_id);
        echo json_encode(['success' => true, 'marked' => $count]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        break;
}

// Helper para tiempo relativo
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'año',
        'm' => 'mes',
        'w' => 'semana',
        'd' => 'día',
        'h' => 'hora',
        'i' => 'minuto',
        's' => 'segundo',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' antes' : 'justo ahora';
}
?>
