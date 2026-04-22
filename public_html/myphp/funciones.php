<?php

// Cargar autoloader de Composer para MongoDB
require_once __DIR__ . '/../vendor/autoload.php';

if (!function_exists('getFechaActualCorregida')) {
    /**
     * Función para obtener la fecha actual corregida
     * Corrige automáticamente si la fecha del servidor está mal configurada
     *
     * @return int Timestamp de la fecha actual corregida
     */
    function getFechaActualCorregida() {
        static $fecha_corregida = null;
        static $fecha_original = null;

        if ($fecha_corregida === null) {
            $fecha_original = time();

            // Detectar si la fecha del servidor está mal configurada
            // Si la fecha está más de 6 meses en el futuro, probablemente esté mal
            $fecha_futura_limite = strtotime('+6 months');

            if ($fecha_original > $fecha_futura_limite) {
                // La fecha del servidor está en el futuro, corregirla
                // Estimar que debe estar aproximadamente 1 año atrás
                $fecha_corregida = $fecha_original - (365 * 24 * 60 * 60);

                // Log de la corrección para debugging
                log_debug("Fecha del servidor corregida: " . date('Y-m-d H:i:s', $fecha_original) . " -> " . date('Y-m-d H:i:s', $fecha_corregida));
            } else {
                $fecha_corregida = $fecha_original;
            }
        }

        return $fecha_corregida;
    }
}

if (!function_exists('fechaCorregida')) {
    /**
     * Función para formatear fecha corregida
     * Similar a date() pero usando la fecha corregida
     *
     * @param string $format Formato de fecha
     * @param int $timestamp Timestamp opcional
     * @return string Fecha formateada
     */
    function fechaCorregida($format, $timestamp = null) {
        if ($timestamp === null) {
            $timestamp = getFechaActualCorregida();
        }
        return date($format, $timestamp);
    }
}

if (!function_exists('diasDesdeFechaCorregida')) {
    /**
     * Función para calcular días entre fechas usando fecha corregida
     *
     * @param string $fecha_str Fecha en formato string
     * @return int Días desde la fecha hasta hoy (corregido)
     */
    function diasDesdeFechaCorregida($fecha_str) {
        $timestamp_fecha = strtotime($fecha_str);
        $fecha_actual = getFechaActualCorregida();

        return floor(($fecha_actual - $timestamp_fecha) / (24 * 60 * 60));
    }
}

if (!function_exists('createConnection')) {
    function createConnection() {

            global $db,$sum;

            //$uri = "mongodb://ratUser:electr!cMongo3$@127.0.0.1:27017";
            $uri = "mongodb://127.0.0.1:27017";

            if($db){
                return $db;
            }

            try {
                $mongo = new MongoDB\Client($uri);

                $db = $mongo->codigo_db;
                //$db->setLogLevel(5);

                return $db;
            }
            catch (MongoDB\Driver\Exception\Exception $e) {
                log_debug("Error de MongoDB en createConnection: " . $e->getMessage());
                return null;
            }
            catch (Exception $e) {
                log_debug("Error general en createConnection: " . $e->getMessage());
                return null;
            }

        }
}



/**
 * Shim para compatibilidad con llamadas a debuglog() (sin guion bajo)
 */
if (!function_exists('debuglog')) {
    function debuglog($message, $data = null) {
        if (function_exists('debug_log')) {
            debug_log($message, $data);
        } else if (function_exists('log_debug')) {
            log_debug($message, $data);
        } else {
            error_log($message . ($data ? " :: " . print_r($data, true) : ""));
        }
    }
}

/**
 * Fallback para debug_log() cuando no está definido en el entry point
 */
if (!function_exists('debug_log')) {
    function debug_log($message, $data = null) {
        if (function_exists('log_debug')) {
            log_debug($message, $data);
        } else {
            error_log($message . ($data ? " :: " . print_r($data, true) : ""));
        }
    }
}

    if (!function_exists('mandaBot')) {

    function mandaBot($manda){

        // Inicializar variables para evitar warnings
        $error = array();
        $_ERRORS = array();

        $errno   = isset($error["type"]) ? $error["type"] : 0;
        $errfile = isset($error["file"]) ? $error["file"] : '';
        $errline = isset($error["line"]) ? $error["line"] : 0;
        $errstr  = isset($error["message"]) ? $error["message"] : '';

        $manda.= "CODIGOAMIGO*\n";

        if (is_array($_ERRORS)) {
            foreach($_ERRORS as $a => $line){
                $manda.= "***".$line."*****\n";
            }
        }

        if($errno == 1 || $errno == 4){ //SOLO FATALES

            $manda.= $errno."\n".$errfile."\n".$errline."\n".$errstr;



            if($archivos_incluidos){
                $limit = 0;
                foreach ($archivos_incluidos as $nombre_archivo) {
                    if($limit<=5){
                        $last_file.= "\n::".$nombre_archivo;
                    }
                    $limit++;
                }
                $manda.= "\n\nEncontrado en: ".$last_file."...";
            }

            // Ensure configuration is loaded
            if (!defined('TELEGRAM_BOT_TOKEN')) {
                // Try to find the config file relative to this file
                $config_path = __DIR__ . '/../config/ai_config.php';
                if (file_exists($config_path)) {
                    @include_once $config_path;
                }
            }

            $botToken = defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : "1208948207:AAF0O45V1zcsp7wjRgbwOrLQ7tNRUHlfnME";
            $chatId = defined('TELEGRAM_ADMIN_CHAT_ID') ? TELEGRAM_ADMIN_CHAT_ID : "-563343505";

            $url = "https://api.telegram.org/bot".$botToken. "/sendMessage?chat_id=" . $chatId;

            $post = [
                'chat_id'=>$chatId,
                'text' => $manda
            ];

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $data = curl_exec($curl);

            curl_close($curl);

            //decoding request
            $result = json_decode($data, true);

        }
    }
}

    /**********************************************************
     *  CONEXION CON BD - USUARIO
     *********************************************************/

    if (!function_exists('getObjectUser')) {
    function getObjectUser ($parameter, $value) {

        $collection_usuarios = getCollectionUsuarios();


        $usuario = $collection_usuarios->findOne([$parameter => $value]);
        
        // Log para verificar qué imagen se obtiene de la base de datos
        // Comentado para evitar spam en logs
        // if ($usuario && isset($usuario["img"])) {
        //     log_debug("getObjectUser - Imagen obtenida de BD: " . $usuario["img"]);
        // }
        
        // Respeta la URL original de la imagen sin modificaciones
        // Las URLs de imágenes deben mantenerse exactamente como están almacenadas
        
        //$usuario["img"] = "aaa";
        
        return $usuario;
    }
}

    /**********************************************************
     *  PROPIAS DE USUARIO
     *********************************************************/

    if (!function_exists('more_codes')) {
    function more_codes($datos) {

        $marca["nombre_clave"] = $datos["nombre_clave"];
        $skip_patrocinados = $datos["skip"];

        $array_skip = array("limit"=>13);
        $array_skip = array_merge($array_skip, array("skip"=>$skip_patrocinados));
        $array_skip = array_merge($array_skip, array("sort"=>array('destacado' => -1)));

        /* Listado NORMAL */
        $array_filtro = array("marca"=>$marca["nombre_clave"]);
        $array_filtro = array_merge($array_filtro, array("estado"=>0));
        $array_filtro = array_merge($array_filtro, array("destacado"=>0));

        //TOMO LOS CODIGOS
        $lista_codigos_pre = get_all_listado_codigos_array($array_filtro, $array_skip);

        $lista_codigos = $lista_codigos_pre["results"];
        $numero_codigos = $lista_codigos_pre["total_number"];



        block_listado_codigos($lista_codigos, $a_printar);


    }
}



    if (!function_exists('show_estatistics')) {
    function show_estatistics($datos){

        session_start();

        // ── Comprobación de propiedad ────────────────────────────────────────
        // Solo el propietario REAL del código puede ver las estadísticas de visitas.
        // No hay bypass de admin para esta sección.
        if (!empty($datos['data_codigo_id'])) {
            $obj_id_codigo = new \MongoDB\BSON\ObjectId($datos['data_codigo_id']);
            $codigo_check  = getCodeByID($obj_id_codigo);
            if ($codigo_check) {
                $codigo_user_id = is_object($codigo_check['id_usuario'])
                    ? (string)$codigo_check['id_usuario']
                    : (string)($codigo_check['id_usuario'] ?? '');
                $es_propietario = isset($_SESSION['user_id']) &&
                                  !empty($_SESSION['user_id']) &&
                                  $codigo_user_id === (string)$_SESSION['user_id'];
                if (!$es_propietario) {
                    return; // No mostrar nada a usuarios no propietarios
                }
            }
        }
        // ────────────────────────────────────────────────────────────────────

        $share_url = $datos["data_codigo_url"];
        $codigo_to_show["codigo"] = $datos["data_codigo_url"];

        ?>
                <div class="modal-dialog modal-md">
                <div class="modal-content">

                    <div class="modal-header" style="background: #3466FF; color: white; padding: 20px; font-size: 20px;">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <span class="modal-title" style="font-size:20px;">Visitas en tu código!</span>
                    </div>

                    <div class="text-center" style="font-size: 16px; padding: 30px; overflow:auto;max-height:800px;">

                   		 <?php muestra_visitas($datos["data_codigo_id"]); ?>
                    </div>

                </div>
                </div>

		<?

    }
}

    if (!function_exists('last_codigo')) {
    function last_codigo($datos,$marca='') {

        session_start();


        $share_url = $datos["data_codigo_url"];
        $codigo_to_show["codigo"] = $datos["data_codigo_url"];



        ?>
        <div class="modal-dialog modal-md">
        <div class="modal-content">

            <div class="modal-header" style="background: #3466FF; color: white; padding: 20px; font-size: 20px;">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <span class="modal-title" style="font-size:20px;">Comparte tu código en redes!</span>
            </div>

            <div class="text-center" style="font-size: 16px; padding: 10px;">

                        	<p><?php


                        	if((!$_SESSION["user_id"])) { ?>
                    	    		<div class="text-center block_codigo_no_sesion"><p>Por favor, inicia sesión para poder ver este código</p>
                    	    			<button class="btn btn_codigo_amigo btn-custom btn-mini login open_modal_login"><i class="fa fa-user"></i> Iniciar sesión</button>
                    	    		</div>

                        	 <?php }else{

                        	if(strpos($codigo_to_show["codigo"], "http") !==false){
                        	    ?><a target="_blank" href="<? echo $codigo_to_show["codigo"]; ?>"><? echo $codigo_to_show["codigo"]; ?></a><?
                        	}else{
                        	    echo $codigo_to_show["codigo"];
                        	}

                        	}



                        	?>
                        	</p>

                        	<?php echo "<br><i class='fa fa-clipboard' aria-hidden='true'></i> <a onclick='executeCopy(\"".$codigo_to_show["codigo"]."\",$(this));'>Copiar al portapapeles</a>"; ?>                       		<hr>
                    		<div class="text-center">
						<?php


                		 // Get current page URL
                		 $crunchifyURL = urlencode($share_url);

                		 $title = "Ahorra";

                		 if($datos["descuento"]){
                		     $title.= " ".$datos["descuento"];
                		 }

                		 if($datos["marca"]){
                		     $title.= " de ".$datos["marca"];
                		 }

                		 $title.= " con mi código amigo";

                		 // Get current page title
                		 $crunchifyTitle = htmlspecialchars(urlencode(html_entity_decode($title)));

                		 // $crunchifyTitle = str_replace( ' ', '%20', get_the_title());

                		 // Get Post Thumbnail for pinterest
                		 $crunchifyThumbnail = $imagen_social;

                		                 		 // Construct sharing URL without using any script
                		 $twitterURL = 'https://twitter.com/intent/tweet?text='.$crunchifyTitle.'&amp;url='.$crunchifyURL.'&amp;via=codigoamigoweb&amp;hashtags=codigoamigo,descuentos,codigopromocional'.optimizeUrlPath($datos["marca"]).','.optimizeUrlPath($datos["marca"]);
                		 $facebookURL = 'https://www.facebook.com/sharer/sharer.php?u='.$crunchifyURL;
                		 $googleURL = 'https://plus.google.com/share?url='.$crunchifyURL;
                		 $bufferURL = 'https://bufferapp.com/add?url='.$crunchifyURL.'&amp;text='.$crunchifyTitle;
                		 $whatsappURL = 'whatsapp://send?text='.$crunchifyTitle.' '.$crunchifyURL;
                		 $telegramURL = 'https://telegram.me/share/url?url='.$crunchifyURL.'&text='.$crunchifyTitle;
                		 $linkedInURL = 'https://www.linkedin.com/shareArticle?mini=true&url='.$crunchifyURL.'&amp;title='.$crunchifyTitle;

                		 // Based on popular demand added Pinterest too
                		 $pinterestURL = 'https://pinterest.com/pin/create/button/?url='.$crunchifyURL.'&amp;media='.$crunchifyThumbnail[0].'&amp;description='.$crunchifyTitle;

                		 // Add sharing button at the end of page/page content
                		 //$content .= '<!-- Implement your own superfast social sharing buttons without any JavaScript loading. No plugin required. Detailed steps here: http://crunchify.me/1VIxAsz -->';



                		 $content2 .= '<div class="crunchify-social">';
                		 $content2 .= '<h5>Comparte</h5>';
                		 $content2 .= '<a class="crunchify-link crunchify-whatsapp" href="'.$whatsappURL.'" target="_blank">Whatsapp</a>';$content2 .= '<a class="crunchify-link crunchify-linkedin" href="'.$linkedInURL.'" target="_blank">LinkedIn</a>';
                		 $content2 .= '<a class="crunchify-link crunchify-telegram" href="'.$telegramURL.'" target="_blank">Telegram</a>';

                		 $content2 .=' <a class="crunchify-link crunchify-twitter" href="'. $twitterURL .'" target="_blank">Twitter</a>';
                		$content2 .= '<a class="crunchify-link crunchify-facebook" href="'.$facebookURL.'" target="_blank">Facebook</a>';
                		 $content2 .= '<a class="crunchify-link crunchify-pinterest" href="'.$pinterestURL.'" data-pin-custom="true" target="_blank">Pin It</a>';
                		 $content2 .= '</div>';

                		 echo $content2;

                		 ?>                            </div>
                        </div>

            </div>
        </div>


        <?

    }
}


    /**********************************************************
     *  CONEXION CON BD - MARCA
     *********************************************************/

    if (!function_exists('getObjectMarca')) {
    function getObjectMarca ($parameter, $value) {

        $collection_marcas = getCollectionMarcas();
        $marca = $collection_marcas->findOne([$parameter => $value]);
        
        // Verificar si se encontró la marca
        if (!$marca) {
            return null;
        }
        
        // Verificar si existe la clave "nombre" antes de procesarla
        if (isset($marca["nombre"])) {
            $marca["nombre"] = ucwords(strtolower($marca["nombre"]));
        }





        // Verificar si existe la clave "imagen" antes de procesarla
        if (isset($marca["imagen"]) && is_string($marca["imagen"])) {
            if(strpos($marca["imagen"],'http://') !==false){
                $marca["imagen"] = str_replace("http://", "https://", $marca["imagen"]);
            }

            // CloudFront CDN (d3hcf0nbuqjt3g.cloudfront.net) is down - serve images directly
            // Convert S3 URLs to direct server URLs
            if(strpos($marca["imagen"],'https://cdn-codigoamigo.s3-eu-west-1.amazonaws.com/') !==false ){
                $marca["imagen"] = str_replace("https://cdn-codigoamigo.s3-eu-west-1.amazonaws.com/","https://www.codigoamigo.com/img/",$marca["imagen"]);
            }
            // Convert dead CloudFront URLs to direct server URLs
            if(strpos($marca["imagen"],'https://d3hcf0nbuqjt3g.cloudfront.net/') !==false ){
                $marca["imagen"] = str_replace("https://d3hcf0nbuqjt3g.cloudfront.net/","https://www.codigoamigo.com/img/",$marca["imagen"]);
            }

            if($marca["imagen"] == 'Sin imagen'){
                $marca["imagen"] = "";
            }
        } else {
            // Si no existe la clave "imagen", establecer un valor por defecto
            $marca["imagen"] = "";
        }

        return $marca;
    }
}

    /**********************************************************
     *  CONEXION CON BD - CÓDIGO
     *********************************************************/

    // Función para añadir impresión cuando un código se muestra en una lista
    // OPTIMIZACIÓN: Solo se registra 1 de cada 5 impresiones para reducir carga
    function añadir_impresion_codigo ($codigo_id) {
        // Solo registrar 1 de cada 5 impresiones para reducir carga en el servidor
        if (rand(1, 5) !== 1) {
            return true;
        }

        try {
            $collection_codigos = getCollectionCodigos();
            
            // Usar ObjectId si no lo es ya
            if (!($codigo_id instanceof \MongoDB\BSON\ObjectId)) {
                $codigo_id = new \MongoDB\BSON\ObjectId($codigo_id);
            }
            
            // Obtener fecha actual en formato Y-m-d para el tracking diario
            $fecha_hoy = date('Y-m-d');
            $campo_stats = 'stats_diarias.' . $fecha_hoy . '.impresiones';
            
            // Incrementar el contador total y el contador diario (multiplicado por 5 para compensar el muestreo)
            $collection_codigos->updateOne(
                ['_id' => $codigo_id],
                [
                    '$inc' => [
                        'total_impressions' => 5,
                        $campo_stats => 5
                    ]
                ]
            );
            
            return true;
        } catch(Exception $e) {
            // Silenciosamente fallar para no interrumpir la renderización
            return false;
        }
    }

    // Función para añadir click cuando un usuario hace click en el código
    function añadir_vista_codigo ($codigo) {
        try {
            $collection_codigos = getCollectionCodigos();
            
            // Obtener el ID del código
            $codigo_id = null;
            if (isset($codigo['_id'])) {
                if ($codigo['_id'] instanceof \MongoDB\BSON\ObjectId) {
                    $codigo_id = $codigo['_id'];
                } else {
                    $codigo_id = new \MongoDB\BSON\ObjectId($codigo['_id']);
                }
            } else {
                return false; // No hay ID, no se puede registrar
            }
            
            // Obtener fecha actual en formato Y-m-d para el tracking diario
            $fecha_hoy = date('Y-m-d');
            $campo_stats = 'stats_diarias.' . $fecha_hoy . '.clicks';
            
            // Incrementar el contador total y el contador diario (sin random)
            $updateResult = $collection_codigos->updateOne(
                ['_id' => $codigo_id],
                [
                    '$inc' => [
                        'totalclicks' => 1,
                        $campo_stats => 1
                    ]
                ]
            );
            
            return true;
        } catch(MongoDB\Driver\Exception\WriteException $e) {
            // Silenciosamente fallar para no interrumpir la renderización
            return false;
        } catch (Exception $e) {
            // Silenciosamente fallar para no interrumpir la renderización
            return false;
        }

    }


    // Función para obtener estadísticas diarias reales de un código
    function get_estadisticas_diarias_codigo($codigo_id, $fecha_inicio, $fecha_fin) {
        try {
            $collection_codigos = getCollectionCodigos();
            
            // Usar ObjectId si no lo es ya
            if (!($codigo_id instanceof \MongoDB\BSON\ObjectId)) {
                $codigo_id = new \MongoDB\BSON\ObjectId($codigo_id);
            }
            
            // Obtener el código con sus estadísticas diarias
            $codigo = $collection_codigos->findOne(['_id' => $codigo_id]);
            
            if (!$codigo) {
                return [];
            }
            
            // Convertir a array si es objeto
            if (is_object($codigo)) {
                $codigo = (array)$codigo;
            }
            
            $estadisticas_diarias = [];
            $stats_diarias = isset($codigo['stats_diarias']) ? $codigo['stats_diarias'] : [];
            
            // Convertir a array si es objeto
            if (is_object($stats_diarias)) {
                $stats_diarias = (array)$stats_diarias;
            }
            
            // Generar array con todas las fechas del rango
            $fecha_actual = clone $fecha_inicio;
            while ($fecha_actual <= $fecha_fin) {
                $fecha_str = $fecha_actual->format('Y-m-d');
                
                $impresiones = 0;
                $clicks = 0;
                
                // Obtener estadísticas reales si existen
                if (isset($stats_diarias[$fecha_str])) {
                    $stats_dia = $stats_diarias[$fecha_str];
                    if (is_object($stats_dia)) {
                        $stats_dia = (array)$stats_dia;
                    }
                    $impresiones = isset($stats_dia['impresiones']) ? (int)$stats_dia['impresiones'] : 0;
                    $clicks = isset($stats_dia['clicks']) ? (int)$stats_dia['clicks'] : 0;
                }
                
                $estadisticas_diarias[] = [
                    'fecha' => $fecha_str,
                    'impresiones' => $impresiones,
                    'clicks' => $clicks
                ];
                
                $fecha_actual->modify('+1 day');
            }
            
            return $estadisticas_diarias;
            
        } catch (Exception $e) {
            return [];
        }
    }

     function añadir_historial_codigo ($codigo) {
         $rand = rand(1, 3);
         $num_vistas = $codigo["totalclicks"] + $rand;

         try {
             $collection_historial = getCollectionHistorial();

             $data = [
                 "id_codigo" => new \MongoDB\BSON\ObjectId($codigo["_id"]),
                 "user_id" => $_SESSION["user_id"],
                 "fecha_visita" => date('d-m-Y  H:i:s'),
             ];

             $collection_historial->insertOne($data);

         } catch(MongoDB\Driver\Exception\WriteException $e) {
             $writeResult = $e->getWriteResult();
             echo "Errores en MongoDB\n";
         }
     }

     function añadir_destacado_codigo_usuario ($usuario,$codigo_operacion='BCV') {







         //DESTACA TODOS LOS CODIGOS DE UN USUARIO

         //añadir_destacado_codigo

             try {
                 $collection_codigos = getCollectionCodigos();
                 //print_r($collection_codigos);
                 $updateResult = $collection_codigos->updateMany(
                    ['id_usuario' => $usuario["_id"] ],
                    ['$set' => ['destacado' => strtotime('now')]]
                 );
             } catch(MongoDB\Driver\Exception\WriteException $e) {
                 $writeResult = $e->getWriteResult();
                 echo "Errores en MongoDB\n";
             }



             /* EMAIL */

             $usuario = getObjectUser('_id', $usuario["_id"] );
             $usuario_original = get_array_de_usuario($usuario);


             /* SACO TODOS LOS CODIGOS DE LA MARCA QUE ESTAN PATROCINADOS */
             $array_filtro = array("id_usuario"=>$usuario["_id"]);
             $array_filtro = array_merge($array_filtro, array("estado"=>0));
             $array_filtro = array_merge($array_filtro, array("destacado"=>array('$ne' => 0)));

             $array_skip = array("limit"=>13);
             $array_skip = array_merge($array_skip, array("sort"=>array('destacado' => -1)));

             //TOMO LOS CODIGOS
             $lista_codigos_pre = get_all_listado_codigos_array($array_filtro, $array_skip);
             $lista_codigos_patrocinados = $lista_codigos_pre["results"];

             // Array para rastrear emails ya enviados y evitar duplicados
             $emails_enviados = array();

             foreach($lista_codigos_patrocinados as $listado){

                 $usuario = getObjectUser('_id', new \MongoDB\BSON\ObjectId($listado["id_usuario"]));
                 $datos_usuario = get_array_de_usuario($usuario);

                 if($usuario_original["mail"] != $datos_usuario["mail"]){
                     
                     // Verificar si ya se envió un email a este destinatario
                     $email_destinatario = strtolower(trim($datos_usuario["mail"] ?? ''));
                     if (!empty($email_destinatario) && !isset($emails_enviados[$email_destinatario])) {
                         
                         // Marcar este email como enviado
                         $emails_enviados[$email_destinatario] = true;

                         $codigo_to_show = $collection_codigos->findOne(['_id' => $codigo["_id"]]);
                         $m = getObjectMarca("nombre_clave", $listado["marca"]);
                         $u = getObjectUser('_id', $codigo_to_show["id_usuario"]);
                         $actual_link = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                         enviar_mail_codigo_no_destacado($listado,  $datos_usuario["mail"], $datos_usuario["username"], $actual_link, $m["nombre"], $m["imagen"], $usuario_original);
                     }

                 }

             }

     }


    function destacar_codigo_moderno($codigo_id, $tipo_destacado = 'normal') {
        try {
            $collection_codigos = getCollectionCodigos();
            $obj_id_codigo = new \MongoDB\BSON\ObjectId($codigo_id);
            
            $duracion_dias = ($tipo_destacado === 'super') ? (defined('DESTACADO_DURACION_SUPER') ? DESTACADO_DURACION_SUPER : 14) : (defined('DESTACADO_DURACION_NORMAL') ? DESTACADO_DURACION_NORMAL : 7);
            
            $update_data = [
                'estado' => 0, // Reactivar código si estaba desactivado/caducado (-2/-3)
                'destacado' => strtotime('now'),
                'fecha_destacado' => date('Y-m-d H:i:s'),
                'tipo_destacado' => $tipo_destacado,
                'prioridad_pago' => strtotime('now'),
                'fecha_fin_destacado' => new \MongoDB\BSON\UTCDateTime((time() + ($duracion_dias * 86400)) * 1000)
            ];
            
            // Para destacado super, marcar también destacado_social (aparece en home)
            if($tipo_destacado == 'super') {
                $update_data['destacado_social'] = strtotime('now');
            }
            
            $updateResult = $collection_codigos->updateOne(
                ['_id' => $obj_id_codigo],
                ['$set' => $update_data]
            );
            
            if($updateResult->getModifiedCount() > 0) {
                // Enviar notificación por email al usuario
                enviar_notificacion_destacado($codigo_id, $tipo_destacado);
                
                // Si es destacado super, notificar a todos los usuarios con códigos en el home
                if($tipo_destacado == 'super') {
                    try {
                        if (function_exists('notificar_competencia_home_destacado_super')) {
                            $codigo_actualizado = $collection_codigos->findOne(['_id' => $obj_id_codigo]);
                            $usuario_id = isset($codigo_actualizado['id_usuario']) ? (string)$codigo_actualizado['id_usuario'] : '';
                            if ($usuario_id) {
                                $emails_enviados = notificar_competencia_home_destacado_super(
                                    $codigo_id,
                                    $usuario_id,
                                    $codigo_actualizado
                                );
                                log_debug("Notificaciones de competencia home enviadas desde destacar_codigo_moderno: $emails_enviados");
                            }
                        }
                    } catch (Exception $e) {
                        if (function_exists('log_error')) {
                            log_error("Error enviando notificaciones desde destacar_codigo_moderno: " . $e->getMessage());
                        }
                    }
                }
                
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            // Registrar error sin detener la ejecución
            log_debug("Error al destacar código: " . $e->getMessage());
            return false;
        }
    }
    
    // Función para enviar notificación de destacado
    function enviar_notificacion_destacado($codigo_id, $tipo_destacado) {
        try {
            // Datos del código y usuario propietario
            $codigo = getCodeByID(new \MongoDB\BSON\ObjectId($codigo_id));
            if(!$codigo) return false;

            $usuario = getObjectUser('_id', $codigo['id_usuario']);
            if(!$usuario) return false;

            $marca = getObjectMarca('nombre_clave', $codigo['marca']);
            $marca_nombre = $marca['nombre'] ?? $codigo['marca'];

            $tipo_texto = $tipo_destacado == 'super' ? 'Super Destacado' : 'Destacado Normal';

            // Email al propietario confirmando el destacado
            $asunto = "¡Tu código ha sido destacado exitosamente!";
            $html = "<h2>¡Felicidades! Tu código ha sido destacado</h2>";
            $html .= "<p>Tu código para <strong>{$marca_nombre}</strong> ha sido destacado como <strong>{$tipo_texto}</strong>.</p>";
            $html .= "<ul><li>Marca: {$marca_nombre}</li><li>Código: {$codigo['codigo']}</li><li>Tipo: {$tipo_texto}</li><li>Prioridad: Sin límite de tiempo (mantienes la primera posición hasta que otro usuario te supere)</li><li>Fecha: ".date('d/m/Y H:i')."</li></ul>";
            $html .= "<p>Tu código ahora aparecerá en primera posición y tendrá mayor visibilidad.</p>";
            // Enlazar directamente a la ficha pública del código en CodigoAmigo
            $link_codigo_amigo = $GLOBALS['website'] . 'de-' . strtolower($codigo['marca']) . '?codigo=' . (string)$codigo['_id'];
            $html .= "<p><a href='" . $link_codigo_amigo . "' style='background:#E30613;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px;'>Ver código destacado</a></p>";

            if (!function_exists('enviarEmailConBrevoYRegistrar')) {
                include_once __DIR__ . '/email_helper.php';
            }
            enviarEmailConBrevoYRegistrar(
                $usuario['mail'] ?? '',
                $usuario['username'] ?? 'Usuario',
                $asunto,
                $html,
                'confirmacion_destacado',
                (string)($usuario['_id'] ?? ''),
                ['codigo_id' => (string)$codigo['_id'], 'marca' => $marca_nombre, 'tipo' => $tipo_destacado]
            );

            // Notificar a competidores de la misma marca para incentivar recuperar la posición #1
            $collection_codigos = getCollectionCodigos();
            $filtro = ['marca' => $codigo['marca'], 'estado' => 0, 'destacado' => ['$ne' => 0]];
            if ($tipo_destacado === 'super') {
                $filtro['destacado_social'] = ['$exists' => true];
            }
            $competidores = get_all_listado_codigos_array($filtro, ['limit' => 50, 'sort' => ['destacado' => -1]]);
            $lista = $competidores['results'] ?? [];
            
            // Array para rastrear emails ya enviados y evitar duplicados
            $emails_enviados = array();
            
            foreach ($lista as $comp) {
                // Evitar enviar al propio dueño que acaba de destacar
                if (isset($comp['id_usuario']) && (string)$comp['id_usuario'] === (string)$codigo['id_usuario']) {
                    continue;
                }
                $u_comp = getObjectUser('_id', new \MongoDB\BSON\ObjectId($comp['id_usuario']));
                if (!$u_comp) { continue; }
                $datos_u = get_array_de_usuario($u_comp);
                
                // Verificar si ya se envió un email a este destinatario
                $email_destinatario = strtolower(trim($datos_u['mail'] ?? ''));
                if (empty($email_destinatario) || isset($emails_enviados[$email_destinatario])) {
                    continue; // Saltar si el email está vacío o ya se envió
                }
                
                // Marcar este email como enviado
                $emails_enviados[$email_destinatario] = true;
                
                $actual_link = isset($_SERVER['HTTP_HOST']) ? "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'] : $GLOBALS['website'];
                if ($tipo_destacado === 'super') {
                    enviar_mail_codigo_no_destacado_home($comp, $datos_u['mail'], $datos_u['username'], $actual_link, $marca_nombre, $marca['imagen'] ?? '', get_array_de_usuario($usuario));
                } else {
                    enviar_mail_codigo_no_destacado($comp, $datos_u['mail'], $datos_u['username'], $actual_link, $marca_nombre, $marca['imagen'] ?? '', get_array_de_usuario($usuario));
                }
            }

            return true;
        } catch(Exception $e) {
            log_debug('Error enviando notificaciones de destacado: ' . $e->getMessage());
            return false;
        }
    }

    function añadir_destacado_codigo ($codigo,$codigo_operacion='BCV') {

        //BCV == destacado normal
        //BCS == destacado social

        //añadir_destacado_codigo

        

        if($codigo_operacion=='BCV'){ //BCV == destacado normal

            try {
                $collection_codigos = getCollectionCodigos();
                //print_r($collection_codigos);
                $updateResult = $collection_codigos->updateOne(
                    ['_id' => new \MongoDB\BSON\ObjectId($codigo["_id"]) ],
                    ['$set' => [
                        'destacado' => strtotime('now'),
                        'tipo_destacado' => 'normal', // Mantener consistencia con nuevo sistema
                        'prioridad_pago' => strtotime('now')
                    ]]
                    );
            } catch(MongoDB\Driver\Exception\WriteException $e) {
                $writeResult = $e->getWriteResult();
                echo "Errores en MongoDB\n";
            }

            // echo "doble o nada";
            // print_r($codigo);
            // echo $codigo_operacion;
            // die;

            /* EMAIL */

            $usuario = getObjectUser('_id', new \MongoDB\BSON\ObjectId($codigo["id_usuario"]));
            $usuario_original = get_array_de_usuario($usuario);


            /* SACO TODOS LOS CODIGOS DE LA MARCA QUE ESTAN PATROCINADOS */

            $array_filtro = array("marca"=>$codigo["marca"]);
            $array_filtro = array_merge($array_filtro, array("estado"=>0));
            $array_filtro = array_merge($array_filtro, array("destacado"=>array('$ne' => 0)));

            $array_skip = array("limit"=>13);
            $array_skip = array_merge($array_skip, array("sort"=>array('destacado' => -1)));

            //TOMO LOS CODIGOS
            $lista_codigos_pre = get_all_listado_codigos_array($array_filtro, $array_skip);
            $lista_codigos_patrocinados = $lista_codigos_pre["results"];

            // Array para rastrear emails ya enviados y evitar duplicados
            $emails_enviados = array();

            foreach($lista_codigos_patrocinados as $listado){

                $usuario = getObjectUser('_id', new \MongoDB\BSON\ObjectId($listado["id_usuario"]));
                $datos_usuario = get_array_de_usuario($usuario);

                if($usuario_original["mail"] != $datos_usuario["mail"]){
                    
                    // Verificar si ya se envió un email a este destinatario
                    $email_destinatario = strtolower(trim($datos_usuario["mail"] ?? ''));
                    if (!empty($email_destinatario) && !isset($emails_enviados[$email_destinatario])) {
                        
                        // Marcar este email como enviado
                        $emails_enviados[$email_destinatario] = true;

                        $codigo_to_show = $collection_codigos->findOne(['_id' => $codigo["_id"]]);
                        $m = getObjectMarca("nombre_clave", $listado["marca"]);
                        $u = getObjectUser('_id', $codigo_to_show["id_usuario"]);
                        $actual_link = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                        enviar_mail_codigo_no_destacado($listado,  $datos_usuario["mail"], $datos_usuario["username"], $actual_link, $m["nombre"], $m["imagen"], $usuario_original);
                    }

                }

            }

        }elseif($codigo_operacion=='BCS'){ //BCS == destacado social (equivalente a super)

            try {
                $collection_codigos = getCollectionCodigos();
                

                $updateResult = $collection_codigos->updateOne(
                    ['_id' => new \MongoDB\BSON\ObjectId($codigo["_id"]) ],
                    ['$set' => [
                        'destacado' => strtotime('now'), 
                        'destacado_social' => strtotime('now'),
                        'tipo_destacado' => 'super', // Mantener consistencia con nuevo sistema
                        'prioridad_pago' => strtotime('now')
                    ]]
                    );


            } catch(MongoDB\Driver\Exception\WriteException $e) {
                $writeResult = $e->getWriteResult();
                echo "Errores en MongoDB\n";
            }

            /* ENVIO CORREOS */

            $usuario = getObjectUser('_id', new \MongoDB\BSON\ObjectId($codigo["id_usuario"]));
            $usuario_original = get_array_de_usuario($usuario);

            /* SACO TODOS LOS CODIGOS DE LA MARCA QUE ESTAN PATROCINADOS */

            $array_filtro = array("marca"=>$codigo["marca"]);
            $array_filtro = array_merge($array_filtro, array("estado"=>0));
            $array_filtro = array_merge($array_filtro, array("destacado_social"=>array('$exists' => true)));
            $array_filtro = array_merge($array_filtro, array("destacado"=>array('$ne' => 0)));

            $array_skip = array("limit"=>13);
            $array_skip = array_merge($array_skip, array("sort"=>array('destacado_social' => -1)));

            //TOMO LOS CODIGOS
            $lista_codigos_pre = get_all_listado_codigos_array($array_filtro, $array_skip);
            $lista_codigos_patrocinados = $lista_codigos_pre["results"];

            // Array para rastrear emails ya enviados y evitar duplicados
            $emails_enviados = array();

            foreach($lista_codigos_patrocinados as $listado){

                $usuario = getObjectUser('_id', new \MongoDB\BSON\ObjectId($listado["id_usuario"]));
                $datos_usuario = get_array_de_usuario($usuario);



                if($usuario_original["mail"] != $datos_usuario["mail"]){
                    
                    // Verificar si ya se envió un email a este destinatario
                    $email_destinatario = strtolower(trim($datos_usuario["mail"] ?? ''));
                    if (!empty($email_destinatario) && !isset($emails_enviados[$email_destinatario])) {
                        
                        // Marcar este email como enviado
                        $emails_enviados[$email_destinatario] = true;

                        $codigo_to_show = $collection_codigos->findOne(['_id' => $codigo["_id"]]);
                        $m = getObjectMarca("nombre_clave", $listado["marca"]);
                        $u = getObjectUser('_id', $codigo_to_show["id_usuario"]);
                        $actual_link = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                        enviar_mail_codigo_no_destacado_home($listado,  $datos_usuario["mail"], $datos_usuario["username"], $actual_link, $m["nombre"], $m["imagen"], $usuario_original);
                    }
                }

            }


        }

    }

function send_mail_elastic($data) {



        $url = "https://api.elasticemail.com/v2/email/send";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $respuesta = curl_exec($ch);
        curl_close ($ch);

        $info_res = json_decode($respuesta);
        return $info_res;


}


function sendToTelegram($anuncio){

    global $arrProv,$arrSubCats,$arrMicro,$arrCatsT;




    if(isset($GLOBALS["SYSTEM_CONF"]) && isset($GLOBALS["SYSTEM_CONF"]['WEBSITE'])){
        if($GLOBALS["SYSTEM_CONF"]['WEBSITE']=="AR"){
            $icono_pais = "";
        }elseif($GLOBALS["SYSTEM_CONF"]['WEBSITE']=="CU"){
            $icono_pais = "";
        }elseif($GLOBALS["SYSTEM_CONF"]['WEBSITE']=="CO"){
            $icono_pais = "";
        }elseif($GLOBALS["SYSTEM_CONF"]['WEBSITE']=="UY"){
            $icono_pais = "🇺🇾";
        }elseif($GLOBALS["SYSTEM_CONF"]['WEBSITE']=="CA"){
            $icono_pais = "🇪🇸";
            $publica = 1;
        }
    } else {
        $icono_pais = "";
    }


    if(isset($anuncio['id_subcategoria']) && $anuncio['id_subcategoria']==371 && isset($anuncio['id_usuario']) && $anuncio['id_usuario']==1732){ //STOCKS SERGI
        $stocks = 1;
        $icono = "📱";
        //$chat_id = "-1001075210999";
        $chat_id = "@casinuevo_stock";
    }elseif(isset($anuncio['id_categoria']) && $anuncio['id_categoria']==1){ //COCHES
        $icono = "🚗";
        //$chat_id = "-1001075210999";
        $chat_id = "@casinuevo_motor";
    }elseif(isset($anuncio['id_categoria']) && $anuncio['id_categoria']==2){ //VIVIENDAS
        $icono = "🏪";
        $chat_id = "@casinuevo_pisos";
    }elseif(isset($anuncio['id_categoria']) && $anuncio['id_categoria']==3){ //ELECTRONICA
        $icono = "📱";
        $chat_id = "@moviles_segunda_mano";
    }elseif(isset($anuncio['id_categoria']) && ($anuncio['id_categoria']==7 || $anuncio['id_categoria']==5 || $anuncio['id_categoria']==4)){ //CASINUEVO
        $icono = "📲💵";
        $chat_id = "@casinuevo";
    }elseif(isset($anuncio['id_categoria']) && ($anuncio['id_categoria']==6 || $anuncio['id_categoria']==8)){ //EMPLEO
        $icono = "⛓";
        //$chat_id = "-1001077892633";
        $chat_id = "@casinuevo_servicios_empleo";

    }elseif(isset($anuncio['id_categoria']) && $anuncio['id_categoria']==321){ //CONTACTOS
        $icono = "💑";
        //$chat_id = "-1001099207555";
        $chat_id = "@casinovios";

    }

    $publica = 1;
    $chat_id = "@codigoamigocom";
    
    // Inicializar variables
    $icono = isset($icono) ? $icono : "📱";
    $icono_pais = isset($icono_pais) ? $icono_pais : "";

    $optimize_name_marca = optimizeUrlPath($anuncio["marca"]);
    $optimize_name_marca = str_replace("-", "", $optimize_name_marca);



    if($chat_id && $publica){

        $caract = "\n♦️ ".$anuncio["numerobeneficio"]." ".$anuncio["descuentos"]." en ".$anuncio["marca"]."\n";

        $tags = isset($tags) ? $tags : "";
        $datos = isset($datos) ? $datos : array("localidad" => "", "provincia" => "");
        $tags.= "\n".$icono_pais." #".($datos["localidad"])." - ".($datos["provincia"]);


        $href_prod = "🔗  https://www.codigoamigo.com/de-".$optimize_name_marca;

        $mensaje= "\n\n".$icono.$icono."\n\n<pre>".recorta_texto_pos($anuncio["descripcion"],160,"..")."</pre>\n".$caract.$tags;


        $mensaje.=$href_prod."\n\n";

        $mensaje.="Más códigos de amigo en <a href='https://telegram.me/".$chat_id."'>".$chat_id."</a>";





        $token = "822797607:AAE-3fPaZsUc1LKKgFaWgqmfLRCPgu1sDdw";



        $bot_url    = "https://api.telegram.org/bot$token/";
        $url = $bot_url."sendMessage?chat_id=".$chat_id."&text=".urlencode($mensaje)."&parse_mode=HTML&disable_notification=true";
        $ret = file_get_contents($url);

    }

}

function formatDateAgo($value)
{
    // Soportar MongoDB\BSON\UTCDateTime además de strings
    if ($value instanceof \MongoDB\BSON\UTCDateTime) {
        $d = $value->toDateTime();
        $time = $d->getTimestamp();
    } else {
        $time = strtotime($value);
        $d = new \DateTime($value);
    }

    $weekDays = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    $months = ['Janvier', 'Février', 'Mars', 'Avril',' Mai', 'Juin', 'Juillet', 'Aout', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];


    if ($time > strtotime('-2 minutes'))
    {
        return 'Hace unos segundos';
    }
    elseif ($time > strtotime('-30 minutes'))
    {
        return 'Hoy, hace ' . floor((strtotime('now') - $time)/60) . ' min';
    }
    elseif ($time > strtotime('today'))
    {
        return $d->format('G:i');
    }
    elseif ($time > strtotime('yesterday'))
    {
        return 'Ayer, ' . $d->format('G:i');
    }
    elseif ($time > strtotime('Esta semana'))
    {
        return $weekDays[$d->format('N') - 1] . ', ' . $d->format('G:i');
    }
    else
    {
        return $d->format('j') . ' ' . $months[$d->format('n') - 1] . ', ' . $d->format('G:i');
    }
}

function formatDateAgoLarge($value)
{
    // Soportar MongoDB\BSON\UTCDateTime además de strings
    if ($value instanceof \MongoDB\BSON\UTCDateTime) {
        $d = $value->toDateTime();
        $time = $d->getTimestamp();
    } else {
        $time = strtotime($value);
        $d = new \DateTime($value);
    }

    $weekDays = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    $months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    if ($time > strtotime('-2 minutes'))
    {
        return 'Hace unos segundos';
    }
    elseif ($time > strtotime('-30 minutes'))
    {
        return 'Hoy, hace ' . floor((strtotime('now') - $time)/60) . ' min';
    }
    elseif ($time > strtotime('today'))
    {
        return 'Hoy, ' . $d->format('d/m/Y H:i');
    }
    elseif ($time > strtotime('yesterday'))
    {
        return 'Ayer, ' . $d->format('d/m/Y H:i');
    }
    elseif ($time > strtotime('-7 days'))
    {
        return $weekDays[$d->format('N') - 1] . ', ' . $d->format('d') . ' ' . $months[$d->format('n') - 1] . ' ' . $d->format('Y');
    }
    else
    {
        return $d->format('d') . ' ' . $months[$d->format('n') - 1] . ' ' . $d->format('Y');
    }
}



    /**********************************************************
     *  PROPIAS DE CÓDIGO
     *********************************************************/

    function get_all_codigos() {

        $collection_codigos = getCollectionCodigos();
        /* Comprobamos num de codigos antes de jugar */

        $count = $collection_codigos->count(['estado' => 0]);

        if($count > 0) {
            $lista_codigos = $collection_codigos->find(['estado' => 0], ['sort' => ['_id' => -1]]);
            $array_codigos = iterator_to_array($lista_codigos);
            return $array_codigos;
        }

    }

    function get_all_listado_codigos($parameter, $value) {

        $collection_codigos = getCollectionCodigos();
        /* Comprobamos num de codigos antes de jugar */
        $count = $collection_codigos->count(['estado' => 0, $parameter => $value]);
        if($count > 0) {

            $lista_codigos = $collection_codigos->find(['estado' => 0,
                $parameter => $value],
                ['sort' => ['_id' => -1]]);

            $array_codigos = iterator_to_array($lista_codigos);
            return $array_codigos;
        }

    }


    function get_all_listado_codigos_by_user($parameter, $value,$id_user) {

        $collection_codigos = getCollectionCodigos();
        /* Comprobamos num de codigos antes de jugar */
        $count = $collection_codigos->count(['estado' => 0, $parameter => $value]);
        if($count > 0) {

            $lista_codigos = $collection_codigos->find([
                'id_usuario' => $id_user,
                $parameter => $value],
                ['sort' => ['_id' => -1]]);

            $array_codigos = iterator_to_array($lista_codigos);
            return $array_codigos;
        }

    }


    function getCodeByID($id_codigo) {
        $collection_codigos = getCollectionCodigos();

        // Una sola consulta usando findOne() ya que buscamos por ID
        $codigo = $collection_codigos->findOne(['_id' => $id_codigo]);

        return $codigo;
    }

    function getCodeByID_prelista($id_codigo) {
        $collection_codigos = getCollectionCodigos();

        // Una sola consulta usando findOne() ya que buscamos por ID
        $codigo = $collection_codigos->findOne(['_id' => $id_codigo]);
        
        if ($codigo) {
            // Retornamos el resultado en un array para mantener compatibilidad
            return [$codigo];
        }
        
        return null;
    }


    function get_all_listado_codigos_filtro($parameter, $value, $limit='', $skip='') {


        $collection_codigos = getCollectionCodigos();
        $count = $collection_codigos->count(['estado' => 0, $parameter => $value]);
        if($count > 0) {
            $lista_codigos = $collection_codigos->find(
                [
                    'estado' => 0,
                    $parameter => $value,
                ],
                [
                    'limit' => $limit,
                    'skip' => $skip,
                    'sort' => ['_id' => -1]
                ]
            );

            $array_codigos = iterator_to_array($lista_codigos);
            return $array_codigos;
        }

    }


    function count_all_listado_codigos_array($array_filtro, $array_skip='',$array_group='',$results=1) {


        $collection_codigos = getCollectionCodigos();

        if(!$array_skip){
            $array_skip = array();
        }


        if($array_filtro){

            $count = $collection_codigos->count($array_filtro);
            $array_codigos["total_number"] = $count;



            return $array_codigos["total_number"];

        }

    }


    /**
     * Genera un filtro MongoDB para excluir códigos cuya fecha_validez haya pasado.
     * Los códigos sin fecha_validez (vacío, null, no existe) se muestran siempre.
     * fecha_validez es un string en formato YYYY-MM-DD, por lo que la comparación de strings funciona.
     *
     * Usa $nor para evitar colisiones con otros filtros que ya usen $or.
     * Lógica: excluir documentos que tengan fecha_validez como string no vacía Y menor que hoy.
     *
     * @return array Filtro listo para array_merge con otros filtros MongoDB
     */
    function get_filtro_no_expirados() {
        $hoy = date('Y-m-d'); // Formato compatible con fecha_validez
        return array(
            '$nor' => array(
                // Excluir códigos cuya fecha_validez sea un string no vacío Y anterior a hoy
                array(
                    'fecha_validez' => array(
                        '$type' => 'string',  // Solo si es string (no null/no existe)
                        '$ne' => '',          // No vacío
                        '$lt' => $hoy         // Anterior a hoy = expirado
                    )
                )
            )
        );
    }

    function get_all_listado_codigos_array($array_filtro, $array_skip='',$array_group='',$results=1) {


        $collection_codigos = getCollectionCodigos();


        if(!$array_skip){
            $array_skip = array();
        }


        if($array_filtro){

            

            $count = $collection_codigos->count($array_filtro);



            $array_codigos["total_number"] = $count;

            if($count > 0 && $results==1) {

                $lista_codigos = $collection_codigos->find($array_filtro,$array_skip);
                $array_codigos["results"] = iterator_to_array($lista_codigos);
            }



            return $array_codigos;

        }

    }

    function get_all_listado_codigos_destacados($parameter, $value, $limit='', $skip='') {


        $collection_codigos = getCollectionCodigos();


        if($limit){
        if($parameter && $value){



            $count = $collection_codigos->count(['estado' => 0, 'destacado' => ['$ne' => 0], $parameter => $value]);


            if($count > 0) {
                $lista_codigos = $collection_codigos->find(
                    [
                        'estado' => 0,
                        'destacado' => ['$ne' => 0],
                        $parameter => $value
                    ],
                    [
                        'limit' => $limit,
                        'skip' => $skip,
                        'sort' => ['destacado' => -1, '_id' => -1],
                    ]
                    );
                $array_codigos = iterator_to_array($lista_codigos);
                return $array_codigos;
            }

        }else{

            $count = $collection_codigos->count(['estado' => 0]);

            if($count > 0) {
                $lista_codigos = $collection_codigos->find(
                    [
                        'estado' => 0,
                        'destacado' => ['$ne' => 0],
                    ],
                    [
                        'limit' => $limit,
                        'skip' => $skip,
                        'sort' => ['destacado' => -1, '_id' => -1],
                    ]
                    );
                $array_codigos = iterator_to_array($lista_codigos);
                return $array_codigos;
            }

        }
        }

    }





    function guardar_token_compra_lead_sin_validar($datos) {

        session_start();

        $_SESSION["compra_lead_sin_validar"]["token_id"] = $datos["token_id"];
        $_SESSION["compra_lead_sin_validar"]["cantidad"] = $datos["cantidad"];
        $_SESSION["compra_lead_sin_validar"]["codigo"] = $datos["codigo"];
        $_SESSION["compra_lead_sin_validar"]["lead_id"] = $datos["lead_id"];

    }

    function guardar_token_compra_lead_validado($datos) {

        session_start();

        $_SESSION["compra_lead_validado"]["token_id"] = $datos["token_id"];
        $_SESSION["compra_lead_validado"]["cantidad"] = $datos["cantidad"];
        $_SESSION["compra_lead_validado"]["codigo"] = $datos["codigo"];
        $_SESSION["compra_lead_validado"]["lead_id"] = $datos["lead_id"];

        //print_x($_SESSION);

    }






    function get_listado_codigos ($parameter, $value, $limit='', $skip='') {

	    $collection_codigos = getCollectionCodigos();
	    $count = $collection_codigos->count(
	        [
	            'estado' => 0,
	            $parameter => $value,
	        ]);
	    if($count > 0) {
	        $lista_codigos = $collection_codigos->find(
	            [
	                'estado' => 0,
	                $parameter => $value,
	            ],
	            [
	                'limit' => $limit,
	                'skip' => $skip,
	                'sort' => ['_id' => -1],
	            ]
	            );
	        $lista_codigos->num = $count;
	        return $lista_codigos;
	    }

	}

	function order_listado_codigos_localizacion($marca) {

	    global $array_mes2;

	    $array_ordenada = array();
	    $lista = get_all_listado_codigos('marca', $marca["nombre_clave"]);

	    foreach ($lista as $index=>$item) {

	        $item_c = optimizeUrlPath($item["provincia"]);


	        $array_ordenada[$item_c][$item_c][] = $item;

	    }

	    return $array_ordenada;

	}

	function order_listado_codigos($marca) {

	    global $array_mes2;

	    $array_ordenada = array();
	    $lista = get_all_listado_codigos('marca', $marca["nombre_clave"]);

	    foreach ($lista as $index=>$item) {

	        $fec = $item["fecha_publicacion"];
	        if (strpos($fec, "-18") !== false) { $fec = str_replace("-18", "-2018", $fec); }
	        if (strpos($fec, "-17") !== false) { $fec = str_replace("-17", "-2017", $fec); }
	        $fecha = date("d-m-Y", strtotime($fec));

	        $mes = date("m", strtotime($fecha));
	        $año = date("Y", strtotime($fecha));

	        $array_ordenada[$año][$array_mes2[$mes]][] = $item;

	    }

	    return $array_ordenada;

	}


	function get_prev_and_next($num_codigos, $codigos_restantes) {

	    //Boton siguiente
	    if($_GET["page"] == "" || $_GET["page"] == 1) { $page = 2; $page_prev = ''; }
	    else { $page = $_GET["page"] + 1; $page_prev = $_GET["page"] - 1;}

	    if($page_prev){
	        if($page_prev>1){
    	        $url_prev = strtok($GLOBALS["actual_url"], '?')."?page=".$page_prev;
	        }else{
	            $url_prev = str_replace("?page=2","",($GLOBALS["actual_url"]));
	        }
    	    $links["prev"] = $url_prev;
	    }

	    $url_next = strtok($GLOBALS["actual_url"], '?')."?page=".$page;


	    $links["next"] = $url_next;

	    return $links;

	}

	function show_buttons_paginate($num_codigos, $codigos_restantes,$marca='') {



        $html = '<div class="row text-center  col-xs-12" style="margin-bottom: 20px;">';



// 	    $html .= ' <button id="show_more">dsa</button>';


// 	    $html .= '</div>';

	    //Boton anterior
	    if(isset($_GET["page"]) && $_GET["page"] > 1) {
	        $page_atras = $_GET["page"] - 1;
	        if($page_atras != 1) { $url_atras = strtok($GLOBALS["actual_url"], '?')."?page=".$page_atras; }
	        else { $url_atras = strtok($GLOBALS["actual_url"], '?'); }
	        $html .= '<a href="'.$url_atras.'" class="btn btn-primary btn-custom"> <i class="fas fa-angle-left"></i> Códigos anteriores</a>';
	    }

	    //Boton siguiente
	    if(!isset($_GET["page"]) || $_GET["page"] < 15){
    	    if((!isset($_GET["page"]) || $_GET["page"] == "" || $_GET["page"] == 1)) { $page = 2; }
    	    else { $page = $_GET["page"] + 1; }
    	    // Inicializar variable global si no está definida
    	    if (!isset($GLOBALS["actual_url"])) {
    	        $GLOBALS["actual_url"] = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    	    }
    	    $url = strtok($GLOBALS["actual_url"], '?')."?page=".$page;
    	    if($num_codigos > 10 && $codigos_restantes >= 1) {
    	        $html .= ' <a href="'.$url.'" class="text-center btn btn-primary btn-custom">Ver más códigos descuento de '.$marca["nombre"].'  <i class="fas fa-angle-right"></i></a>';
    	    }

    	    $html .= '</div>';
	    }

        echo $html;

	}







	function rich_snippet_page () { ?>
	    <?php
	        global $rating_count_rs, $rating_value_rs, $url_logo_rs, $url_marca_rs, $title_marca_rs;


	        $url_logo_rs = str_replace("https://www.codigoamigo.comhttps","https",$url_logo_rs);
	    ?>
	    <script type="application/ld+json">
            {
                "@context": "http://schema.org",
                "@type": "WebPage",
                "name": "<?php echo $title_marca_rs; ?>",
                "url": "<?php echo $url_marca_rs; ?>",
                "image": "<?php echo $url_logo_rs; ?>",
                "aggregateRating": {
                    "@type": "AggregateRating",
                    "ratingValue": "<?php echo $rating_value_rs; ?>",
                    "ratingCount": "<?php echo $rating_count_rs; ?>"
 	            }
            }
        </script>

	<?php }










/********************************************************************
 * AYUDA
 *******************************************************************/


function get_skip_patrocinados_in_pagination() {

    if(isset($_GET["page"]) && $_GET["page"] > 1) { $skip = ($_GET["page"] - 1) * 3; }
    else { $skip = 0; }
    return $skip;

}


function get_skip_in_pagination() {

    if(isset($_GET["page"]) && $_GET["page"] > 1) { $skip = ($_GET["page"] - 1) * 10; }
    else { $skip = 0; }
    return $skip;

}

function print_x($array) {

    echo "<pre>";
    print_r($array);

}

function get_date_today() {

    date_default_timezone_set('Europe/London');
    $hoy = getdate();

    $dia = $hoy["mday"];
    $mes = $hoy["mon"];
    if($mes < 10) { $mes = "0".$mes; }
    $año = $hoy["year"];
    $hora = $hoy["hours"];
    $min = $hoy["minutes"];

    $fecha = $dia."/".$mes."/".$año." ".$hora.":".$min;
    return $fecha;

}

/* Funciones encriptación */
function encriptar($cadena){
    $key = 'codigoamigo';
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($cadena, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

function desencriptar($cadena){
    $key = 'codigoamigo';
    
    // Si es un hash MD5 (32 caracteres hexadecimales), es un código antiguo
    if (preg_match('/^[a-f0-9]{32}$/', $cadena)) {
        // Para códigos antiguos, intentar buscar en la base de datos
        // o devolver un valor que indique que es un código antiguo
        return false; // Código antiguo no válido
    }
    
    try {
        $data = base64_decode($cadena);
        if (strlen($data) < 16) {
            return false; // Datos insuficientes
        }
        
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
        return $decrypted;
    } catch (Exception $e) {
        return false; // Error en desencriptación
    }
}

function get_posicion_codigo_en_marca($codigo_id, $marca_clave) {
    // Obtener códigos destacados de la marca (igual que en la página de marca)
    $array_filtro_destacados = array(
        "marca" => $marca_clave, 
        "estado" => 0, 
        '$or' => array(
            array("destacado_social" => array('$ne' => 0)),
            array("destacado" => array('$ne' => 0))
        )
    );
    $array_opciones_destacados = array(
        'limit' => 10,
        'sort' => array('destacado_social' => -1, 'destacado' => -1, '_id' => -1)
    );
    
    $lista_codigos_destacados = get_all_listado_codigos_array($array_filtro_destacados, $array_opciones_destacados);
    $codigos_destacados = isset($lista_codigos_destacados["results"]) ? $lista_codigos_destacados["results"] : [];
    
    // Obtener códigos normales (excluyendo los destacados y los destacados sociales)
    $array_filtro_normales = array("marca" => $marca_clave, "estado" => 0, "destacado" => 0, "destacado_social" => 0);
    $array_opciones_normales = array(
        'limit' => 20,
        'sort' => array('_id' => -1)
    );

    $lista_codigos_normales = get_all_listado_codigos_array($array_filtro_normales, $array_opciones_normales);
    $codigos_normales = isset($lista_codigos_normales["results"]) ? $lista_codigos_normales["results"] : [];
    
    // Combinar códigos destacados primero, luego normales (igual que en la página de marca)
    $codigos = array_merge($codigos_destacados, $codigos_normales);
    
    // Buscar la posición del código específico
    $posicion = 1;
    foreach($codigos as $codigo) {
        if((string)$codigo['_id'] === (string)$codigo_id) {
            return $posicion;
        }
        $posicion++;
    }
    
    // Si no se encuentra en los primeros 30 (10 destacados + 20 normales), devolver una posición alta
    return 999;
}

/**
 * Actualiza la visibilidad de un código basada en su posición real en la marca
 * 
 * @param string $codigo_id ID del código
 * @param string $marca_clave Clave de la marca
 * @return bool True si se actualizó correctamente
 */
function updateCodeVisibilityByPosition($codigo_id, $marca_clave) {
    try {
        $posicion = get_posicion_codigo_en_marca($codigo_id, $marca_clave);
        
        // Determinar visibilidad basada en posición
        $visibilidad = 'baja'; // Por defecto
        if ($posicion == 1) {
            $visibilidad = 'alta';
        } elseif ($posicion == 2) {
            $visibilidad = 'media';
        } elseif ($posicion <= 5) {
            $visibilidad = 'baja';
        } else {
            $visibilidad = 'baja'; // Posiciones muy altas también son baja visibilidad
        }
        
        // Actualizar en la base de datos
        $db = createConnection();
        $collection = $db->selectCollection('codigos');
        
        $result = $collection->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($codigo_id)],
            [
                '$set' => [
                    'visibilidad' => $visibilidad,
                    'posicion_real' => $posicion,
                    'fecha_modificacion' => date('Y-m-d H:i:s'),
                    'updated_at' => new MongoDB\BSON\UTCDateTime()
                ]
            ]
        );
        
        if ($result->getModifiedCount() > 0) {
            log_info("Visibilidad actualizada", [
                'codigo_id' => $codigo_id,
                'marca' => $marca_clave,
                'posicion' => $posicion,
                'visibilidad' => $visibilidad
            ]);
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        log_error("Error actualizando visibilidad", [
            'codigo_id' => $codigo_id,
            'marca' => $marca_clave,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * Actualiza la visibilidad de todos los códigos de una marca
 * 
 * @param string $marca_clave Clave de la marca
 * @return int Número de códigos actualizados
 */
function updateAllCodesVisibilityInBrand($marca_clave) {
    try {
        $db = createConnection();
        $collection = $db->selectCollection('codigos');
        
        // Obtener todos los códigos activos de la marca
        $codigos = $collection->find([
            'marca' => $marca_clave,
            'estado' => 0
        ]);
        
        $actualizados = 0;
        foreach ($codigos as $codigo) {
            if (updateCodeVisibilityByPosition((string)$codigo['_id'], $marca_clave)) {
                $actualizados++;
            }
        }
        
        log_info("Visibilidad actualizada para marca", [
            'marca' => $marca_clave,
            'codigos_actualizados' => $actualizados
        ]);
        
        return $actualizados;
    } catch (Exception $e) {
        log_error("Error actualizando visibilidad de marca", [
            'marca' => $marca_clave,
            'error' => $e->getMessage()
        ]);
        return 0;
    }
}

// Función para obtener un código por ID
function getObjectCodigo($codigo_id) {
    $db = createConnection();
    
    try {
        $collection = $db->selectCollection('codigos');
        $codigo = $collection->findOne(['_id' => new MongoDB\BSON\ObjectId($codigo_id)]);
        
        if ($codigo) {
            return iterator_to_array($codigo);
        }
    } catch (Exception $e) {
        log_debug("Error al obtener código: " . $e->getMessage());
    }
    
    return false;
}

// Función para actualizar un código
function updateCodigo($codigo_id, $update_data) {
    $db = createConnection();
    
    try {
        $collection = $db->selectCollection('codigos');
        
        // Obtener el código original para preservar campos importantes
        $codigo_original = $collection->findOne(['_id' => new MongoDB\BSON\ObjectId($codigo_id)]);
        
        if (!$codigo_original) {
            return false;
        }
        
        // Preservar campos importantes que no deben perderse
        $preserved_fields = [
            'destacado' => $codigo_original['destacado'] ?? 0,
            'destacado_social' => $codigo_original['destacado_social'] ?? 0,
            'id_usuario' => $codigo_original['id_usuario'],
            'estado' => $codigo_original['estado'] ?? 0,
            'totalclicks' => $codigo_original['totalclicks'] ?? 0,
            'fecha_publicacion' => $codigo_original['fecha_publicacion']
        ];
        
        // Si se está actualizando la marca, buscar o crear marca existente
        if (isset($update_data['marca']) && !empty($update_data['marca'])) {
            $marca_normalizada = normalizeMarcaName($update_data['marca']);
            $marca_existente = findOrCreateMarca(
                $update_data['marca'],
                $marca_normalizada,
                $update_data['url_imagen'] ?? null,
                $update_data['categoria_valor'] ?? null,
                $update_data['categoria_clave'] ?? null
            );
            $update_data['marca'] = $marca_existente['nombre_clave'];
        }
        
        // Combinar datos preservados con datos de actualización
        $final_update_data = array_merge($preserved_fields, $update_data);
        
        // Añadir campos de auditoría
        $final_update_data['fecha_modificacion'] = date('Y-m-d H:i:s');
        $final_update_data['updated_at'] = new \MongoDB\BSON\UTCDateTime();
        
        $result = $collection->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($codigo_id)],
            ['$set' => $final_update_data]
        );
        
        return $result->getModifiedCount() > 0;
    } catch (Exception $e) {
        log_debug("Error al actualizar código: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene datos del usuario priorizando la sesión sobre la base de datos
 */
function getObjectUserWithSession($parameter, $value) {
    $usuario = getObjectUser($parameter, $value);
    
    // Si la sesión tiene datos más recientes, usarlos
    if (isset($_SESSION["img"]) && !empty($_SESSION["img"])) {
        $usuario["img"] = $_SESSION["img"];
    }
    if (isset($_SESSION["username"]) && !empty($_SESSION["username"])) {
        $usuario["username"] = $_SESSION["username"];
    }
    
    return $usuario;
}

// Función auxiliar para normalizar nombres de marcas
function normalizeMarcaName($marca_name) {
    // Convertir a minúsculas y limpiar caracteres especiales
    $normalized = strtolower(trim($marca_name));
    
    // Reemplazar caracteres especiales comunes
    $normalized = str_replace(['.', ',', ' ', '-', '_'], '', $normalized);
    
    // Casos especiales conocidos
    $special_cases = [
        'make.com' => 'makecom',
        'make.com' => 'makecom',
        'social car' => 'socialcar',
        'social-car' => 'socialcar',
        'social_car' => 'socialcar'
    ];
    
    if (isset($special_cases[strtolower($marca_name)])) {
        return $special_cases[strtolower($marca_name)];
    }
    
    return $normalized;
}

// Función auxiliar para buscar o crear marca
function findOrCreateMarca($marca_name, $marca_normalizada, $imagen_url = null, $categoria = null, $categoria_clave = null) {
    log_debug("findOrCreateMarca - Marca: $marca_name, Imagen: $imagen_url, Categoria: $categoria, Categoria clave: $categoria_clave");

    $db = createConnection();
    $collection_marcas = $db->selectCollection('marcas');

    // Buscar marca existente por nombre_clave normalizado
    $marca_existente = $collection_marcas->findOne(['nombre_clave' => $marca_normalizada]);

    if ($marca_existente) {
        return iterator_to_array($marca_existente);
    }

    // Si no existe, buscar por nombre exacto (sin normalizar)
    $marca_exacta = $collection_marcas->findOne(['nombre' => $marca_name]);

    if ($marca_exacta) {
        return iterator_to_array($marca_exacta);
    }

    // Si no existe ninguna, crear nueva marca
    $nueva_marca = [
        'estado' => 1,
        'nombre' => $marca_name,
        'nombre_clave' => $marca_normalizada,
        'categoria' => $categoria ?: 'General',
        'categoria_clave' => $categoria_clave ?: 'general',
        'imagen' => $imagen_url ?: '/img/no_image.png',
        'descripción' => '',
        'descripción_larga' => '',
        'fecha_publicacion' => date('d-m-Y H:i', strtotime('now')),
        'usuario_creador' => $_SESSION["user_id"] ?? 'system',
        'url' => '',
        'url_register' => '',
        'aviso' => 'Marca creada por usuario'
    ];

    log_debug("Creando nueva marca con imagen: " . ($imagen_url ?: '/img/no_image.png') . ", categoria: " . ($categoria ?: 'General'));

    $result = $collection_marcas->insertOne($nueva_marca);

    // Retornar la marca recién creada
    $marca_creada = $collection_marcas->findOne(['_id' => $result->getInsertedId()]);
    return iterator_to_array($marca_creada);
}

?>