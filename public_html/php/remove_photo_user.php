<?php 

    // Iniciar sesión si no está iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    include_once __DIR__ . '/../inc/includes.php';
    include_once __DIR__ . '/../myphp/funciones_usuario.php';

    // Solo el propio usuario puede eliminar su foto (antes se usaba
    // $_POST["mail"] sin autenticación: cualquiera podía borrar la foto
    // de otro usuario)
    if (!isset($_SESSION["mail"]) || empty($_SESSION["mail"])) {
        http_response_code(401);
        exit;
    }

    try {
        $collection_usuarios = getCollectionUsuarios();
        $updateResult = $collection_usuarios->updateOne(
            ['mail' => $_SESSION["mail"]],
            ['$set' => ['img' => ""]]
            );
    } catch(Exception $e) {
        echo "Error al modificar datos\n";
    }
    
?>