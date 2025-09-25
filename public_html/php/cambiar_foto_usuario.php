<?php 

    //echo '<pre>';
    //print_r($_POST);
    //print_r($_FILES);
    

    $msg = "";
    if (!empty($_FILES)) {
        $msg = uploadFotoUsuario($_FILES);
        if ($msg == "Foto de perfil cambiada correctamente") {
            echo "<script>window.location='/usuario';</script>";
        } else {
            session_start();
            $_SESSION["msg"] = $msg;
            echo "<script>window.location='/usuario';</script>";
        }   
    } 

?>