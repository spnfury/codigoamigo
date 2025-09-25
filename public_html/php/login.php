<?php if(!empty($_POST)) {

    // Incluir funciones necesarias
    include_once __DIR__ . '/../myphp/funciones_usuario.php';
    
    $mail_ = $_POST['mail'];
    $pass_ = $_POST['pass'];

    $collection_usuarios = getCollectionUsuarios();
    $usuario = $collection_usuarios->findOne(
        [
            'mail' => $mail_,
            'pass' => $pass_,
        ]);
    if(empty($usuario["username"])) {
        echo "<script>alert('Usuario y contraseña no coinciden.'); window.location='". $_SERVER["HTTP_REFERER"] ."';</script>";
    } else {
        if(strcmp($usuario["estado"], 0) == 0) {
            echo "<script>alert('Recuerde que debe verificar su correo antes de poder iniciar sesión.'); window.location='". $GLOBALS["website"] ."';</script>";
        } else {
            $_SESSION["user_id"] = $usuario["_id"];
            $_SESSION["mail"] = $usuario["mail"];
            $_SESSION["username"] = $usuario["username"];
            
            $cadena = $_SERVER["HTTP_REFERER"];
            $busca = "https://www.codigoamigo.com/bienvenido_de_nuevo?";
            $res = strpos($cadena, $busca);

            if($_SERVER["HTTP_REFERER"] == "http://www.codigoamigo.com/registro") {
                echo "<script>window.location='". $GLOBALS["website"] ."';</script>";
            }
            if ($res !== false) {
                echo "<script>window.location='". $GLOBALS["website"] ."';</script>";
            } else {
                echo "<script>window.location='". $_SERVER["HTTP_REFERER"] ."';</script>";
            }
        }
    }
} ?>