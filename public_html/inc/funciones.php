<?php



	function recorta_texto_pos($texto,$caracteres,$puntos_suspensivos) {
        //esta funcion recorta un texto donde encuentre el primer espacio despues del tamaño para no cortar carácteres UTF8 y salgan simbolos raros
        if (strlen($texto) <= $caracteres) {
            return $texto;
        }
        $corte = strpos($texto," ",$caracteres);
        if (!$corte) { return $texto; }
        else { return substr($texto,0,$corte).$puntos_suspensivos; }
    }

    function transformafecha($date,$long='',$morelong='',$FixaNew = false) {

        global $month_arr,$month_arr_long,$weekdays_arr,$amZona,$debugAdmin;

        /*if($GLOBALS["SYSTEM_CONF"]["timezone"]){
         date_default_timezone_set($GLOBALS["SYSTEM_CONF"]["timezone"]);
         }*/


        //no puede mostrar fechas en el futuro
        $opening_date = date('Y-m-d G:i:s',strtotime('now'));
        $current_date = date('Y-m-d G:i:s',strtotime($date));

        if ($opening_date < $current_date){
            $date = $opening_date;
            $date = date('Y-m-d',strtotime('now'));
        }
        if(date('Ymd') == date('Ymd', strtotime($date))){
            //echo date('Ymd')."--".date('Ymd', strtotime($date));
            $es_hoy = 1;
        }
        if(date("d-m-y")==date("d-m-y",strtotime($date))){
            if(!$morelong){
                $str_week = " ";
            } else {
                $str_week = $weekdays_arr[date("w",strtotime($date))+1];
            }
        } else {
            $str_week = $weekdays_arr[date("w",strtotime($date))+1];
        }
        $day = date("d",strtotime($date));

        $month = $month_arr[date("n",strtotime($date))];
        $month_l = $month_arr_long[date("n",strtotime($date))];

        $year = date("y",strtotime($date));
        $yearF = date("Y",strtotime($date));
        $hora = date("H:i",strtotime($date));

        $nuevahora = strtotime('now')-strtotime($date);
        $nuevahora_horas = (int)($nuevahora/(60*60));
        $nuevahora_minutos = date("i",$nuevahora);

        if(!$long){
            if($str_week==' '){
                //$form_date = "<b>".$str_week."</b><br>".$hora." ".$zona;
                //RESTO el numero de tiempo que hace a partir de hoy
                if($hora=='00:00'){
                    $form_date = "Hoy";

                } else {
                    $form_date = "Hoy, ".$hora;
                }
            } else {
                if($morelong){
                    $form_date = $str_week.", ".$day." de ".$month_l;
                } else {
                    $form_date = $day." ".ucfirst($month_l);
                }
            }
        } else {
            if($es_hoy==1){
                if ($opening_date < $current_date){
                    $form_date = "hace menos de un día";
                } else {
                    $form_date = "Hoy a las ".$hora." ".$zona;
                }
            } else {
                $form_date = $str_week."  ".$day." ".$month_l/*." a las ".$hora." ".$zona*/;
            }
        }
        if($FixaNew == true){
            $form_date = $day." de ".$month_l;
        }
        return $form_date;
    }


    function uploadFotoUsuario ($file) {

        $msg = "";
        $uploadedfileload = "true";
        
        // Validar tamaño
        if (($file["uploadedfile"]['size']) > 8000000) {
            $uploadedfile_size = $file['uploadedfile']['size'];
            $msg = "Solo es posible subir fotos menores de 8MB";
            $uploadedfileload = "false";
        }
        
        // Validar tipo de archivo
        if (!($file["uploadedfile"]['type'] =="image/jpeg" OR $file["uploadedfile"]['type'] =="image/gif" OR $file["uploadedfile"]['type'] =="image/png")) {
            $msg = "Solo es posible subir archivos que sean imágenes.";
            $uploadedfileload = "false";
        }
        
        if($uploadedfileload == "true") {
            // Generar nombre único para evitar conflictos
            $file_name = $file["uploadedfile"]['name'];
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $unique_name = uniqid() . '_' . time() . '.' . $file_ext;
            $add = $_SERVER['DOCUMENT_ROOT']."/uploads/$unique_name";

            if(move_uploaded_file ($file["uploadedfile"]['tmp_name'], $add)) {
                include_once($_SERVER["DOCUMENT_ROOT"]."/inc/resize_class.php");
                $img = new img($add);
                $img->resize(302,404,true);
                $img->store($add,50);

                $msg = "Foto de perfil cambiada correctamente";
                $nueva_url_foto = "https://www.codigoamigo.com/uploads/$unique_name";
                cambiarFotoUsuario($_SESSION["mail"], $nueva_url_foto);
            } else { 
                $msg = "Error al subir el archivo"; 
            }
        }
        return $msg;
    }

    /****************** MAILS *******************************/

    function enviarMailVerOferta ($datos) {

        $nombre = $datos["username"];
        $email = $datos['mail'];

        $codigo = $datos["codigo"];

        $email_encriptado = encriptar($email);

        $url = 'https://www.codigoamigo.com/bienvenido_de_nuevo?codigo='. $email_encriptado;

        $mensaje = '
	        <html>
                <head>
                  <title>Bienvenido a código amigo</title><br>
                </head>
                <body>
                    <img src="http://www.codigoamigo.com/img/logo_codigoamigo.jpg"><br><br>
                    <span>Enhorabuena <b>' . $nombre . '</b>! </span><br><br>
                    <span>El usuario '.$_SESSION["username"].' ha abierto tu código amigo de '.$codigo["marca"].' es muy probable que lo use y te beneficies de '.$codigo["num_beneficio"].' '.$codigo["tipo_descuento"].'!</span><br><br>Comparte tu código para que llegue aún más personas:<br><a href="'.$url.'">'.$url.'</a><br><br>Un Saludo, Tamara de CodigoAmigo.com

                </body>
                </html>
	        ';


        $dest = $email;
        $headers = "From: $nombre <$email>\r\n";
        $headers = "cc: $nombre <$email>\r\n";
        $headers .= "X-Mailer: PHP5\n";
        $headers .= 'MIME-Version: 1.0' . "\n";
        $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
        $asunto = $datos["username"].", alguien ha abierto tu código amigo!";
        $cuerpo .= $mensaje;


        if($nombre != '' && $email != '' && $mensaje != ''){
            mail($dest,$asunto,$cuerpo,$headers);
        }
    }

    function enviarMailActivacion ($datos) {
        // Incluir el helper de email moderno
        include_once __DIR__ . '/../myphp/email_helper.php';
        
        $nombre = $datos["username"];
        $email = $datos['mail'];
        $email_encriptado = encriptar($email);
        
        $html_content = '
            <html>
                <head>
                    <title>Activar tu cuenta de Código Amigo</title>
                    <meta charset="UTF-8">
                </head>
                <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                        <img src="https://www.codigoamigo.com/img/logo_codigoamigo.png" alt="Código Amigo" style="max-width: 200px; margin-bottom: 20px;"><br><br>
                        
                        <h2 style="color: #E30613;">¡Bienvenido a Código Amigo!</h2>
                        
                        <p>Estimado usuario <strong>' . htmlspecialchars($nombre) . '</strong>:</p>
                        
                        <p>Gracias por registrarte en nuestra web <a href="https://www.codigoamigo.com" style="color: #E30613;">Código Amigo</a>. 
                        Estamos encantados de tenerte como parte de nuestra comunidad.</p>
                        
                        <p>Para activar tu cuenta y comenzar a disfrutar de todos nuestros códigos descuento, 
                        por favor, haz clic en el siguiente enlace:</p>
                        
                        <div style="text-align: center; margin: 30px 0;">
                            <a href="https://www.codigoamigo.com/bienvenido_de_nuevo?codigo=' . $email_encriptado . '" 
                               style="background-color: #E30613; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
                                Activar mi cuenta
                            </a>
                        </div>
                        
                        <p>Si el botón no funciona, copia y pega este enlace en tu navegador:</p>
                        <p style="word-break: break-all; background-color: #f5f5f5; padding: 10px; border-radius: 4px;">
                            https://www.codigoamigo.com/bienvenido_de_nuevo?codigo=' . $email_encriptado . '
                        </p>
                        
                        <p>Una vez activada tu cuenta, podrás:</p>
                        <ul>
                            <li>Acceder a cientos de códigos descuento exclusivos</li>
                            <li>Compartir tus propios códigos con la comunidad</li>
                            <li>Disfrutar de ofertas especiales</li>
                        </ul>
                        
                        <p>Si tienes cualquier duda, pregunta o sugerencia, no dudes en contactarnos en 
                        <a href="mailto:info@codigoamigo.com" style="color: #E30613;">info@codigoamigo.com</a></p>
                        
                        <p>¡Esperamos verte pronto en Código Amigo!</p>
                        
                        <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
                        <p style="font-size: 12px; color: #666;">
                            Este email fue enviado automáticamente. Si no te registraste en Código Amigo, puedes ignorar este mensaje.
                        </p>
                    </div>
                </body>
            </html>
        ';
        
        $text_content = "¡Bienvenido a Código Amigo!\n\n" .
                       "Estimado usuario " . $nombre . ":\n\n" .
                       "Gracias por registrarte en nuestra web Código Amigo. Estamos encantados de tenerte como parte de nuestra comunidad.\n\n" .
                       "Para activar tu cuenta y comenzar a disfrutar de todos nuestros códigos descuento, " .
                       "por favor, accede al siguiente enlace:\n\n" .
                       "https://www.codigoamigo.com/bienvenido_de_nuevo?codigo=" . $email_encriptado . "\n\n" .
                       "Una vez activada tu cuenta, podrás acceder a cientos de códigos descuento exclusivos, " .
                       "compartir tus propios códigos con la comunidad y disfrutar de ofertas especiales.\n\n" .
                       "Si tienes cualquier duda, pregunta o sugerencia, no dudes en contactarnos en info@codigoamigo.com\n\n" .
                       "¡Esperamos verte pronto en Código Amigo!\n\n" .
                       "El equipo de Código Amigo";
        
        // Usar el sistema moderno con Brevo
        $resultado = enviarEmailConBrevo(
            $email,
            $nombre,
            "Activar tu cuenta de Código Amigo",
            $html_content,
            $text_content,
            "noreply@codigoamigo.com",
            "Código Amigo"
        );
        
        if (!$resultado['success']) {
            error_log("Error enviando email de activación: " . $resultado['error']);
            mandaBot("Error crítico enviando email de activación: " . $resultado['error']);
            return false;
        } else {
            error_log("Email de activación enviado correctamente via " . $resultado['method'] . " a: " . $email);
            return true;
        }
    }

    function enviarMailRecuerdoPass ($datos) {
        // Incluir el helper de email
        include_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/email_helper.php';
        
        // Usar la función helper con fallback automático
        $resultado = enviarEmailRecuperacionPassword($datos);
        
        if (!$resultado['success']) {
            error_log("Error crítico enviando email de recuperación: " . $resultado['error']);
            mandaBot("Error crítico enviando email de recuperación: " . $resultado['error']);
        } else {
            error_log("Email de recuperación enviado correctamente via " . $resultado['method'] . " a: " . $datos['mail']);
        }
    }


// Product field functions
function optimizeUrlPath($texto , $space=false,$espacios='',$junto=''){

    global $debugAdmin;
    $texto = (string)$texto;
    if(is_string($texto)){
        $texto = strtolower($texto);
        $texto = trim($texto);
        $arCharReplace["\¿"] = "";
        $arCharReplace["\?"] = "";
        $arCharReplace["'"] = "";
        $arCharReplace["´"] = "";
        $arCharReplace["á"] = "a";
        $arCharReplace["à"] = "a";
        $arCharReplace["ä"] = "a";
        $arCharReplace["â"] = "a";
        $arCharReplace["è"] = "e";
        $arCharReplace["é"] = "e";
        $arCharReplace["ë"] = "e";
        $arCharReplace["ê"] = "e";
        $arCharReplace["í"] = "i";
        $arCharReplace["ì"] = "i";
        $arCharReplace["î"] = "i";
        $arCharReplace["ï"] = "i";
        $arCharReplace["ô"] = "o";
        $arCharReplace["ö"] = "o";
        $arCharReplace["ó"] = "o";
        $arCharReplace["ò"] = "o";
        $arCharReplace["î"] = "i";
        $arCharReplace["ï"] = "i";
        $arCharReplace["ì"] = "i";
        $arCharReplace["ú"] = "u";
        $arCharReplace["ù"] = "u";
        $arCharReplace["ü"] = "u";
        $arCharReplace["û"] = "u";
        $arCharReplace["ñ"] = "n";
        $arCharReplace["ç"] = "c";
        $arCharReplace["l`"] = "l";
        $arCharReplace["l'"] = "l";
        $arCharReplace["d'"] = "d";
        $arCharReplace["€"] = "";
        $arCharReplace["\”"] = "";
        //$arCharReplace["-"] = "";
        $arCharReplace["¡"] = "";
        $arCharReplace["!"] = "";
        $arCharReplace["“"] = "";
        $arCharReplace["&"] = "-";
        $arCharReplace["”"] = "";
        $arCharReplace["Nº"] = "n";

        $arCharReplace["Á"] = "a";
        $arCharReplace["À"] = "a";
        $arCharReplace["Ä"] = "a";
        $arCharReplace["Â"] = "a";
        $arCharReplace["È"] = "e";
        $arCharReplace["É"] = "e";
        $arCharReplace["Ë"] = "e";
        $arCharReplace["Ê"] = "e";
        $arCharReplace["Í"] = "i";
        $arCharReplace["Ì"] = "i";
        $arCharReplace["Î"] = "i";
        $arCharReplace["Ï"] = "i";
        $arCharReplace["Ô"] = "o";
        $arCharReplace["Ö"] = "o";
        $arCharReplace["Ó"] = "o";
        $arCharReplace["Ò"] = "o";
        $arCharReplace["Ú"] = "u";
        $arCharReplace["Ù"] = "u";
        $arCharReplace["Ü"] = "u";
        $arCharReplace["Û"] = "u";
        $arCharReplace["Ç"] = "c";
        $arCharReplace["Ñ"] = "n";
        $arCharReplace["L`"] = "l";
        $arCharReplace["L'"] = "l";
        $arCharReplace["D'"] = "d";

        foreach($arCharReplace AS $kChar=>$vChar){
            $texto = preg_replace('#'.$kChar.'#i',$vChar,$texto);
        }

        $texto = strtolower( trim($texto, '-') );

        //if($texto !== mb_convert_encoding( mb_convert_encoding($texto, 'UTF-32', 'UTF-8'), 'UTF-8', 'UTF-32') )
        //$str_actual_enc = mb_detect_encoding($texto);
        /*if(isset($str_actual_enc) && $str_actual_enc!=''){
         $texto = mb_convert_encoding($texto, 'UTF-8', $str_actual_enc);
         }else{
         $texto = mb_convert_encoding($texto, 'UTF-8');
        }*/

        $texto = preg_replace('`&([a-z]{1,2})(acute|eacute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig);`i', '\1', $texto);
        $texto = preg_replace("/[^A-Za-z0-9?! ]/","",$texto);
       	$texto = str_replace(' ', '-', $texto);
        $texto = str_replace("amp-amp", "amp", $texto);

        if($junto){
            $texto = str_replace("-","",$texto);
        }
        $url = $texto;
        if($espacios){ //aado espacios a las barras
            $url = str_replace('-', ' ', $url);
        }
        return $url;
    }
}

// Incluir utilidad de cache
require_once __DIR__ . '/../myphp/SimpleCache.php';

function getListMarcaSpecial() {
    $cacheKey = "getListMarcaSpecial";
    $cached = SimpleCache::get($cacheKey);
    if ($cached !== null) {
        return $cached;
    }
    
    // Obtener la lista de marcas ordenadas por número de códigos
    $lista_marcas = getMarcas(50); // Obtener más marcas para tener mejor selección
    
    // Lógica para fijar marcas específicas (como ING)
    $pinned_brands = ['ing']; // Añadir aquí las marcas que queremos forzar
    $existing_keys = [];
    
    if (!empty($lista_marcas)) {
        foreach ($lista_marcas as $m) {
            if (isset($m['nombre_clave'])) {
                $existing_keys[$m['nombre_clave']] = true;
            }
        }
    }
    
    // Intentar buscar las marcas fijadas si no están en la lista
    foreach ($pinned_brands as $pinned) {
        if (!isset($existing_keys[$pinned]) && function_exists('getObjectMarca')) {
            $brand_obj = getObjectMarca('nombre_clave', $pinned);
            if ($brand_obj) {
                // Si getObjectMarca devuelve un objeto o array, nos aseguramos que tenga numero_codigos
                // Si no tiene el count, lo seteamos bajo para que aparezca pero no rompa nada, 
                // o intentamos contarlos si fuera crítico, pero asumimos que queremos mostrarla.
                if (!isset($brand_obj['numero_codigos'])) {
                    $brand_obj['numero_codigos'] = 1; // Asumimos al menos 1 para que pase el filtro
                }
                $lista_marcas[] = $brand_obj;
            }
        }
    }
    
    $array_marcas = array();
    
    if (empty($lista_marcas)) {
        return []; // Devuelve un array vacío si no hay marcas
    }
    
    // Procesar cada marca
    foreach ($lista_marcas as $marca) {
        // Simplificar el proceso de limpieza de la URL de la imagen
        $url_remover = ['http://www.codigoamigo.com', 'https://www.codigoamigo.com', 'https://codigoamigo.com'];
        $marca["imagen"] = str_replace($url_remover, '', $marca["imagen"]);
        
        // Usar el número de códigos que ya viene calculado en getMarcas()
        $num_codes = $marca["numero_codigos"] ?? 0;
        
        // Si es una marca fijada y tiene 0 códigos, le ponemos al menos 1 para que se muestre si queremos forzarlo
        if (in_array($marca["nombre_clave"], $pinned_brands) && $num_codes == 0) {
           // Opcional: descomentar si queremos mostrar marcas vacías fijadas
           // $num_codes = 1; 
        }

        // Crear el array de la marca
        $elemento = array(
            'nombre' => $marca["nombre"],
            'codes' => $num_codes,
            'nombre_clave' => $marca["nombre_clave"],
            'imagen' => $marca["imagen"]
        );
        
        // Añadir al array de marcas
        $array_marcas[] = $elemento;
    }
    
    // Ordenar el array por el número de códigos de forma descendente
    // Modificado para priorizar marcas pinned si se desea, por ahora mantenemos orden natural por códigos
    usort($array_marcas, function($a, $b) use ($pinned_brands) {
        // Si ambos están en pinned o ninguno, ordenar por códigos
        // Si queremos forzar pinned arriba descomentar lo siguiente:
        /*
        $a_pinned = in_array($a['nombre_clave'], $pinned_brands);
        $b_pinned = in_array($b['nombre_clave'], $pinned_brands);
        if ($a_pinned && !$b_pinned) return -1;
        if (!$a_pinned && $b_pinned) return 1;
        */
        
        return $b['codes'] - $a['codes'];
    });
    
    
    // Dividir el array según el tipo de dispositivo
    if (isset($GLOBALS["detect"]) && $GLOBALS["detect"]->isMobile()) {
        $array_chunk = array_chunk($array_marcas, 12); // Dividir en bloques de 12 para móviles
    } else {
        $array_chunk = array_chunk($array_marcas, 20); // Dividir en bloques de 20 para escritorio
    }
    
    // Devolver el primer bloque si existe, o el array completo si no hay bloques
    $finalResult = !empty($array_chunk) ? $array_chunk[0] : $array_marcas;
    SimpleCache::set($cacheKey, $finalResult);
    return $finalResult;
}


function array_sort_by(&$arrIni, $col, $order = SORT_ASC)
{
    $arrAux = array();
    foreach ($arrIni as $key=> $row)
    {
        $arrAux[$key] = is_object($row) ? $arrAux[$key] = $row->$col : $row[$col];
        $arrAux[$key] = strtolower($arrAux[$key]);
    }
    array_multisort($arrAux, $order, $arrIni);
}


function printCuadroCodigo ($tipo, $list_codigos) {

    /* 4 tipos: 'miscodigos', 'listado', 'ficha', 'codigo' */

	   $code = "";
	   $css = "cbp-item col-md-4 col-xs-12 ";

    	if($tipo == "ficha" || $tipo == "codigo") {

    	    $code = $list_codigos;
    	    $list_codigos = array();
    	    array_push($list_codigos, $code);
    	    $css = "cbp-item ";
    }

    foreach ($list_codigos as $codigo) {

        $usuario = getObjectUser('_id', $codigo["id_usuario"]);
        $marca = getObjectMarca ('nombre_clave', $codigo["marca"]);

        $marca["imagen"] = str_replace("http://","https://",$marca["imagen"]);

        ?>

		<div class="<?php echo $css.$codigo["marca"]?>">
            <div class="cbp-caption-defaultWrap">
                <div class="cbp-caption">
                	<?php if($tipo == 'miscodigos') { ?>
                		<a class="eliminar_codigo" data-id="<?php echo $codigo["_id"] ?>" title="Eliminar código">
    						<span class="glyphicon glyphicon-remove"></span>
                		</a>
                	<?php } ?>
                    <div  style="position:relative;">
                    	<?php if($marca["imagen"] == "No se encontro logo para la imagen") { ?>
                    		<p style="font-size: 20px;">Código Amigo de<br><a class="enlace" href="<?php echo link_codigo($codigo["_id"],$codigo["marca"]); ?>"><strong><?php echo $marca["nombre"]?></strong></a></p>
                    	<?php } else { ?>
                    		<a href="<?php echo link_codigo($codigo["_id"],$codigo["marca"]); ?>">
                				<img class="div_marca"  alt="Código amigo de <?php echo $codigo["marca"]; ?>" src="<?php echo $marca["imagen"]; ?>">
            				</a>
                    	<?php } ?>
                    	<div class="usuario">
                    		<a title="Publicado por <?php echo $usuario["username"]; ?>" href="<?php echo link_usuario($usuario["username"], $usuario["_id"]); ?>">
                    			<?php if($usuario["img"]) { ?>
                    				<img src="<?php echo $usuario["img"];?>">
                				<?php } else { ?><img src="../img/po.png"><?php } ?>
                    		</a>
                		</div>
                    </div>
                </div>
                <div class="cbp-1-title-bg listadocodigos">
                    <div class="cbp-l-grid-projects-title"><?php echo $codigo["descripcion"] ?></div>
                    <div class="cbp-l-grid-projects-desc descuento">
                        <i class="fa fa-trophy" aria-hidden="true"></i> <b><?php echo $codigo["num_beneficio"] ?></b> <?php echo $codigo["tipo_descuento"] ?>
                    </div>
                 	<?php if(!empty($_SESSION["user_id"])) {
                 	          if($tipo == "ficha" || $tipo == "codigo") { ?>
                 					<div class="cbp-l-grid-projects-desc">
                                		<button class="btn btn-success mostrar_code btn_mostrar_code" data-id="<?php echo $codigo["_id"]; ?>">Ver código</button>
                                    </div>
                                    <div class="cbp-l-grid-projects-desc hide thecode<?php echo $codigo["_id"]; ?>">
                                    	<div class="rev">
                                    		<span style="font-size: 15px !important;"><?php
                                    		if(strstr($codigo["codigo"], "http") || strstr($codigo["codigo"], "www")){
                                    		    echo "<a href='".$codigo["codigo"]."' target='_blank'>".$codigo["codigo"]."</a>";
                                    		}else{
                                    		    echo $codigo["codigo"];
                                    		}
                                    		?></span>
                                    	</div><br>
                                        <?php if (!empty($marca["url_register"])) { ?>
                                        	<span style="font-size: 15px;">No esperes más y <a class="enlace" target="_blank" href="<?php echo $marca["url_register"]?>"><strong>regístrate ya en <?php echo $marca["nombre"]?></strong></a></span><br>
                                        <?php } else { ?>
                                        	<span style="font-size: 15px;">No esperes más y <a class="enlace" target="_blank" href="<?php echo $marca["url"]?>"><strong>visita ya la web de <?php echo $marca["nombre"]?></strong></a></span><br>
                                        <?php } ?>
                                    </div>
                 				<?php } else { ?>
                 					<div class="cbp-l-grid-projects-desc">
                                		<a href="<?php echo link_codigo($codigo["_id"],$codigo["marca"]); ?>">
                                			<button class="btn btn-success btn_mostrar_code">Ir al código</button>
                            			</a>
                                    </div>
                 				<?php } ?>
					<?php } else { ?>
    					<div class="cbp-l-grid-projects-desc">
                    		<button class="btn btn-success mostrar_code_nosession btn_mostrar_code">Ir al código</button>
                        </div>
					<?php } ?>
                    <div class="cbp-l-grid-projects-desc">
                		<span class="glyphicon glyphicon-eye-open"></span>
                    	<?php if ($codigo["totalclicks"] == 1) { echo $codigo["totalclicks"] . " vez"; }
                    	       else { echo $codigo["totalclicks"] . " veces";} ?>
                	</div>
                	<div class="cbp-l-grid-projects-desc">
                		<?php if($tipo == 'miscodigos' || $tipo == 'ficha' || $tipo = 'miscodigos_usuario_externo') { ?>
                        	<?php if (!empty($codigo["fecha_validez"])) { ?>
                				<i class="fa fa-newspaper-o" aria-hidden="true"></i> <?php echo $codigo["fecha_publicacion"] ?> -
                				<i class="fa fa-times" aria-hidden="true"></i> <?php echo $codigo["fecha_validez"] ?>
            				<?php } else { ?>
            					<i class="fa fa-newspaper-o" aria-hidden="true"></i> <?php echo $codigo["fecha_publicacion"] ?>
            				<?php }} ?>
    				</div>
                	<?php if ($tipo == 'ficha' || $tipo == 'miscodigos_usuario_externo') { ?>
                		<div class="cbp-l-grid-projects-desc"><i class="fa fa-location-arrow" aria-hidden="true"></i> <?php echo $codigo["provincia"] ?>-<?php echo $codigo["localidad"] ?></div>
                	<?php } ?>
<!--                 	<div class="sharethis-inline-share-buttons"></div> -->
					<div class="text-center">
						<?php if ($tipo == 'ficha') {
						    shareBySocialBlade("ficha");
						} ?>
					</div>
                </div>
        	</div>
    	</div>
    	<?php } publicaCodigo();

}

function printPanel_($tipo, $object, $lista_codigos) { ?>
<?php /*?>
	<section class="main-contain">
    	<div class="container">
        	<div class="cd-home-title">
                <?php if($tipo == "usuario") { ?>
                	<h2 style="padding: 20px;">Otros <a href="<?php echo link_usuario($object["username"], $object["_id"]); ?>">códigos amigo de <?php echo $object["username"]; ?></a></h2>
            	<?php } elseif ($tipo == "marca") {
            	       $marca = getObjectMarca('nombre_clave', $object["marca"]);?>
            		<h2>Otros <a href="<?php echo link_marca($object["marca"]) ?>">códigos amigo de <?php echo $marca["nombre"]; ?></a></h2>
            	<?php } elseif ($tipo == "categoria") {
            	    $categoria = getObjectCategoria('nombre_clave', $object["clave_categoria"]);?>
            		<h2>Otros <a href="<?php echo link_categoria($categoria["nombre_clave"]); ?>">códigos amigo de <?php echo $categoria["nombre"]; ?></a></h2>
            	<?php } elseif ($tipo == "provincia"){ ?>
            		<h2>Otros <a>códigos amigo de <?php echo $object["provincia"]; ?></a></h2>
            	<?php } ?>
            </div>
           <div class="row">
             	<?php //printCuadroCodigo('listado', $lista_codigos);
            	    printaNuevoCuadroCodigo($lista_codigos); ?>
        	</div>
        </div>
    </section>
    <? */ ?>
<?php }


?>