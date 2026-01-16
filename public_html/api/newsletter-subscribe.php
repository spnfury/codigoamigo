<?php
/**
 * API endpoint para suscripciones al newsletter
 */

header('Content-Type: application/json');

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../inc/conexion.php';

/**
 * Crea o actualiza un usuario con opt-in de newsletter.
 * Devuelve el id del usuario (string) o false si falla.
 */
function upsertNewsletterUser($email) {
    if (!function_exists('getCollectionUsuarios')) {
        return false;
    }

    $collection_usuarios = getCollectionUsuarios();
    if (!$collection_usuarios) {
        return false;
    }

    $now = new MongoDB\BSON\UTCDateTime();
    $username = explode('@', $email)[0];

    try {
        // Intentar encontrar usuario existente
        $usuario = $collection_usuarios->findOne(['mail' => $email]);

        if ($usuario) {
            $collection_usuarios->updateOne(
                ['_id' => $usuario['_id']],
                [
                    '$set' => [
                        'newsletter' => true,
                        'newsletter_optin_at' => $now
                    ]
                ]
            );
            return (string)$usuario['_id'];
        }

        // Crear usuario nuevo solo para newsletter
        $randomPass = bin2hex(random_bytes(8));
        $nuevo_usuario = [
            'estado' => 1,
            'type' => 'newsletter',
            'username' => $username,
            'mail' => $email,
            'pass' => $randomPass,
            'confirm_password' => $randomPass,
            'fecha_registro' => date("d-m-y H:i", strtotime("now")),
            'newsletter' => true,
            'newsletter_optin_at' => $now
        ];

        $result = $collection_usuarios->insertOne($nuevo_usuario);
        return $result->getInsertedId() ? (string)$result->getInsertedId() : false;
    } catch (Exception $e) {
        return false;
    }
}

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener email del formulario
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

// Validar email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email no válido']);
    exit;
}

// Crear o actualizar usuario con flag de newsletter
$user_result = upsertNewsletterUser($email);
if ($user_result === false) {
    echo json_encode(['success' => false, 'message' => 'No se pudo registrar el usuario para el newsletter.']);
    exit;
}

try {
    // Obtener colección de suscripciones (crear si no existe)
    $db = createConnection();
    $collection = $db->selectCollection('newsletter_suscripciones');
    
    // Verificar si el email ya está suscrito
    $existente = $collection->findOne(['email' => $email]);
    
    if ($existente) {
        // Si ya existe pero está desactivado, reactivarlo
        if (isset($existente['activo']) && !$existente['activo']) {
            $collection->updateOne(
                ['email' => $email],
                [
                    '$set' => [
                        'activo' => true,
                        'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
                    ]
                ]
            );
            echo json_encode([
                'success' => true, 
                'message' => '¡Bienvenido de nuevo! Tu suscripción ha sido reactivada.'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Este email ya está suscrito al newsletter.'
            ]);
        }
        exit;
    }
    
    // Crear nueva suscripción
    $suscripcion = [
        'email' => $email,
        'activo' => true,
        'fecha_suscripcion' => new MongoDB\BSON\UTCDateTime(),
        'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime(),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];
    
    $result = $collection->insertOne($suscripcion);
    
    if ($result->getInsertedCount() > 0) {
        echo json_encode([
            'success' => true, 
            'message' => '¡Te has suscrito correctamente! Recibirás las mejores ofertas en tu email.'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Error al procesar la suscripción. Inténtalo de nuevo.'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en newsletter-subscribe: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor. Inténtalo más tarde.'
    ]);
}

