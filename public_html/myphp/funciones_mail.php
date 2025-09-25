<?php

use SendGrid\Mail\To;
use SendGrid\Mail\Cc;
use SendGrid\Mail\Bcc;
use SendGrid\Mail\From;
use SendGrid\Mail\Content;
use SendGrid\Mail\Mail;
use SendGrid\Mail\Personalization;
use SendGrid\Mail\Subject;
use SendGrid\Mail\Header;
use SendGrid\Mail\CustomArg;
use SendGrid\Mail\SendAt;
use SendGrid\Mail\Attachment;
use SendGrid\Mail\Asm;
use SendGrid\Mail\MailSettings;
use SendGrid\Mail\BccSettings;
use SendGrid\Mail\SandBoxMode;
use SendGrid\Mail\BypassListManagement;
use SendGrid\Mail\Footer;
use SendGrid\Mail\SpamCheck;
use SendGrid\Mail\TrackingSettings;
use SendGrid\Mail\ClickTracking;
use SendGrid\Mail\OpenTracking;
use SendGrid\Mail\SubscriptionTracking;
use SendGrid\Mail\Ganalytics;
use SendGrid\Mail\ReplyTo;



    /**************************************
     * CONTACTO
     * ************************************/

    function formulario_contacto($datos) {

        global $url_logo_web;

        $email = new \SendGrid\Mail\Mail();

        $to = "thevega82@gmail.com";
        $asunto = $datos["origin"];

        $body = "<img src='".$url_logo_web."' alt='logo codigo amigo' /><br><br>";
        $body .= "<h1>Nuevo contacto desde el formulario ".$datos["origin"].": </h1>";
        $body .= "<ul>";
            $body .= "<li><b>Nombre: </b>".$datos["nombre"]."</li><br>";
            $body .= "<li><b>Correo: </b>".$datos["correo"]."</li><br>";
            $body .= "<li><b>Teléfono: </b>".$datos["telefono"]."</li><br>";
            $body .= "<li><b>Mensaje: </b>".$datos["mensaje"]."</li><br>";
        $body .= "</ul>";



        $email->setFrom("info@codigoamigo.com", "Código Amigo");
        $email->setReplyTo($datos["correo"], $datos["nombre"]);
        $email->setSubject($asunto);
        $email->addTo($to, "Sergi");
        $email->addContent("text/plain", "and easy to do anywhere, even with PHP");
        $email->addContent(
            "text/html", $body
            );


        $sendgrid = new \SendGrid('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');

        try {

            $response = $sendgrid->send($email);

            if($response->statusCode()!=200){
                mandaBot("eee");
            }

        } catch (Exception $e) {
            echo 'Caught exception: '. $e->getMessage() ."\n";
            mandaBot($e->getMessage());
        }


        print_r($response);
        echo "***".$response."***";
        die;



    }

    /*************************************
     * REGISTRO DE USUARIO
     * ***********************************/

    function enviar_mail_activacion($datos) {

        global $url_logo_web;

        $email = new \SendGrid\Mail\Mail();

        $to = $datos["correo"];

        $asunto = "Bienvenido a Código Amigo";

        $href= "https://www.codigoamigo.com/bienvenido_de_nuevo?user=".$datos["correo"]."&codigo=".encriptar($datos["correo"]);

        $body = "<img src='".$url_logo_web."' alt='logo codigo amigo' /><br><br>";
        $body .= "<h1>Bienvenido a Código Amigo</h1>";
        $body .= "<p>";
        $body .= "Hola, ".$datos["nombre"].". Gracias por darte de alta en nuestra plataforma. Código Amigo pone a tu alcance cientos de códigos descuento
            para que ahorres tiempo en tus servicios preferidos.";
        $body .= "</p>";
        $body .= "<p>Para activar tu usuario, por favor, haz click en el enlace que verás a continuación. En caso de no funcionar, por favor, copia
            y pegalo directamente en tu navegador.</p><br>";
        $body .= "<a href='".$href."' title='activar usuario'>".$href."</a>";
        $body .= "<br><p>Recuerda que si tienes cualquier duda, pregunta o sugerencia, puedes hacernosla llegar a <b>info@codigoamigo.com</b></p>";


        $email->setFrom("info@codigoamigo.com", "Código Amigo");
        //$email->setReplyTo($datos["correo"], $datos["nombre"]);
        $email->setSubject($asunto);
        $email->addTo($to, "Sergi");
        $email->addContent("text/plain", "and easy to do anywhere, even with PHP");
        $email->addContent(
            "text/html", $body
            );

        $sendgrid = new \SendGrid('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');

        try {
            $response = $sendgrid->send($email);

            //print_r($response);
        } catch (Exception $e) {
            echo 'Caught exception: '. $e->getMessage() ."\n";
        }






        //mail($to, $asunto, $body, $headers, "-finfo@codigoamigo.com");

    }

    function enviar_mail_apertura_codigo($codigo_to_show, $correo, $nombre, $url_codigo, $nombre_marca, $img_marca) {

        global $url_logo_web;
        session_start();
        $nombre_quien_ha_abierto = $_SESSION["username"];
        
        $to = $correo;
        //$to = "luiss.garces@gmail.com";
        
        $headers = "From: Código Amigo <info@codigoamigo.com>\r\n";
        $headers .= "X-Mailer: PHP5\n";
        $headers .= "MIME-Version: 1.0"."\n";
        $headers .= "Content-type: text/html; charset=iso-8859-1"."\r\n";

        $asunto = "Han abierto tu código amigo de ".$nombre_marca;

        $body = "<img src='".$url_logo_web."' alt='logo codigo amigo' /><br><br>";
        $body .= "<h1>Hola, ".$nombre."</h1>";
        $body .= "<p>";
        $body .= "Queremos informarte de que un miembro de la comunidad (<b>".$nombre_quien_ha_abierto."</b>) acaba de abrir tu código amigo de la
            marca <b>".$nombre_marca."</b>. Estate atento en las próximas horas porque es posible que recibas un nuevo beneficio
            (".$codigo_to_show["num_beneficio"]." ".$codigo_to_show["tipo_descuento"].") gracias a este código.";
        $body .= "</p>";
        $body .= "<a target='_blank' href='".$url_codigo."' title='ver código'>'".$url_codigo."'</a>";
        $body .= "<br><p>Recuerda que si tienes cualquier duda, pregunta o sugerencia, puedes hacernosla llegar a <b>info@codigoamigo.com</b></p>";
        $body .= "<hr><p>Si no deseas recibir más correos de este tipo, puedes editar los avisos en tu
            <a href='https://www.codigoamigo.com/usuario' title='panel de control'>panel de control.</a></p>";
        $body = ($body);

        //mail($to, $asunto, $body, $headers, "-finfo@codigoamigo.com");

        
        $email = new \SendGrid\Mail\Mail();
        $email->setFrom("info@codigoamigo.com", "Código Amigo");
        //$email->setReplyTo($datos["correo"], $datos["nombre"]);
        $email->setSubject($asunto);
        $email->addTo($to, $nombre_quien_ha_abierto);
        $email->addContent("text/plain", "and easy to do anywhere, even with PHP");
        $email->addContent(
            "text/html", $body
            );
        
        $sendgrid = new \SendGrid('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');

        try {
            $response = $sendgrid->send($email);

            //print_r($response);
        } catch (Exception $e) {
            echo 'Caught exception: '. $e->getMessage() ."\n";
        }
        
        //echo "siiiiiiiiii".$to;die;

    }


    function enviar_buzz_codigo($data) {

        global $url_logo_web;
        session_start();

        

        /* INSERTO ZUMBIDO EN LA TABLA DE REGISTROS */
        try {
            $collection_zumbidos = getCollectionZumbidos();

            $data = [
                "id_codigo" => new \MongoDB\BSON\ObjectId($codigo["_id"]),
                "id_user_a_enviar_zumbido" => $data["id_user"],
                "user_id" => $_SESSION["user_id"],
                "fecha_visita" => date('d-m-Y  H:i:s'),
            ];

            $collection_zumbidos->insertOne($data);

        } catch(MongoDB\Driver\Exception\WriteException $e) {
            $writeResult = $e->getWriteResult();
            echo "Errores en MongoDB\n";
        }
        /* ZUMBIDOS */


        /* ACTUALIZO SALDO ZUMBIDOS DEL USUARIo*/
        try {

            $collection_usuarios = getCollectionUsuarios();

            $updateResult = $collection_usuarios->updateOne(
                ['_id' => new \MongoDB\BSON\ObjectId($_SESSION["user_id"])],
                ['$set' => ['zumbido_saldo' => $_SESSION["zumbido_saldo"]-1]]
                );


        } catch(MongoDB\Driver\Exception\WriteException $e) {
            $writeResult = $e->getWriteResult();
            echo "Errores en MongoDB\n";
        }

        $_SESSION["zumbido_saldo"]-=1;


        echo $_SESSION["zumbido_saldo"];

        $pre_codigo = $data['data_codigo_id'];

        $obj_id_codigo = new \MongoDB\BSON\ObjectId($pre_codigo);
        $codigo_to_show = getCodeByID($obj_id_codigo);

        //echo "*".$data["id_user"]."*";
        $obj_id_codigo_user = new \MongoDB\BSON\ObjectId($data["id_user_a_enviar_zumbido"]);
        $u = getObjectUser('_id', $obj_id_codigo_user);

        $correo = $u['mail'];
        $nombre = $u['username'];
        
        /* END ACTUALIZO SALDO ZUMBIDOS */

        $marca = get_object_marca("nombre_clave", $codigo_to_show['marca']);



        $url_codigo = $data['url_codigo'];
        $nombre_marca = $codigo_to_show['marca'];

        $img_marca = $marca['imagen'];

        $usuario_original = $data['usuario_original'];

        $nombre_quien_ha_abierto = $_SESSION["username"];

        $url = "https://www.codigoamigo.com/de-".$marca["nombre_clave"]."?codigo=".$pre_codigo;

        $to = $correo;
        
        
        

        //$asunto = $nombre.", ".$nombre_quien_ha_abierto." te está esperando";
        $asunto = $nombre.", ¡¿qué pasa con tu código de ".$nombre_marca."?!";

//         $body = $nombre.", ".$nombre_quien_ha_abierto." es un CazaCódigos profesional.
//                 Como ha visto que abriste su código de ".$nombre_marca."
//                 Se muere de ganas porque lo apliques.
//                 ¡Activa tu código, obtén tu promoción y haz feliz a ".$nombre_quien_ha_abierto."!
//                 Ambos ganáis, ¿a qué esperas?";



        $body = "Hola ".$nombre.",<br>
        ¿Recuerdas que visitaste el código de ".$nombre_marca." en ".$url."?<br><br>
        <img src='".$img_marca."' width='200px'>
        <br>
        <h1>¿No quieres ganar tu recompensa?</h1>

        ¡".$nombre_quien_ha_abierto." está deseando que lo actives!


        <br>Sabemos que algunos códigos, como el de ".$nombre_marca.", requieren un proceso de verificación para ganar tu recompensa.<br>
        <br>
        ¡Ya casi lo tienes! Finaliza el proceso y, ¡disfruta de tu premio!";

//         $body = "<img src='".$url_logo_web."' alt='logo codigo amigo' /><br><br>";

//         $body .= "<h1>Hola, ".$nombre."</h1>";

//         $body .="<p>El usuario <b>".$usuario_original["username"]."</b> te acaba de quitar tu posición con tu código amigo de <b>".$nombre_marca."</b>

//             <br>

//             No dejes que esto pase, ¡el trono debe ser tuyo!

//             <br>

//             <a href='https://www.codigoamigo.com/destaca?codigo=".$codigo_to_show["_id"]."'>Haz click en este enlace</a>

//             <br>¡Vamos!</p>";


        

        $email = new \SendGrid\Mail\Mail();
        $email->setFrom("info@codigoamigo.com", "Código Amigo");
        $email->setSubject($asunto);
        $email->addTo($to, $nombre);
        $email->addContent("text/plain", "and easy to do anywhere, even with PHP");
        $email->addContent(
            "text/html", $body
            );

        $sendgrid = new \SendGrid('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');
        
        try {
            $response = $sendgrid->send($email);

        } catch (Exception $e) {
            echo 'Caught exception: '. $e->getMessage() ."\n";
        }
        
        

        /*
         * AÑADO EN TABLA DE ZUMBIDOS
         */


    }

    function enviar_mail_codigo_no_destacado($codigo_to_show, $correo, $nombre, $url_codigo, $nombre_marca, $img_marca,$usuario_original) {

        global $url_logo_web;
        session_start();
        $nombre_quien_ha_abierto = $_SESSION["username"];

        $to = $correo;

        $asunto = "Pst, tienes competencia en ".$nombre_marca;

        $body = "<img src='".$url_logo_web."' alt='logo codigo amigo' /><br><br>";

        $body .= "<h1>Hola, ".$nombre."</h1>";

        $body .="<p>El usuario <b>".$usuario_original["username"]."</b> te acaba de quitar tu posición con tu código amigo de <b>".$nombre_marca."</b>

            <br>

            No dejes que esto pase, ¡el trono debe ser tuyo!

            <br>

            <a href='https://www.codigoamigo.com/destaca?codigo=".$codigo_to_show["_id"]."'>Haz click en este enlace</a>

            <br>¡Vamos!</p>";



        $email = new \SendGrid\Mail\Mail();
        $email->setFrom("info@codigoamigo.com", "Código Amigo");
        $email->setSubject($asunto);
        $email->addTo($to, $nombre);
        $email->addContent("text/plain", "and easy to do anywhere, even with PHP");
        $email->addContent(
            "text/html", $body
            );

        $sendgrid = new \SendGrid('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');

        try {
            $response = $sendgrid->send($email);

            //print_r($response);
        } catch (Exception $e) {
            echo 'Caught exception: '. $e->getMessage() ."\n";
        }

    }



    function enviar_mail_codigo_no_destacado_home($codigo_to_show, $correo, $nombre, $url_codigo, $nombre_marca, $img_marca,$usuario_original) {

        global $url_logo_web;
        session_start();
        $nombre_quien_ha_abierto = $_SESSION["username"];

        $to = $correo;

        $headers = "From: Código Amigo <info@codigoamigo.com>\r\n";
        $headers .= "X-Mailer: PHP5\n";
        $headers .= "MIME-Version: 1.0"."\n";
        $headers .= "Content-type: text/html; charset=iso-8859-1"."\r\n";

        $asunto = "Pst, tienes competencia en ".$nombre_marca;



        $body = "<img src='".$url_logo_web."' alt='logo codigo amigo' /><br><br>";

        $body .= "<h1>Hola, ".$nombre."</h1>";

        $body .="<p>El usuario <b>".$usuario_original["username"]."</b> te acaba de quitar tu posición con tu código amigo de <b>".$nombre_marca."</b>

            <br>
            No dejes que esto pase, ¡el trono debe ser tuyo!

            <br>Vuélvete a posicionar el primero en la portada!
            <br>

            <a href='https://www.codigoamigo.com/destaca?codigo=".$codigo_to_show["_id"]."'>Haz click en este enlace</a>

            ¡Vamos!</p>";


        $email = new \SendGrid\Mail\Mail();
        $email->setFrom("info@codigoamigo.com", "Código Amigo");
        $email->setSubject($asunto);
        $email->addTo($to, $nombre);
        $email->addContent("text/plain", "and easy to do anywhere, even with PHP");
        $email->addContent(
            "text/html", $body
            );

        $sendgrid = new \SendGrid('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');

        try {
            $response = $sendgrid->send($email);

            //print_r($response);
        } catch (Exception $e) {
            echo 'Caught exception: '. $e->getMessage() ."\n";
        }

    }

    function enviar_mail_codigo_publicado($codigo_data, $user_data) {
        global $url_logo_web;

        $email = new \SendGrid\Mail\Mail();

        $to = $user_data["mail"];
        $asunto = "¡Tu código de " . $codigo_data["marca"] . " ha sido publicado!";

        $optimize_name_marca = optimizeUrlPath($codigo_data["marca"]);
        $optimize_name_marca = str_replace("-", "", $optimize_name_marca);

        $link_codigo = link_codigo($codigo_data["_id"], $optimize_name_marca)."&nuevo_codigo=1";

        $url_codigo = "https://www.codigoamigo.com/".$link_codigo;
        $url_editar = "https://www.codigoamigo.com/modificar_codigo/" . $codigo_data["_id"];
        $url_destacar = "https://www.codigoamigo.com/destaca?codigo=" . $codigo_data["_id"];

        $body = "<img src='" . $url_logo_web . "' alt='logo codigo amigo' /><br><br>";
        $body .= "<h1>¡Enhorabuena " . $user_data["username"] . "!</h1>";
        $body .= "<p>Tu código de <b>" . $codigo_data["marca"] . "</b> ya está publicado y listo para generar beneficios.</p>";
        
        // Botones de acción
        $body .= "<div style='margin: 20px 0;'>";
        $body .= "<a href='" . $url_codigo . "' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; margin-right: 10px;'>Ver Código</a>";
        $body .= "<a href='" . $url_editar . "' style='background: #2196F3; color: white; padding: 10px 20px; text-decoration: none;'>Editar Código</a>";
        $body .= "</div>";

        // Sección destacada
        $body .= "<div style='background: #FFF3CD; border: 2px solid #FFE69C; padding: 20px; margin: 30px 0; border-radius: 5px;'>";
        $body .= "<h2 style='color: #856404; margin-top: 0;'>🚀 ¡Multiplica tus ingresos por 100!</h2>";
        $body .= "<p>¿Sabías que puedes multiplicar tus ganancias destacando tu código? Los códigos destacados reciben hasta 100 veces más visitas.</p>";
        $body .= "<a href='" . $url_destacar . "' style='background: #FFC107; color: #000; padding: 15px 30px; text-decoration: none; display: inline-block; margin-top: 10px; font-weight: bold;'>¡Destacar mi código ahora!</a>";
        $body .= "</div>";

        $body .= "<p>Recuerda que si tienes cualquier duda, pregunta o sugerencia, puedes hacernosla llegar a <b>info@codigoamigo.com</b></p>";

        $email->setFrom("info@codigoamigo.com", "Código Amigo");
        $email->setSubject($asunto);
        $email->addTo($to, $user_data["username"]);
        $email->addContent("text/plain", "and easy to do anywhere, even with PHP");
        $email->addContent("text/html", $body);

        $sendgrid = new \SendGrid('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');

        try {
            $response = $sendgrid->send($email);
        } catch (Exception $e) {
            echo 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

?>