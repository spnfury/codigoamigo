<?php

    // Usar la variable $mail_ que viene del filtro de app.php
    $usuario = getObjectUser('mail', $mail_);

    if(empty($usuario["username"])) {
        return $response->withRedirect('/cambiar_password?msg_error=ok');
    } else {

        if($usuario["estado"] == 0){ //SI ES 0 REENVIO ACTIVACION
            $usuario["correo"] = $usuario["mail"];
            $usuario["nombre"] = $usuario["username"];
            enviar_mail_activacion($usuario);
        }else{
            enviarMailRecuerdoPass($usuario);
        }
        return $response->withRedirect('/cambiar_password?msg=ok');
    }

?>