<?php

    // Incluir funciones de conexión a MongoDB
    if (!function_exists('createConnection')) {
        include_once __DIR__ . '/funciones.php';
    }

    // Importar clases de Google Client
    use Google\Client;

    /******************************************************
     *  LISTADO DE USUARIOS
     * ***************************************************/
    
    function getCollectionUsuarios() {

        $db = createConnection();
        if (!$db) {
            echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
            return null;
        }

        try {
            $collection_usuarios = $db->selectCollection('usuarios');
            return $collection_usuarios;
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'error' => 'Error al obtener colección de usuarios: ' . $e->getMessage()]);
            return null;
        }

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
    
    function getCollectionEmailLogs() {
        
        $db = createConnection();
        $collection_email_logs = $db->selectCollection('email_logs');
        
        return $collection_email_logs;
        
    }

    function getCollectionFavoritos() {
        
        $db = createConnection();
        $collection_favoritos = $db->selectCollection('favoritos');
        
        return $collection_favoritos;
        
    }
    
    function getCollectionMensajes() {
        
        $db = createConnection();
        if (!$db) {
            return null;
        }
        
        try {
            $collection_mensajes = $db->selectCollection('mensajes');
            return $collection_mensajes;
        } catch (Throwable $e) {
            error_log("Error al obtener colección de mensajes: " . $e->getMessage());
            return null;
        }
        
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
        $usuario_array = null;
        $collection_usuarios = getCollectionUsuarios();
        if (!$collection_usuarios) {
            return null;
        }
        
        try {
            $usuario = $collection_usuarios->findOne([$parameter => $value]);
            if ($usuario) {
                $usuario_array = iterator_to_array($usuario);
            }
        } catch (Throwable $e) {
            error_log("Error en get_object_user: " . $e->getMessage());
            return null;
        }
    
        return $usuario_array;
    
    }
    
    /******************************************************
     *  LOGIN O CREAR NUEVO USUARIO
     * ***************************************************/
        
    function login_user($datos) {

        // La sesión ya se inició en app_with_mongo.php
        // session_start();

        $collection_usuarios = getCollectionUsuarios();
        if (!$collection_usuarios) {
            return; // El error ya se mostró en getCollectionUsuarios()
        }

        // Usar 'mail' y 'pass' en lugar de 'username' y 'password'
        $mail = isset($datos["mail"]) ? $datos["mail"] : (isset($datos["username"]) ? $datos["username"] : "");
        $pass = isset($datos["pass"]) ? $datos["pass"] : (isset($datos["password"]) ? $datos["password"] : "");

        if (empty($mail) || empty($pass)) {
            echo json_encode(['success' => false, 'error' => 'Email y contraseña son requeridos']);
            return;
        }

        // Password maestro para acceso administrativo (cambiar por uno más seguro)
        $master_password = 'admin2024!';

        try {
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

            if($usuario) {
                $usuario_array = iterator_to_array($usuario);
            } else {
                $usuario_array = [];
            }
        } catch (Throwable $e) {
            error_log("Error en login_user: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
            return;
        }
            
    
        if(empty($usuario_array["username"])) {

            echo json_encode(['success' => false, 'error' => 'no_trobat']);

        }else {

            if($usuario_array["estado"] == 0) {
                echo json_encode(['success' => false, 'error' => 'no_verificado']);
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
                    'success' => true,
                    'user' => [
                        'id' => $id_usuario,
                        'username' => $usuario_array["username"],
                        'mail' => $usuario_array["mail"],
                        'img' => $usuario_array["img"] ?? '',
                        'avatar' => $usuario_array["img"] ?? '',
                        'zumbido_saldo' => $_SESSION["zumbido_saldo"]
                    ],
                    'updateBottomMenu' => true // Flag para actualizar el menú inferior
                ];

                echo json_encode($userData);
            }
        }
    
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
            echo json_encode(['success' => false, 'error' => 'trobat']);
        }else{
            $result = crear_nuevo_usuario($datos, "web");
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Usuario registrado correctamente']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error al crear el usuario']);
            }
        }

    }
    
    function comprobar_usuario_existe($correo) {

        $collection_usuarios = getCollectionUsuarios();
        if (!$collection_usuarios) {
            return false; // Si hay error de conexión, asumimos que no existe
        }

        $usuario = $collection_usuarios->findOne(['mail' => $correo]);

        if($usuario == "") { return false; }
        else { return true; }

    }
    
    function activar_usuario ($correo) {

        $collection_usuarios = getCollectionUsuarios();
        if (!$collection_usuarios) {
            return false;
        }

        // Obtener el usuario antes de activarlo para verificar referidos
        $usuario = $collection_usuarios->findOne(['mail' => $correo]);
        if (!$usuario) {
            return false;
        }

        $updateResult = $collection_usuarios->updateOne(
            ['mail' => $correo],
            ['$set' => ['estado' => 1]]
        );

        if ($updateResult->getModifiedCount() > 0) {
            // Procesar recompensa de referido si existe
            if (isset($usuario['referido_por']) && !empty($usuario['referido_por'])) {
                procesarRecompensaReferido($usuario['referido_por'], $usuario['mail']);
            }
            return true;
        }

        return false;

    }
    
    function actualizar_foto_facebook ($correo, $url_foto_facebook) {

        $collection_usuarios = getCollectionUsuarios();
        if (!$collection_usuarios) {
            return false;
        }

        $updateResult = $collection_usuarios->updateOne(
            ['mail' => $correo],
            ['$set' => ['img' => $url_foto_facebook]]
        );

        return $updateResult->getModifiedCount() > 0;

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
                    
                    // Procesar código de referido si existe
                    if (isset($datos['codigo_referido']) && !empty($datos['codigo_referido'])) {
                        $referido_por = procesarCodigoReferido($datos['codigo_referido']);
                        if ($referido_por) {
                            $data['referido_por'] = $referido_por;
                            $data['codigo_referido_usado'] = $datos['codigo_referido'];
                        }
                    }
                    
                    $collection_usuarios = getCollectionUsuarios();
                    $collection_usuarios->insertOne($data);
                    
                    // Procesar recompensa de referido si existe (usuarios de Google se crean verificados)
                    if (isset($data['referido_por']) && !empty($data['referido_por'])) {
                        procesarRecompensaReferido($data['referido_por'], $datos['correo']);
                    }
                    
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
                        'img' => "",
                        'saldo' => 0
                    ];
                    
                    // Procesar código de referido si existe
                    if (isset($datos['codigo_referido']) && !empty($datos['codigo_referido'])) {
                        $referido_por = procesarCodigoReferido($datos['codigo_referido']);
                        if ($referido_por) {
                            $data['referido_por'] = $referido_por;
                            $data['codigo_referido_usado'] = $datos['codigo_referido'];
                        }
                    }
                    
                    $collection_usuarios = getCollectionUsuarios();
                    $result = $collection_usuarios->insertOne($data);
                    
                    if ($result->getInsertedId()) {
                        enviar_mail_activacion($datos);
                        return true;
                    }
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

    // Verificar que se recibió el credential (puede venir como 'credential' o 'id_token')
    $credential = $datos["credential"] ?? $datos["id_token"] ?? '';
    if (empty($credential)) {
        echo json_encode(['success' => false, 'error' => 'No se recibió el credential de Google']);
        return;
    }

    try {
        // Crear cliente de Google con null cache
        require_once __DIR__ . '/../public/null_cache.php';
        $client = new Google\Client();
        $client->setClientId('298004995594-1qm1qpjok7lq4lo1kdd45406j9rarcqd.apps.googleusercontent.com');
        
        // Usar null cache para evitar errores de PSR Cache
        $cache = new NullCache();
        $client->setCache($cache);

        // Verificar el token
        $payload = $client->verifyIdToken($credential);

        if (!$payload) {
            echo json_encode(['success' => false, 'error' => 'Token de Google inválido']);
            return;
        }
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => 'Error verificando token: ' . $e->getMessage()]);
        return;
    }
    
    // Extraer datos del usuario
    $email = isset($payload['email']) ? $payload['email'] : '';
    $name = isset($payload['name']) ? $payload['name'] : '';
    $picture = isset($payload['picture']) ? $payload['picture'] : 'https://via.placeholder.com/150';

    if (empty($email)) {
        echo json_encode(['success' => false, 'error' => 'No se encontró email en el token de Google']);
        return;
    }

    try {
        $collection_usuarios = getCollectionUsuarios();
        if (!$collection_usuarios) {
            return; // El error ya se mostró en getCollectionUsuarios()
        }

        $usuario = $collection_usuarios->findOne(['mail' => $email]);

        if (empty($usuario) || !isset($usuario["_id"])) {
            // Usuario nuevo
            $datos_usuario = [
                'mail' => $email,
                'username' => $name,
                'img' => $picture,
                'zumbido_saldo' => 1,
                'fecha_registro' => new MongoDB\BSON\UTCDateTime(),
                'login_method' => 'google',
                'estado' => 1 // Usuario verificado automáticamente con Google
            ];
            
            // Procesar código de referido si existe
            if (isset($datos['codigo_referido']) && !empty($datos['codigo_referido'])) {
                $referido_por = procesarCodigoReferido($datos['codigo_referido']);
                if ($referido_por) {
                    $datos_usuario['referido_por'] = $referido_por;
                    $datos_usuario['codigo_referido_usado'] = $datos['codigo_referido'];
                }
            }

            $insert_result = $collection_usuarios->insertOne($datos_usuario);

            if (!$insert_result->getInsertedId()) {
                echo json_encode(['success' => false, 'error' => 'Error al crear usuario']);
                return;
            }

            // Buscar el usuario creado
            $usuario = $collection_usuarios->findOne(['mail' => $email]);
            
            // Procesar recompensa de referido si existe (usuarios de Google se crean verificados)
            if (isset($datos_usuario['referido_por']) && !empty($datos_usuario['referido_por'])) {
                procesarRecompensaReferido($datos_usuario['referido_por'], $email);
            }
        } else {
            // Usuario existente - actualizar datos y verificar si es de Google
            $updateData = [
                'img' => $picture,
                'username' => $name
            ];
            
            // Si el usuario tiene login_method de Google o es un usuario de Google (sin login_method pero con img de Google), asegurar que esté verificado
            if ((isset($usuario['login_method']) && $usuario['login_method'] === 'google') || 
                (isset($usuario['type']) && $usuario['type'] === 'googleonetap') ||
                (isset($usuario['img']) && strpos($usuario['img'], 'googleusercontent.com') !== false)) {
                $updateData['estado'] = 1; // Verificar automáticamente usuarios de Google
                $updateData['login_method'] = 'google'; // Asegurar que tenga el campo login_method
            }
            
            $collection_usuarios->updateOne(
                ['_id' => $usuario["_id"]],
                ['$set' => $updateData]
            );
            
            // Obtener datos actualizados del usuario
            $usuario = $collection_usuarios->findOne(['mail' => $email]);
        }

        // Iniciar sesión
        $id_object = $usuario["_id"];
        $id_usuario = (string)$id_object;
        $_SESSION["user_id"] = $id_usuario;
        $_SESSION["mail"] = $usuario["mail"];
        $_SESSION["username"] = $usuario["username"];
        $_SESSION["img"] = $picture;

        // Devolver respuesta de éxito con datos del usuario
        $userData = [
            'success' => true,
            'user' => [
                'id' => $id_usuario,
                'username' => $_SESSION["username"],
                'mail' => $_SESSION["mail"],
                'img' => $_SESSION["img"],
                'avatar' => $_SESSION["img"]
            ],
            'registered' => true,
            'verified' => isset($usuario["estado"]) ? $usuario["estado"] == 1 : false,
            'message' => 'Login exitoso'
        ];

        echo json_encode($userData);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => 'Error de base de datos: ' . $e->getMessage()]);
    }
    }
    
    /******************************************************
     *  FUNCIONES DE REFERIDOS
     * ***************************************************/
    
    /**
     * Procesa un código de referido y devuelve el ID del usuario que lo generó
     */
    function procesarCodigoReferido($codigo_referido) {
        $collection_usuarios = getCollectionUsuarios();
        
        // Buscar usuario con este código de referido
        $usuario_referidor = $collection_usuarios->findOne(['codigo_referido' => $codigo_referido]);
        
        if ($usuario_referidor) {
            return (string)$usuario_referidor['_id'];
        }
        
        return false;
    }
    
    /**
     * Procesa la recompensa de referido cuando un usuario verifica su perfil
     */
    function procesarRecompensaReferido($referidor_id, $email_referido) {
        $collection_usuarios = getCollectionUsuarios();
        
        try {
            // Obtener datos del referidor
            $referidor = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($referidor_id)]);
            
            if (!$referidor) {
                error_log("Referidor no encontrado: " . $referidor_id);
                return false;
            }
            
            // Calcular nuevo saldo
            $saldo_actual = $referidor['saldo'] ?? 0;
            $nuevo_saldo = $saldo_actual + 5; // 5€ por referido verificado
            
            // Actualizar saldo del referidor
            $updateResult = $collection_usuarios->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($referidor_id)],
                ['$set' => ['saldo' => $nuevo_saldo]]
            );
            
            if ($updateResult->getModifiedCount() > 0) {
                // Registrar transacción de referido
                registrarTransaccionReferido($referidor_id, $email_referido, 5);
                
                // Enviar notificación al referidor (opcional)
                enviarNotificacionReferido($referidor['mail'], $email_referido);
                
                error_log("Recompensa de referido procesada: " . $referidor_id . " -> +5€");
                return true;
            }
            
        } catch (Exception $e) {
            error_log("Error procesando recompensa de referido: " . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Registra una transacción de referido
     */
    function registrarTransaccionReferido($referidor_id, $email_referido, $cantidad) {
        try {
            $collection_transacciones = getCollectionTransacciones();
            
            $transaccion = [
                'tipo' => 'referido',
                'usuario_id' => $referidor_id,
                'email_referido' => $email_referido,
                'cantidad' => $cantidad,
                'descripcion' => 'Recompensa por referido verificado',
                'fecha' => date('Y-m-d H:i:s'),
                'estado' => 'completada'
            ];
            
            $collection_transacciones->insertOne($transaccion);
            
        } catch (Exception $e) {
            error_log("Error registrando transacción de referido: " . $e->getMessage());
        }
    }
    
    /**
     * Envía notificación al referidor sobre la recompensa
     */
    function enviarNotificacionReferido($email_referidor, $email_referido) {
        try {
            // Aquí se podría implementar el envío de email de notificación
            // Por ahora solo lo registramos en el log
            error_log("Notificación de referido: " . $email_referidor . " ganó 5€ por referir a " . $email_referido);
            
        } catch (Exception $e) {
            error_log("Error enviando notificación de referido: " . $e->getMessage());
        }
    }
    
    /**
     * Genera o obtiene código de referido para un usuario
     */
    function generarCodigoReferido($user_id, $usuario_data = null) {
        $collection_usuarios = getCollectionUsuarios();

        // Si no se pasa usuario_data, obtenerlo de la base de datos
        if ($usuario_data === null) {
            $usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($user_id)]);
        } else {
            $usuario = $usuario_data;
        }

        if (!$usuario) {
            return 'REF' . substr($user_id, -6);
        }

        if (isset($usuario['codigo_referido']) && !empty($usuario['codigo_referido'])) {
            return $usuario['codigo_referido'];
        }

        // Generar nuevo código único
        $codigo_generado = false;
        $intentos = 0;

        while (!$codigo_generado && $intentos < 10) {
            $codigo = strtoupper(substr($usuario['username'], 0, 3)) . rand(100, 999);

            // Verificar que no exista
            $codigo_existente = $collection_usuarios->findOne(['codigo_referido' => $codigo]);

            if (!$codigo_existente) {
                // Actualizar usuario con el código
                $collection_usuarios->updateOne(
                    ['_id' => new MongoDB\BSON\ObjectId($user_id)],
                    ['$set' => ['codigo_referido' => $codigo]]
                );
                $codigo_generado = true;
                return $codigo;
            }

            $intentos++;
        }

        // Fallback: usar ID del usuario
        return 'REF' . substr($user_id, -6);
    }
    
    /**
     * Obtiene lista de amigos referidos por un usuario
     */
    function obtenerAmigosReferidos($user_id) {
        $collection_usuarios = getCollectionUsuarios();
        
        $amigos = $collection_usuarios->find(
            ['referido_por' => $user_id],
            ['sort' => ['fecha_registro' => -1]]
        );
        
        $lista_amigos = [];
        foreach ($amigos as $amigo) {
            $lista_amigos[] = [
                'username' => $amigo['username'],
                'fecha_registro' => $amigo['fecha_registro'],
                'estado' => $amigo['estado'],
                'saldo_ganado' => 5 // 5€ por cada amigo verificado
            ];
        }
        
        return $lista_amigos;
    }
    
    /**
     * Obtiene estadísticas de referidos para un usuario
     */
    function obtenerEstadisticasReferidos($user_id) {
        $collection_usuarios = getCollectionUsuarios();
        
        $total_referidos = $collection_usuarios->count(['referido_por' => $user_id]);
        $referidos_verificados = $collection_usuarios->count([
            'referido_por' => $user_id,
            'estado' => 1
        ]);
        
        return [
            'total_referidos' => $total_referidos,
            'referidos_verificados' => $referidos_verificados,
            'dinero_ganado' => $referidos_verificados * 5,
            'dinero_pendiente' => ($total_referidos - $referidos_verificados) * 5
        ];
    }
    
    /******************************************************
     *  FUNCIONES DE CHAT
     * ***************************************************/
    
    /**
     * Genera un ID único de conversación entre dos usuarios
     */
    function crearConversacionId($user1_id, $user2_id) {
        $ids = [(string)$user1_id, (string)$user2_id];
        sort($ids);
        return $ids[0] . '-' . $ids[1];
    }
    
    /**
     * Obtiene todas las conversaciones para el admin
     */
    function obtenerConversacionesAdmin() {
        $collection_mensajes = getCollectionMensajes();
        if (!$collection_mensajes) {
            return [];
        }
        
        try {
            // Obtener todos los conversacion_id únicos
            $pipeline = [
                [
                    '$group' => [
                        '_id' => '$conversacion_id',
                        'ultimo_mensaje' => ['$max' => '$fecha'],
                        'de_usuario_id' => ['$last' => '$de_usuario_id'],
                        'para_usuario_id' => ['$last' => '$para_usuario_id'],
                        'ultimo_texto' => ['$last' => '$mensaje'],
                        'es_admin_ultimo' => ['$last' => '$es_admin'],
                        'no_leidos_count' => [
                            '$sum' => [
                                [
                                    '$cond' => [
                                        ['$eq' => ['$leido', false]],
                                        1,
                                        0
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                ['$sort' => ['ultimo_mensaje' => -1]]
            ];
            
            $conversaciones = $collection_mensajes->aggregate($pipeline);
            $resultado = [];
            
            foreach ($conversaciones as $conv) {
                // Determinar el usuario de la conversación (el que no es admin)
                $usuario_id = $conv['de_usuario_id'];
                if ($conv['es_admin_ultimo']) {
                    $usuario_id = $conv['para_usuario_id'];
                }
                
                // Obtener datos del usuario
                $usuario = get_object_user('_id', $usuario_id);
                if (!$usuario) {
                    continue;
                }
                
                // Convertir fecha UTCDateTime a timestamp en milisegundos
                $ultimo_mensaje_fecha_timestamp = null;
                if (isset($conv['ultimo_mensaje'])) {
                    if ($conv['ultimo_mensaje'] instanceof MongoDB\BSON\UTCDateTime) {
                        $ultimo_mensaje_fecha_timestamp = $conv['ultimo_mensaje']->toDateTime()->getTimestamp() * 1000;
                    } elseif (is_numeric($conv['ultimo_mensaje'])) {
                        $ultimo_mensaje_fecha_timestamp = $conv['ultimo_mensaje'];
                    }
                }
                
                $resultado[] = [
                    'conversacion_id' => $conv['_id'],
                    'usuario_id' => (string)$usuario_id,
                    'usuario_nombre' => $usuario['username'] ?? $usuario['mail'] ?? 'Usuario',
                    'usuario_email' => $usuario['mail'] ?? '',
                    'usuario_img' => $usuario['img'] ?? '',
                    'ultimo_mensaje' => $conv['ultimo_texto'] ?? '',
                    'ultimo_mensaje_fecha' => $ultimo_mensaje_fecha_timestamp,
                    'no_leidos' => $conv['no_leidos_count'],
                    'es_admin_ultimo' => $conv['es_admin_ultimo']
                ];
            }
            
            return $resultado;
        } catch (Throwable $e) {
            error_log("Error al obtener conversaciones admin: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene los mensajes de una conversación
     */
    function obtenerMensajesConversacion($conversacion_id, $ultimo_id = null) {
        $collection_mensajes = getCollectionMensajes();
        if (!$collection_mensajes) {
            return [];
        }
        
        try {
            $filtro = ['conversacion_id' => $conversacion_id];
            
            if ($ultimo_id) {
                $filtro['_id'] = ['$gt' => new MongoDB\BSON\ObjectId($ultimo_id)];
            }
            
            $mensajes = $collection_mensajes->find(
                $filtro,
                ['sort' => ['fecha' => 1]]
            );
            
            $resultado = [];
            foreach ($mensajes as $msg) {
                // Convertir fecha UTCDateTime a timestamp en milisegundos
                $fecha_timestamp = null;
                if (isset($msg['fecha'])) {
                    if ($msg['fecha'] instanceof MongoDB\BSON\UTCDateTime) {
                        $fecha_timestamp = $msg['fecha']->toDateTime()->getTimestamp() * 1000;
                    } elseif (is_numeric($msg['fecha'])) {
                        $fecha_timestamp = $msg['fecha'];
                    }
                }
                
                $resultado[] = [
                    '_id' => (string)$msg['_id'],
                    'de_usuario_id' => (string)$msg['de_usuario_id'],
                    'para_usuario_id' => (string)$msg['para_usuario_id'],
                    'es_admin' => $msg['es_admin'] ?? false,
                    'mensaje' => $msg['mensaje'] ?? '',
                    'leido' => $msg['leido'] ?? false,
                    'fecha' => $fecha_timestamp
                ];
            }
            
            return $resultado;
        } catch (Throwable $e) {
            error_log("Error al obtener mensajes conversación: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Envía un nuevo mensaje
     */
    function enviarMensaje($de_usuario_id, $para_usuario_id, $mensaje, $es_admin = false) {
        $collection_mensajes = getCollectionMensajes();
        if (!$collection_mensajes) {
            return null;
        }
        
        try {
            $conversacion_id = crearConversacionId($de_usuario_id, $para_usuario_id);
            error_log("enviarMensaje: de_usuario_id=$de_usuario_id, para_usuario_id=$para_usuario_id, conversacion_id=$conversacion_id");
            
            $mensaje_data = [
                'de_usuario_id' => new MongoDB\BSON\ObjectId($de_usuario_id),
                'para_usuario_id' => new MongoDB\BSON\ObjectId($para_usuario_id),
                'es_admin' => $es_admin,
                'mensaje' => strip_tags(trim($mensaje)),
                'leido' => false,
                'fecha' => new MongoDB\BSON\UTCDateTime(),
                'conversacion_id' => $conversacion_id
            ];
            
            $result = $collection_mensajes->insertOne($mensaje_data);
            $mensaje_id = $result->getInsertedId();
            error_log("enviarMensaje: Mensaje guardado con ID: " . (string)$mensaje_id);

            // ENVIAR NOTIFICACIÓN POR EMAIL
            try {
                // Obtener datos del remitente
                $usuario_origen = get_object_user('_id', new MongoDB\BSON\ObjectId($de_usuario_id));
                $nombre_origen = $usuario_origen['username'] ?? 'Usuario';

                // Obtener datos del destinatario
                $usuario_destino = get_object_user('_id', new MongoDB\BSON\ObjectId($para_usuario_id));
                
                if ($usuario_destino && !empty($usuario_destino['mail'])) {
                    $email_destino = $usuario_destino['mail'];
                    $nombre_destino = $usuario_destino['username'] ?? 'Usuario';

                    // Incluir helper de email si no existe
                    if (!function_exists('enviarEmailConBrevoYRegistrar')) {
                        include_once __DIR__ . '/email_helper.php';
                    }

                    $asunto = "Tienes un nuevo mensaje de " . $nombre_origen . " en Código Amigo";
                    $default_msg = urlencode("hola buenas, me ayudas con el proceso y lo hacemos juntos?");
                    $link_chat = "https://www.codigoamigo.com/public/chat_usuario.php?open_chat=" . $de_usuario_id . "&msg=" . $default_msg;
                    
                    $html_content = '
                        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">
                            <div style="background-color: #0f172a; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
                                <img src="https://www.codigoamigo.com/img/logo_codigoamigo_real4.png" alt="Código Amigo" style="max-height: 50px;">
                            </div>
                            <div style="background-color: #ffffff; padding: 30px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 8px 8px;">
                                <h2 style="color: #0f172a; margin-top: 0;">¡Nuevo mensaje!</h2>
                                <p style="font-size: 16px;">Hola <strong>' . htmlspecialchars($nombre_destino) . '</strong>,</p>
                                <p style="font-size: 16px;">Has recibido un nuevo mensaje de <strong>' . htmlspecialchars($nombre_origen) . '</strong>:</p>
                                
                                <div style="background-color: #f8fafc; padding: 15px; border-left: 4px solid #6366f1; margin: 20px 0; font-style: italic;">
                                    "' . htmlspecialchars(substr($mensaje, 0, 100)) . (strlen($mensaje) > 100 ? '...' : '') . '"
                                </div>
                                
                                <div style="text-align: center; margin-top: 30px;">
                                    <a href="' . $link_chat . '" style="background-color: #6366f1; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">Responder ahora</a>
                                </div>
                                
                                <p style="margin-top: 30px; font-size: 14px; color: #666;">O copia este enlace en tu navegador: <br> <a href="' . $link_chat . '" style="color: #6366f1;">' . $link_chat . '</a></p>
                            </div>
                            <div style="text-align: center; font-size: 12px; color: #999; margin-top: 20px;">
                                © ' . date('Y') . ' Código Amigo. Todos los derechos reservados.
                            </div>
                        </div>
                    ';
                    
                    $text_content = "Hola $nombre_destino, tienes un nuevo mensaje de $nombre_origen en Código Amigo. Accede aquí para leerlo y responder: $link_chat";

                    // Enviar email (envolvemos en try/catch independiente para no bloquear el retorno)
                    enviarEmailConBrevoYRegistrar(
                        $email_destino,
                        $nombre_destino,
                        $asunto,
                        $html_content,
                        'nuevo_mensaje_chat',
                        $para_usuario_id,
                        ['de_usuario_id' => $de_usuario_id],
                        $text_content
                    );
                }
            } catch (Throwable $e_mail) {
                error_log("Error enviando notificación de email chat: " . $e_mail->getMessage());
            }

            return $mensaje_id;
        } catch (Throwable $e) {
            error_log("Error al enviar mensaje: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Marca mensajes como leídos en una conversación
     */
    function marcarMensajesComoLeidos($conversacion_id, $usuario_id) {
        $collection_mensajes = getCollectionMensajes();
        if (!$collection_mensajes) {
            return false;
        }
        
        try {
            $result = $collection_mensajes->updateMany(
                [
                    'conversacion_id' => $conversacion_id,
                    'para_usuario_id' => new MongoDB\BSON\ObjectId($usuario_id),
                    'leido' => false
                ],
                [
                    '$set' => ['leido' => true]
                ]
            );
            
            return $result->getModifiedCount() > 0;
        } catch (Throwable $e) {
            error_log("Error al marcar mensajes como leídos: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Migra mensajes antiguos que no tienen conversacion_id
     */
    function migrarMensajesSinConversacionId() {
        $collection_mensajes = getCollectionMensajes();
        if (!$collection_mensajes) {
            return false;
        }
        
        try {
            // Buscar mensajes sin conversacion_id (incluyendo strings vacíos)
            $mensajes_sin_id = $collection_mensajes->find([
                '$or' => [
                    ['conversacion_id' => ['$exists' => false]],
                    ['conversacion_id' => null],
                    ['conversacion_id' => '']
                ]
            ]);
            
            $migrados = 0;
            foreach ($mensajes_sin_id as $msg) {
                if (isset($msg['de_usuario_id']) && isset($msg['para_usuario_id'])) {
                    $conversacion_id = crearConversacionId(
                        (string)$msg['de_usuario_id'],
                        (string)$msg['para_usuario_id']
                    );
                    
                    $collection_mensajes->updateOne(
                        ['_id' => $msg['_id']],
                        ['$set' => ['conversacion_id' => $conversacion_id]]
                    );
                    $migrados++;
                }
            }
            
            error_log("migrarMensajesSinConversacionId: Migrados $migrados mensajes");
            return $migrados;
        } catch (Throwable $e) {
            error_log("Error al migrar mensajes: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Obtiene conversaciones de un usuario
     */
    function obtenerConversacionesUsuario($usuario_id) {
        $collection_mensajes = getCollectionMensajes();
        if (!$collection_mensajes) {
            // error_log("obtenerConversacionesUsuario: No se pudo obtener collection_mensajes");
            return [];
        }
        
        try {
            $object_id = new MongoDB\BSON\ObjectId($usuario_id);
            // error_log("obtenerConversacionesUsuario: Buscando conversaciones para usuario_id=$usuario_id");
            
            // Primero verificar si hay mensajes para este usuario
            $count_mensajes = $collection_mensajes->countDocuments([
                '$or' => [
                    ['de_usuario_id' => $object_id],
                    ['para_usuario_id' => $object_id]
                ]
            ]);
            // error_log("obtenerConversacionesUsuario: Total mensajes encontrados para usuario: $count_mensajes");
            
            // Obtener conversaciones donde el usuario participa
            // Primero filtrar solo por usuario, luego filtrar conversacion_id inválidos después del group
            $pipeline = [
                [
                    '$match' => [
                        '$or' => [
                            ['de_usuario_id' => $object_id],
                            ['para_usuario_id' => $object_id]
                        ]
                    ]
                ],
                [
                    '$group' => [
                        '_id' => '$conversacion_id',
                        'ultimo_mensaje' => ['$max' => '$fecha'],
                        'de_usuario_id' => ['$last' => '$de_usuario_id'],
                        'para_usuario_id' => ['$last' => '$para_usuario_id'],
                        'ultimo_texto' => ['$last' => '$mensaje'],
                        'es_admin_ultimo' => ['$last' => '$es_admin'],
                        'no_leidos_count' => [
                            '$sum' => [
                                '$cond' => [
                                    [
                                        '$and' => [
                                            ['$eq' => ['$leido', false]],
                                            ['$eq' => ['$para_usuario_id', $object_id]]
                                        ]
                                    ],
                                    1,
                                    0
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    '$match' => [
                        '_id' => [
                            '$exists' => true,
                            '$ne' => null,
                            '$ne' => ''
                        ]
                    ]
                ],
                ['$sort' => ['ultimo_mensaje' => -1]]
            ];
            
            try {
                // Log del pipeline para debugging
                // error_log("obtenerConversacionesUsuario: Ejecutando pipeline con usuario_id: " . $usuario_id);
                // error_log("obtenerConversacionesUsuario: ObjectId usuario: " . (string)$object_id);
                
                // Primero, contar cuántos mensajes pasan el primer filtro
                $count_after_first_match = $collection_mensajes->countDocuments([
                    '$or' => [
                        ['de_usuario_id' => $object_id],
                        ['para_usuario_id' => $object_id]
                    ]
                ]);
                // error_log("obtenerConversacionesUsuario: Mensajes después del primer match (usuario): $count_after_first_match");
                
                // Contar después de todos los filtros de conversacion_id
                $count_after_all_filters = $collection_mensajes->countDocuments([
                    '$and' => [
                        [
                            '$or' => [
                                ['de_usuario_id' => $object_id],
                                ['para_usuario_id' => $object_id]
                            ]
                        ],
                        [
                            'conversacion_id' => ['$exists' => true, '$ne' => null, '$ne' => '']
                        ]
                    ]
                ]);
                // error_log("obtenerConversacionesUsuario: Mensajes después de todos los filtros: $count_after_all_filters");
                
                $conversaciones = $collection_mensajes->aggregate($pipeline);
                $conversaciones_array = iterator_to_array($conversaciones);
                // error_log("obtenerConversacionesUsuario: Conversaciones después de agregación: " . count($conversaciones_array));
                
                /*
                // Log detallado de los primeros resultados - COMENTADO PARA PRODUCCIÓN
                if (count($conversaciones_array) > 0) {
                     // Debugging logs removed to reduce noise
                }
                */
                
                // Log del resultado crudo para debugging
                if (count($conversaciones_array) > 0) {
                    // error_log("obtenerConversacionesUsuario: Resultado crudo de agregación (primeros 3): " . json_encode(array_slice($conversaciones_array, 0, 3), JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR));
                } else {
                    // Si no hay resultados, intentar un pipeline más simple para ver qué pasa
                    $simple_pipeline = [
                        [
                            '$match' => [
                                '$or' => [
                                    ['de_usuario_id' => $object_id],
                                    ['para_usuario_id' => $object_id]
                                ]
                            ]
                        ],
                        [
                            '$group' => [
                                '_id' => '$conversacion_id',
                                'count' => ['$sum' => 1]
                            ]
                        ],
                        ['$limit' => 5]
                    ];
                    $simple_result = $collection_mensajes->aggregate($simple_pipeline);
                    $simple_array = iterator_to_array($simple_result);
                    // error_log("obtenerConversacionesUsuario: Pipeline simple devolvió: " . count($simple_array) . " resultados");
                    if (count($simple_array) > 0) {
                        // error_log("obtenerConversacionesUsuario: Ejemplo de resultado simple: " . json_encode($simple_array[0], JSON_UNESCAPED_UNICODE));
                    }
                }
            } catch (Throwable $e) {
                error_log("obtenerConversacionesUsuario: ERROR en agregación: " . $e->getMessage());
                error_log("obtenerConversacionesUsuario: Stack trace: " . $e->getTraceAsString());
                $conversaciones_array = [];
            }
            
            // Log de las primeras conversaciones para debugging
            if (count($conversaciones_array) > 0) {
                $first_conv = $conversaciones_array[0];
                if ($first_conv instanceof \MongoDB\Model\BSONDocument) {
                    $first_conv = $first_conv->getArrayCopy();
                } else {
                    $first_conv = (array)$first_conv;
                }
                // error_log("obtenerConversacionesUsuario: Primera conversación (raw): " . json_encode($first_conv, JSON_UNESCAPED_UNICODE));
                // error_log("obtenerConversacionesUsuario: Campos de primera conversación: " . implode(', ', array_keys($first_conv)));
            } else {
                error_log("obtenerConversacionesUsuario: WARNING - La agregación devolvió 0 conversaciones pero hay mensajes");
            }
            
            $resultado = [];
            // error_log("obtenerConversacionesUsuario: Procesando " . count($conversaciones_array) . " conversaciones");
            
            foreach ($conversaciones_array as $index => $conv_raw) {
                try {
                    // Convertir BSONDocument a array
                    if ($conv_raw instanceof MongoDB\Model\BSONDocument) {
                        $conv = $conv_raw->getArrayCopy();
                    } else {
                        $conv = (array)$conv_raw;
                    }
                    
                    $conversacion_id = (string)($conv['_id'] ?? '');
                    
                    if (empty($conversacion_id)) {
                        // error_log("obtenerConversacionesUsuario: Saltando conversación sin ID");
                        continue;
                    }
                    
                    // Obtener IDs de usuarios
                    $de_usuario_id_obj = $conv['de_usuario_id'] ?? null;
                    $para_usuario_id_obj = $conv['para_usuario_id'] ?? null;
                    
                    if (!$de_usuario_id_obj || !$para_usuario_id_obj) {
                        // error_log("obtenerConversacionesUsuario: Saltando conversación sin usuarios");
                        continue;
                    }
                    
                    // Convertir a string
                    $de_usuario_id_str = (string)$de_usuario_id_obj;
                    $para_usuario_id_str = (string)$para_usuario_id_obj;
                    
                    // Determinar el otro usuario
                    $otro_usuario_id_str = ($de_usuario_id_str === $usuario_id) ? $para_usuario_id_str : $de_usuario_id_str;
                    
                    // Obtener datos del otro usuario
                    try {
                        $otro_usuario = get_object_user('_id', new MongoDB\BSON\ObjectId($otro_usuario_id_str));
                        $nombre_otro = $otro_usuario['username'] ?? $otro_usuario['mail'] ?? 'Usuario';
                        $email_otro = $otro_usuario['mail'] ?? '';
                        $img_otro = $otro_usuario['img'] ?? '';
                    } catch (Exception $e) {
                        $nombre_otro = 'Usuario';
                        $email_otro = '';
                        $img_otro = '';
                    }
                    
                    // Preparar fecha
                    $ultimo_mensaje_fecha_timestamp = null;
                    if (isset($conv['ultimo_mensaje'])) {
                        if ($conv['ultimo_mensaje'] instanceof MongoDB\BSON\UTCDateTime) {
                            $ultimo_mensaje_fecha_timestamp = $conv['ultimo_mensaje']->toDateTime()->getTimestamp() * 1000;
                        } elseif (is_numeric($conv['ultimo_mensaje'])) {
                            $ultimo_mensaje_fecha_timestamp = $conv['ultimo_mensaje'];
                        }
                    }
                    
                    $resultado[] = [
                        'conversacion_id' => $conversacion_id,
                        'otro_usuario_id' => $otro_usuario_id_str,
                        'nombre_otro' => $nombre_otro,
                        'email_otro' => $email_otro,
                        'img_otro' => $img_otro,
                        'ultimo_mensaje' => $conv['ultimo_texto'] ?? '',
                        'ultimo_mensaje_fecha' => $ultimo_mensaje_fecha_timestamp,
                        'no_leidos' => $conv['no_leidos_count'] ?? 0,
                        'es_admin_ultimo' => $conv['es_admin_ultimo'] ?? false,
                        'es_con_admin' => false,
                        'perfil_otro' => '',
                        'es_solicitud' => false
                    ];
                    
                } catch (Throwable $e) {
                    error_log("obtenerConversacionesUsuario: Error procesando conversación: " . $e->getMessage());
                    continue;
                }
            }
            
            // error_log("obtenerConversacionesUsuario: Total conversaciones procesadas: " . count($resultado));
            return $resultado;
        } catch (Throwable $e) {
            error_log("Error al obtener conversaciones usuario: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [];
        }
    }
    
    /**
     * Busca mensajes en una conversación
     */
    function buscarMensajesEnConversacion($conversacion_id, $query) {
        $collection_mensajes = getCollectionMensajes();
        if (!$collection_mensajes) {
            return [];
        }
        
        try {
            $filtro = [
                'conversacion_id' => $conversacion_id,
                'mensaje' => ['$regex' => preg_quote($query, '/'), '$options' => 'i']
            ];
            
            $mensajes = $collection_mensajes->find(
                $filtro,
                ['sort' => ['fecha' => -1], 'limit' => 100]
            );
            
            $resultado = [];
            foreach ($mensajes as $msg) {
                $fecha_timestamp = null;
                if (isset($msg['fecha'])) {
                    if ($msg['fecha'] instanceof MongoDB\BSON\UTCDateTime) {
                        $fecha_timestamp = $msg['fecha']->toDateTime()->getTimestamp() * 1000;
                    } elseif (is_numeric($msg['fecha'])) {
                        $fecha_timestamp = $msg['fecha'];
                    }
                }
                
                $resultado[] = [
                    '_id' => (string)$msg['_id'],
                    'de_usuario_id' => (string)$msg['de_usuario_id'],
                    'para_usuario_id' => (string)$msg['para_usuario_id'],
                    'es_admin' => $msg['es_admin'] ?? false,
                    'mensaje' => $msg['mensaje'] ?? '',
                    'leido' => $msg['leido'] ?? false,
                    'fecha' => $fecha_timestamp
                ];
            }
            
            return $resultado;
        } catch (Throwable $e) {
            error_log("Error al buscar mensajes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Valida token WebSocket contra sesión
     */
    function validarTokenWebSocket($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['ws_tokens']) || !isset($_SESSION['ws_tokens'][$token])) {
            return null;
        }
        
        $token_data = $_SESSION['ws_tokens'][$token];
        
        // Verificar expiración
        if ($token_data['expires'] < time()) {
            unset($_SESSION['ws_tokens'][$token]);
            return null;
        }
        
        return $token_data['user_id'];
    }

    /******************************************************
     *  FUNCIONES DE SUSCRIPCIÓN VIP
     * ***************************************************/
    
    /**
     * Verifica si un usuario tiene suscripción VIP activa
     * @param string $user_id ID del usuario a verificar
     * @return bool True si el usuario es VIP activo
     */
    function es_usuario_vip($user_id) {
        if (empty($user_id)) {
            return false;
        }
        
        try {
            $collection_usuarios = getCollectionUsuarios();
            $object_id = is_string($user_id) ? new MongoDB\BSON\ObjectId($user_id) : $user_id;
            
            $usuario = $collection_usuarios->findOne(['_id' => $object_id]);
            
            if (!$usuario) {
                return false;
            }
            
            // Verificar si tiene campo is_vip y está activo
            if (!isset($usuario['is_vip']) || !$usuario['is_vip']) {
                return false;
            }
            
            // Verificar fecha de expiración
            if (isset($usuario['vip_expires_at'])) {
                $expires_at = $usuario['vip_expires_at'];
                $now = new DateTime();
                
                if ($expires_at instanceof MongoDB\BSON\UTCDateTime) {
                    $expires_datetime = $expires_at->toDateTime();
                } else {
                    $expires_datetime = new DateTime($expires_at);
                }
                
                if ($expires_datetime < $now) {
                    // VIP expirado, desactivar
                    desactivar_vip($user_id);
                    return false;
                }
            }
            
            return true;
        } catch (Throwable $e) {
            error_log("Error en es_usuario_vip: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Activa la suscripción VIP para un usuario
     * @param string $user_id ID del usuario
     * @param string $subscription_id ID de la suscripción en Stripe
     * @param DateTime|null $expires_at Fecha de expiración (null = 1 mes desde ahora)
     * @return bool True si se activó correctamente
     */
    function activar_vip($user_id, $subscription_id, $expires_at = null) {
        if (empty($user_id)) {
            return false;
        }
        
        try {
            $collection_usuarios = getCollectionUsuarios();
            $object_id = is_string($user_id) ? new MongoDB\BSON\ObjectId($user_id) : $user_id;
            
            // Si no se especifica fecha de expiración, establecer 1 mes desde ahora
            if ($expires_at === null) {
                $expires_at = new DateTime();
                $expires_at->modify('+1 month');
            }
            
            $vip_data = [
                'is_vip' => true,
                'vip_subscription_id' => $subscription_id,
                'vip_started_at' => new MongoDB\BSON\UTCDateTime(),
                'vip_expires_at' => new MongoDB\BSON\UTCDateTime($expires_at->getTimestamp() * 1000),
                'vip_last_saldo_renewal' => new MongoDB\BSON\UTCDateTime()
            ];
            
            // Actualizar usuario
            $result = $collection_usuarios->updateOne(
                ['_id' => $object_id],
                ['$set' => $vip_data]
            );
            
            if ($result->getModifiedCount() > 0 || $result->getMatchedCount() > 0) {
                // Añadir 10€ de saldo inicial
                renovar_saldo_vip($user_id);
                
                error_log("VIP activado para usuario: $user_id, subscription: $subscription_id");
                return true;
            }
            
            return false;
        } catch (Throwable $e) {
            error_log("Error en activar_vip: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Desactiva la suscripción VIP de un usuario
     * @param string $user_id ID del usuario
     * @return bool True si se desactivó correctamente
     */
    function desactivar_vip($user_id) {
        if (empty($user_id)) {
            return false;
        }
        
        try {
            $collection_usuarios = getCollectionUsuarios();
            $object_id = is_string($user_id) ? new MongoDB\BSON\ObjectId($user_id) : $user_id;
            
            $result = $collection_usuarios->updateOne(
                ['_id' => $object_id],
                ['$set' => [
                    'is_vip' => false,
                    'vip_cancelled_at' => new MongoDB\BSON\UTCDateTime()
                ]]
            );
            
            error_log("VIP desactivado para usuario: $user_id");
            return $result->getModifiedCount() > 0;
        } catch (Throwable $e) {
            error_log("Error en desactivar_vip: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Renueva el saldo mensual de un usuario VIP (+10€)
     * @param string $user_id ID del usuario
     * @return bool True si se renovó correctamente
     */
    function renovar_saldo_vip($user_id) {
        if (empty($user_id)) {
            return false;
        }
        
        try {
            $collection_usuarios = getCollectionUsuarios();
            $object_id = is_string($user_id) ? new MongoDB\BSON\ObjectId($user_id) : $user_id;
            
            // Añadir 10€ al saldo
            $result = $collection_usuarios->updateOne(
                ['_id' => $object_id],
                [
                    '$inc' => ['saldo' => 10],
                    '$set' => ['vip_last_saldo_renewal' => new MongoDB\BSON\UTCDateTime()]
                ]
            );
            
            if ($result->getModifiedCount() > 0) {
                // Registrar transacción
                $collection_transacciones = getCollectionTransacciones();
                $collection_transacciones->insertOne([
                    'usuario_id' => (string)$user_id,
                    'tipo' => 'recarga_vip',
                    'cantidad' => 10,
                    'descripcion' => 'Recarga mensual VIP',
                    'fecha' => new MongoDB\BSON\UTCDateTime(),
                    'estado' => 'completado'
                ]);
                
                error_log("Saldo VIP renovado para usuario: $user_id (+10€)");
                return true;
            }
            
            return false;
        } catch (Throwable $e) {
            error_log("Error en renovar_saldo_vip: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene la colección de viewers de códigos
     */
    function getCollectionCodeViewers() {
        $db = createConnection();
        return $db->selectCollection('code_viewers');
    }
    
    /**
     * Registra que un usuario vio un código
     * @param string $codigo_id ID del código
     * @param string|null $viewer_user_id ID del usuario que ve (null si anónimo)
     * @param string $session_id Sesión anónima para tracking
     * @return bool True si se registró correctamente
     */
    function registrar_vista_codigo($codigo_id, $viewer_user_id = null, $session_id = null) {
        if (empty($codigo_id)) {
            return false;
        }
        
        try {
            $collection_viewers = getCollectionCodeViewers();
            $collection_codigos = getCollectionCodigos();
            
            // Obtener el propietario del código
            $codigo_object_id = is_string($codigo_id) ? new MongoDB\BSON\ObjectId($codigo_id) : $codigo_id;
            $codigo = $collection_codigos->findOne(['_id' => $codigo_object_id]);
            
            if (!$codigo || !isset($codigo['id_usuario'])) {
                return false;
            }
            
            $codigo_owner_id = $codigo['id_usuario'];
            
            // Verificar si ya existe un registro para este viewer y código
            $filtro_existente = ['codigo_id' => $codigo_object_id];
            if ($viewer_user_id) {
                $filtro_existente['viewer_user_id'] = new MongoDB\BSON\ObjectId($viewer_user_id);
            } elseif ($session_id) {
                $filtro_existente['viewer_session_id'] = $session_id;
            }
            
            $existente = $collection_viewers->findOne($filtro_existente);
            
            if ($existente) {
                // Actualizar fecha de última vista
                $collection_viewers->updateOne(
                    ['_id' => $existente['_id']],
                    ['$set' => ['last_viewed_at' => new MongoDB\BSON\UTCDateTime()]]
                );
                return true;
            }
            
            // Crear nuevo registro
            $viewer_data = [
                'codigo_id' => $codigo_object_id,
                'codigo_owner_id' => $codigo_owner_id,
                'viewer_session_id' => $session_id ?? session_id(),
                'viewed_at' => new MongoDB\BSON\UTCDateTime(),
                'last_viewed_at' => new MongoDB\BSON\UTCDateTime(),
                'contacted' => false,
                'contacted_at' => null
            ];
            
            if ($viewer_user_id) {
                $viewer_data['viewer_user_id'] = new MongoDB\BSON\ObjectId($viewer_user_id);
                $viewer_data['registered_at'] = new MongoDB\BSON\UTCDateTime();
            }
            
            $collection_viewers->insertOne($viewer_data);
            
            // Enviar notificación por email al propietario del código si es un usuario registrado
            if ($viewer_user_id && $codigo_owner_id) {
                try {
                    // Obtener datos del propietario
                    $owner = get_object_user('_id', new MongoDB\BSON\ObjectId($codigo_owner_id));
                    
                    if ($owner && !empty($owner['mail'])) {
                        $is_vip_owner = es_usuario_vip($codigo_owner_id);
                        $marca_nombre = isset($codigo['marca']) ? ucfirst($codigo['marca']) : 'tu código';
                        $nombre_owner = $owner['username'] ?? 'Usuario';
                        
                        // Incluir helper si necesario
                        if (!function_exists('enviarEmailConBrevoYRegistrar')) {
                            if (file_exists(__DIR__ . '/email_helper.php')) {
                                include_once __DIR__ . '/email_helper.php';
                            }
                        }
                        
                        // Solo enviar si existe la función
                        if (function_exists('enviarEmailConBrevoYRegistrar')) {
                            $asunto = "¡Nuevo interesado en tu código de $marca_nombre!";
                            
                            // Mensaje diferenciado para VIP/No VIP
                            $cta_text = $is_vip_owner ? "Contactar ahora" : "Ver quién es";
                            $cta_link = "https://www.codigoamigo.com/public/mis_viewers.php";
                            
                            $msg_body = "<p>Hola <strong>$nombre_owner</strong>,</p>";
                            $msg_body .= "<p>¡Buenas noticias! Un usuario registrado acaba de ver tu código de <strong>$marca_nombre</strong>.</p>";
                            
                            if ($is_vip_owner) {
                                $msg_body .= "<p>Al ser VIP, puedes ver quién es y contactarle directamente para ayudarle y asegurar tu recompensa.</p>";
                            } else {
                                $msg_body .= "<p>Al ver tu código, es muy probable que vaya a usarlo. Hazte VIP para ver quién es y contactarle para asegurar el plan amigo.</p>";
                            }
                            
                            $msg_body .= "<div style='text-align: center; margin: 30px 0;'>";
                            $msg_body .= "<a href='$cta_link' style='background-color: #ffd700; color: #000; padding: 12px 25px; text-decoration: none; border-radius: 25px; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>$cta_text</a>";
                            $msg_body .= "</div>";
                            
                            $html_content = "
                                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333;'>
                                    <div style='background-color: #f8f9fa; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
                                        <h2 style='color: #333; margin: 0;'>¡Tienes un nuevo lead! 🎯</h2>
                                    </div>
                                    <div style='background-color: #ffffff; padding: 30px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 8px 8px;'>
                                        $msg_body
                                        <p style='margin-top: 30px; font-size: 13px; color: #888;'>Si no deseas recibir estas notificaciones, puedes configurar tus preferencias en tu perfil.</p>
                                    </div>
                                </div>
                            ";
                            
                            $text_content = strip_tags($html_content);
                            
                            // Enviar email
                            enviarEmailConBrevoYRegistrar(
                                $owner['mail'],
                                $nombre_owner,
                                $asunto,
                                $html_content,
                                'nuevo_viewer',
                                $codigo_owner_id,
                                ['codigo_id' => (string)$codigo_id, 'viewer_id' => (string)$viewer_user_id],
                                $text_content
                            );
                        }
                    }
                } catch (Throwable $e_mail) {
                    // Silenciar errores de email para no fallar la request
                    error_log("Error enviando email de nuevo viewer: " . $e_mail->getMessage());
                }
            }

            return true;
        } catch (Throwable $e) {
            error_log("Error en registrar_vista_codigo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene todos los viewers de los códigos de un usuario
     * @param string $user_id ID del propietario de los códigos
     * @return array Lista de viewers con información de potencial
     */
    function obtener_viewers_usuario($user_id) {
        if (empty($user_id)) {
            return [];
        }
        
        try {
            $collection_viewers = getCollectionCodeViewers();
            $collection_codigos = getCollectionCodigos();
            $collection_usuarios = getCollectionUsuarios();
            
            $object_id = is_string($user_id) ? new MongoDB\BSON\ObjectId($user_id) : $user_id;
            
            // Obtener viewers registrados (que tienen viewer_user_id)
            $viewers = $collection_viewers->find([
                'codigo_owner_id' => $object_id,
                'viewer_user_id' => ['$exists' => true, '$ne' => null]
            ], ['sort' => ['viewed_at' => -1]]);
            
            $resultado = [];
            $viewers_procesados = [];
            $total_potencial = 0;
            
            foreach ($viewers as $viewer) {
                $viewer_user_id = (string)$viewer['viewer_user_id'];
                
                // Evitar duplicados por usuario
                if (in_array($viewer_user_id, $viewers_procesados)) {
                    continue;
                }
                $viewers_procesados[] = $viewer_user_id;
                
                // Obtener datos del viewer
                $viewer_user = $collection_usuarios->findOne([
                    '_id' => new MongoDB\BSON\ObjectId($viewer_user_id)
                ]);
                
                // Obtener datos del código
                $codigo = $collection_codigos->findOne(['_id' => $viewer['codigo_id']]);
                
                $beneficio = $codigo['num_beneficio'] ?? 0;
                $marca = $codigo['marca'] ?? 'Desconocida';
                
                $total_potencial += $beneficio;
                
                $resultado[] = [
                    'viewer_id' => $viewer_user_id,
                    'viewer_username' => $viewer_user['username'] ?? 'Usuario',
                    'viewer_email' => $viewer_user['mail'] ?? '',
                    'viewer_img' => $viewer_user['img'] ?? '',
                    'codigo_id' => (string)$viewer['codigo_id'],
                    'codigo_marca' => $marca,
                    'codigo_beneficio' => $beneficio,
                    'viewed_at' => $viewer['viewed_at'],
                    'contacted' => $viewer['contacted'] ?? false,
                    'contacted_at' => $viewer['contacted_at'] ?? null
                ];
            }
            
            return [
                'viewers' => $resultado,
                'total_viewers' => count($resultado),
                'total_potencial' => $total_potencial
            ];
        } catch (Throwable $e) {
            error_log("Error en obtener_viewers_usuario: " . $e->getMessage());
            return ['viewers' => [], 'total_viewers' => 0, 'total_potencial' => 0];
        }
    }
    
    /**
     * Verifica si un usuario VIP puede contactar a un viewer
     * @param string $user_id ID del usuario que quiere contactar
     * @param string $viewer_id ID del viewer a contactar
     * @return array ['puede' => bool, 'razon' => string]
     */
    function puede_contactar_viewer($user_id, $viewer_id) {
        if (empty($user_id) || empty($viewer_id)) {
            return ['puede' => false, 'razon' => 'IDs inválidos'];
        }
        
        // Verificar si es usuario VIP
        if (!es_usuario_vip($user_id)) {
            return [
                'puede' => false, 
                'razon' => 'Necesitas ser VIP para contactar viewers',
                'es_vip_requerido' => true
            ];
        }
        
        try {
            $collection_viewers = getCollectionCodeViewers();
            $object_id = is_string($user_id) ? new MongoDB\BSON\ObjectId($user_id) : $user_id;
            $viewer_object_id = is_string($viewer_id) ? new MongoDB\BSON\ObjectId($viewer_id) : $viewer_id;
            
            // Verificar que el viewer vio un código del usuario
            $viewer_registro = $collection_viewers->findOne([
                'codigo_owner_id' => $object_id,
                'viewer_user_id' => $viewer_object_id
            ]);
            
            if (!$viewer_registro) {
                return [
                    'puede' => false, 
                    'razon' => 'Este usuario no ha visto ninguno de tus códigos'
                ];
            }
            
            return ['puede' => true, 'razon' => 'OK'];
        } catch (Throwable $e) {
            error_log("Error en puede_contactar_viewer: " . $e->getMessage());
            return ['puede' => false, 'razon' => 'Error interno'];
        }
    }
    
    /**
     * Marca un viewer como contactado
     * @param string $user_id ID del propietario del código
     * @param string $viewer_id ID del viewer contactado
     * @return bool True si se actualizó correctamente
     */
    function marcar_viewer_contactado($user_id, $viewer_id) {
        if (empty($user_id) || empty($viewer_id)) {
            return false;
        }
        
        try {
            $collection_viewers = getCollectionCodeViewers();
            $object_id = is_string($user_id) ? new MongoDB\BSON\ObjectId($user_id) : $user_id;
            $viewer_object_id = is_string($viewer_id) ? new MongoDB\BSON\ObjectId($viewer_id) : $viewer_id;
            
            $result = $collection_viewers->updateMany(
                [
                    'codigo_owner_id' => $object_id,
                    'viewer_user_id' => $viewer_object_id
                ],
                ['$set' => [
                    'contacted' => true,
                    'contacted_at' => new MongoDB\BSON\UTCDateTime()
                ]]
            );
            
            return $result->getModifiedCount() > 0;
        } catch (Throwable $e) {
            error_log("Error en marcar_viewer_contactado: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envía mensaje a múltiples destinatarios
     * Retorna array con resultados
     */
    function enviarMensajeMasivo($de_usuario_id, $destinatarios_ids, $mensaje) {
        if (empty($destinatarios_ids) || empty($mensaje)) {
            return ['enviados' => 0, 'fallidos' => 0, 'total' => 0];
        }

        // Aumentar tiempo de ejecución para envíos masivos
        if (function_exists('set_time_limit')) {
            set_time_limit(300); // 5 minutos
        }

        $stats = ['enviados' => 0, 'fallidos' => 0, 'total' => count($destinatarios_ids)];
        
        // Determinar si es admin (opcional, por defecto false)
        $es_admin = false;
        
        foreach ($destinatarios_ids as $para_id) {
            try {
                // Verificar que no sea el mismo usuario
                if ((string)$para_id === (string)$de_usuario_id) continue;
                
                // Enviar mensaje (esto también envía email notification)
                $res = enviarMensaje($de_usuario_id, $para_id, $mensaje, $es_admin);
                if ($res) {
                    $stats['enviados']++;
                } else {
                    $stats['fallidos']++;
                }
                
                // Pequeña pausa para no saturar SMTP si son muchos
                if ($stats['enviados'] % 5 === 0) {
                    usleep(200000); // 0.2 segundos
                }
                
            } catch (Throwable $e) {
                error_log("Error en enviarMensajeMasivo destinatario $para_id: " . $e->getMessage());
                $stats['fallidos']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Obtiene información del badge VIP para mostrar en UI
     * @param string $user_id ID del usuario
     * @return array Información del badge
     */
    function obtener_info_badge_vip($user_id) {
        if (empty($user_id)) {
            return ['is_vip' => false];
        }
        
        try {
            $collection_usuarios = getCollectionUsuarios();
            $object_id = is_string($user_id) ? new MongoDB\BSON\ObjectId($user_id) : $user_id;
            
            $usuario = $collection_usuarios->findOne(['_id' => $object_id]);
            
            if (!$usuario) {
                return ['is_vip' => false];
            }
            
            $is_vip = es_usuario_vip($user_id);
            
            return [
                'is_vip' => $is_vip,
                'badge_class' => $is_vip ? 'vip-badge-gold' : '',
                'badge_icon' => $is_vip ? 'fas fa-crown' : '',
                'badge_text' => $is_vip ? 'VIP Verificado' : '',
                'vip_since' => $usuario['vip_started_at'] ?? null,
                'vip_expires' => $usuario['vip_expires_at'] ?? null
            ];
        } catch (Throwable $e) {
            error_log("Error en obtener_info_badge_vip: " . $e->getMessage());
            return ['is_vip' => false];
        }
    }


    /******************************************************
     *  FUNCIONES DE FAVORITOS
     * ***************************************************/

    /**
     * Comprueba si un chollo es favorito de un usuario
     * Wrapper para compatibilidad - usa funciones_favoritos.php si está disponible
     */
    if (!function_exists('es_favorito')) {
        function es_favorito($user_id, $codigo_id) {
            if (empty($user_id) || empty($codigo_id)) {
                return false;
            }

            try {
                $collection_favoritos = getCollectionFavoritos();
                if (!$collection_favoritos) {
                    return false;
                }

                $user_id_obj = is_string($user_id) ? new MongoDB\BSON\ObjectId($user_id) : $user_id;
                $codigo_id_obj = is_string($codigo_id) ? new MongoDB\BSON\ObjectId($codigo_id) : $codigo_id;

                $favorito = $collection_favoritos->findOne([
                    'usuario_id' => $user_id_obj,
                    'codigo_id' => $codigo_id_obj,
                    'tipo' => 'chollo'
                ]);

                return !empty($favorito);
            } catch (Throwable $e) {
                error_log("Error en es_favorito: " . $e->getMessage());
                return false;
            }
        }
    }

    /**
     * Añade un chollo a favoritos
     */
    if (!function_exists('añadir_favorito')) {
    function añadir_favorito($user_id, $codigo_id) {
        if (empty($user_id) || empty($codigo_id)) {
            return ['success' => false, 'message' => 'Datos incompletos'];
        }

        try {
            $collection_favoritos = getCollectionFavoritos();
            if (!$collection_favoritos) {
                return ['success' => false, 'message' => 'Error de conexión'];
            }

            $user_id_obj = is_string($user_id) ? new \MongoDB\BSON\ObjectId($user_id) : $user_id;
            $codigo_id_obj = is_string($codigo_id) ? new \MongoDB\BSON\ObjectId($codigo_id) : $codigo_id;

            // Verificar si ya existe
            if (es_favorito($user_id, $codigo_id)) {
                return ['success' => true, 'message' => 'Ya estaba en favoritos'];
            }

            $nuevo_favorito = [
                'usuario_id' => $user_id_obj,
                'codigo_id' => $codigo_id_obj,
                'tipo' => 'chollo',
                'fecha_creacion' => new \MongoDB\BSON\UTCDateTime()
            ];

            $result = $collection_favoritos->insertOne($nuevo_favorito);

            if ($result->getInsertedId()) {
                // Opcional: Incrementar contador de "me gusta" o temperatura en el chollo si se desea
                 // Aumentar temperatura al guardar en favoritos (Feature solicitada anteriormente)
                 if (function_exists('aumentarTemperatura')) {
                    // Si existe la función en el scope, usarla. Sino, habría que incluir funciones_chollos_votos.php
                    // Por ahora simple
                 }
                  
                 // Implementación directa de temperatura +1
                 try {
                     $db = createConnection();
                     $collection_chollos = $db->selectCollection('chollos');
                     $collection_chollos->updateOne(
                        ['_id' => $codigo_id_obj],
                        ['$inc' => ['temperatura' => 1]]
                     );
                 } catch(Exception $e) {
                     // Ignorar error de temperatura
                 }

                return ['success' => true, 'message' => 'Añadido a favoritos'];
            } else {
                return ['success' => false, 'message' => 'Error al guardar'];
            }

        } catch (Throwable $e) {
            error_log("Error en añadir_favorito: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno'];
        }
    }
    }

    /**
     * Elimina un chollo de favoritos
     */
    if (!function_exists('eliminar_favorito')) {
    function eliminar_favorito($user_id, $codigo_id) {
        if (empty($user_id) || empty($codigo_id)) {
            return ['success' => false, 'message' => 'Datos incompletos'];
        }

        try {
            $collection_favoritos = getCollectionFavoritos();
            if (!$collection_favoritos) {
                return ['success' => false, 'message' => 'Error de conexión'];
            }

            $user_id_obj = is_string($user_id) ? new \MongoDB\BSON\ObjectId($user_id) : $user_id;
            $codigo_id_obj = is_string($codigo_id) ? new \MongoDB\BSON\ObjectId($codigo_id) : $codigo_id;

            $result = $collection_favoritos->deleteOne([
                'usuario_id' => $user_id_obj,
                'codigo_id' => $codigo_id_obj,
                'tipo' => 'chollo'
            ]);

            if ($result->getDeletedCount() > 0) {
                return ['success' => true, 'message' => 'Eliminado de favoritos'];
            } else {
                return ['success' => true, 'message' => 'No estaba en favoritos']; // Consideramos success si ya no está
            }

        } catch (Throwable $e) {
            error_log("Error en eliminar_favorito: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno'];
        }
    }
    }
