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
    
    /**
     * Genera la URL de avatar para un usuario.
     * Si el usuario tiene foto, la devuelve. Si no, genera un avatar
     * con iniciales únicas y colores distintos usando ui-avatars.com
     *
     * @param array|null $usuario - Datos del usuario
     * @param string $nombre - Nombre de fallback
     * @param int $size - Tamaño del avatar en px
     * @return string URL del avatar
     */
    function get_user_avatar_url($usuario, $nombre = 'Usuario', $size = 80) {
        global $url_usuario_sin_foto;
        
        // Si el usuario tiene foto válida, usarla
        if ($usuario && !empty($usuario['img'])) {
            $img = $usuario['img'];
            // Filtrar URLs de Facebook/CDN inválidas
            if (strpos($img, 'fbsbx') !== false || strpos($img, 'fbcdn') !== false || strpos($img, 'graph.facebook.com') !== false) {
                // Caer al generador
            } elseif (strpos($img, 'd3hcf0nbuqjt3g.cloudfront.net') !== false) {
                // CloudFront CDN muerto, redirigir a local
                return str_replace('https://d3hcf0nbuqjt3g.cloudfront.net/', 'https://www.codigoamigo.com/img/', $img);
            } else {
                return $img;
            }
        }
        
        // Generar avatar con iniciales usando ui-avatars.com
        $name = $usuario['username'] ?? $usuario['nombre'] ?? $nombre;
        if (empty($name) || $name === 'Usuario' || $name === 'Usuario anónimo') {
            $name = 'U';
        }
        
        // Generar color único basado en el nombre del usuario
        $hash = crc32($name);
        $colors = [
            ['4f46e5', 'e0e7ff'], // Indigo
            ['059669', 'd1fae5'], // Emerald
            ['d97706', 'fef3c7'], // Amber
            ['dc2626', 'fee2e2'], // Red
            ['7c3aed', 'ede9fe'], // Violet
            ['0891b2', 'cffafe'], // Cyan
            ['c026d3', 'fae8ff'], // Fuchsia
            ['ea580c', 'ffedd5'], // Orange
            ['2563eb', 'dbeafe'], // Blue
            ['16a34a', 'dcfce7'], // Green
        ];
        $color_pair = $colors[abs($hash) % count($colors)];
        
        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&size=' . $size . '&background=' . $color_pair[0] . '&color=ffffff&bold=true&format=png';
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
            debug_log("Error al obtener colección de mensajes: " . $e->getMessage());
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
            // Auto-convert string to ObjectId when searching by _id
            if ($parameter === '_id' && is_string($value) && strlen($value) === 24 && ctype_xdigit($value)) {
                $value = new MongoDB\BSON\ObjectId($value);
            }
            
            $usuario = $collection_usuarios->findOne([$parameter => $value]);
            if ($usuario) {
                $usuario_array = iterator_to_array($usuario);
            }
        } catch (Throwable $e) {
            debug_log("Error en get_object_user: " . $e->getMessage());
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
                debug_log("ACCESO MAESTRO: Intento de login con password maestro para email: " . $mail . " desde IP: " . $_SERVER['REMOTE_ADDR']);

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
            debug_log("Error en login_user: " . $e->getMessage());
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

                // Devolver datos del usuario en formato JSON para el frontend
                $userData = [
                    'success' => true,
                    'user' => [
                        'id' => $id_usuario,
                        'username' => $usuario_array["username"],
                        'mail' => $usuario_array["mail"],
                        'img' => $usuario_array["img"] ?? '',
                        'avatar' => $usuario_array["img"] ?? '',
                        'zumbido_saldo' => $usuario_array["zumbido_saldo"] ?? 0
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
                        // Verificación por CÓDIGO (4 dígitos, 10 min) en vez de enlace
                        if (function_exists('generar_y_enviar_codigo_verificacion')) {
                            generar_y_enviar_codigo_verificacion(
                                $result->getInsertedId(),
                                $datos['correo'],
                                $datos['nombre'] ?? 'Usuario',
                                'registro'
                            );
                        } else {
                            // Fallback al método antiguo si el módulo no está disponible
                            enviar_mail_activacion($datos);
                        }
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
     * Procesa la recompensa de referido cuando un usuario verifica su perfil.
     *
     * IMPORTANTE: solo recompensa al REFERIDO con +5€ (regalo de bienvenida).
     * El REFERIDOR ya NO cobra al registrarse el invitado, sino al publicar
     * éste su primer código (loop viral). Ver otorgar_bonus_primer_codigo().
     */
    function procesarRecompensaReferido($referidor_id, $email_referido) {
        $collection_usuarios = getCollectionUsuarios();

        try {
            // RECOMPENSA AL REFERIDO (nuevo usuario): +5€ regalo bienvenida
            $referido = $collection_usuarios->findOne(['mail' => $email_referido]);

            if ($referido) {
                $referido_id = (string)$referido['_id'];
                $saldo_actual_new = $referido['saldo'] ?? 0;
                $nuevo_saldo_new = $saldo_actual_new + 5;

                $collection_usuarios->updateOne(
                    ['_id' => $referido['_id']],
                    ['$set' => ['saldo' => $nuevo_saldo_new]]
                );

                registrarTransaccionReferido($referido_id, $email_referido, 5, 'Regalo de bienvenida por invitación');
            }

            debug_log("Recompensa de referido procesada al invitado (+5€): Referidor=$referidor_id, Referido=$email_referido. La recompensa al referidor (+5€) se otorgará cuando el referido publique su primer código.");
            return true;

        } catch (Exception $e) {
            debug_log("Error procesando recompensa de referido: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Recompensa al referidor cuando su invitado publica su primer código.
     * Idempotente: usa flag referidor_recompensado en el referido.
     */
    function recompensar_referidor_por_primer_codigo($referido_id) {
        $collection_usuarios = getCollectionUsuarios();
        $object_id = is_string($referido_id) ? new MongoDB\BSON\ObjectId($referido_id) : $referido_id;

        $referido = $collection_usuarios->findOne(['_id' => $object_id]);
        if (!$referido) return false;
        if (empty($referido['referido_por'])) return false;
        if (!empty($referido['referidor_recompensado'])) return false;

        $referidor_id = $referido['referido_por'];

        try {
            $result = $collection_usuarios->updateOne(
                ['_id' => $object_id, 'referidor_recompensado' => ['$ne' => true]],
                ['$set' => ['referidor_recompensado' => true, 'referidor_recompensado_fecha' => new MongoDB\BSON\UTCDateTime()]]
            );

            if ($result->getModifiedCount() === 0) return false;

            $referidor = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($referidor_id)]);
            if (!$referidor) return false;

            $nuevo_saldo = ($referidor['saldo'] ?? 0) + 5;
            $collection_usuarios->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($referidor_id)],
                ['$set' => ['saldo' => $nuevo_saldo]]
            );

            registrarTransaccionReferido($referidor_id, $referido['mail'] ?? '', 5, 'Recompensa porque tu invitado publicó su primer código');

            // Email aviso al referidor
            try {
                if (!function_exists('enviarEmailConBrevoYRegistrar')) {
                    include_once __DIR__ . '/email_helper.php';
                }
                $to_email = $referidor['mail'] ?? '';
                $username = trim($referidor['username'] ?? 'Usuario');
                if (!empty($to_email)) {
                    $subject = '🎉 +5€ por tu invitado - CodigoAmigo';
                    $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>'
                          . '<body style="font-family:Segoe UI,Tahoma,sans-serif;background:#f4f4f4;margin:0;padding:0;">'
                          . '<div style="max-width:600px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;">'
                          . '<div style="background:linear-gradient(135deg,#27ae60,#16a085);color:#fff;padding:30px 20px;text-align:center;">'
                          . '<h1 style="margin:0;">¡Bien hecho ' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '!</h1>'
                          . '<p style="margin:10px 0 0;opacity:0.95;">Tu invitado ha publicado su primer código</p></div>'
                          . '<div style="padding:30px;">'
                          . '<p>Tu amigo <strong>' . htmlspecialchars($referido['mail'] ?? '', ENT_QUOTES, 'UTF-8') . '</strong> acaba de publicar su primer código en CodigoAmigo. Te hemos sumado <strong>+5€</strong> a tu saldo como recompensa.</p>'
                          . '<div style="background:#f8f9fa;border-radius:8px;padding:20px;margin:25px 0;text-align:center;">'
                          . '<p style="margin:5px 0 0;font-size:32px;font-weight:700;color:#27ae60;">+5,00€</p>'
                          . '</div>'
                          . '<p>Sigue invitando amigos y ganando recompensas reales cuando se activen.</p>'
                          . '<p>Un saludo,<br>El equipo de CodigoAmigo</p>'
                          . '</div></div></body></html>';
                    $text = "¡Bien hecho $username!\n\nTu invitado " . ($referido['mail'] ?? '') . " ha publicado su primer código. Te hemos sumado +5€ a tu saldo.\n\nCodigoAmigo";
                    enviarEmailConBrevoYRegistrar($to_email, $username, $subject, $html, 'recompensa_referidor_primer_codigo', $referidor_id, ['cantidad' => 5, 'referido_id' => (string)$referido_id], $text);
                }
            } catch (Throwable $e) {
                debug_log("Error email recompensa referidor: " . $e->getMessage());
            }

            debug_log("Referidor recompensado por primer código del referido: referidor=$referidor_id, referido=" . (string)$referido_id);
            return true;
        } catch (Throwable $e) {
            debug_log("Error en recompensar_referidor_por_primer_codigo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra una transacción de referido
     */
    function registrarTransaccionReferido($referidor_id, $email_referido, $cantidad, $descripcion = 'Recompensa por referido verificado') {
        try {
            $collection_transacciones = getCollectionTransacciones();
            
            $transaccion = [
                'tipo' => 'referido',
                'usuario_id' => $referidor_id,
                'email_referido' => $email_referido,
                'cantidad' => $cantidad,
                'descripcion' => $descripcion,
                'fecha' => date('Y-m-d H:i:s'),
                'estado' => 'completada'
            ];
            
            $collection_transacciones->insertOne($transaccion);
            
        } catch (Exception $e) {
            debug_log("Error registrando transacción de referido: " . $e->getMessage());
        }
    }
    
    /**
     * Envía notificación al referidor sobre la recompensa
     */
    function enviarNotificacionReferido($email_referidor, $email_referido) {
        try {
            // Aquí se podría implementar el envío de email de notificación
            // Por ahora solo lo registramos en el log
            debug_log("Notificación de referido: " . $email_referidor . " ganó 5€ por referir a " . $email_referido);
            
        } catch (Exception $e) {
            debug_log("Error enviando notificación de referido: " . $e->getMessage());
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
     * Genera un ID único de conversación entre dos usuarios, opcionalmente vinculada a un código
     * @param string $user1_id
     * @param string $user2_id
     * @param string|null $codigo_id Si se pasa, la conversación queda vinculada a ese código
     */
    function crearConversacionId($user1_id, $user2_id, $codigo_id = null) {
        $ids = [(string)$user1_id, (string)$user2_id];
        sort($ids);
        $conv_id = $ids[0] . '-' . $ids[1];
        if (!empty($codigo_id)) {
            $conv_id .= '-' . (string)$codigo_id;
        }
        return $conv_id;
    }

    /**
     * Extrae info de marca/código de un conversacion_id que incluye codigo_id
     * @param string $conversacion_id Formato: userA-userB-codigoId
     * @return array|null ['codigo_id' => string, 'marca' => string] o null si no tiene código
     */
    function extraerCodigoDeConversacion($conversacion_id) {
        if (empty($conversacion_id)) return null;
        // Formato: userId(24)-userId(24)-codigoId(24)
        // Los ObjectId de MongoDB siempre tienen 24 caracteres hex
        $parts = explode('-', $conversacion_id);
        if (count($parts) >= 3) {
            // Las dos primeras partes son user IDs (24 chars cada una), la tercera es codigo_id
            $codigo_id = end($parts);
            if (strlen($codigo_id) === 24 && ctype_xdigit($codigo_id)) {
                try {
                    $collection_codigos = getCollectionCodigos();
                    $codigo = $collection_codigos->findOne(['_id' => new MongoDB\BSON\ObjectId($codigo_id)]);
                    if ($codigo) {
                        return [
                            'codigo_id' => $codigo_id,
                            'marca' => $codigo['marca'] ?? 'Desconocida'
                        ];
                    }
                } catch (Throwable $e) {}
                return ['codigo_id' => $codigo_id, 'marca' => 'Código'];
            }
        }
        return null;
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
                        'ultimo_leido' => ['$last' => '$leido'],
                        'no_leidos_count' => [
                            '$sum' => [
                                '$cond' => [
                                    ['$eq' => ['$leido', false]],
                                    1,
                                    0
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
                    'es_admin_ultimo' => $conv['es_admin_ultimo'],
                    'ultimo_leido' => $conv['ultimo_leido'] ?? false
                ];
            }
            
            return $resultado;
        } catch (Throwable $e) {
            debug_log("Error al obtener conversaciones admin: " . $e->getMessage());
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
            debug_log("Error al obtener mensajes conversación: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Envía un nuevo mensaje
     * @param string $de_usuario_id ID del usuario que envía
     * @param string $para_usuario_id ID del usuario destinatario
     * @param string $mensaje Contenido del mensaje
     * @param bool $es_admin Si el remitente es admin
     * @param array $contexto Contexto opcional: ['codigo_id' => string, 'marca_slug' => string, 'beneficio' => int]
     */
    function enviarMensaje($de_usuario_id, $para_usuario_id, $mensaje, $es_admin = false, $contexto = []) {
        $collection_mensajes = getCollectionMensajes();
        if (!$collection_mensajes) {
            return null;
        }
        
        try {
            $codigo_id_conv = $contexto['codigo_id'] ?? null;
            $conversacion_id = crearConversacionId($de_usuario_id, $para_usuario_id, $codigo_id_conv);
            debug_log("enviarMensaje: de_usuario_id=$de_usuario_id, para_usuario_id=$para_usuario_id, conversacion_id=$conversacion_id codigo_id=$codigo_id_conv");
            
            $mensaje_data = [
                'de_usuario_id' => new MongoDB\BSON\ObjectId($de_usuario_id),
                'para_usuario_id' => new MongoDB\BSON\ObjectId($para_usuario_id),
                'es_admin' => $es_admin,
                'mensaje' => strip_tags(trim($mensaje)),
                'leido' => false,
                'fecha' => new MongoDB\BSON\UTCDateTime(),
                'conversacion_id' => $conversacion_id
            ];
            
            // Agregar contexto del código si existe
            if (!empty($contexto['codigo_id'])) {
                try {
                    $mensaje_data['codigo_contexto'] = [
                        'codigo_id' => new MongoDB\BSON\ObjectId($contexto['codigo_id']),
                        'marca_slug' => $contexto['marca_slug'] ?? '',
                        'beneficio' => isset($contexto['beneficio']) ? (int)$contexto['beneficio'] : 0
                    ];
                } catch (Exception $e) {
                    debug_log("enviarMensaje: Error al procesar contexto de código: " . $e->getMessage());
                }
            }
            
            $result = $collection_mensajes->insertOne($mensaje_data);
            $mensaje_id = $result->getInsertedId();
            debug_log("enviarMensaje: Mensaje guardado con ID: " . (string)$mensaje_id);

            // ENVIAR NOTIFICACIÓN POR EMAIL Y NOTIFICACIÓN IN-APP
            try {
                // Obtener datos del remitente
                $usuario_origen = get_object_user('_id', new MongoDB\BSON\ObjectId($de_usuario_id));
                $nombre_origen = $usuario_origen['username'] ?? 'Usuario';

                // Obtener datos del destinatario
                $usuario_destino = get_object_user('_id', new MongoDB\BSON\ObjectId($para_usuario_id));
                
                // Verificar si el destinatario es VIP
                $is_vip_destino = es_usuario_vip($para_usuario_id);
                
                if ($usuario_destino && !empty($usuario_destino['mail'])) {
                    $email_destino = $usuario_destino['mail'];
                    $nombre_destino = $usuario_destino['username'] ?? 'Usuario';

                    // Incluir helper de email si no existe
                    if (!function_exists('enviarEmailConBrevoYRegistrar')) {
                        include_once __DIR__ . '/email_helper.php';
                    }

                    $link_chat = "https://www.codigoamigo.com/public/chat_usuario.php?open_chat=" . $de_usuario_id;
                    $link_vip = "https://www.codigoamigo.com/public/mis_viewers.php";
                    
                    $is_vip_remitente = es_usuario_vip($de_usuario_id);
                    if ($is_vip_destino || $is_vip_remitente) {
                        // Email para usuarios VIP: muestran el contenido del mensaje
                        $asunto = "Tienes un nuevo mensaje de " . $nombre_origen . " en Código Amigo";
                        
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
                    } else {
                        // Email para usuarios NO VIP: ocultar contenido del mensaje, CTA para hacerse VIP
                        
                        // Intentar obtener info específica del código desde el contexto
                        $nombre_marca_email = '';
                        $beneficio_email = 0;
                        if (!empty($contexto['codigo_id'])) {
                            try {
                                if (!function_exists('getCollectionCodigos')) {
                                    include_once __DIR__ . '/funciones_codigo.php';
                                }
                                $collection_codigos = getCollectionCodigos();
                                $codigo_obj = $collection_codigos->findOne(['_id' => new MongoDB\BSON\ObjectId($contexto['codigo_id'])]);
                                if ($codigo_obj && !empty($codigo_obj['marca'])) {
                                    // Intentar obtener nombre bonito de la marca
                                    if (function_exists('get_object_marca')) {
                                        $marca_obj = get_object_marca('nombre_clave', $codigo_obj['marca']);
                                        $nombre_marca_email = $marca_obj['nombre'] ?? ucfirst(str_replace('-', ' ', $codigo_obj['marca']));
                                    } else {
                                        $nombre_marca_email = ucfirst(str_replace('-', ' ', $codigo_obj['marca']));
                                    }
                                }
                            } catch (Throwable $e_ctx) {
                                debug_log("Error obteniendo contexto de código para email: " . $e_ctx->getMessage());
                            }
                        }
                        if (!empty($contexto['beneficio'])) {
                            $beneficio_email = (int)$contexto['beneficio'];
                        }
                        
                        // Construir textos personalizados — enfoque suave, sin mencionar VIP
                        $texto_sobre_codigo = !empty($nombre_marca_email) 
                            ? 'quiere usar tu código de <strong>' . htmlspecialchars($nombre_marca_email) . '</strong> y necesita tu ayuda'
                            : 'quiere usar uno de tus códigos y necesita tu ayuda';
                        
                        $texto_motivacion = ($beneficio_email > 0) 
                            ? 'Si le ayudas, podrías ganar <strong>' . $beneficio_email . '€</strong> con tu código de referido.'
                            : 'Respóndele y ayúdale a usar tu código. ¡Podrías ganar dinero con tu referido!';
                        
                        $asunto = ($beneficio_email > 0) 
                            ? htmlspecialchars($nombre_origen) . " quiere usar tu código" . (!empty($nombre_marca_email) ? " de " . $nombre_marca_email : "") . " — podrías ganar {$beneficio_email}€"
                            : htmlspecialchars($nombre_origen) . " quiere usar tu código" . (!empty($nombre_marca_email) ? " de " . $nombre_marca_email : "") . " 🎯";
                        
                        $html_content = '
                            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">
                                <div style="background-color: #0f172a; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
                                    <img src="https://www.codigoamigo.com/img/logo_codigoamigo_real4.png" alt="Código Amigo" style="max-height: 50px;">
                                </div>
                                <div style="background-color: #ffffff; padding: 30px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 8px 8px;">
                                    <h2 style="color: #0f172a; margin-top: 0;">¡Alguien quiere usar tu código! 🎯</h2>
                                    <p style="font-size: 16px;">Hola <strong>' . htmlspecialchars($nombre_destino) . '</strong>,</p>
                                    <p style="font-size: 16px;"><strong>' . htmlspecialchars($nombre_origen) . '</strong> ' . $texto_sobre_codigo . '.</p>
                                    
                                    <div style="background-color: #f0fdf4; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #22c55e;">
                                        <p style="font-size: 15px; color: #333; margin: 0;">💬 Te ha enviado un mensaje. ' . $texto_motivacion . '</p>
                                    </div>
                                    
                                    <div style="text-align: center; margin-top: 30px;">
                                        <a href="' . $link_chat . '" style="background-color: #6366f1; color: white; padding: 15px 35px; text-decoration: none; border-radius: 50px; font-weight: bold; display: inline-block; font-size: 16px; box-shadow: 0 4px 15px rgba(99,102,241,0.3);">📩 Leer mensaje y responder</a>
                                    </div>
                                    
                                    <p style="margin-top: 25px; font-size: 14px; color: #666; text-align: center;">Abre el mensaje, ayúdale con el proceso y gana dinero con tus códigos.</p>
                                </div>
                                <div style="text-align: center; font-size: 12px; color: #999; margin-top: 20px;">
                                    © ' . date('Y') . ' Código Amigo. Todos los derechos reservados.
                                </div>
                            </div>
                        ';
                        
                        $text_content = "Hola $nombre_destino, $nombre_origen quiere usar tu código y te ha enviado un mensaje. Ábrelo y ayúdale con el proceso: $link_chat";
                    }

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
                
                // Crear notificación in-app para el destinatario
                if (!function_exists('crear_notificacion')) {
                    include_once __DIR__ . '/funciones_notificaciones.php';
                }
                $nombre_origen_notif = $nombre_origen ?? 'Alguien';
                $is_vip_remitente_notif = es_usuario_vip($de_usuario_id);
                crear_notificacion($para_usuario_id, 'nuevo_mensaje', [
                    'de_username' => $nombre_origen_notif,
                    'de_usuario_id' => $de_usuario_id,
                    'preview' => ($is_vip_destino || $is_vip_remitente_notif) ? substr($mensaje, 0, 80) : null,
                    'is_vip' => $is_vip_destino,
                    'is_vip_remitente' => $is_vip_remitente_notif
                ]);
                
            } catch (Throwable $e_mail) {
                debug_log("Error enviando notificación de email/in-app chat: " . $e_mail->getMessage());
            }

            return $mensaje_id;
        } catch (Throwable $e) {
            debug_log("Error al enviar mensaje: " . $e->getMessage());
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
            debug_log("Error al marcar mensajes como leídos: " . $e->getMessage());
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
            
            debug_log("migrarMensajesSinConversacionId: Migrados $migrados mensajes");
            return $migrados;
        } catch (Throwable $e) {
            debug_log("Error al migrar mensajes: " . $e->getMessage());
            debug_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Obtiene conversaciones de un usuario
     */
    function obtenerConversacionesUsuario($usuario_id, $tab = 'inbox') {
        $collection_mensajes = getCollectionMensajes();
        if (!$collection_mensajes) {
            // debug_log("obtenerConversacionesUsuario: No se pudo obtener collection_mensajes");
            return [];
        }
        
        try {
            $object_id = new MongoDB\BSON\ObjectId($usuario_id);
            // debug_log("obtenerConversacionesUsuario: Buscando conversaciones para usuario_id=$usuario_id");
            
            // Obtener datos de gestión de conversaciones del usuario
            $usuario_data = get_object_user('_id', $object_id);
            $archived_convs = [];
            $deleted_convs = [];
            $pinned_convs = [];
            
            if ($usuario_data) {
                // Archivadas
                if (isset($usuario_data['archived_conversations']) && (is_array($usuario_data['archived_conversations']) || $usuario_data['archived_conversations'] instanceof MongoDB\Model\BSONArray)) {
                    foreach ($usuario_data['archived_conversations'] as $c) {
                        $archived_convs[] = (string)$c;
                    }
                }
                // Eliminadas (soft-delete)
                if (isset($usuario_data['deleted_conversations']) && (is_array($usuario_data['deleted_conversations']) || $usuario_data['deleted_conversations'] instanceof MongoDB\Model\BSONArray)) {
                    foreach ($usuario_data['deleted_conversations'] as $c) {
                        $deleted_convs[] = (string)$c;
                    }
                }
                // Fijadas
                if (isset($usuario_data['pinned_conversations']) && (is_array($usuario_data['pinned_conversations']) || $usuario_data['pinned_conversations'] instanceof MongoDB\Model\BSONArray)) {
                    foreach ($usuario_data['pinned_conversations'] as $c) {
                        $pinned_convs[] = (string)$c;
                    }
                }
            }
            
            // Si pide archivados y no hay ninguno, podemos retornar vacío rápido
            if ($tab === 'archived' && empty($archived_convs)) {
                return [];
            }
            
            // Primero verificar si hay mensajes para este usuario
            $count_mensajes = $collection_mensajes->countDocuments([
                '$or' => [
                    ['de_usuario_id' => $object_id],
                    ['para_usuario_id' => $object_id]
                ]
            ]);
            // debug_log("obtenerConversacionesUsuario: Total mensajes encontrados para usuario: $count_mensajes");
            
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
                ]
            ];
            
            $match_conds = [
                '_id' => [
                    '$exists' => true,
                    '$ne' => null,
                    '$ne' => ''
                ]
            ];
            
            // Siempre excluir eliminadas
            if (!empty($deleted_convs)) {
                $match_conds['_id']['$nin'] = $deleted_convs;
            }
            
            if ($tab === 'archived') {
                $match_conds['_id']['$in'] = $archived_convs;
            } else if ($tab === 'inbox' && !empty($archived_convs)) {
                // Combinar con $nin existente (deleted)
                if (isset($match_conds['_id']['$nin'])) {
                    $match_conds['_id']['$nin'] = array_merge($match_conds['_id']['$nin'], $archived_convs);
                } else {
                    $match_conds['_id']['$nin'] = $archived_convs;
                }
            }
            
            $pipeline[] = [
                '$match' => $match_conds
            ];
            
            // Añadir campo is_pinned para ordenar fijados primero
            if (!empty($pinned_convs)) {
                $pipeline[] = [
                    '$addFields' => [
                        'is_pinned' => ['$in' => ['$_id', $pinned_convs]]
                    ]
                ];
                $pipeline[] = ['$sort' => ['is_pinned' => -1, 'ultimo_mensaje' => -1]];
            } else {
                $pipeline[] = ['$sort' => ['ultimo_mensaje' => -1]];
            }
            try {
                // Log del pipeline para debugging
                // debug_log("obtenerConversacionesUsuario: Ejecutando pipeline con usuario_id: " . $usuario_id);
                // debug_log("obtenerConversacionesUsuario: ObjectId usuario: " . (string)$object_id);
                
                // Primero, contar cuántos mensajes pasan el primer filtro
                $count_after_first_match = $collection_mensajes->countDocuments([
                    '$or' => [
                        ['de_usuario_id' => $object_id],
                        ['para_usuario_id' => $object_id]
                    ]
                ]);
                // debug_log("obtenerConversacionesUsuario: Mensajes después del primer match (usuario): $count_after_first_match");
                
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
                // debug_log("obtenerConversacionesUsuario: Mensajes después de todos los filtros: $count_after_all_filters");
                
                $conversaciones = $collection_mensajes->aggregate($pipeline);
                $conversaciones_array = iterator_to_array($conversaciones);
                // debug_log("obtenerConversacionesUsuario: Conversaciones después de agregación: " . count($conversaciones_array));
                
                /*
                // Log detallado de los primeros resultados - COMENTADO PARA PRODUCCIÓN
                if (count($conversaciones_array) > 0) {
                     // Debugging logs removed to reduce noise
                }
                */
                
                // Log del resultado crudo para debugging
                if (count($conversaciones_array) > 0) {
                    // debug_log("obtenerConversacionesUsuario: Resultado crudo de agregación (primeros 3): " . json_encode(array_slice($conversaciones_array, 0, 3), JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR));
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
                    // debug_log("obtenerConversacionesUsuario: Pipeline simple devolvió: " . count($simple_array) . " resultados");
                    if (count($simple_array) > 0) {
                        // debug_log("obtenerConversacionesUsuario: Ejemplo de resultado simple: " . json_encode($simple_array[0], JSON_UNESCAPED_UNICODE));
                    }
                }
            } catch (Throwable $e) {
                debug_log("obtenerConversacionesUsuario: ERROR en agregación: " . $e->getMessage());
                debug_log("obtenerConversacionesUsuario: Stack trace: " . $e->getTraceAsString());
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
                // debug_log("obtenerConversacionesUsuario: Primera conversación (raw): " . json_encode($first_conv, JSON_UNESCAPED_UNICODE));
                // debug_log("obtenerConversacionesUsuario: Campos de primera conversación: " . implode(', ', array_keys($first_conv)));
            } else {
                debug_log("obtenerConversacionesUsuario: WARNING - La agregación devolvió 0 conversaciones pero hay mensajes");
            }
            
            $resultado = [];
            // debug_log("obtenerConversacionesUsuario: Procesando " . count($conversaciones_array) . " conversaciones");
            
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
                        // debug_log("obtenerConversacionesUsuario: Saltando conversación sin ID");
                        continue;
                    }
                    
                    // Obtener IDs de usuarios
                    $de_usuario_id_obj = $conv['de_usuario_id'] ?? null;
                    $para_usuario_id_obj = $conv['para_usuario_id'] ?? null;
                    
                    if (!$de_usuario_id_obj || !$para_usuario_id_obj) {
                        // debug_log("obtenerConversacionesUsuario: Saltando conversación sin usuarios");
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
                    
                    // Extraer info de marca/código si la conversación está vinculada
                    $codigo_info = extraerCodigoDeConversacion($conversacion_id);
                    
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
                        'es_solicitud' => false,
                        'codigo_info' => $codigo_info,
                        'is_pinned' => in_array($conversacion_id, $pinned_convs)
                    ];
                    
                } catch (Throwable $e) {
                    debug_log("obtenerConversacionesUsuario: Error procesando conversación: " . $e->getMessage());
                    continue;
                }
            }
            
            // debug_log("obtenerConversacionesUsuario: Total conversaciones procesadas: " . count($resultado));
            return $resultado;
        } catch (Throwable $e) {
            debug_log("Error al obtener conversaciones usuario: " . $e->getMessage());
            debug_log("Stack trace: " . $e->getTraceAsString());
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
            debug_log("Error al buscar mensajes: " . $e->getMessage());
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
     *  FUNCIONES DE GESTIÓN DE CONVERSACIONES
     * ***************************************************/

    /**
     * Archiva una conversación para un usuario
     */
    function archivarConversacion($usuario_id, $conversacion_id) {
        $collection = getCollectionUsuarios();
        if (!$collection) return false;
        try {
            $result = $collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($usuario_id)],
                ['$addToSet' => ['archived_conversations' => $conversacion_id]]
            );
            return $result->getModifiedCount() > 0 || $result->getMatchedCount() > 0;
        } catch (Throwable $e) {
            debug_log("Error archivando conversación: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Desarchiva una conversación para un usuario
     */
    function desarchivarConversacion($usuario_id, $conversacion_id) {
        $collection = getCollectionUsuarios();
        if (!$collection) return false;
        try {
            $result = $collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($usuario_id)],
                ['$pull' => ['archived_conversations' => $conversacion_id]]
            );
            return $result->getModifiedCount() > 0 || $result->getMatchedCount() > 0;
        } catch (Throwable $e) {
            debug_log("Error desarchivando conversación: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fija una conversación (máximo 3)
     */
    function fijarConversacion($usuario_id, $conversacion_id) {
        $collection = getCollectionUsuarios();
        if (!$collection) return false;
        try {
            // Verificar cuántas tiene fijadas
            $usuario = $collection->findOne(
                ['_id' => new MongoDB\BSON\ObjectId($usuario_id)],
                ['projection' => ['pinned_conversations' => 1]]
            );
            $pinned = [];
            if ($usuario && isset($usuario['pinned_conversations'])) {
                foreach ($usuario['pinned_conversations'] as $p) {
                    $pinned[] = (string)$p;
                }
            }
            if (count($pinned) >= 3 && !in_array($conversacion_id, $pinned)) {
                return false; // Máximo 3
            }
            $result = $collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($usuario_id)],
                ['$addToSet' => ['pinned_conversations' => $conversacion_id]]
            );
            return $result->getModifiedCount() > 0 || $result->getMatchedCount() > 0;
        } catch (Throwable $e) {
            debug_log("Error fijando conversación: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Desfija una conversación
     */
    function desfijarConversacion($usuario_id, $conversacion_id) {
        $collection = getCollectionUsuarios();
        if (!$collection) return false;
        try {
            $result = $collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($usuario_id)],
                ['$pull' => ['pinned_conversations' => $conversacion_id]]
            );
            return $result->getModifiedCount() > 0 || $result->getMatchedCount() > 0;
        } catch (Throwable $e) {
            debug_log("Error desfijando conversación: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina (soft-delete) una conversación para un usuario
     */
    function eliminarConversacion($usuario_id, $conversacion_id) {
        $collection = getCollectionUsuarios();
        if (!$collection) return false;
        try {
            // Añadir a eliminadas y quitar de fijadas/archivadas
            $result = $collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($usuario_id)],
                [
                    '$addToSet' => ['deleted_conversations' => $conversacion_id],
                    '$pull' => [
                        'pinned_conversations' => $conversacion_id,
                        'archived_conversations' => $conversacion_id
                    ]
                ]
            );
            return $result->getModifiedCount() > 0 || $result->getMatchedCount() > 0;
        } catch (Throwable $e) {
            debug_log("Error eliminando conversación: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Guarda una Push Subscription para un usuario
     */
    function guardarPushSubscription($usuario_id, $subscription) {
        $collection = getCollectionUsuarios();
        if (!$collection) return false;
        try {
            // Buscar si ya existe esta subscription (por endpoint)
            $endpoint = $subscription['endpoint'] ?? '';
            if (empty($endpoint)) return false;

            // Eliminar suscrípciones antiguas con el mismo endpoint
            $collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($usuario_id)],
                ['$pull' => ['push_subscriptions' => ['endpoint' => $endpoint]]]
            );

            // Añadir la nueva
            $result = $collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($usuario_id)],
                ['$push' => ['push_subscriptions' => $subscription]]
            );
            return $result->getModifiedCount() > 0 || $result->getMatchedCount() > 0;
        } catch (Throwable $e) {
            debug_log("Error guardando push subscription: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina una Push Subscription
     */
    function eliminarPushSubscription($usuario_id, $endpoint) {
        $collection = getCollectionUsuarios();
        if (!$collection) return false;
        try {
            $result = $collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($usuario_id)],
                ['$pull' => ['push_subscriptions' => ['endpoint' => $endpoint]]]
            );
            return $result->getModifiedCount() > 0 || $result->getMatchedCount() > 0;
        } catch (Throwable $e) {
            debug_log("Error eliminando push subscription: " . $e->getMessage());
            return false;
        }
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
            debug_log("Error en es_usuario_vip: " . $e->getMessage());
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
            
            // Obtener usuario actual para saber si ya es VIP (renovación vs primera vez)
            $usuario_actual = $collection_usuarios->findOne(['_id' => $object_id]);
            if (!$usuario_actual) {
                debug_log("activar_vip: Usuario no encontrado: $user_id");
                return false;
            }
            
            $ya_era_vip = isset($usuario_actual['is_vip']) && $usuario_actual['is_vip'] === true;
            
            // Si no se especifica fecha de expiración, establecer 1 mes desde ahora
            if ($expires_at === null) {
                $expires_at = new DateTime();
                $expires_at->modify('+1 month');
            }
            
            $vip_data = [
                'is_vip' => true,
                'vip_subscription_id' => $subscription_id,
                'vip_expires_at' => new MongoDB\BSON\UTCDateTime($expires_at->getTimestamp() * 1000),
            ];
            
            // Solo establecer vip_started_at la primera vez (no en renovaciones)
            if (!$ya_era_vip) {
                $vip_data['vip_started_at'] = new MongoDB\BSON\UTCDateTime();
            }
            
            // Actualizar usuario
            $result = $collection_usuarios->updateOne(
                ['_id' => $object_id],
                ['$set' => $vip_data]
            );
            
            if ($result->getModifiedCount() > 0 || $result->getMatchedCount() > 0) {
                // Añadir 10€ de saldo (con protección contra duplicados)
                renovar_saldo_vip($user_id);
                
                // Enviar email de bienvenida VIP SOLO la primera vez (no en renovaciones)
                if (!$ya_era_vip) {
                    try {
                        if (!function_exists('enviarEmailBienvenidaVIP')) {
                            include_once __DIR__ . '/funciones_email.php';
                        }
                        $usuario_data = $collection_usuarios->findOne(['_id' => $object_id]);
                        if ($usuario_data) {
                            enviarEmailBienvenidaVIP($usuario_data);
                        }
                    } catch (Throwable $email_e) {
                        debug_log("Error enviando email bienvenida VIP: " . $email_e->getMessage());
                    }
                }
                
                $tipo_accion = $ya_era_vip ? 'renovado' : 'activado';
                debug_log("VIP $tipo_accion para usuario: $user_id, subscription: $subscription_id");
                return true;
            }
            
            return false;
        } catch (Throwable $e) {
            debug_log("Error en activar_vip: " . $e->getMessage());
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
                    'vip_cancelled_at' => new MongoDB\BSON\UTCDateTime(),
                    'vip_cancel_pending' => false,
                    'vip_retention_applied' => false
                ]]
            );
            
            debug_log("VIP desactivado para usuario: $user_id");
            return $result->getModifiedCount() > 0;
        } catch (Throwable $e) {
            debug_log("Error en desactivar_vip: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Renueva el saldo mensual de un usuario VIP (+10€)
     * Incluye protección contra duplicados: no permite renovar si ya se renovó en las últimas 24 horas
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
            
            // Protección contra duplicados: verificar última recarga
            $usuario = $collection_usuarios->findOne(['_id' => $object_id]);
            if ($usuario && isset($usuario['vip_last_saldo_renewal'])) {
                $last_renewal = $usuario['vip_last_saldo_renewal'];
                if ($last_renewal instanceof MongoDB\BSON\UTCDateTime) {
                    $last_renewal_time = $last_renewal->toDateTime()->getTimestamp();
                    $ahora = time();
                    $horas_desde_ultima = ($ahora - $last_renewal_time) / 3600;
                    
                    // Si la última recarga fue hace menos de 24 horas, no renovar (evita duplicados)
                    if ($horas_desde_ultima < 24) {
                        debug_log("renovar_saldo_vip: Recarga ignorada para usuario $user_id - última recarga hace " . round($horas_desde_ultima, 1) . "h (< 24h)");
                        return false;
                    }
                }
            }
            
            // Añadir 10€ al saldo y marcar timestamp de renovación
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
                
                debug_log("Saldo VIP renovado para usuario: $user_id (+10€)");
                return true;
            }
            
            return false;
        } catch (Throwable $e) {
            debug_log("Error en renovar_saldo_vip: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Otorga +1€ de bonus al usuario por publicar su primer código.
     * Idempotente: solo se concede una vez (flag bonus_primer_codigo).
     */
    function otorgar_bonus_primer_codigo($user_id, $codigo_id, $marca) {
        if (empty($user_id)) return false;

        $collection_usuarios = getCollectionUsuarios();
        $object_id = is_string($user_id) ? new MongoDB\BSON\ObjectId($user_id) : $user_id;

        $usuario = $collection_usuarios->findOne(['_id' => $object_id]);
        if (!$usuario) return false;

        if (!empty($usuario['bonus_primer_codigo'])) {
            return false;
        }

        $result = $collection_usuarios->updateOne(
            ['_id' => $object_id, 'bonus_primer_codigo' => ['$ne' => true]],
            [
                '$inc' => ['saldo' => 1],
                '$set' => [
                    'bonus_primer_codigo' => true,
                    'bonus_primer_codigo_fecha' => new MongoDB\BSON\UTCDateTime(),
                    'bonus_primer_codigo_codigo_id' => (string)$codigo_id,
                ],
            ]
        );

        if ($result->getModifiedCount() > 0) {
            $collection_transacciones = getCollectionTransacciones();
            $collection_transacciones->insertOne([
                'usuario_id' => (string)$user_id,
                'tipo' => 'bonus_primer_codigo',
                'cantidad' => 1,
                'descripcion' => 'Bonus de bienvenida por publicar tu primer código',
                'fecha' => new MongoDB\BSON\UTCDateTime(),
                'estado' => 'completado',
                'codigo_id' => (string)$codigo_id,
                'marca' => $marca,
            ]);

            // Loop viral: si fue invitado, recompensar al referidor (+5€)
            try {
                recompensar_referidor_por_primer_codigo($user_id);
            } catch (Throwable $e) {
                debug_log("Error recompensando referidor: " . $e->getMessage());
            }

            // Email de aviso al usuario (best-effort)
            try {
                if (!function_exists('enviarEmailConBrevoYRegistrar')) {
                    include_once __DIR__ . '/email_helper.php';
                }
                $to_email = $usuario['mail'] ?? '';
                $username = trim($usuario['username'] ?? 'Usuario');
                if (!empty($to_email)) {
                    $subject = '¡+1€ de regalo por tu primer código! - CodigoAmigo';
                    $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>'
                          . '<body style="font-family:Segoe UI,Tahoma,sans-serif;background:#f4f4f4;margin:0;padding:0;">'
                          . '<div style="max-width:600px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.1);">'
                          . '<div style="background:linear-gradient(135deg,#27ae60,#16a085);color:#fff;padding:30px 20px;text-align:center;">'
                          . '<h1 style="margin:0;font-size:26px;">¡Bienvenido ' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '!</h1>'
                          . '<p style="margin:10px 0 0;opacity:0.95;">Has publicado tu primer código</p></div>'
                          . '<div style="padding:30px;">'
                          . '<p>Te hemos sumado <strong>1€ de regalo</strong> en tu saldo como bienvenida.</p>'
                          . '<div style="background:#f8f9fa;border-radius:8px;padding:20px;margin:25px 0;text-align:center;">'
                          . '<p style="margin:0;color:#666;">Bonus de bienvenida</p>'
                          . '<p style="margin:5px 0 0;font-size:32px;font-weight:700;color:#27ae60;">+1,00€</p>'
                          . '</div>'
                          . '<p>Cuanto más publiques, más posibilidad tienes de que otros usuarios usen tus códigos. Cada vez que alguien interactúa con tu código, ganas potencial de comisiones.</p>'
                          . '<p>Un saludo,<br>El equipo de CodigoAmigo</p>'
                          . '</div></div></body></html>';
                    $text = "Bienvenido $username,\n\nTe hemos sumado 1€ de regalo por publicar tu primer código.\n\nCodigoAmigo";
                    enviarEmailConBrevoYRegistrar($to_email, $username, $subject, $html, 'bonus_primer_codigo', (string)$user_id, ['cantidad'=>1, 'codigo_id'=>$codigo_id], $text);
                }
            } catch (Throwable $e) {
                debug_log("Error email bonus primer código: " . $e->getMessage());
            }

            return true;
        }

        return false;
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
                debug_log("registrar_vista_codigo: código no encontrado o sin id_usuario. codigo_id=$codigo_id");
                return false;
            }
            
            $codigo_owner_id = (string)$codigo['id_usuario'];
            
            // NO REGISTRAR si el que ve el código es el mismo dueño (Evitar auto-leads)
            if ($viewer_user_id && (string)$viewer_user_id === $codigo_owner_id) {
                debug_log("registrar_vista_codigo: auto-view bloqueado. owner=$codigo_owner_id viewer=$viewer_user_id");
                return false;
            }
            
            // Log si el viewer es anónimo (no logueado)
            if (empty($viewer_user_id)) {
                debug_log("registrar_vista_codigo: vista anónima (sin login). codigo_id=$codigo_id session=" . ($session_id ?? 'null'));
            }
            
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
                debug_log("registrar_vista_codigo: vista ya existente actualizada. codigo_id=$codigo_id viewer=" . ($viewer_user_id ?? 'anon'));
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
            
            try {
                if ($viewer_user_id && is_string($viewer_user_id)) {
                    if (strlen($viewer_user_id) === 24 && ctype_xdigit($viewer_user_id)) {
                        $viewer_data['viewer_user_id'] = new MongoDB\BSON\ObjectId($viewer_user_id);
                    } else {
                        $viewer_data['viewer_user_id'] = $viewer_user_id;
                    }
                    $viewer_data['registered_at'] = new MongoDB\BSON\UTCDateTime();
                } else if ($viewer_user_id instanceof MongoDB\BSON\ObjectId) {
                    $viewer_data['viewer_user_id'] = $viewer_user_id;
                    $viewer_data['registered_at'] = new MongoDB\BSON\UTCDateTime();
                }
            } catch (Throwable $e) {
                // Safeguard string
                $viewer_data['viewer_user_id'] = $viewer_user_id;
                $viewer_data['registered_at'] = new MongoDB\BSON\UTCDateTime();
            }
            
            $collection_viewers->insertOne($viewer_data);
            debug_log("registrar_vista_codigo: NUEVO viewer registrado. codigo_id=$codigo_id owner=$codigo_owner_id viewer=" . ($viewer_user_id ?? 'anon') . " tiene_user_id=" . (isset($viewer_data['viewer_user_id']) ? 'SI' : 'NO'));
            
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
                            $cta_text = $is_vip_owner ? "Identificarme y contactar" : "Ver quién es";
                            
                            // Generar token temporal de autologin
                            $autologin_token = bin2hex(random_bytes(16));
                            $collection_usuarios = getCollectionUsuarios();
                            $collection_usuarios->updateOne(
                                ['_id' => new MongoDB\BSON\ObjectId($codigo_owner_id)],
                                ['$set' => ['autologin_token' => $autologin_token]]
                            );
                            
                            $cta_link = "https://www.codigoamigo.com/login?redirect=/public/mis_viewers.php&autologin=" . $autologin_token;
                            
                            // Datos adicionales para el email
                            $nombre_viewer = 'un usuario';
                            if ($viewer_user_id) {
                                try {
                                    $id_obj = (is_string($viewer_user_id) && strlen($viewer_user_id) === 24 && ctype_xdigit($viewer_user_id)) 
                                        ? new MongoDB\BSON\ObjectId($viewer_user_id) 
                                        : $viewer_user_id;
                                    $viewer_user = get_object_user('_id', $id_obj);
                                    if ($viewer_user && !empty($viewer_user['username'])) {
                                        $nombre_viewer = $viewer_user['username'];
                                    }
                                } catch (Throwable $e) {}
                            }
                            $hora = date('H:i');
                            $beneficio = $codigo['num_beneficio'] ?? 0;
                            
                            $msg_body = "<p>Hola <strong>$nombre_owner</strong>,</p>";
                            $msg_body .= "<p>El usuario <strong>$nombre_viewer</strong> acaba de ver tu código de <strong>$marca_nombre</strong> hoy a las $hora. Además te puede hacer ganar <strong>{$beneficio}€</strong>.</p>";
                            
                            if ($is_vip_owner) {
                                $msg_body .= "<p>Al ser VIP, puedes <strong>acceder a tu cuenta</strong> para ver quién es y contactarle directamente para asegurar tu recompensa.</p>";
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
                    debug_log("Error enviando email de nuevo viewer: " . $e_mail->getMessage());
                }
            }

            // Crear notificación in-app para el propietario del código
            if ($viewer_user_id && $codigo_owner_id) {
                try {
                    if (!function_exists('crear_notificacion')) {
                        include_once __DIR__ . '/funciones_notificaciones.php';
                    }
                    $is_vip_owner_notif = es_usuario_vip($codigo_owner_id);
                    $marca_notif = isset($codigo['marca']) ? ucfirst($codigo['marca']) : 'tu código';
                    $beneficio_notif = $codigo['num_beneficio'] ?? 0;
                    
                    // Obtener nombre del viewer para la notificación
                    $nombre_viewer_notif = 'un usuario';
                    try {
                        $id_obj_notif = (is_string($viewer_user_id) && strlen($viewer_user_id) === 24 && ctype_xdigit($viewer_user_id))
                            ? new MongoDB\BSON\ObjectId($viewer_user_id)
                            : $viewer_user_id;
                        $viewer_user_notif = get_object_user('_id', $id_obj_notif);
                        if ($viewer_user_notif && !empty($viewer_user_notif['username'])) {
                            $nombre_viewer_notif = $viewer_user_notif['username'];
                        }
                    } catch (Throwable $e) {}
                    
                    crear_notificacion($codigo_owner_id, 'nuevo_viewer', [
                        'viewer_username' => $nombre_viewer_notif,
                        'marca' => $marca_notif,
                        'beneficio' => $beneficio_notif,
                        'codigo_id' => (string)$codigo_id,
                        'is_vip' => $is_vip_owner_notif
                    ]);
                } catch (Throwable $e_notif) {
                    debug_log("Error creando notificación in-app de nuevo viewer: " . $e_notif->getMessage());
                }
            }

            return true;
        } catch (Throwable $e) {
            debug_log("Error en registrar_vista_codigo: " . $e->getMessage());
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
            // codigo_owner_id se almacena siempre como string, pero buscamos ambos por seguridad
            $owner_id_string = (string)$user_id;
            $viewers = $collection_viewers->find([
                'codigo_owner_id' => ['$in' => [$owner_id_string, $object_id]],
                'viewer_user_id' => ['$exists' => true, '$ne' => null]
            ], ['sort' => ['viewed_at' => -1]]);
            
            $resultado = [];
            $views_procesadas = []; // Deduplicar por viewer+codigo (no por viewer solo)
            $total_potencial = 0;
            
            // Collect code IDs for this owner for the fallback
            $codigos_owner = iterator_to_array($collection_codigos->find(['id_usuario' => $object_id]));
            $codigos_ids_owner = array_map(function($c) { return $c['_id']; }, $codigos_owner);
            
            // Obtener completados de una vez para mapeo rápido
            $db = createConnection();
            $collection_completados = $db->selectCollection('codigos_completados');
            $completados_db = iterator_to_array($collection_completados->find(['owner_id' => $object_id]));
            $completados_map = [];
            foreach ($completados_db as $comp) {
                $comp_key = (string)$comp['viewer_id'] . '|' . (string)$comp['codigo_id'];
                $completados_map[$comp_key] = true;
            }
            
            // First pass: code_viewers — cada vista de código es un lead separado
            foreach ($viewers as $viewer) {
                $viewer_user_id = (string)$viewer['viewer_user_id'];
                $codigo_id_str = (string)$viewer['codigo_id'];
                
                // Deduplicar por combinación viewer+código (no por viewer solo)
                $clave_unica = $viewer_user_id . '|' . $codigo_id_str;
                if (in_array($clave_unica, $views_procesadas)) { continue; }
                $views_procesadas[] = $clave_unica;
                
                try {
                    $viewer_user = $collection_usuarios->findOne([
                        '_id' => new MongoDB\BSON\ObjectId($viewer_user_id)
                    ]);
                } catch(Throwable $e) {
                    $viewer_user = null;
                }
                
                $codigo = $collection_codigos->findOne(['_id' => $viewer['codigo_id']]);
                $beneficio = $codigo['num_beneficio'] ?? 0;
                $marca = $codigo['marca'] ?? 'Desconocida';
                $total_potencial += $beneficio;
                
                $resultado[] = [
                    'viewer_id' => $viewer_user_id,
                    'viewer_username' => $viewer_user['username'] ?? 'Usuario',
                    'viewer_email' => $viewer_user['mail'] ?? '',
                    'viewer_img' => $viewer_user['img'] ?? '',
                    'codigo_id' => $codigo_id_str,
                    'codigo_marca' => $marca,
                    'codigo_beneficio' => $beneficio,
                    'viewed_at' => $viewer['viewed_at'],
                    'contacted' => $viewer['contacted'] ?? false,
                    'contacted_at' => $viewer['contacted_at'] ?? null,
                    'completado' => isset($completados_map[$clave_unica])
                ];
            }
            
            // Fallback pass: historial (if no new leads found)
            if (count($resultado) === 0 && count($codigos_ids_owner) > 0) {
                $collection_historial = getCollectionHistorial();
                $historial_views = $collection_historial->find([
                    'id_codigo' => ['$in' => $codigos_ids_owner],
                    'user_id' => ['$exists' => true, '$ne' => null]
                ], ['limit' => 200]); // Limit to recent 200, no sort to avoid full collection scan
                
                foreach ($historial_views as $hv) {
                    $viewer_user_id = (string)$hv['user_id'];
                    $codigo_id_hv = (string)$hv['id_codigo'];
                    // Exclude self-views
                    if ($viewer_user_id === (string)$user_id) { continue; }
                    // Deduplicar por combinación viewer+código
                    $clave_unica = $viewer_user_id . '|' . $codigo_id_hv;
                    if (in_array($clave_unica, $views_procesadas)) { continue; }
                    $views_procesadas[] = $clave_unica;
                    
                    try {
                        $viewer_user = $collection_usuarios->findOne([
                            '_id' => new MongoDB\BSON\ObjectId($viewer_user_id)
                        ]);
                    } catch(Throwable $e) {
                        $viewer_user = null;
                    }
                    
                    $codigo = current(array_filter($codigos_owner, function($c) use ($hv) { 
                        return (string)$c['_id'] === (string)$hv['id_codigo']; 
                    }));
                    
                    $beneficio = $codigo['num_beneficio'] ?? 0;
                    $marca = $codigo['marca'] ?? 'Desconocida';
                    $total_potencial += $beneficio;
                    
                    // Parse date string like 'd-m-Y  H:i:s' into UTCDateTime
                    $viewed_at_dt = new MongoDB\BSON\UTCDateTime();
                    if (isset($hv['fecha_visita'])) {
                        $ts = strtotime(str_replace('  ', ' ', $hv['fecha_visita']));
                        if ($ts) $viewed_at_dt = new MongoDB\BSON\UTCDateTime($ts * 1000);
                    }
                    
                    $resultado[] = [
                        'viewer_id' => $viewer_user_id,
                        'viewer_username' => $viewer_user['username'] ?? 'Usuario',
                        'viewer_email' => $viewer_user['mail'] ?? '',
                        'viewer_img' => $viewer_user['img'] ?? '',
                        'codigo_id' => (string)$hv['id_codigo'],
                        'codigo_marca' => $marca,
                        'codigo_beneficio' => $beneficio,
                        'viewed_at' => $viewed_at_dt,
                        'contacted' => false,
                        'contacted_at' => null,
                        'completado' => isset($completados_map[$clave_unica])
                    ];
                }
                
                // Sort in PHP if needed
                usort($resultado, function($a, $b) {
                    $time_a = $a['viewed_at'] instanceof MongoDB\BSON\UTCDateTime ? $a['viewed_at']->toDateTime()->getTimestamp() : 0;
                    $time_b = $b['viewed_at'] instanceof MongoDB\BSON\UTCDateTime ? $b['viewed_at']->toDateTime()->getTimestamp() : 0;
                    return $time_b - $time_a;
                });
            }
            
            return [
                'viewers' => $resultado,
                'total_viewers' => count($resultado),
                'total_potencial' => $total_potencial
            ];
        } catch (Throwable $e) {
            debug_log("Error en obtener_viewers_usuario: " . $e->getMessage());
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
            debug_log("Error en puede_contactar_viewer: " . $e->getMessage());
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
            $str_id = (string)$user_id;
            
            $viewer_object_id = is_string($viewer_id) ? new MongoDB\BSON\ObjectId($viewer_id) : $viewer_id;
            $viewer_str_id = (string)$viewer_id;
            
            $result = $collection_viewers->updateMany(
                [
                    'codigo_owner_id' => ['$in' => [$str_id, $object_id]],
                    'viewer_user_id' => ['$in' => [$viewer_str_id, $viewer_object_id]]
                ],
                ['$set' => [
                    'contacted' => true,
                    'contacted_at' => new MongoDB\BSON\UTCDateTime()
                ]]
            );
            
            return $result->getModifiedCount() > 0;
        } catch (Throwable $e) {
            debug_log("Error en marcar_viewer_contactado: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envía mensaje a múltiples destinatarios
     * Retorna array con resultados
     */
    function enviarMensajeMasivo($de_usuario_id, $destinatarios_ids, $mensaje) {
        if (empty($destinatarios_ids) || empty($mensaje)) {
            return ['enviados' => 0, 'fallidos' => 0, 'omitidos' => 0, 'total' => 0];
        }

        // Aumentar tiempo de ejecución para envíos masivos
        if (function_exists('set_time_limit')) {
            set_time_limit(300); // 5 minutos
        }

        $stats = ['enviados' => 0, 'fallidos' => 0, 'omitidos' => 0, 'total' => count($destinatarios_ids)];
        
        // Determinar si es admin (opcional, por defecto false)
        $es_admin = false;
        
        $collection_mensajes = getCollectionMensajes();
        
        foreach ($destinatarios_ids as $para_id) {
            try {
                // Verificar que no sea el mismo usuario
                if ((string)$para_id === (string)$de_usuario_id) {
                    $stats['omitidos']++;
                    continue;
                }
                
                // Verificar si ya se ha contactado previamente (para evitar spam)
                $conversacion_id = crearConversacionId($de_usuario_id, $para_id);
                $mensaje_previo = $collection_mensajes->findOne(['conversacion_id' => $conversacion_id]);
                
                if ($mensaje_previo) {
                    $stats['omitidos']++;
                    continue;
                }
                
                // Enviar mensaje (esto también envía email notification)
                $res = enviarMensaje($de_usuario_id, $para_id, $mensaje, $es_admin);
                if ($res) {
                    marcar_viewer_contactado($de_usuario_id, $para_id);
                    $stats['enviados']++;
                } else {
                    $stats['fallidos']++;
                }
                
                // Pequeña pausa para no saturar SMTP si son muchos
                if ($stats['enviados'] % 5 === 0) {
                    usleep(200000); // 0.2 segundos
                }
                
            } catch (Throwable $e) {
                debug_log("Error en enviarMensajeMasivo destinatario $para_id: " . $e->getMessage());
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
            debug_log("Error en obtener_info_badge_vip: " . $e->getMessage());
            return ['is_vip' => false];
        }
    }


    /**
     * Obtiene las interacciones de un usuario específico con los códigos del propietario
     * Útil para el contexto del chat
     * @param string $owner_id ID del propietario de los códigos (usuario actual)
     * @param string $viewer_id ID del usuario que ha visto los códigos
     * @return array Lista de interacciones con códigos
     */
    function obtener_interacciones_usuario_con_mis_codigos($owner_id, $viewer_id) {
        if (empty($owner_id) || empty($viewer_id)) {
            return ['interacciones' => [], 'total' => 0, 'total_potencial' => 0];
        }
        
        try {
            $collection_vistas = getCollectionVistas();
            $collection_codigos = getCollectionCodigos();
            
            // 1. Obtener todos los IDs de códigos del propietario
            $owner_object_id = is_string($owner_id) ? new MongoDB\BSON\ObjectId($owner_id) : $owner_id;
            $codigos_owner = $collection_codigos->find(['id_usuario' => $owner_object_id]);
            
            $map_codigos = [];
            $ids_codigos = [];
            foreach ($codigos_owner as $c) {
                $id_s = (string)$c['_id'];
                $ids_codigos[] = $c['_id'];
                $map_codigos[$id_s] = $c;
            }
            
            if (empty($ids_codigos)) {
                return ['interacciones' => [], 'total' => 0, 'total_potencial' => 0];
            }
            
            // 2. Buscar visitas de este usuario a los códigos del propietario
            // Manejamos viewer_id como String y como ObjectId por inconsistencias detectadas en la DB
            $viewer_object_id = is_string($viewer_id) ? new MongoDB\BSON\ObjectId($viewer_id) : $viewer_id;
            
            $query = [
                'id_usuario' => ['$in' => [(string)$viewer_id, $viewer_object_id]],
                'id_codigo' => ['$in' => $ids_codigos]
            ];
            
            // Ordenamos por _id descendente para obtener las más recientes primero
            $views = $collection_vistas->find($query, ['sort' => ['_id' => -1]]);
            
            // También revisar en code_viewers y historial
            $collection_viewers = getCollectionCodeViewers();
            $query_cv = [
                'owner_id' => ['$in' => [(string)$owner_id, $owner_object_id]],
                'viewer_id' => (string)$viewer_id 
            ];
            $cvs = $collection_viewers->find($query_cv);

            $collection_historial = getCollectionHistorial();
            $query_h = [
                'id_codigo' => ['$in' => $ids_codigos],
                'user_id' => (string)$viewer_id
            ];
            $hists = $collection_historial->find($query_h);
            
            $interacciones = [];
            $total_potencial = 0;
            $codigos_procesados = []; // Para mostrar cada código una única vez
            
            // Unify all sources into a simple array
            $all_sources = [];
            foreach ($views as $v) { $all_sources[] = [(string)$v['id_codigo'], $v['fecha_vista'] ?? 'Recientemente']; }
            foreach ($cvs as $cv) { $all_sources[] = [(string)$cv['codigo_id'], $cv['viewed_at'] instanceof MongoDB\BSON\UTCDateTime ? $cv['viewed_at']->toDateTime()->format('d-m-Y H:i') : 'Recientemente']; }
            foreach ($hists as $h) { $all_sources[] = [(string)$h['id_codigo'], $h['fecha_visita'] ?? 'Recientemente']; }

            foreach ($all_sources as $source) {
                $codigo_id_str = $source[0];
                $fecha_vista = $source[1];

                // Si ya procesamos este código, saltamos (solo nos interesa la visita más reciente para la lista)
                if (isset($codigos_procesados[$codigo_id_str])) continue;
                $codigos_procesados[$codigo_id_str] = true;
                
                $codigo = $map_codigos[$codigo_id_str] ?? null;
                if (!$codigo) continue;
                
                $beneficio = $codigo['num_beneficio'] ?? 0;
                $marca = $codigo['marca'] ?? 'Desconocida';
                $total_potencial += $beneficio;
                
                $interacciones[] = [
                    'codigo_id' => $codigo_id_str,
                    'marca' => ucfirst($marca),
                    'beneficio' => $beneficio,
                    'last_viewed_at' => $fecha_vista,
                    'contacted' => false // El historial de 'vistas' no trackea contacto, pero mantenemos campo por UI
                ];
            }
            
            return [
                'interacciones' => $interacciones,
                'total' => count($interacciones),
                'total_potencial' => $total_potencial
            ];
            
        } catch (Throwable $e) {
            debug_log("Error en obtener_interacciones_usuario_con_mis_codigos: " . $e->getMessage());
            return ['interacciones' => [], 'total' => 0, 'total_potencial' => 0];
        }
    }
    
    /******************************************************
     *  FUNCIONES DE FAVORITOS
     * ***************************************************/

    /**
     * Comprueba si un chollo es favorito de un usuario
     * Wrapper para compatibilidad - usa funciones_favoritos.php si está disponible
     */

    /**
     * Añade un chollo a favoritos
     */

    /**
     * Elimina un chollo de favoritos
     */

    /******************************************************
     *  POTENCIAL DE GANANCIAS Y MENSAJE MASIVO
     * ***************************************************/

    /**
     * Calcula el potencial de ganancias completo para un usuario y todos sus códigos
     * @param string $user_id ID del usuario
     * @return array Datos de potencial (total, por código, y lista de destinatarios únicos)
     */
    function obtener_potencial_completo_usuario($user_id) {
        if (empty($user_id)) return ['total_potential' => 0, 'total_unique_viewers' => 0, 'per_code' => [], 'all_viewer_ids' => []];
        
        try {
            $user_obj_id = is_string($user_id) ? new \MongoDB\BSON\ObjectId($user_id) : $user_id;
            
            // 1. Obtener todos los códigos activos del usuario
            if (!function_exists('getCollectionCodigos')) {
                include_once __DIR__ . '/../inc/conexion.php';
            }
            $collection_codigos = getCollectionCodigos();
            $codigos = $collection_codigos->find(['id_usuario' => $user_obj_id, 'estado' => 0]);
            
            $total_potential = 0;
            $per_code = [];
            $all_viewer_ids = [];
            
            $collection_vistas = getCollectionVistas();
            
            foreach ($codigos as $code) {
                $code_id_str = (string)$code['_id'];
                $beneficio = isset($code['num_beneficio']) ? floatval($code['num_beneficio']) : 5;
                if ($beneficio <= 0) $beneficio = 5; // Valor por defecto
                
                // Buscar visualizaciones para este código
                $vistas = $collection_vistas->find(['id_codigo' => $code['_id']]);
                
                $unique_viewers_code = [];
                foreach ($vistas as $v) {
                    if (isset($v['id_usuario']) && !empty($v['id_usuario'])) {
                        $v_id_str = (string)$v['id_usuario'];
                        // Evitar al propio dueño del código
                        if ($v_id_str !== (string)$user_id) {
                            $unique_viewers_code[$v_id_str] = true;
                            $all_viewer_ids[$v_id_str] = true;
                        }
                    }
                }
                
                $count = count($unique_viewers_code);
                $potential = $count * $beneficio;
                $total_potential += $potential;
                
                $per_code[$code_id_str] = [
                    'count' => $count,
                    'potential' => $potential,
                    'viewer_ids' => array_keys($unique_viewers_code)
                ];
            }
            
            return [
                'total_potential' => $total_potential,
                'total_unique_viewers' => count($all_viewer_ids),
                'all_viewer_ids' => array_keys($all_viewer_ids),
                'per_code' => $per_code
            ];
            
        } catch (Throwable $e) {
            debug_log("Error en obtener_potencial_completo_usuario: " . $e->getMessage());
            return ['total_potential' => 0, 'total_unique_viewers' => 0, 'per_code' => [], 'all_viewer_ids' => []];
        }
    }
