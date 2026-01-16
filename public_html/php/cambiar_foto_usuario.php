<?php 

    // Iniciar sesión si no está iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Incluir funciones necesarias
    include_once __DIR__ . '/../inc/includes.php';
    include_once __DIR__ . '/../inc/funciones.php';
    include_once __DIR__ . '/../inc/conexion.php';
    
    // Verificar que el usuario esté logueado
    if (!isset($_SESSION["mail"]) || empty($_SESSION["mail"])) {
        $_SESSION["msg"] = "Debes iniciar sesión para cambiar tu foto de perfil";
        echo "<script>window.location='/usuario';</script>";
        exit;
    }

    $msg = "";
    if (!empty($_FILES) && isset($_FILES["uploadedfile"])) {
        $msg = uploadFotoUsuario($_FILES);
        if ($msg == "Foto de perfil cambiada correctamente") {
            unset($_SESSION["msg"]); // Limpiar mensaje de error si existe
            echo "<script>window.location='/usuario';</script>";
        } else {
            $_SESSION["msg"] = $msg;
            echo "<script>window.location='/usuario';</script>";
        }   
    } else {
        $_SESSION["msg"] = "No se recibió ningún archivo";
        echo "<script>window.location='/usuario';</script>";
    }

?>