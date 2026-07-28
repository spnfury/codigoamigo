<?php

include_once __DIR__ . '/email_helper.php';
// Plantilla visual moderna (_templateBaseDestacadoEmail) para las conversiones
// que buscan patrocinados/VIP: los emails de este fichero usaban HTML suelto
// con colores inconsistentes, en vez de la plantilla de marca ya existente.
include_once __DIR__ . '/funciones_destacados_email.php';

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



    function enviar_mail_codigo_no_destacado($codigo_to_show, $correo, $nombre, $url_codigo, $nombre_marca, $img_marca, $usuario_original) {

        // Comprobar preferencia del usuario destinatario
        $id_usuario_codigo = $codigo_to_show['id_usuario'] ?? null;
        if ($id_usuario_codigo && !usuarioAceptaEmail((string)$id_usuario_codigo, 'competencia')) {
            error_log("Email competencia NO enviado a $correo: usuario ha desactivado esta notificación");
            return false;
        }

        $to_email = $correo;
        $to_name = $nombre;
        $asunto = "Pst, tienes competencia en " . $nombre_marca;
        $url_destacar = "https://www.codigoamigo.com/destacar_codigo?codigo=" . $codigo_to_show["_id"];

        // Plantilla de marca (antes HTML suelto sin mención a VIP, la vía de
        // conversión recurrente que más interesa frente al destacar puntual)
        $contenido = '
            <p style="margin-top:0;">Hola <strong>' . htmlspecialchars($nombre) . '</strong>,</p>
            <p>El usuario <strong>' . htmlspecialchars($usuario_original["username"]) . '</strong> te acaba de quitar tu posición con tu código amigo de <strong>' . htmlspecialchars($nombre_marca) . '</strong>.</p>
            <div style="background-color:#fdf3f4;border:1px solid #f8d7da;border-radius:8px;padding:16px;margin:25px 0;">
                <p style="margin:0;color:#c7254e;font-weight:600;">No dejes que esto pase, ¡el trono debe ser tuyo!</p>
            </div>
            <div style="background-color:#f4f7fa;padding:20px;border-radius:8px;border-left:4px solid #E30613;margin-top:20px;">
                <p style="margin:0 0 8px 0;color:#222;font-weight:bold;font-size:15px;">&#11088; ¿Te pasa esto a menudo?</p>
                <p style="margin:0;color:#555;font-size:14px;line-height:1.5;">Con <a href="https://www.codigoamigo.com/public/suscripcion_vip.php" style="color:#E30613;font-weight:bold;text-decoration:none;">VIP (9,99€/mes)</a> recibes 10€ de saldo cada mes para recuperar tu posición sin pensarlo, primer mes a mitad de precio.</p>
            </div>
            <div style="text-align:center;margin-top:25px;">
                <a href="https://www.codigoamigo.com/mis-anuncios" style="color:#555555;text-decoration:none;font-weight:600;">Ver mis códigos</a>
            </div>';

        $html_content = _templateBaseDestacadoEmail('Nueva competencia en ' . htmlspecialchars($nombre_marca), $contenido, 'Recuperar mi posición', $url_destacar);

        $text_content = "Hola, " . $nombre . "\n\n" .
                       "El usuario " . $usuario_original["username"] . " te acaba de quitar tu posición con tu código amigo de " . $nombre_marca . "\n\n" .
                       "No dejes que esto pase, ¡el trono debe ser tuyo!\n\n" .
                       "Recuperar mi posición: " . $url_destacar . "\n\n" .
                       "¿Te pasa a menudo? Hazte VIP por 9,99€/mes (primer mes a mitad de precio) y recibe 10€ de saldo cada mes: https://www.codigoamigo.com/public/suscripcion_vip.php\n\n" .
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

        // Comprobar preferencia del usuario destinatario
        $id_usuario_codigo = $codigo_to_show['id_usuario'] ?? null;
        if ($id_usuario_codigo && !usuarioAceptaEmail((string)$id_usuario_codigo, 'competencia_home')) {
            error_log("Email competencia_home NO enviado a $correo: usuario ha desactivado esta notificación");
            return false;
        }

        $to_email = $correo;
        $to_name = $nombre;
        $asunto = "Pst, tienes competencia en " . $nombre_marca;
        $url_destacar = "https://www.codigoamigo.com/destacar_codigo?codigo=" . $codigo_to_show["_id"];

        $contenido = '
            <p style="margin-top:0;">Hola <strong>' . htmlspecialchars($nombre) . '</strong>,</p>
            <p>El usuario <strong>' . htmlspecialchars($usuario_original["username"]) . '</strong> te acaba de quitar tu posición con tu código amigo de <strong>' . htmlspecialchars($nombre_marca) . '</strong>.</p>
            <div style="background-color:#fdf3f4;border:1px solid #f8d7da;border-radius:8px;padding:16px;margin:25px 0;">
                <p style="margin:0;color:#c7254e;font-weight:600;">No dejes que esto pase, ¡vuelve a ser el primero en la portada!</p>
            </div>
            <div style="background-color:#f4f7fa;padding:20px;border-radius:8px;border-left:4px solid #E30613;margin-top:20px;">
                <p style="margin:0 0 8px 0;color:#222;font-weight:bold;font-size:15px;">&#11088; ¿Te pasa esto a menudo?</p>
                <p style="margin:0;color:#555;font-size:14px;line-height:1.5;">Con <a href="https://www.codigoamigo.com/public/suscripcion_vip.php" style="color:#E30613;font-weight:bold;text-decoration:none;">VIP (9,99€/mes)</a> recibes 10€ de saldo cada mes para recuperar tu posición sin pensarlo, primer mes a mitad de precio.</p>
            </div>
            <div style="text-align:center;margin-top:25px;">
                <a href="https://www.codigoamigo.com/mis-anuncios" style="color:#555555;text-decoration:none;font-weight:600;">Ver mis códigos</a>
            </div>';

        $html_content = _templateBaseDestacadoEmail('Nueva competencia en el home', $contenido, 'Recuperar mi posición', $url_destacar);

        $text_content = "Hola, " . $nombre . "\n\n" .
                       "El usuario " . $usuario_original["username"] . " te acaba de quitar tu posición con tu código amigo de " . $nombre_marca . "\n\n" .
                       "No dejes que esto pase, ¡vuelve a ser el primero en la portada!\n\n" .
                       "Recuperar mi posición: " . $url_destacar . "\n\n" .
                       "¿Te pasa a menudo? Hazte VIP por 9,99€/mes (primer mes a mitad de precio) y recibe 10€ de saldo cada mes: https://www.codigoamigo.com/public/suscripcion_vip.php\n\n" .
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

            // Códigos que están en el home (destacado_social > 0).
            //
            // Antes se avisaba a TODOS (299 usuarios por cada destacado nuevo):
            // 1.026 emails en 30 días para 2 pagos = 513 emails por venta y
            // 0,0049€ de ingreso por email (medido 2026-07-28). Ese volumen sin
            // interacción degrada la reputación del dominio en Gmail y arrastra
            // al resto de envíos (reengagement, verificaciones) hacia spam.
            //
            // Ahora solo se avisa a quien tiene algo concreto que perder: los
            // que están al final de la cola del home, que son los que el nuevo
            // destacado desplaza. Mensaje más urgente y ~90% menos volumen.
            $collection_codigos = getCollectionCodigos();
            $filtro_home = [
                'estado' => 0,
                'destacado_social' => ['$gt' => 0]
            ];

            $tope_destinatarios = 30;
            $codigos_home = $collection_codigos->find($filtro_home, [
                'sort'  => ['destacado_social' => 1, '_id' => 1], // los más débiles primero
                'limit' => $tope_destinatarios + 5,               // margen por exclusiones
            ])->toArray();

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

                // Evitar duplicados, emails vacíos y emails malformados
                if (empty($email_usuario) || isset($emails_enviados[$email_usuario])) {
                    continue;
                }
                if (!filter_var($email_usuario, FILTER_VALIDATE_EMAIL)) {
                    error_log("Email competencia_home_super inválido, saltado: $email_usuario (usuario $usuario_id_home)");
                    $emails_enviados[$email_usuario] = true;
                    continue;
                }

                // Comprobar preferencia del usuario destinatario
                if (!usuarioAceptaEmail($usuario_id_home, 'competencia_home_super')) {
                    error_log("Email competencia_home_super NO enviado a $email_usuario: usuario ha desactivado esta notificación");
                    continue;
                }

                // Tope duro de destinatarios por disparo (ver nota arriba)
                if ($emails_enviados_count >= $tope_destinatarios) {
                    break;
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
        $to_email = $user_data["mail"];
        $to_name = $user_data["username"];
        $asunto = "¡Tu código de " . $codigo_data["marca"] . " ha sido publicado!";

        $optimize_name_marca = optimizeUrlPath($codigo_data["marca"]);
        $optimize_name_marca = str_replace("-", "", $optimize_name_marca);

        $link_codigo = link_codigo($codigo_data["_id"], $optimize_name_marca)."&nuevo_codigo=1";

        $url_codigo = "https://www.codigoamigo.com/".$link_codigo;
        $url_editar = "https://www.codigoamigo.com/modificar_codigo/" . $codigo_data["_id"];
        $url_destacar = "https://www.codigoamigo.com/destaca?codigo=" . $codigo_data["_id"];

        // Contenido con la plantilla de marca (_templateBaseDestacadoEmail), no HTML
        // suelto: antes este email (el de mayor volumen, se manda en cada publicación)
        // usaba botones verde/azul sin relación con la marca y nunca mencionaba VIP,
        // que es la vía de conversión recurrente que más interesa al negocio.
        $contenido = '
            <p style="margin-top:0;">¡Enhorabuena <strong>' . htmlspecialchars($user_data["username"]) . '</strong>!</p>
            <p>Tu código de <strong>' . htmlspecialchars($codigo_data["marca"]) . '</strong> ya está publicado y listo para generar beneficios.</p>

            <div style="text-align:center;margin:20px 0;">
                <a href="' . htmlspecialchars($url_codigo) . '" style="color:#E30613;text-decoration:none;font-weight:600;margin-right:20px;">Ver código</a>
                <a href="' . htmlspecialchars($url_editar) . '" style="color:#555555;text-decoration:none;font-weight:600;">Editar código</a>
            </div>

            <div style="background-color:#fdf3f4;border:1px solid #f8d7da;border-radius:8px;padding:16px;margin:25px 0;">
                <p style="margin:0;color:#c7254e;font-weight:700;font-size:15px;">&#128640; Multiplica tus visitas destacando tu código</p>
                <p style="margin:8px 0 0 0;color:#a94442;font-size:14px;">Los códigos destacados reciben hasta 100 veces más visitas que uno normal. Por solo 0,99€ tu código sube de posición.</p>
                <div style="text-align:center;margin-top:15px;">
                    <a href="' . htmlspecialchars($url_destacar) . '" style="background:#E30613;color:white;padding:12px 28px;text-decoration:none;border-radius:8px;font-weight:bold;font-size:15px;display:inline-block;">¡Destacar mi código ahora!</a>
                </div>
            </div>

            <div style="background-color:#f4f7fa;padding:20px;border-radius:8px;border-left:4px solid #E30613;margin-top:20px;">
                <p style="margin:0 0 8px 0;color:#222;font-weight:bold;font-size:15px;">&#11088; ¿Publicas códigos a menudo?</p>
                <p style="margin:0;color:#555;font-size:14px;line-height:1.5;">Hazte <a href="https://www.codigoamigo.com/public/suscripcion_vip.php" style="color:#E30613;font-weight:bold;text-decoration:none;">Usuario VIP por 9,99€/mes</a> y recibe 10€ de saldo gratis cada mes para destacar tus códigos sin pagar de tu bolsillo, badge verificado y chat ilimitado con quien te contacte. El primer mes, a mitad de precio.</p>
            </div>

            <p style="margin-top:25px;">Si tienes cualquier duda, pregunta o sugerencia, escríbenos a <strong>info@codigoamigo.com</strong></p>';

        $html_content = _templateBaseDestacadoEmail('¡Código publicado!', $contenido);

        $text_content = "¡Enhorabuena " . $user_data["username"] . "!\n\n" .
                       "Tu código de " . $codigo_data["marca"] . " ya está publicado y listo para generar beneficios.\n\n" .
                       "Enlaces de acceso:\n" .
                       "Ver código: " . $url_codigo . "\n" .
                       "Editar código: " . $url_editar . "\n\n" .
                       "Los códigos destacados reciben hasta 100 veces más visitas. Destacar código (0,99€): " . $url_destacar . "\n\n" .
                       "¿Publicas a menudo? Hazte VIP por 9,99€/mes (primer mes a mitad de precio) y recibe 10€ de saldo gratis cada mes, badge verificado y chat ilimitado: https://www.codigoamigo.com/public/suscripcion_vip.php\n\n" .
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