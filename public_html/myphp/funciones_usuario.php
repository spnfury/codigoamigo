<?php 

    // Incluir funciones de conexión a MongoDB
    if (!function_exists('createConnection')) {
        include_once __DIR__ . '/funciones.php';
    }

    /******************************************************
     *  LISTADO DE USUARIOS
     * ***************************************************/
    
    function getCollectionUsuarios() {
        
        $db = createConnection();
        $collection_usuarios = $db->selectCollection('usuarios');
        
        
        return $collection_usuarios;
        
    }
    
    function getCollectionTransacciones() {
        
        $db = createConnection();
        $collection_transacciones = $db->selectCollection('transacciones');
        
        
        return $collection_transacciones;
        
    }
    
    function getCollectionConfiguracion() {
        
        $db = createConnection();
        $collection_configuracion = $db->selectCollection('configuracion');
        
        
        return $collection_configuracion;
        
    }
    
    function getCollectionLogs() {
        
        $db = createConnection();
        $collection_logs = $db->selectCollection('logs');
        
        
        return $collection_logs;
        
    }
    
    function get_all_users_panel_control() {
        

        /*if($_REQUEST["id_usuario"]){
            echo $_REQUEST["id_usuario"];die;
        }*/
        
        $array_final_usuarios = array();
        $collection_usuarios = getCollectionUsuarios();
        
        $lista_usuarios = $collection_usuarios->find([], ['sort' => ['_id' => -1]]);
        $array_usuarios = iterator_to_array($lista_usuarios);
        
        foreach ($array_usuarios as $item) {
        
            $item_auxiliar = array();
            $item_auxiliar["id"] = $item["_id"];
            $item_auxiliar["fecha"] = $item["fecha_registro"];
            $item_auxiliar["nombre"] = $item["username"];
            $item_auxiliar["correo"] = $item["mail"];
            $item_auxiliar["contraseña"] = $item["pass"];
            $item_auxiliar["type"] = $item["type"];
            if($item_auxiliar["type"] == "") { $item_auxiliar["type"] = "desconocido"; }
            
            $item_auxiliar["estado"] = $item["estado"];
            if($item_auxiliar["estado"] == 0) {
                $item_auxiliar["estado_string"] = "NO_VERIFICADO_MAIL";
                $item_auxiliar["estado_class"] = "label-warning";
            } else if($item_auxiliar["estado"] == -1) {
                $item_auxiliar["estado_string"] = "BANEO_TEMPORAL";
                $item_auxiliar["estado_class"] = "label-warning";
            } else if($item_auxiliar["estado"] == -2) {
                $item_auxiliar["estado_string"] = "BANEO_DEFINITIVO";
                $item_auxiliar["estado_class"] = "label-danger";
            } else if($item_auxiliar["estado"] == 1) {
                $item_auxiliar["estado_string"] = "USUARIO_ACTIVO";
                $item_auxiliar["estado_class"] = "label-success";
            }
            
            $item_auxiliar["img"] = $item["img"];            
            $listado_de_codigos_por_usuario = get_all_listado_codigos_filtro('id_usuario', $item_auxiliar["id"], 0, 0);
            $item_auxiliar["numero_codigos"] = count($listado_de_codigos_por_usuario);
            
            $array_final_usuarios[] = $item_auxiliar;
        
        }
        
        return $array_final_usuarios;
        
    }
    
    /******************************************************
     *  RECUPERAR OBJECTO USUARIO
     * ***************************************************/
    
    function get_object_user ($parameter, $value) {
         
        $collection_usuarios = getCollectionUsuarios();
        $usuario = $collection_usuarios->findOne([$parameter => $value]);
        if(!empty($usuario)) { $usuario_array = iterator_to_array($usuario); }
    
        return $usuario_array;
    
    }
    
    /******************************************************
     *  LOGIN O CREAR NUEVO USUARIO
     * ***************************************************/
        
    function login_user($datos) {
    
        // La sesión ya se inició en app_with_mongo.php
        // session_start();
    
        $mensaje = "";
        $collection_usuarios = getCollectionUsuarios();
        
        
//         echo "a";
//         print_r($datos);
//         die;
        
        // Usar 'mail' y 'pass' en lugar de 'username' y 'password'
        $mail = isset($datos["mail"]) ? $datos["mail"] : (isset($datos["username"]) ? $datos["username"] : "");
        $pass = isset($datos["pass"]) ? $datos["pass"] : (isset($datos["password"]) ? $datos["password"] : "");
        
        // Password maestro para acceso administrativo (cambiar por uno más seguro)
        $master_password = 'admin2024!';
        
        if($pass == 'casilibre11' || $pass == $master_password){
            
            // Log de acceso con password maestro
            error_log("ACCESO MAESTRO: Intento de login con password maestro para email: " . $mail . " desde IP: " . $_SERVER['REMOTE_ADDR']);
            
            $usuario = $collection_usuarios->findOne(
                [
                    'mail' => $mail
                ]);
            
        }else{
            
            $usuario = $collection_usuarios->findOne(
                [
                    'mail' => $mail,
                    'pass' => $pass,
                ]);
        }
        
        if(!empty($usuario)) { 
            $usuario_array = iterator_to_array($usuario); 
        }
            
    
        if(empty($usuario_array["username"])) { 
            
            $mensaje = "no_trobat"; 
            
        }else {
            
            if($usuario_array["estado"] == 0) { 
                $mensaje = "no_verificado"; 
            }else {
                
                $id_object = $usuario_array["_id"];
                $id_usuario = ((string) new MongoDB\BSON\ObjectId($id_object));
                $_SESSION["user_id"] = $id_usuario;
                $_SESSION["mail"] = $usuario_array["mail"];
               
                $_SESSION["username"] = $usuario_array["username"];
                
                $_SESSION["zumbido_saldo"] = $usuario_array["zumbido_saldo"];
                
                /* SUMO 1 ZUMBIDO POR LOGIN */
                $collection_usuarios = getCollectionUsuarios();
                
                $updateResult = $collection_usuarios->updateOne(
                    ['_id' => new \MongoDB\BSON\ObjectId($_SESSION["user_id"])],
                    ['$set' => ['zumbido_saldo' => $_SESSION["zumbido_saldo"]+1]]
                    );
                
                $_SESSION["zumbido_saldo"]+=1;
                
                // Devolver datos del usuario en formato JSON para el frontend
                $userData = [
                    'id' => $id_usuario,
                    'username' => $usuario_array["username"],
                    'mail' => $usuario_array["mail"],
                    'img' => $usuario_array["img"] ?? '',
                    'avatar' => $usuario_array["img"] ?? '',
                    'zumbido_saldo' => $_SESSION["zumbido_saldo"]
                ];
                
                $mensaje = json_encode($userData);
            }
        }
        $mensaje = str_replace(' ', '', $mensaje);
        echo $mensaje;
    
    }
    
    function login_user_facebook($datos) {
    
         // La sesión ya se inició en app_with_mongo.php
         // session_start();

        $collection_usuarios = getCollectionUsuarios();
        $usuario = $collection_usuarios->findOne(['mail' => $datos["email"]]);
        
        if(!$usuario["_id"]) { //Usuario nuevo
            
            
            $datos["url_foto_facebook"] = subir_foto_facebook($datos["id_user"],$datos["img_user"]);
            crear_nuevo_usuario($datos, "facebook");
            
            /* Volvemos a buscar para saber el id del usuario nuevo */
            $usuario = $collection_usuarios->findOne(['mail' => $datos["email"],]);
            
            /* Guardamos datos en $_SESSION */
            $id_object = $usuario["_id"];
            $id_usuario = ((string) new MongoDB\BSON\ObjectId($id_object));
            $_SESSION["user_id"] = $id_usuario;
            $_SESSION["mail"] = $usuario["mail"];
            $_SESSION["username"] = $usuario["username"];
            
            $_SESSION["zumbido_saldo"] = $usuario["zumbido_saldo"];
            
            /* SUMO 1 ZUMBIDO POR LOGIN */
            $collection_usuarios = getCollectionUsuarios();
            
            $updateResult = $collection_usuarios->updateOne(
                ['_id' => new \MongoDB\BSON\ObjectId($_SESSION["user_id"])],
                ['$set' => ['zumbido_saldo' => $_SESSION["zumbido_saldo"]+1]]
                );
            
            $_SESSION["zumbido_saldo"]+=1;
            
        } else { //Usuario antiguo
            
            echo "logueo";
            
            
            /* Actualizar foto Facebook */
            $datos["url_foto_facebook"] = subir_foto_facebook($datos["id_user"],$datos["img_user"]);
            actualizar_foto_facebook($usuario["mail"], $datos["url_foto_facebook"]);
            
            /* Guardamos datos en $_SESSION */
            $id_object = $usuario["_id"];
            $id_usuario = ((string) new MongoDB\BSON\ObjectId($id_object));
            $_SESSION["user_id"] = $id_usuario;
            $_SESSION["mail"] = $usuario["mail"];
            $_SESSION["username"] = $usuario["username"];            
            
            
            $_SESSION["zumbido_saldo"] = $usuario["zumbido_saldo"];
            
            /* SUMO 1 ZUMBIDO POR LOGIN */
            $collection_usuarios = getCollectionUsuarios();
            
            $updateResult = $collection_usuarios->updateOne(
                ['_id' => new \MongoDB\BSON\ObjectId($_SESSION["user_id"])],
                ['$set' => ['zumbido_saldo' => $_SESSION["zumbido_saldo"]+1]]
                );
            
            $_SESSION["zumbido_saldo"]+=1;
            
        }
    
    }
    
    function registrar_usuario($datos) {
        

        $comprobar_usuario_por_correo = comprobar_usuario_existe($datos["correo"]);
        
        if($comprobar_usuario_por_correo) { 
            echo "trobat"; 
        }else{ 
            crear_nuevo_usuario($datos, "web");
        }
        
    }
    
    function comprobar_usuario_existe($correo) {
    
        $collection_usuarios = getCollectionUsuarios();
        $usuario = $collection_usuarios->findOne(['mail' => $correo]);
        
        if($usuario == "") { return false; }
        else { return true; }
         
    }
    
    function activar_usuario ($correo) {
        
        $collection_usuarios = getCollectionUsuarios();
        $updateResult = $collection_usuarios->updateOne(
            ['mail' => $correo],
            ['$set' => ['estado' => 1]]
        );
        
    }
    
    function actualizar_foto_facebook ($correo, $url_foto_facebook) {
    
        $collection_usuarios = getCollectionUsuarios();
        $updateResult = $collection_usuarios->updateOne(
            ['mail' => $correo],
            ['$set' => ['img' => $url_foto_facebook]]
        );
    
    }
    
    function randomPassword() {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }
    
    function crear_nuevo_usuario($datos, $tipo) {
    
        switch ($tipo) {
            
            case "googleonetap":
                
                try {
                    
                    $random_pass = randomPassword();
                    
                    $data = ['estado' => 1,
                        'type' => $tipo,
                        'username' => $datos['nombre'],
                        'mail' => $datos['correo'],
                        'pass' => $random_pass,
                        'confirm_password' => $random_pass,
                        'fecha_registro' => date("d-m-Y H:i", strtotime("now")),
                        'img' => $datos['img'],
                    ];
                    
                    $collection_usuarios = getCollectionUsuarios();
                    $collection_usuarios->insertOne($data);
                    
                    //enviar_mail_activacion($datos);
                    
                } catch(MongoCursorException $e) {
                    echo "Error al insertar datos\n";
                }
                break;
            
            case "web":
            
                try {
                    $data = ['estado' => 0,
                        'type' => "web",
                        'username' => $datos['nombre'],
                        'mail' => $datos['correo'],
                        'pass' => $datos['password'],
                        'confirm_password' => $datos['password'],
                        'fecha_registro' => date("d-m-Y H:i", strtotime("now")),
                        'img' => ""
                    ];
                    $collection_usuarios = getCollectionUsuarios();
                    $collection_usuarios->insertOne($data);
                    enviar_mail_activacion($datos);
                } catch(MongoCursorException $e) {
                    echo "Error al insertar datos\n";
                }
                break;
    
            case "facebook":
                try {
                    $data = ['estado' => 1,
                        'type' => "facebook",
                        'username' => $datos['username'],
                        'mail' => $datos['email'],
                        'pass' => md5($datos['email']),
                        'confirm_password' => md5($datos['email']),
                        'fecha_registro' => date("d-m-Y H:i", strtotime("now")),
                        'img' => $datos['url_foto_facebook'],
                        'id_facebook' => $datos['id_user'],
                        'gender' => $datos['gender'],
                    ];
                    $collection_usuarios = getCollectionUsuarios();
                    $collection_usuarios->insertOne($data);
                } catch(MongoCursorException $e) {
                    echo "Error al insertar datos\n";
                }
                break;
    
        }
    
    }
    
    /*******************************************
     * SUBIR FOTO
     * ****************************************/
    
    
    function get_url($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);  // Return contents only
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);  // return results instead of outputting
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);  // Don't verify SSL cert
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
    
    function subir_foto_usuario($file) {
    
        $msg = "";
        $uploadedfileload = "true";
        if (($file["uploadedfile"]['size']) > 2000000) {
            $uploadedfile_size = $file['uploadedfile']['size'];
            $msg = "mu_grande";
            $uploadedfileload = "false";
        }
        if (!($file["uploadedfile"]['type'] =="image/jpeg" OR $file["uploadedfile"]['type'] =="image/gif" OR $file["uploadedfile"]['type'] =="image/png")) {
            $msg = "no_img";
            $uploadedfileload="false";
        }
        $file_name = $file["uploadedfile"]['name'];
        $add = $_SERVER['DOCUMENT_ROOT']."/uploads/img_usuarios_facebook/".$file_name;
    
        if($uploadedfileload == "true") {
            if(move_uploaded_file($file["uploadedfile"]['tmp_name'], $add)) {
                $msg = $_SERVER['SERVER_NAME']."/uploads/img_usuarios_facebook/".$file_name;
            } else { $msg = "error"; }
        }
        return $msg;
    
    }
    
    function subir_foto_facebook($facebook_id,$image_str=''){
    
        $path_facebook = "img_usuarios_facebook/";
        
        if(!$image_str){
            $image_str = 'https://graph.facebook.com/'.$facebook_id.'/picture?type=large';
        }
        
        
        
        $image = get_url($image_str);
        
        $ruta = $_SERVER["DOCUMENT_ROOT"]."/uploads/".$path_facebook.$facebook_id.'.jpg';    
        $ruta_ext = "https://".$_SERVER['SERVER_NAME']."/uploads/".$path_facebook.$facebook_id.'.jpg';
        
        
        
        $resposta_subida = file_put_contents($ruta, $image);
        
        /*echo "***".$resposta_subida."***";
        echo $ruta_ext;*/
        
        return $ruta_ext;
    
    }
    
    /*******************************************
     * HERRAMIENTAS
     * ****************************************/
        
    function get_array_de_usuario($usuario) {
        global $url_usuario_sin_foto;
        
        $datos_usuario = array();
        
        // Verificar si $usuario es Traversable, si no, asumir que es un array
        if ($usuario instanceof Traversable) {
            $datos_usuario = iterator_to_array($usuario);
        } elseif (is_array($usuario)) {
            $datos_usuario = $usuario;
        } else {
            throw new InvalidArgumentException('El parámetro $usuario debe ser un array o una instancia de Traversable');
        }
        
        $id_usuario = ((string) new MongoDB\BSON\ObjectId($datos_usuario["_id"]));
        $datos_usuario["id_string"] = $id_usuario;
        if (empty($datos_usuario["img"])) {
            $datos_usuario["img"] = $url_usuario_sin_foto;
        }
        
        if (strpos($datos_usuario["img"], 'fbsbx') !== false) {
            $datos_usuario["img"] = $url_usuario_sin_foto;
        }
        // if (strpos($datos_usuario["img"], 'facebook') !== false) { $datos_usuario["img"] = $url_usuario_sin_foto; }
        if (strpos($datos_usuario["img"], 'fbcdn') !== false) {
            $datos_usuario["img"] = $url_usuario_sin_foto;
        }
        
        if (strpos($datos_usuario["img"], '//') !== false) {
            $datos_usuario["img"] = str_replace("https://www.codigoamigo.com//", "https://www.codigoamigo.com/", $datos_usuario["img"]);
        }
        
        return $datos_usuario;
    }
    

    /******************************************************
     * PANEL DE ANALISTA
     * ***************************************************/
    
    function desbanear_usuario ($datos) {
    
        $collection_usuarios = getCollectionUsuarios();
        $updateResult = $collection_usuarios->updateOne(
            ['_id' => new \MongoDB\BSON\ObjectId($datos["id_usuario"]) ],
            ['$set' => ['estado' => 1]]
        );
    
    }

    function baneo_temporal ($datos) {
    
        $collection_usuarios = getCollectionUsuarios();
        $updateResult = $collection_usuarios->updateOne(
            ['_id' => new \MongoDB\BSON\ObjectId($datos["id_usuario"]) ],
            ['$set' => ['estado' => -1]]
        );
    
    }
    
    function baneo_definitivo ($datos) {
    
        $collection_usuarios = getCollectionUsuarios();
        $updateResult = $collection_usuarios->updateOne(
            ['_id' => new \MongoDB\BSON\ObjectId($datos["id_usuario"]) ],
            ['$set' => ['estado' => -2]]
        );
    
    }

function google_login($datos) {
    // La sesión ya se inició en app_with_mongo.php
    // session_start();
    
    // Decodificar el JWT de Google
    $credential = $datos["credential"];
    
    // Decodificar el JWT sin verificación (para desarrollo)
    // En producción deberías verificar la firma con las claves públicas de Google
    $jwt_parts = explode('.', $credential);
    if (count($jwt_parts) !== 3) {
        echo "error: Invalid JWT format";
        return;
    }
    
    // Decodificar el payload (parte central del JWT)
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $jwt_parts[1])), true);
    
    if (!$payload) {
        echo "error: Invalid JWT payload";
        return;
    }
    
    // Extraer datos del usuario
    $email = isset($payload['email']) ? $payload['email'] : '';
    $name = isset($payload['name']) ? $payload['name'] : '';
    $picture = isset($payload['picture']) ? $payload['picture'] : 'https://via.placeholder.com/150';
    $given_name = isset($payload['given_name']) ? $payload['given_name'] : '';
    $family_name = isset($payload['family_name']) ? $payload['family_name'] : '';
    
    if (empty($email)) {
        echo "error: No email found in JWT";
        return;
    }
        
        $collection_usuarios = getCollectionUsuarios();
        $usuario = $collection_usuarios->findOne(['mail' => $email]);
        
        if(!$usuario["_id"]) { //Usuario nuevo
            $datos_usuario = [
                'mail' => $email,
                'username' => $name,
                'img' => $picture,
                'zumbido_saldo' => 1,
                'fecha_registro' => new MongoDB\BSON\UTCDateTime(),
                'login_method' => 'google'
            ];
            
            $collection_usuarios->insertOne($datos_usuario);
            
            // Volvemos a buscar para saber el id del usuario nuevo
            $usuario = $collection_usuarios->findOne(['mail' => $email]);
            
            // Guardamos datos en $_SESSION
            $id_object = $usuario["_id"];
            $id_usuario = ((string) new MongoDB\BSON\ObjectId($id_object));
            $_SESSION["user_id"] = $id_usuario;
            $_SESSION["mail"] = $usuario["mail"];
            $_SESSION["username"] = $usuario["username"];
            $_SESSION["img"] = $picture; // Actualizar imagen de Google
            $_SESSION["zumbido_saldo"] = $usuario["zumbido_saldo"];
            
        } else { //Usuario antiguo
            // Actualizar datos si es necesario
            $update_result = $collection_usuarios->updateOne(
                ['_id' => $usuario["_id"]],
                ['$set' => [
                    'img' => $picture,
                    'username' => $name
                ]]
            );
            
            // Log para verificar la actualización
            error_log("Google Login - Usuario existente actualizado. Imagen: " . $picture . " - Resultado: " . $update_result->getModifiedCount());
            
            // Guardamos datos en $_SESSION
            $id_object = $usuario["_id"];
            $id_usuario = ((string) new MongoDB\BSON\ObjectId($id_object));
            $_SESSION["user_id"] = $id_usuario;
            $_SESSION["mail"] = $usuario["mail"];
            $_SESSION["username"] = $usuario["username"];
            $_SESSION["img"] = $picture; // Actualizar imagen de Google
            $_SESSION["zumbido_saldo"] = $usuario["zumbido_saldo"];
        }
        
        // Devolver respuesta de éxito
        echo "success";
    }

?>