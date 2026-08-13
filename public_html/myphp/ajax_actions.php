<?php

// Iniciar sesión ANTES de cualquier output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cargar autoloader de Composer para MongoDB
require_once __DIR__ . '/../vendor/autoload.php';

include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/recaptcha_helper.php';



if ($_REQUEST) {

    $datos = $_REQUEST;
    switch ($_REQUEST["metodo"]) {

        /****************************************************************************
         *  USUARIO
         ****************************************************************************/

        case "login_user":
            login_user($datos);
            break;

        case "check_session":
            check_session();
            break;

        case "more_codes":
            more_codes($datos);
            break;

        case "last_codigo":
            last_codigo($datos);
            break;

        case "show_estatistics":
            show_estatistics($datos);
            break;

        case "login_user_facebook":
            login_user_facebook($datos);
            break;

        case "google_login":
            google_login($datos);
            break;

        case "registrar_usuario":
            registrar_usuario($datos, $datos["origin"]);
            break;

        case "verificar_codigo":
            header('Content-Type: application/json');
            echo json_encode(verificar_codigo_cuenta($datos["correo"] ?? '', $datos["codigo"] ?? ''));
            break;

        case "reenviar_codigo":
            header('Content-Type: application/json');
            $col_u = getCollectionUsuarios();
            $u = $col_u ? $col_u->findOne(['mail' => $datos["correo"] ?? '']) : null;
            if (!$u) {
                echo json_encode(['success' => false, 'error' => 'usuario', 'mensaje' => 'Usuario no encontrado.']);
            } elseif ((int)($u['estado'] ?? 0) === 1) {
                echo json_encode(['success' => false, 'error' => 'ya_activo', 'mensaje' => 'Tu cuenta ya está verificada.']);
            } else {
                $r = generar_y_enviar_codigo_verificacion($u['_id'], $u['mail'], $u['username'] ?? 'Usuario', 'reenvio', true);
                echo json_encode($r);
            }
            break;

        case "solicitar_activacion":
            solicitar_activacion($datos);
            break;

        case "editar_perfil":
            editar_perfil($datos);
            break;

        case "eliminar_cuenta":
            eliminar_cuenta($datos);
            break;

        case "desbanear_usuario":
            desbanear_usuario($datos);
            break;

        case "baneo_temporal":
            baneo_temporal($datos);
            break;


        case "baneo_definitivo":
            baneo_definitivo($datos);
            break;


        case "añadir_favorito":
            header('Content-Type: application/json');
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                exit;
            }
            // Asegurar que funciones_favoritos.php esté incluido
            if (!function_exists('añadir_favorito')) {
                include_once __DIR__ . '/funciones_favoritos.php';
            }
            $tipo = $datos['tipo'] ?? 'codigo';
            $result = añadir_favorito($_SESSION['user_id'], $datos['codigo_id'], $tipo);
            echo json_encode($result);
            break;

        case "eliminar_favorito":
            header('Content-Type: application/json');
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                exit;
            }
            // Asegurar que funciones_favoritos.php esté incluido
            if (!function_exists('eliminar_favorito')) {
                include_once __DIR__ . '/funciones_favoritos.php';
            }
            $tipo = $datos['tipo'] ?? 'codigo';
            $result = eliminar_favorito($_SESSION['user_id'], $datos['codigo_id'], $tipo);
            echo json_encode($result);
            break;

        case "check_favorito":
            header('Content-Type: application/json');
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'is_favorito' => false]);
                exit;
            }
            if (!function_exists('es_favorito')) {
                include_once __DIR__ . '/funciones_favoritos.php';
            }
            $tipo = $datos['tipo'] ?? 'codigo';
            $result = es_favorito($_SESSION['user_id'], $datos['codigo_id'], $tipo);
            echo json_encode(['success' => true, 'is_favorito' => $result]);
            break;

        case "track_amazon":
            header('Content-Type: application/json');
            require_once __DIR__ . '/funciones_amazon_services.php';
            
            // Get data from request (supports both POST form-data and JSON input)
            // But since this file uses $_REQUEST, we expect POST form-data usually.
            // Our JS will send JSON, so we need to decode it if $_REQUEST is empty of our keys.
            
            $slug = $datos['slug'] ?? '';
            $origin = $datos['origin'] ?? 'unknown';
            
            // If empty in $_REQUEST, try JSON input
            if (empty($slug)) {
                $json = json_decode(file_get_contents('php://input'), true);
                if ($json) {
                    $slug = $json['slug'] ?? '';
                    $origin = $json['origin'] ?? 'unknown';
                }
            }
            
            $referrer = $_SERVER['HTTP_REFERER'] ?? '';
            
            if ($slug) {
                logAmazonServiceClick($slug, $referrer, $origin);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Missing slug']);
            }
            break;


        /***********************************
         *  CAPTACIÓN DE CLIENTES
         **********************************/

        case "descontar_lead_sin_validar":
            descontar_lead_sin_validar($datos);
            break;

        case "guardar_token_compra_lead_sin_validar":
            guardar_token_compra_lead_sin_validar($datos);
            break;

        case "descontar_lead_validado":
            descontar_lead_validado($datos);
            break;

        case "guardar_token_compra_lead_validado":
            guardar_token_compra_lead_validado($datos);
            break;

        /****************************************************************************
         *  PANEL DE CONTROL
         ****************************************************************************/


        case "desactivar_codigo":
            desactivar_codigo($datos);
            break;

        case "desactivar_codigo_usuario":

            desactivar_codigo_usuario($datos);
            break;

        case "restaurar_codigo_usuario":

            restaurar_codigo_usuario($datos);
            break;

        case "borrar_codigo":
            borrar_codigo($datos);
            break;

        case "actualizar_codigo":
            actualizar_codigo($datos);
            break;

        case "actualizar_marca":
            actualizar_marca($datos);
            break;


        case "update_marca":

            update_marca($datos);
            break;


        case "fusiona_marcas":

            fusiona_marcas($datos);
            break;

        case "sube_imagen_marca":

            sube_imagen_marca($datos);
            break;

        case "borrar_marca":
            borrar_marca($datos);
            break;

        /**********************************
         *  PUBLICAR CÓDIGO
         *********************************/

        case "publicar_nuevo_codigo":
            publicar_nuevo_codigo($datos);

            break;

        case "buscar_marcas":
            buscar_marcas($datos);
            break;

        /**********************************
         *  CONTACTO
         *********************************/

        case "formulario_contacto":
            // Validar reCAPTCHA v3 con verificación de score
            $recaptchaValidado = false;
            $recaptchaScore = 0;
            $motivoRechazo = 'Sin token';
            
            if (isset($datos['recaptcha_response']) && !empty($datos['recaptcha_response'])) {
                $resultado = validarRecaptcha($datos['recaptcha_response'], $_SERVER['REMOTE_ADDR'] ?? null);
                
                if ($resultado['success']) {
                    $recaptchaScore = $resultado['score'] ?? 0;
                    // Score mínimo de 0.5 (recomendación oficial de Google para v3)
                    if ($recaptchaScore >= 0.5) {
                        $recaptchaValidado = true;
                        log_info("reCAPTCHA OK: score $recaptchaScore (IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'desconocida') . ")");
                    } else {
                        // Score bajo = posible spam
                        $motivoRechazo = "Score bajo: $recaptchaScore";
                        log_warning("reCAPTCHA score bajo: " . $recaptchaScore . " (IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'desconocida') . "). Usuario: " . ($datos['nombre'] ?? 'anon'));
                    }
                } else {
                    $motivoRechazo = "Error validación: " . ($resultado['error'] ?? 'desconocido');
                    log_warning("reCAPTCHA validación fallida técnica: " . $motivoRechazo . " (IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'desconocida') . ")");
                }
            }
            
            // Fallback: si el score es bajo pero no es un error técnico y el usuario no parece un bot de fuerza bruta (rate limit)
            // permitimos el envío pero con un log especial. 
            // Si es un error técnico (invalid keys, timeout), también intentamos permitirlo si la IP es limpia.
            if (!$recaptchaValidado) {
                if (permiteContactoSinRecaptcha()) {
                    log_warning("reCAPTCHA FALLBACK ACTIVADO (Motivo: $motivoRechazo). Permitiendo envío para IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unk'));
                    $recaptchaValidado = true; 
                    $datos['_sistema_nota'] = "Verificado mediante fallback de seguridad. Motivo: $motivoRechazo. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'desconocida');
                } else {
                    echo "recaptcha_error";
                    break;
                }
            }

            // Antidoble envío: si mismo payload se envía en < 8s, no volver a disparar
            if (session_status() === PHP_SESSION_NONE) { session_start(); }
            $payloadHash = hash('sha256', trim(($datos['nombre'] ?? '') . '|' . ($datos['correo'] ?? '') . '|' . ($datos['telefono'] ?? '') . '|' . ($datos['mensaje'] ?? '')));
            $nowTs = time();
            if (isset($_SESSION['contact_last_hash']) && isset($_SESSION['contact_last_time'])) {
                if ($_SESSION['contact_last_hash'] === $payloadHash && ($nowTs - (int)$_SESSION['contact_last_time']) < 8) {
                    echo "success"; // idempotente: consideramos enviado
                    break;
                }
            }
            $_SESSION['contact_last_hash'] = $payloadHash;
            $_SESSION['contact_last_time'] = $nowTs;

            $result = formulario_contacto($datos);
            echo $result;
            break;

        /**********************************
         *  DESTACADOS
         *********************************/

        case "toggle_auto_renovar":
            header('Content-Type: application/json');
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                exit;
            }
            $codigo_id = $datos['codigo_id'] ?? '';
            if (empty($codigo_id)) {
                echo json_encode(['success' => false, 'message' => 'Código no especificado']);
                exit;
            }
            try {
                $collection_codigos = getCollectionCodigos();
                $codigo = $collection_codigos->findOne(['_id' => new MongoDB\BSON\ObjectId($codigo_id)]);
                if (!$codigo || (string)$codigo['id_usuario'] !== $_SESSION['user_id']) {
                    echo json_encode(['success' => false, 'message' => 'No autorizado']);
                    exit;
                }
                // Comparación laxa a propósito: el campo se guarda unas veces
                // como booleano true y otras como entero 1 (p. ej. desde
                // admin_dashboard.php). Con `=== true` un valor 1 se leía como
                // "desactivado", así que el toggle lo volvía a poner en true y
                // el usuario no podía desactivar la renovación nunca.
                $nuevo_valor = empty($codigo['auto_renovar_destacado']);
                $collection_codigos->updateOne(
                    ['_id' => new MongoDB\BSON\ObjectId($codigo_id)],
                    ['$set' => ['auto_renovar_destacado' => $nuevo_valor]]
                );
                echo json_encode(['success' => true, 'auto_renovar' => $nuevo_valor]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

    }

}

function update_marca($datos)
{

    //     echo "adsadsdasdas";
//     print_r($datos);die;

    try {

        $collection_marcas = getCollectionMarcas();

        $updateResult = $collection_marcas->updateOne(
            ['_id' => new \MongoDB\BSON\ObjectId($datos["id_marca"])],
            [
                '$set' =>
                    [
                        'categoria' => $datos['marca_sel_txt'],
                        'categoria_clave' => $datos['marca_sel'],
                        'aviso' => 'revisada'

                    ]
            ]
        );
    } catch (MongoCursorException $e) {
        echo "Error al modificar datos\n";
    }

}

function eliminar_cuenta($datos)
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'No autorizado. Inicia sesión de nuevo.']);
        return;
    }
    $password = $datos['password'] ?? '';
    if ($password === '') {
        echo json_encode(['success' => false, 'message' => 'Introduce tu contraseña para confirmar.']);
        return;
    }
    try {
        $col_usuarios = getCollectionUsuarios();
        $col_codigos  = getCollectionCodigos();
        $userId = new \MongoDB\BSON\ObjectId((string)$_SESSION['user_id']);
        $usuario = $col_usuarios->findOne(['_id' => $userId]);
        if (!$usuario) {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
            return;
        }
        // Verificar contraseña (comparación en claro, igual que login)
        if (($usuario['pass'] ?? '') !== $password) {
            echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta.']);
            return;
        }
        // Anonimizar: borrar PII + estado=-3 (baja por usuario). Misma lógica que el API.
        $col_usuarios->updateOne(
            ['_id' => $userId],
            ['$set' => [
                'estado'   => -3,
                'mail'     => '',
                'username' => 'deleted_user_' . substr((string)$userId, -6),
                'pass'     => '',
                'img'      => '',
                'desc'     => '',
                'telefono' => '',
                'deleted_at' => new \MongoDB\BSON\UTCDateTime(),
                'baja_solicitada_via' => 'web_self_service',
            ]]
        );
        // Desvincular códigos (el contenido público permanece sin PII)
        $col_codigos->updateMany(
            ['id_usuario' => $userId],
            ['$set' => ['id_usuario' => null, 'owner_deleted_at' => new \MongoDB\BSON\UTCDateTime()]]
        );
        // Cerrar sesión
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Tu cuenta ha sido eliminada. Gracias por haber usado CódigoAmigo.']);
    } catch (\Exception $e) {
        log_error('eliminar_cuenta Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'No se pudo eliminar la cuenta. Inténtalo más tarde.']);
    }
}

function editar_perfil($datos)
{
    try {
        log_info("editar_perfil START with datos: " . json_encode($datos));
        $collection_usuarios = getCollectionUsuarios();
        
        // Preparar datos para actualizar
        $updateData = [
            'username' => $datos['nombre'],
            'notis' => isset($datos['notis']) ? (int)$datos['notis'] : 0,
            'email_competencia' => isset($datos['email_competencia']) ? (int)$datos['email_competencia'] : 1,
            'email_aperturas' => isset($datos['email_aperturas']) ? (int)$datos['email_aperturas'] : 1,
            'email_destacados' => isset($datos['email_destacados']) ? (int)$datos['email_destacados'] : 1,
            'email_reengagement' => isset($datos['email_reengagement']) ? (int)$datos['email_reengagement'] : 1,
        ];
        
        // Solo actualizar contraseña si se proporciona y no está vacía
        if (!empty($datos['password'])) {
            $updateData['pass'] = $datos['password'];
            $updateData['confirm_password'] = $datos['password'];
        }
        
        // Actualizar teléfono y whatsapp si se proporcionan
        if (isset($datos['telefono'])) {
            $updateData['telefono'] = $datos['telefono'];
        }
        if (isset($datos['whatsapp'])) {
            $updateData['whatsapp'] = $datos['whatsapp'];
        }
        
        log_info("editar_perfil updateData: " . json_encode($updateData));
        $updateResult = $collection_usuarios->updateOne(
            ['mail' => $datos["correo"]],
            ['$set' => $updateData]
        );
        
        log_info("editar_perfil Result - Matched: " . $updateResult->getMatchedCount() . ", Modified: " . $updateResult->getModifiedCount());
        
        if ($updateResult->getModifiedCount() > 0 || $updateResult->getMatchedCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Usuario modificado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se realizaron cambios']);
        }
    } catch (Exception $e) {
        log_error("editar_perfil Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al modificar datos: ' . $e->getMessage()]);
    }
}


// Fallback anti-spam: permite unos pocos envíos aunque reCAPTCHA falle
function permiteContactoSinRecaptcha() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    $windowSeconds = 600; // 10 minutos
    $maxAttemptsPerWindow = 10; // permitir hasta 10 intentos para pruebas/uso legítimo

    if (!isset($_SESSION['contact_rate_limit'])) {
        $_SESSION['contact_rate_limit'] = [];
    }

    if (!isset($_SESSION['contact_rate_limit'][$ip])) {
        $_SESSION['contact_rate_limit'][$ip] = [];
    }

    $now = time();
    $_SESSION['contact_rate_limit'][$ip] = array_values(array_filter(
        $_SESSION['contact_rate_limit'][$ip],
        function ($ts) use ($now, $windowSeconds) { return ($now - $ts) <= $windowSeconds; }
    ));

    if (count($_SESSION['contact_rate_limit'][$ip]) >= $maxAttemptsPerWindow) {
        return false;
    }

    $_SESSION['contact_rate_limit'][$ip][] = $now;
    return true;
}

// Función para solicitar nuevo email de activación
function solicitar_activacion($datos) {
    $correo = $datos['correo'];

    // Buscar al usuario por email
    $collection_usuarios = getCollectionUsuarios();
    $usuario = $collection_usuarios->findOne(['mail' => $correo]);

    if (!$usuario) {
        echo "usuario_no_encontrado";
        return;
    }

    if ($usuario['estado'] == 1) {
        echo "usuario_ya_activo";
        return;
    }

    // Enviar CÓDIGO de verificación (coherente con el nuevo flujo, anti-spam)
    if (function_exists('generar_y_enviar_codigo_verificacion')) {
        $r = generar_y_enviar_codigo_verificacion(
            $usuario['_id'],
            $usuario['mail'],
            $usuario['username'] ?? 'Usuario',
            'solicitud_activacion'
        );
        echo ($r['success'] ?? false) ? "email_enviado" : "error_envio";
    } else {
        // Fallback al método antiguo (enlace)
        $email_enviado = enviarMailActivacion([
            'mail' => $usuario['mail'],
            'username' => $usuario['username']
        ]);
        echo $email_enviado ? "email_enviado" : "error_envio";
    }
}

// Función para verificar el estado de la sesión
function clearCurrentSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}

function check_session() {
    // Asegurar que no haya output antes del header
    if (ob_get_level() > 0) {
        ob_clean();
    }
    
    header('Content-Type: application/json');
    
    // Verificar que la sesión esté iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Incluir funciones necesarias si no están incluidas
    if (!function_exists('getObjectUserWithSession')) {
        require_once __DIR__ . '/funciones.php';
    }
    
    // Verificar si hay una sesión de usuario activa
    if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
        clearCurrentSession();
        $response = [
            'success' => false,
            'logged_in' => false,
            'message' => 'Sesión no válida'
        ];
        echo json_encode($response);
        exit;
    }
    
    // Intentar obtener datos del usuario desde la base de datos para validar la sesión
    try {
        $usuario_completo = getObjectUserWithSession('_id', new MongoDB\BSON\ObjectId($_SESSION["user_id"]));
        
        if ($usuario_completo && isset($usuario_completo["username"])) {
            // Sesión válida - devolver datos del usuario
            $userData = [
                'id' => $_SESSION["user_id"],
                'username' => $usuario_completo['username'] ?? $_SESSION["username"] ?? 'Usuario',
                'mail' => $usuario_completo['mail'] ?? $_SESSION["mail"] ?? '',
                'img' => $usuario_completo['img'] ?? $_SESSION["img"] ?? 'https://www.codigoamigo.com/img/utilidades/usuario_sin_foto.jpg',
                'avatar' => $usuario_completo['img'] ?? $_SESSION["img"] ?? 'https://www.codigoamigo.com/img/utilidades/usuario_sin_foto.jpg'
            ];
            
            $response = [
                'success' => true,
                'logged_in' => true,
                'user' => $userData
            ];
            echo json_encode($response);
            exit;
        } else {
            // Usuario no encontrado en la base de datos - sesión inválida
            clearCurrentSession();
            $response = [
                'success' => false,
                'logged_in' => false,
                'message' => 'Usuario no encontrado'
            ];
            echo json_encode($response);
            exit;
        }
    } catch (Exception $e) {
        // Error al obtener el usuario - sesión inválida
        log_error("Error en check_session: " . $e->getMessage());
        clearCurrentSession();
        $response = [
            'success' => false,
            'logged_in' => false,
            'message' => 'Error al validar sesión: ' . $e->getMessage()
        ];
        echo json_encode($response);
        exit;
    }
}

