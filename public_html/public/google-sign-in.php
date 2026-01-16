<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
ini_set("display_errors", "on");
error_reporting(E_ALL);

$projectRoot = dirname(__DIR__);
ob_start();

// use sessions
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// include google API client
require_once $projectRoot . "/vendor/autoload.php";

// Incluir funciones de MongoDB
require_once $projectRoot . "/inc/includes.php";

// set google client ID
$google_oauth_client_id = "298004995594-1qm1qpjok7lq4lo1kdd45406j9rarcqd.apps.googleusercontent.com";

// include null cache implementation
require_once __DIR__ . "/null_cache.php";

// create google client object with client ID
$client = new Google\Client();
$client->setClientId($google_oauth_client_id);

// Usar null cache para evitar errores de PSR Cache
$cache = new NullCache();
$client->setCache($cache);

try {
    // verify the token sent from AJAX
    $id_token = $_POST["id_token"] ?? '';

    if (empty($id_token)) {
        throw new RuntimeException('No se recibió el token de Google', 400);
    }

    try {
        $payload = $client->verifyIdToken($id_token);
    } catch (Exception $e) {
        throw new RuntimeException('Token de Google inválido: ' . $e->getMessage(), 400);
    }

    if (!$payload || !isset($payload['aud']) || $payload['aud'] !== $google_oauth_client_id) {
        throw new RuntimeException('Token de Google inválido o expirado', 400);
    }

    // get user information from Google
    $user_google_id = $payload['sub'];
    $name = $payload["name"];
    $email = $payload["email"];
    $picture = $payload["picture"];

    $collection_usuarios = getCollectionUsuarios();
    if (!$collection_usuarios) {
        throw new RuntimeException('No se pudo acceder a la base de datos', 500);
    }

    $usuario = $collection_usuarios->findOne(['mail' => $email]);

    $response = [
        'success' => true,
        'user' => [
            'id' => $user_google_id,
            'name' => $name,
            'email' => $email,
            'picture' => $picture
        ]
    ];

    if (empty($usuario) || empty($usuario["username"])) {
        $response['registered'] = false;
        $response['message'] = 'Usuario no registrado';
    } else {
        $response['registered'] = true;
        $response['message'] = 'Usuario registrado';

        if (isset($usuario["estado"]) && $usuario["estado"] == 0) {
            $response['verified'] = false;
            $response['message'] = 'Usuario no verificado';
        } else {
            $response['verified'] = true;
            $response['message'] = 'Usuario verificado';

            // Iniciar sesión
            $_SESSION["user_id"] = (string)$usuario["_id"];
            $_SESSION["mail"] = $usuario["mail"];
            $_SESSION["username"] = $usuario["username"];
            $_SESSION["img"] = $picture; // Actualizar imagen de Google

            $response['user']['username'] = $usuario["username"];
            $response['user']['id_session'] = $_SESSION["user_id"];
        }
    }

    $buffer = ob_get_clean();
    if ($buffer !== '') {
        error_log('[google-sign-in] Se detectó salida previa antes de la respuesta JSON: ' . substr(trim($buffer), 0, 400));
    }

    echo json_encode($response);
} catch (Throwable $e) {
    $statusCode = ($e instanceof RuntimeException && $e->getCode() >= 400) ? $e->getCode() : 500;
    if ($statusCode < 400) {
        $statusCode = 500;
    }
    http_response_code($statusCode);

    error_log('[google-sign-in] ' . $e->getMessage());

    $buffer = ob_get_clean();
    if ($buffer !== '') {
        error_log('[google-sign-in] Se detectó salida previa (error): ' . substr(trim($buffer), 0, 400));
    }

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}