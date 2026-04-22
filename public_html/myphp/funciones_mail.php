<?php

include_once __DIR__ . '/email_helper.php';

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

        // Usar el sistema de email mejorado con Brevo como principal
        $resultado = enviarEmailContacto($datos, $url_logo_web);
        
        if ($resultado['success']) {
            return "success";
        } else {
            error_log("Error enviando email de contacto: " . $resultado['error']);
            return "error";
        }

    }

    /*************************************
     * REGISTRO DE USUARIO
     * ***********************************/

    function enviar_mail_activacion($datos) {

        global $url_logo_web;

        $to = $datos["correo"];
        $asunto = "Bienvenido a Código Amigo";
        $href= "https://www.codigoamigo.com/bienvenido_de_nuevo?user=".$datos["correo"]."&codigo=".encriptar($datos["correo"]);

        $body = "<img src='".$url_logo_web."' alt='logo codigo amigo' /><br><br>";
        $body .= "<h1>Bienvenido a Código Amigo</h1>";
        $body .= "<p>Hola, ".$datos["nombre"].". Gracias por registrarte.</p>";
        $body .= "<p>Activa tu usuario haciendo clic en el siguiente enlace:</p><br>";
        $body .= "<a href='".$href."' title='activar usuario'>".$href."</a>";
        $body .= "<br><p>Si tienes dudas, escríbenos a <b>info@codigoamigo.com</b></p>";

        enviarEmailConBrevoYRegistrar(
            $to,
            $datos["nombre"] ?? 'Usuario',
            $asunto,
            $body,
            'activacion_usuario',
            null,
            ['href' => $href],
            strip_tags($body),
            'info@codigoamigo.com',
            'Código Amigo'
        );

    }

    function enviar_mail_apertura_codigo($codigo_to_show, $correo, $nombre, $url_codigo, $nombre_marca, $img_marca) {

        global $url_logo_web;
        session_start();

        // Comprobar preferencia del usuario destinatario
        $id_usuario_codigo = $codigo_to_show['id_usuario'] ?? null;
        if ($id_usuario_codigo && !usuarioAceptaEmail((string)$id_usuario_codigo, 'apertura_codigo')) {
            error_log("Email apertura_codigo NO enviado a $correo: usuario ha desactivado esta notificación");
            return;
        }

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
        $body = ($body);

        //mail($to, $asunto, $body, $headers, "-finfo@codigoamigo.com");

        
        enviarEmailConBrevoYRegistrar(
            $to,
            $nombre,
            $asunto,
            $body,
            'apertura_codigo',
            null,
            ['marca' => $nombre_marca, 'url_codigo' => $url_codigo],
            strip_tags($body),
            'info@codigoamigo.com',
            'Código Amigo'
        );
        
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

    function enviar_mail_codigo_no_destacado($codigo_to_show, $correo, $nombre, $url_codigo, $nombre_marca, $img_marca, $usuario_original) {

        global $url_logo_web;

        // Comprobar preferencia del usuario destinatario
        $id_usuario_codigo = $codigo_to_show['id_usuario'] ?? null;
        if ($id_usuario_codigo && !usuarioAceptaEmail((string)$id_usuario_codigo, 'competencia')) {
            error_log("Email competencia NO enviado a $correo: usuario ha desactivado esta notificación");
            return false;
        }

        $to_email = $correo;
        $to_name = $nombre;
        $asunto = "Pst, tienes competencia en " . $nombre_marca;

        $html_content = "<img src='" . $url_logo_web . "' alt='logo codigo amigo' /><br><br>";
        $html_content .= "<h1>Hola, " . $nombre . "</h1>";
        $html_content .= "<p>El usuario <b>" . $usuario_original["username"] . "</b> te acaba de quitar tu posición con tu código amigo de <b>" . $nombre_marca . "</b></p>";
        $html_content .= "<p>No dejes que esto pase, ¡el trono debe ser tuyo!</p>";
        $html_content .= "<p><a href='https://www.codigoamigo.com/destacar_codigo?codigo=" . $codigo_to_show["_id"] . "' style='background: #E30613; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; display: inline-block;'>Haz click en este enlace</a></p>";
        $html_content .= "<p><a href='https://www.codigoamigo.com/mis-anuncios' style='background: #6c757d; color: white; padding: 10px 18px; text-decoration: none; border-radius: 6px; display: inline-block;'>Ver mis códigos</a></p>";
        $html_content .= "<p>¡Vamos!</p>";

        $text_content = "Hola, " . $nombre . "\n\n" .
                       "El usuario " . $usuario_original["username"] . " te acaba de quitar tu posición con tu código amigo de " . $nombre_marca . "\n\n" .
                       "No dejes que esto pase, ¡el trono debe ser tuyo!\n\n" .
                       "Haz click en este enlace para destacar tu código:\n" .
                       "https://www.codigoamigo.com/destacar_codigo?codigo=" . $codigo_to_show["_id"] . "\n\n" .
                       "Ver mis códigos: https://www.codigoamigo.com/mis-anuncios\n\n" .
                       "¡Vamos!";

        // Usar el sistema de envío con registro en logs
        $resultado = enviarEmailConBrevoYRegistrar(
            $to_email,
            $to_name,
            $asunto,
            $html_content,
            'competencia',
            (string)($usuario_original['_id'] ?? ''),
            ['codigo_id' => (string)$codigo_to_show['_id'], 'marca' => $nombre_marca],
            $text_content,
            "info@codigoamigo.com",
            "Código Amigo"
        );

        if (!$resultado['success']) {
            error_log("Error enviando email de competencia a " . $to_email . ": " . $resultado['error']);
        } else {
            error_log("Email de competencia enviado correctamente a " . $to_email . " via " . $resultado['method']);
        }

        return $resultado['success'];

    }



    function enviar_mail_codigo_no_destacado_home($codigo_to_show, $correo, $nombre, $url_codigo, $nombre_marca, $img_marca, $usuario_original) {

        global $url_logo_web;

        // Comprobar preferencia del usuario destinatario
        $id_usuario_codigo = $codigo_to_show['id_usuario'] ?? null;
        if ($id_usuario_codigo && !usuarioAceptaEmail((string)$id_usuario_codigo, 'competencia_home')) {
            error_log("Email competencia_home NO enviado a $correo: usuario ha desactivado esta notificación");
            return false;
        }

        $to_email = $correo;
        $to_name = $nombre;
        $asunto = "Pst, tienes competencia en " . $nombre_marca;

        $html_content = "<img src='" . $url_logo_web . "' alt='logo codigo amigo' /><br><br>";
        $html_content .= "<h1>Hola, " . $nombre . "</h1>";
        $html_content .= "<p>El usuario <b>" . $usuario_original["username"] . "</b> te acaba de quitar tu posición con tu código amigo de <b>" . $nombre_marca . "</b></p>";
        $html_content .= "<p>No dejes que esto pase, ¡el trono debe ser tuyo!</p>";
        $html_content .= "<p>Vuélvete a posicionar el primero en la portada!</p>";
        $html_content .= "<p><a href='https://www.codigoamigo.com/destacar_codigo?codigo=" . $codigo_to_show["_id"] . "' style='background: #E30613; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; display: inline-block;'>Haz click en este enlace</a></p>";
        $html_content .= "<p><a href='https://www.codigoamigo.com/mis-anuncios' style='background: #6c757d; color: white; padding: 10px 18px; text-decoration: none; border-radius: 6px; display: inline-block;'>Ver mis códigos</a></p>";
        $html_content .= "<p>¡Vamos!</p>";

        $text_content = "Hola, " . $nombre . "\n\n" .
                       "El usuario " . $usuario_original["username"] . " te acaba de quitar tu posición con tu código amigo de " . $nombre_marca . "\n\n" .
                       "No dejes que esto pase, ¡el trono debe ser tuyo!\n\n" .
                       "Vuélvete a posicionar el primero en la portada!\n\n" .
                       "Haz click en este enlace para destacar tu código:\n" .
                       "https://www.codigoamigo.com/destacar_codigo?codigo=" . $codigo_to_show["_id"] . "\n\n" .
                       "Ver mis códigos: https://www.codigoamigo.com/mis-anuncios\n\n" .
                       "¡Vamos!";

        // Usar el sistema de envío con registro en logs
        $resultado = enviarEmailConBrevoYRegistrar(
            $to_email,
            $to_name,
            $asunto,
            $html_content,
            'competencia_home',
            (string)($usuario_original['_id'] ?? ''),
            ['codigo_id' => (string)$codigo_to_show['_id'], 'marca' => $nombre_marca],
            $text_content,
            "info@codigoamigo.com",
            "Código Amigo"
        );

        if (!$resultado['success']) {
            error_log("Error enviando email de competencia home a " . $to_email . ": " . $resultado['error']);
        } else {
            error_log("Email de competencia home enviado correctamente a " . $to_email . " via " . $resultado['method']);
        }

        return $resultado['success'];

    }

    /**
     * Notifica a todos los usuarios que tienen códigos en el home (destacado_social)
     * cuando se destaca un nuevo código super
     * 
     * @param string $codigo_id_nuevo ID del código que acaba de ser destacado
     * @param string $usuario_id_nuevo ID del usuario que acaba de destacar
     * @param array $codigo_nuevo_info Información del código destacado (opcional)
     * @return int Número de emails enviados
     */
    function notificar_competencia_home_destacado_super($codigo_id_nuevo, $usuario_id_nuevo, $codigo_nuevo_info = null) {
        try {
            // Obtener información del código nuevo si no se proporciona
            if (!$codigo_nuevo_info) {
                $codigo_nuevo = getCodeByID(new \MongoDB\BSON\ObjectId($codigo_id_nuevo));
                if (!$codigo_nuevo) {
                    error_log("Error: No se pudo obtener información del código $codigo_id_nuevo");
                    return 0;
                }
            } else {
                $codigo_nuevo = $codigo_nuevo_info;
            }

            // Obtener información del usuario que destacó
            $usuario_nuevo = getObjectUser('_id', new \MongoDB\BSON\ObjectId($usuario_id_nuevo));
            if (!$usuario_nuevo) {
                error_log("Error: No se pudo obtener información del usuario $usuario_id_nuevo");
                return 0;
            }
            $datos_usuario_nuevo = get_array_de_usuario($usuario_nuevo);

            // Obtener marca del código nuevo
            $marca_nuevo = getObjectMarca('nombre_clave', $codigo_nuevo['marca']);
            $marca_nombre = $marca_nuevo['nombre'] ?? $codigo_nuevo['marca'];

            // Obtener todos los códigos con destacado_social > 0 (los que están en el home)
            $collection_codigos = getCollectionCodigos();
            $filtro_home = [
                'estado' => 0,
                'destacado_social' => ['$gt' => 0]
            ];

            $codigos_home = $collection_codigos->find($filtro_home)->toArray();

            // Obtener usuarios únicos que tienen códigos en el home
            $usuarios_home = [];
            $emails_enviados = [];
            $emails_enviados_count = 0;

            foreach ($codigos_home as $codigo_home) {
                // Excluir el código que acaba de ser destacado
                if ((string)$codigo_home['_id'] === (string)$codigo_id_nuevo) {
                    continue;
                }

                // Obtener usuario del código
                $usuario_id_home = (string)$codigo_home['id_usuario'];
                
                // Excluir al usuario que acaba de destacar
                if ($usuario_id_home === (string)$usuario_id_nuevo) {
                    continue;
                }

                // Obtener información del usuario
                $usuario_home = getObjectUser('_id', new \MongoDB\BSON\ObjectId($usuario_id_home));
                if (!$usuario_home) {
                    continue;
                }

                $datos_usuario_home = get_array_de_usuario($usuario_home);
                $email_usuario = strtolower(trim($datos_usuario_home['mail'] ?? ''));

                // Evitar duplicados y emails vacíos
                if (empty($email_usuario) || isset($emails_enviados[$email_usuario])) {
                    continue;
                }

                // Comprobar preferencia del usuario destinatario
                if (!usuarioAceptaEmail($usuario_id_home, 'competencia_home_super')) {
                    error_log("Email competencia_home_super NO enviado a $email_usuario: usuario ha desactivado esta notificación");
                    continue;
                }

                // Marcar email como enviado
                $emails_enviados[$email_usuario] = true;

                // Obtener el primer código del usuario en el home para el enlace
                $codigo_usuario_home = null;
                foreach ($codigos_home as $cod) {
                    if ((string)$cod['id_usuario'] === $usuario_id_home) {
                        $codigo_usuario_home = $cod;
                        break;
                    }
                }

                if (!$codigo_usuario_home) {
                    continue;
                }

                // Enviar email
                global $url_logo_web;
                $to_email = $email_usuario;
                $to_name = $datos_usuario_home['username'] ?? 'Usuario';
                $asunto = "Pst, tienes competencia en el home";

                $html_content = "<img src='" . $url_logo_web . "' alt='logo codigo amigo' /><br><br>";
                $html_content .= "<h1>Hola, " . $to_name . "</h1>";
                $html_content .= "<p>Pst, el usuario <b>" . $datos_usuario_nuevo["username"] . "</b> acaba de destacar un código de <b>" . $marca_nombre . "</b> en el home.</p>";
                $html_content .= "<p>Tu código también está destacado en el home, así que es un buen momento para asegurarte de mantener tu visibilidad.</p>";
                $html_content .= "<p>No dejes que te superen. ¡Mantén tu posición destacada!</p>";
                $html_content .= "<p><a href='https://www.codigoamigo.com/destacar_codigo?codigo=" . $codigo_usuario_home["_id"] . "' style='background: #E30613; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; display: inline-block; margin: 10px 0;'>Destacar mi código ahora</a></p>";
                $html_content .= "<p><a href='https://www.codigoamigo.com/mis-anuncios' style='background: #6c757d; color: white; padding: 10px 18px; text-decoration: none; border-radius: 6px; display: inline-block;'>Ver mis códigos</a></p>";
                $html_content .= "<p>¡Vamos!</p>";

                $text_content = "Hola, " . $to_name . "\n\n" .
                               "Pst, el usuario " . $datos_usuario_nuevo["username"] . " acaba de destacar un código de " . $marca_nombre . " en el home.\n\n" .
                               "Tu código también está destacado en el home, así que es un buen momento para asegurarte de mantener tu visibilidad.\n\n" .
                               "No dejes que te superen. ¡Mantén tu posición destacada!\n\n" .
                               "Destacar mi código: https://www.codigoamigo.com/destacar_codigo?codigo=" . $codigo_usuario_home["_id"] . "\n\n" .
                               "Ver mis códigos: https://www.codigoamigo.com/mis-anuncios\n\n" .
                               "¡Vamos!";

                // Usar el sistema de envío con registro en logs
                $resultado = enviarEmailConBrevoYRegistrar(
                    $to_email,
                    $to_name,
                    $asunto,
                    $html_content,
                    'competencia_home_super',
                    $usuario_id_home,
                    ['codigo_id' => (string)$codigo_usuario_home['_id'], 'codigo_nuevo_id' => (string)$codigo_id_nuevo, 'marca' => $marca_nombre],
                    $text_content,
                    "info@codigoamigo.com",
                    "Código Amigo"
                );

                if ($resultado['success']) {
                    $emails_enviados_count++;
                    error_log("Email de competencia home (super) enviado a " . $to_email);
                } else {
                    error_log("Error enviando email de competencia home (super) a " . $to_email . ": " . $resultado['error']);
                }
            }

            error_log("Total de emails de competencia home enviados: $emails_enviados_count");
            return $emails_enviados_count;

        } catch (Exception $e) {
            error_log("Error en notificar_competencia_home_destacado_super: " . $e->getMessage());
            return 0;
        }
    }

    function enviar_mail_codigo_publicado($codigo_data, $user_data) {
        global $url_logo_web;

        $to_email = $user_data["mail"];
        $to_name = $user_data["username"];
        $asunto = "¡Tu código de " . $codigo_data["marca"] . " ha sido publicado!";

        $optimize_name_marca = optimizeUrlPath($codigo_data["marca"]);
        $optimize_name_marca = str_replace("-", "", $optimize_name_marca);

        $link_codigo = link_codigo($codigo_data["_id"], $optimize_name_marca)."&nuevo_codigo=1";

        $url_codigo = "https://www.codigoamigo.com/".$link_codigo;
        $url_editar = "https://www.codigoamigo.com/modificar_codigo/" . $codigo_data["_id"];
        $url_destacar = "https://www.codigoamigo.com/destaca?codigo=" . $codigo_data["_id"];

        $html_content = "<img src='" . $url_logo_web . "' alt='logo codigo amigo' /><br><br>";
        $html_content .= "<h1>¡Enhorabuena " . $user_data["username"] . "!</h1>";
        $html_content .= "<p>Tu código de <b>" . $codigo_data["marca"] . "</b> ya está publicado y listo para generar beneficios.</p>";

        // Botones de acción
        $html_content .= "<div style='margin: 20px 0;'>";
        $html_content .= "<a href='" . $url_codigo . "' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; margin-right: 10px;'>Ver Código</a>";
        $html_content .= "<a href='" . $url_editar . "' style='background: #2196F3; color: white; padding: 10px 20px; text-decoration: none;'>Editar Código</a>";
        $html_content .= "</div>";

        // Sección destacada
        $html_content .= "<div style='background: #FFF3CD; border: 2px solid #FFE69C; padding: 20px; margin: 30px 0; border-radius: 5px;'>";
        $html_content .= "<h2 style='color: #856404; margin-top: 0;'>🚀 ¡Multiplica tus ingresos por 100!</h2>";
        $html_content .= "<p>¿Sabías que puedes multiplicar tus ganancias destacando tu código? Los códigos destacados reciben hasta 100 veces más visitas.</p>";
        $html_content .= "<a href='" . $url_destacar . "' style='background: #FFC107; color: #000; padding: 15px 30px; text-decoration: none; display: inline-block; margin-top: 10px; font-weight: bold;'>¡Destacar mi código ahora!</a>";
        $html_content .= "</div>";

        $html_content .= "<p>Recuerda que si tienes cualquier duda, pregunta o sugerencia, puedes hacernosla llegar a <b>info@codigoamigo.com</b></p>";

        $text_content = "¡Enhorabuena " . $user_data["username"] . "!\n\n" .
                       "Tu código de " . $codigo_data["marca"] . " ya está publicado y listo para generar beneficios.\n\n" .
                       "Enlaces de acceso:\n" .
                       "Ver código: " . $url_codigo . "\n" .
                       "Editar código: " . $url_editar . "\n\n" .
                       "¿Sabías que puedes multiplicar tus ganancias destacando tu código? Los códigos destacados reciben hasta 100 veces más visitas.\n\n" .
                       "Destacar código: " . $url_destacar . "\n\n" .
                       "Recuerda que si tienes cualquier duda, pregunta o sugerencia, puedes hacernosla llegar a info@codigoamigo.com";

        // Usar el sistema de envío con registro en logs
        $resultado = enviarEmailConBrevoYRegistrar(
            $to_email,
            $to_name,
            $asunto,
            $html_content,
            'codigo_publicado',
            (string)($user_data['_id'] ?? ''),
            ['codigo_id' => (string)$codigo_data['_id'], 'marca' => $codigo_data['marca']],
            $text_content,
            "info@codigoamigo.com",
            "Código Amigo"
        );

        if (!$resultado['success']) {
            error_log("Error enviando email de código publicado a " . $to_email . ": " . $resultado['error']);
        } else {
            error_log("Email de código publicado enviado correctamente a " . $to_email . " via " . $resultado['method']);
        }

        return $resultado['success'];
    }

?>