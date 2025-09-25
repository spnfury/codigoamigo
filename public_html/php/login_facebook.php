<?php 

    if(isset($name) && isset($mail)) {
        
        $usuario = array('username' => $name, 'mail' => $mail, 'img' => $imagen);
        
        $trobat = checkUserExists($mail);
        if (!$trobat) {
            AddNewUser($usuario, "facebook");
        } else {
            cambiarFotoUsuario($mail, $imagen);
        }

        /* Recuperamos el usuario mediante su correo para iniciar sesión con esos datos. */
        $usuario = getObjectUser('mail', $mail);
        session_start();
        $_SESSION["user_id"] = $usuario["_id"];
        $_SESSION["mail"] = $usuario["mail"];
        $_SESSION["username"] = $usuario["username"];
        
        echo $_SERVER["HTTP_REFERER"];
        
        //echo "<script>alert('".$_SERVER["HTTP_REFERER"]."');</script>";
        //echo "<script>window.location='". $_SERVER["HTTP_REFERER"] ."';</script>";
        
//         if($_SERVER["HTTP_REFERER"] == "http://www.codigoamigo.com/registro") {
//             echo "<script>window.location='". $GLOBALS["website"] ."';</script>";
//         } else {
//             echo "<script>window.location='". $_SERVER["HTTP_REFERER"] ."';</script>";
//         }
        //echo "<script>window.location='". $_SERVER["HTTP_REFERER"] ."';</script>";
    }

?>