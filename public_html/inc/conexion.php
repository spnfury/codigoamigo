<?php

// Incluir sistema de logging si no está ya incluido
if (!function_exists('log_warning')) {
    require_once __DIR__ . '/logger.php';
}

// Cargar funciones.php si createConnection no está definido.
// Nota: el comentario previo advertía de una dependencia circular, pero funciones.php
// NO incluye conexion.php, así que el guard por SAPI era innecesario y dejaba fatales
// en rutas web que llegan aquí sin haber cargado antes funciones.php (p.ej. /aviso-legal).
if (!function_exists('createConnection')) {
    require_once __DIR__ . '/../myphp/funciones.php';
}

if (!function_exists("manda_mensaje_bot_fatal")) {
    function manda_mensaje_bot_fatal($mensaje='') {
        
        
        global $_ERRORS,$actual_url_c;
        
        $botToken = "1208948207:AAF0O45V1zcsp7wjRgbwOrLQ7tNRUHlfnME";
        $chatId="-1001914971781";
        
        $mensaje = $actual_url_c.":".$mensaje;
        
        
        $actual_url_c = ($_SERVER["HTTP_HOST"] ?? 'cli').(rawurldecode($_SERVER["REQUEST_URI"] ?? '/'));
        
        $url = "https://api.telegram.org/bot".$botToken. "/sendMessage?chat_id=" . $chatId;
        
        $post = [
            'chat_id'=>$chatId,
            'text' => $mensaje
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


if (!function_exists("manda_mensaje_bot_anuncios")) {
    function manda_mensaje_bot_anuncios($mensaje='') {
        
        
        global $_ERRORS,$actual_url_c;
        
        $botToken = "1208948207:AAF0O45V1zcsp7wjRgbwOrLQ7tNRUHlfnME";
        $chatId="-4031693953";
        
        $mensaje = $actual_url_c.":".$mensaje;
        
        
        $actual_url_c = ($_SERVER["HTTP_HOST"] ?? 'cli').(rawurldecode($_SERVER["REQUEST_URI"] ?? '/'));
        
        
        //https://t.me/+G447tJvFXqQzYzY8
        
        $url = "https://api.telegram.org/bot".$botToken. "/sendMessage?chat_id=" . $chatId;
        
        $post = [
            'chat_id'=>$chatId,
            'text' => $mensaje
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



if (!function_exists("fatal_handler")) {
    function fatal_handler($mensaje='') {
        
        global $_ERRORS,$actual_url_c;
        
        $errfile = "unknown file";
        $errstr  = "shutdown";
        $errno   = E_CORE_ERROR;
        $errline = 0;
        $archivos_incluidos = get_included_files();
        $error = error_get_last();
        
        if($mensaje!=''){
            $error["type"] = 4;
            $error["message"] = $mensaje;
        }
        
        
        if($error !== NULL) {
            
            $errno   = $error["type"];
            $errfile = $error["file"];
            $errline = $error["line"];
            $errstr  = $error["message"];
            
            // Inicializar variable $manda
            $manda = "\n\n\nCODIGOAMIGO globales*\n";
            
            if (is_array($_ERRORS)) {
                foreach($_ERRORS as $a => $line){
                    $manda.= "***".$line."*****\n";
                }
            }
            
            if($errno == 1 || $errno == 4){ //SOLO FATALES
                
                $manda.= $errno."\n".$errfile."\n".$errline."\n".$errstr;
                
                
                
                if($archivos_incluidos){
                    $limit = 0;
                    $last_file = "";
                    foreach ($archivos_incluidos as $nombre_archivo) {
                        if($limit<=5){
                            $last_file.= "\n::".$nombre_archivo;
                        }
                        $limit++;
                    }
                    $manda.= "\n\nEncontrado en: ".$last_file."...";
                }
                
                manda_mensaje_bot_fatal($manda);
                
                
            }
        }
    }
}

register_shutdown_function( "fatal_handler" );


	/***********************
	 * COLLECTIONS
	 * ***********************/

// 	function getCollectionCategorias() {

// 	    $db = createConnection();
// 	    $collection_categorias = $db->selectCollection('categorias');
// 	    return $collection_categorias;

// 	}


	function getCollectionCategorias() {

	    $db = createConnection();
	    $collection_categorias = $db->selectCollection('categorias');
	    return $collection_categorias;

	}

	function getCollectionCategoriasEvo() {

	    $db = createConnection();
	    $collection_categorias = $db->selectCollection('category');
	    return $collection_categorias;

	}

	function getCollectionVistas() {

	    $db = createConnection();
	    $collection_vistas = $db->selectCollection('vistas');
	    return $collection_vistas;
	}

	function getCollectionAffiliationNetworks() {
	    $db = createConnection();
	    return $db->selectCollection('affiliation_networks');
	}

	function getCollectionAffiliationPrograms() {
	    $db = createConnection();
	    return $db->selectCollection('affiliation_programs');
	}

	function getCollectionAffiliationRules() {
	    $db = createConnection();
	    return $db->selectCollection('affiliation_rules');
	}

	/*************** CURSORS - ELEMENTS ACTIVE *****************/

	function getCategorias() {


	    //$collection_categorias = getCollectionCategorias();
	    $collection_categorias = getCollectionCategoriasEvo();

	    $lista_categorias = $collection_categorias->find(
	        [
	            'estado' => 1, 'id_parent' => 0
	        ],
	        [
	            'sort' => ['nombre' => 1],
	        ]
	        );

	    return $lista_categorias;
	}


	function getCategoriasSub($id_cat) {



	    $id_cat  = (string)$id_cat;


	    $collection_categorias = getCollectionCategoriasEvo();

	    $lista_categorias = $collection_categorias->find(
	        [
	            'estado' => 1, 'id_parent' => $id_cat
	        ],
	        [
	            'sort' => ['nombre' => 1],
	        ]
	        );

	    return $lista_categorias;
	}



	function getMarcasRevisadas($limit = 9999, $categoria = '', $excluye = '') {
	    $collection_marcas = getCollectionMarcas();
	    $collection_codigos = getCollectionCodigos();
	    
	    // 1. Obtener el conteo de códigos por marca
	    $codigos_por_marca = $collection_codigos->aggregate([
	        ['$match' => ['estado' => 0]], // solo códigos activos
	        ['$group' => [
	            '_id' => '$marca',
	            'total_codigos' => ['$sum' => 1]
	        ]]
	    ])->toArray();
	    
	    // Convertir a array asociativo para fácil acceso
	    $conteo_marcas = [];
	    foreach ($codigos_por_marca as $item) {
	        $conteo_marcas[$item['_id']] = $item['total_codigos'];
	    }
	    
	    // 2. Construir el filtro para las marcas
	    // Excluir marcas marcadas como inactiva_seo (sin código nuevo en >12m)
	    // para no listarlas en navegación interna.
	    $filtro = [
	        'estado' => 1,
	        'aviso' => 'revisada',
	        'inactiva_seo' => ['$ne' => true]
	    ];
	    
	    if ($categoria !== '') {
	        $filtro['categoria_clave'] = $categoria;
	    }
	    
	    if ($excluye !== '') {
	        $filtro['_id'] = ['$ne' => $excluye];
	    }
	    
	    // 3. Obtener las marcas con proyección optimizada
	    $opciones = [
	        'limit' => (int)$limit,
	        'sort' => $categoria !== '' ? ['nombre' => 1] : ['_id' => -1],
	        'projection' => [
	            'nombre' => 1,
	            'nombre_clave' => 1,
	            'imagen' => 1
	        ]
	    ];
	    
	    $cursor = $collection_marcas->find($filtro, $opciones);
	    $lista_marcas_final = [];
	    
	    foreach ($cursor as $marca) {
	        // Normalizar el nombre_clave para coincidir con el formato de códigos
	        $nombre_clave = str_replace("-", "", $marca->nombre_clave);
	        
	        // Añadir el conteo de códigos
	        $marca->total_codigos = isset($conteo_marcas[$nombre_clave]) ? $conteo_marcas[$nombre_clave] : 0;
	        
	        // Procesar la imagen si existe
	        if (isset($marca->imagen)) {
	            $marca->imagen = str_replace(
	                'https://www.codigoamigo.com/img/',
	                'https://cdn.codigoamigo.com/',
	                $marca->imagen
	            );
	        }
	        
	        $lista_marcas_final[] = $marca;
	    }
	    
	    return $lista_marcas_final;
	}
	

	/*
	 * CASO ESPECIAL:
	 *
	 * los códigos se insertan en la BD por defecto con estado = 0. Los que corresponden
	 * a una marca nueva se insertan con -1. No son visibles. Otros estados corresponden
	 * a los códigos promocionados.
	 *
	 * */

	function getCodigos () {

	    $collection_codigos = getCollectionCodigos();
	    $lista_codigos = $collection_codigos->find(['estado' => 0]);
	    return $lista_codigos;
	}

	function getUsuarios() {

	    $collection_usuarios = getCollectionUsuarios();
	    $lista_usuarios = $collection_usuarios->find(['estado' => 1]);
	    return $lista_usuarios;
	}

	/*********************** CATEGORIAS ***********************/

	function getObjectCategoria ($parameter, $value) {

	    //$collection_categorias = getCollectionCategorias();
	    $collection_categorias = getCollectionCategoriasEvo();
	    $categoria = $collection_categorias->findOne([$parameter => $value]);
	    return $categoria;
	}

	/*********************** MARCAS ***********************/

	function getListObjectsMarca ($parameter, $value) {

	    $collection_marcas = getCollectionMarcas();
	    $count = $collection_marcas->count(
	        [
	            'estado' => 1,
	            $parameter => $value
	        ]);

	    if($count > 0) {
    	    $lista_marcas = $collection_marcas->find(
    	        [
    	            'estado' => 1,
    	            $parameter => $value,
    	        ],
    	        [
    	            'sort' => ['nombre' => 1],
    	        ]
    	        );
    	    return $lista_marcas;
	    } else return 0;
	}

	/************************ CODIGOS ****************************/

	function getObjectCode ($parameter, $value) {

	    $collection_codigos = getCollectionCodigos();
	    $codigo = $collection_codigos->findOne([$parameter => $value]);
	    return $codigo;
	}


// 	function getHistorialCodeByID ($id_codigo) {

// 	    if(isset($id_codigo) && is_object($id_codigo)) {

// 	        $collection_historial = getCollectionHistorial();

// 	        $lista_historial = $collection_historial->find(['id_codigo' => $id_codigo]);

// 	        print_r($lista_historial);

// 	        return $lista_historial;
// 	    } else {
// 	        return;
// 	    }
// 	}



	function getVistasCodeById($id_codigo){

	    $limit = 99;

	    if(isset($id_codigo) && is_object($id_codigo)) {

	        $collection_vistas = getCollectionVistas();

	        $count = $collection_vistas->count(
	            [
	                'id_codigo' => $id_codigo
	            ]);

	        $lista_historial = $collection_vistas->find(
	            [
	                'id_codigo' => $id_codigo

	            ],
	            [
	                'sort' => ['_id' => -1],
	            ]);

	        $array_codigos = iterator_to_array($lista_historial);

	        /*echo "<pre>";
	        print_r($array_codigos);*/

	        return $array_codigos;

	    } else {
	        return;
	    }



	}





    function getNumCodes ($parameter, $value, $limit='', $id_code_exclude='') {

        $collection_codigos = getCollectionCodigos();
        $count = $collection_codigos->count(
            [
                'estado' => 0,
                $parameter => $value,
                '_id' => [ '$ne' => $id_code_exclude]
            ]);
        return $count;
    }

	function getListObjectsCode ($parameter, $value, $limit='', $id_code_exclude='') {

	    $collection_codigos = getCollectionCodigos();
	    $count = $collection_codigos->count(
	        [
	            'estado' => 0,
	            $parameter => $value,
	            '_id' => [ '$ne' => $id_code_exclude]
	        ]);
	    if($count > 0) {
	        $lista_codigos = $collection_codigos->find(
	            [
	                'estado' => 0,
	                $parameter => $value,
	                '_id' => [ '$ne' => $id_code_exclude]
	            ],
	            [
	                'limit' => $limit,
	                'sort' => ['_id' => -1],
	            ]
	            );
	        $lista_codigos->num = $count;

	        return $lista_codigos;
	    } else return 0;
	}

	function addNumVistasCode ($codigo) {

	    $total = $codigo["totalclicks"] + 1;
	    try {
	        $collection_codigos = getCollectionCodigos();
	        $updateResult = $collection_codigos->updateOne(
	            ['_id' => new \MongoDB\BSON\ObjectId($_POST["codigo"]) ],
	            ['$set' => ['totalclicks' => $total]]
	            );
	    } catch(MongoDB\Driver\Exception\WriteException $e) {
	        $writeResult = $e->getWriteResult();
	        echo "Errores en MongoDB\n";
	    }
	}

	/************************ USUARIOS ***************************/

	// Consejo: El parámetro que recibes en esta función no es el mail, sino el _id (hash) del usuario.
	// Te recomiendo cambiar el nombre del parámetro a $user_id para mayor claridad y usarlo como ObjectId en la consulta.
	// ¿Quieres que haga este cambio ahora? Si estás de acuerdo, lo implemento en el siguiente paso.

	function activeUserToLogin($user_id) {
	    $collection_usuarios = getCollectionUsuarios();
	    $updateResult = $collection_usuarios->updateOne(
	        ['_id' => new MongoDB\BSON\ObjectId($user_id)],
	        ['$set' => ['estado' => 1]]
	    );
	    return $updateResult;
	}

	/******************** PHOTO PROFILE USER ***********************/

    function cambiarFotoUsuario ($mail, $url_nueva_foto) {

		$collection_usuarios = getCollectionUsuarios();
	    $updateResult = $collection_usuarios->updateOne(
            ['mail' => $mail ],
            ['$set' => ['img' => $url_nueva_foto]]
            );
	}

	function removePhotoUser ($mail) {

	    $collection_usuarios = getCollectionUsuarios();
	    $updateResult = $collection_usuarios->updateOne(
	        ['mail' => $mail ],
	        ['$set' => ['img' => ""]]
	        );
	}

	/******************** COMPROVE EXISTS ***********************/

	function checkCodeExists ($codigo, $user_id = null) {

	    $collection_codigos = getCollectionCodigos();
	    
	    // Normalizar el código para la comparación
	    $codigo_normalizado = trim($codigo);
	    
	    // Si se proporciona user_id, verificar si el usuario ya tiene ese código
	    if ($user_id) {
	        $codigo_existente = $collection_codigos->findOne([
	            'codigo' => $codigo_normalizado,
	            'id_usuario' => new MongoDB\BSON\ObjectId($user_id)
	        ]);
	        return !empty($codigo_existente["_id"]);
	    }
	    
	    // Verificación global (mantener compatibilidad)
	    $codigo_existente = $collection_codigos->findOne(['codigo' => $codigo_normalizado]);

	    if(empty($codigo_existente["_id"])) return false;
	    else return true;
	}

	function checkUserExists ($mail) {

	    $collection_usuarios = getCollectionUsuarios();
	    $usuario = $collection_usuarios->findOne(['mail' => $mail]);

	    if(empty($usuario["_id"])) return false;
	    else return true;

	}

    /****************************************************
	 ----- ADD ITEM TO BD -----
	****************************************************/

	/****************************************************
	 ----- ADD VIEW -----
	****************************************************/

	function addVista ($codigo) {

	    try {
	        $collection_vistas = getCollectionVistas();
	        $data = ['id_usuario' => $_SESSION["user_id"],
	            'id_codigo' => $codigo["_id"],
	            'fecha_vista' => date("d-m-y H:i", strtotime("now"))
	        ];
	        $collection_vistas->insertOne($data);
	    } catch(MongoDB\Driver\Exception\WriteException $e) {
            $writeResult = $e->getWriteResult();
            echo "Errores en MongoDB\n";
        }
	}

	/****************************************************
	 ----- ADD MARCA -----
	****************************************************/

	function addNewMarca ($nombre_clave_categoria, $nombre_marca, $url_imagen_marca, $id_user){

	    $ruta_imagen = "";
	    $categoria = getObjectCategoria('nombre_clave', $nombre_clave_categoria);
	    $nombre_clave_marca = optimizeUrlPath($nombre_marca);

	    if (!empty($url_imagen_marca)) {
	        if($url_imagen_marca == "Sin imagen") {
	            $ruta_imagen = "No se encontro logo para la imagen";
	        } else {
    	        $imagen = file_get_contents($url_imagen_marca);
    	        $ruta = $_SERVER['DOCUMENT_ROOT']."/uploads/$nombre_clave_marca.jpg";
    	        file_put_contents($ruta, $imagen);
    	        $ruta_imagen = "http://www.codigoamigo.com/uploads/$nombre_clave_marca.jpg";
	        }
	    }

	    try {
	        $data = ['estado' => 1,
	            'nombre' => $nombre_marca,
	            'nombre_clave' => $nombre_clave_marca,
	            'categoria' => $categoria["nombre"],
	            'categoria_clave' => $categoria["nombre_clave"],
	            'imagen' => $ruta_imagen,
	            'descripción' => "",
	            'descripción_larga' => "",
	            'fecha_publicacion' => date("d-m-y H:i", strtotime("now")),
	            'usuario_creador' => $id_user,
	            'url' => "",
	            'url_register' => "",
	            'aviso' => "Marca nueva"
	        ];
	        $collection_marcas = getCollectionMarcas();
	        $collection_marcas->insertOne($data);
	    } catch(MongoCursorException $e) {
	        echo "Error al insertar datos\n";
	    }
	}

	/****************************************************
	 ----- ADD CODE -----
	****************************************************/

	function addNewCode($datos, $id_user) {
	    // DEPRECATED: Usar createNewCode() en su lugar
	    // Esta función se mantiene por compatibilidad pero redirige a la función unificada
	    
	    // Preparar datos para la función unificada
	    $datos_codigo = [
	        'marca' => $datos["marca"],
	        'num_beneficio' => $datos["numerobeneficio"],
	        'tipo_descuento' => $datos["descuentos"],
	        'codigo' => $datos["codigo"],
	        'descripcion' => $datos["descripcion"],
	        'provincia' => $datos["provincia"],
	        'localidad' => $datos["localidad"],
	        'fecha_caducidad' => $datos["fecha"] ?? '',
	        'visibilidad' => 'media'
	    ];
	    
	    // Usar función unificada
	    $resultado = createNewCode($datos_codigo, $id_user);
	    
	    if ($resultado) {
	        return $resultado;
	    } else {
	        print "<script>alert('Este código ya ha sido introducido.'); window.location='". $GLOBALS["website"] . "';</script>";
	        return false;
	    }
	}

	/****************************************************
	 ----- ADD USER -----
	****************************************************/

	function AddNewUser ($datos, $tipo_usuario) {

        switch ($tipo_usuario) {

            case "web":

                try {
                    $data = ['estado' => 0,
                        'type' => $tipo_usuario,
                        'username' => $datos['username'],
                        'mail' => $datos['mail'],
                        'pass' => $datos['pass'],
                        'confirm_password' => $datos['confirm_password'],
                        'fecha_registro' => date("d-m-y H:i", strtotime("now")),
                        'img' => ""
                    ];
                    $collection_usuarios = getCollectionUsuarios();
                    $collection_usuarios->insertOne($data);
                    enviarMailActivacion($datos);
                } catch(MongoCursorException $e) {
                    echo "Error al insertar datos\n";
                }
                break;

            case "facebook":

                try {
                    $data = ['estado' => 1,
                        'type' => "facebook",
                        'username' => $datos['username'],
                        'mail' => $datos['mail'],
                        'pass' => "OyE43zku2OoESkueovaZL",
                        'confirm_password' => "OyE43zku2OoESkueovaZL",
                        'fecha_registro' => date("d-m-y H:i", strtotime("now")),
                        'img' => $datos['img'],
                    ];
                    $collection_usuarios = getCollectionUsuarios();
                    $collection_usuarios->insertOne($data);
                } catch(MongoCursorException $e) {
                    echo "Error al insertar datos\n";
                }
                break;

            case "google":

                try {
                    $data = ['estado' => 1,
                        'type' => "google",
                        'username' => $datos['username'],
                        'mail' => $datos['mail'],
                        'pass' => "OyE43zku2OoESkueovaZL",
                        'confirm_password' => "OyE43zku2OoESkueovaZL",
                        'fecha_registro' => date("d-m-y H:i", strtotime("now")),
                        'img' => $datos["img"]
                    ];
                    $collection_usuarios = getCollectionUsuarios();
                    $collection_usuarios->insertOne($data);
                } catch(MongoCursorException $e) {
                    echo "Error al insertar datos\n";
                }
                break;
        }
	}

    /****************************************************
	  FEATURED CODES
	****************************************************/

	function getFeaturedCode () { }

	/****************************************************
	   AGGREGATIONS
	****************************************************/

	function agregacionesMarcasByCategorias() {

	    $collection_codigos = getCollectionCodigos();

	    $lista_marcas = $collection_codigos->aggregate([
	        [

	        '$match' => ['estado' => 0]

            ],
	        [
	        '$group' =>
	        ['_id' =>'$marca',
	         'count' => ['$sum' => 1]

	        ]
	        ],
	        [

	            '$sort' => ['count' => -1]

	        ],
	        [
	        '$limit' => 20

	        ],
	    ]);

	    foreach ($lista_marcas as $value) {

	       $value["nombre"] = $value["_id"];

	       $results[] = $value;

	   }

	    return $results;
	}
	

	function updateExistingCode($data, $user_id) {

	    // Validate user owns the code
	    $codigo_id = new \MongoDB\BSON\ObjectId($data['codigo_id']);
	    $existing_code = getCodeByID($codigo_id);

		
	    
	    if (!$existing_code || (string)$existing_code["id_usuario"] != (string)$user_id) {
	        return false;
	    }

    // Normalizar nombre de marca y buscar marca existente
    $marca_normalizada = normalizeMarcaName($data['marca']);
    $marca_existente = findOrCreateMarca(
        $data['marca'],
        $marca_normalizada,
        $data['url_imagen'] ?? null,
        $data['categoria_valor'] ?? null,
        $data['categoria_clave'] ?? null
    );

	    // Validar beneficio contra el oficial de la marca
	    $num_beneficio = floatval($data['numerobeneficio'] ?? $data['num_beneficio'] ?? 0);
	    $tipo_beneficio = $data['tipo_descuento'] ?? $data['tipo_beneficio'] ?? 'euros';
	    $validacion_beneficio = validarBeneficioOficial($marca_existente, $num_beneficio, $tipo_beneficio);
	    
	    if (!$validacion_beneficio['valid']) {
	        $_SESSION['msg_error'] = $validacion_beneficio['mensaje'];
	        return false;
	    }

	    // Prepare update data
	    $update_data = array(
	        'codigo' => $data['codigo'],
			'codigo_simple' => $data['codigo_simple'],
	        'descripcion' => $data['descripcion'],
	        'marca' => $marca_existente['nombre_clave'], // Usar nombre_clave de la marca existente
	        'tipo_descuento' => $data['tipo_descuento'],
	        'num_beneficio' => floatval($data['numerobeneficio']),
	        'fecha_modificacion' => new \MongoDB\BSON\UTCDateTime(),
	        'updated_at' => new \MongoDB\BSON\UTCDateTime()
	    );


	    // Optional fields
	    if (isset($data['provincia'])) {
	        $update_data['provincia'] = $data['provincia'];
	    }
	    if (isset($data['localidad'])) {
	        $update_data['localidad'] = $data['localidad'];
	    }

	    // Update the document
	    $collection = getCollectionCodigos();
	    $result = $collection->updateOne(
	        ['_id' => $codigo_id],
	        ['$set' => $update_data]
	    );

	    if ($result->getModifiedCount() > 0) {
	        // Recalcular visibilidad tras modificar el código
	        $codigo_id_string = (string)$codigo_id;
	        updateCodeVisibilityByPosition($codigo_id_string, $marca_existente['nombre_clave']);
	        updateAllCodesVisibilityInBrand($marca_existente['nombre_clave']);
	        return true;
	    }

	    return false;
	}

	// Las funciones normalizeMarcaName() y findOrCreateMarca() están definidas en myphp/funciones.php

	/****************************************************
	 ----- UNIFIED CODE CREATION -----
	****************************************************/

	/**
	 * Valida que el beneficio de un código no supere el beneficio oficial de la marca.
	 * @param array $marca_data Datos de la marca (debe contener 'beneficio_oficial' si existe)
	 * @param float $num_beneficio El beneficio que el usuario quiere publicar
	 * @param string $tipo_beneficio 'euros' o 'porcentaje'
	 * @return array ['valid' => bool, 'max' => float|null, 'tipo' => string, 'texto' => string]
	 */
	function validarBeneficioOficial($marca_data, $num_beneficio, $tipo_beneficio = 'euros') {
	    // Si la marca no tiene beneficio oficial definido, todo OK
	    if (!isset($marca_data['beneficio_oficial']) || empty($marca_data['beneficio_oficial']['cantidad'])) {
	        return ['valid' => true, 'max' => null, 'tipo' => '', 'texto' => ''];
	    }
	    
	    $bo = $marca_data['beneficio_oficial'];
	    $max_cantidad = floatval($bo['cantidad']);
	    $bo_tipo = $bo['tipo'] ?? 'euros';
	    $bo_texto = $bo['texto'] ?? '';
	    
	    // Solo validar si el tipo de beneficio coincide (euros con euros, % con %)
	    if ($tipo_beneficio !== $bo_tipo) {
	        return ['valid' => true, 'max' => $max_cantidad, 'tipo' => $bo_tipo, 'texto' => $bo_texto];
	    }
	    
	    // Validar que no supere el máximo
	    if (floatval($num_beneficio) > $max_cantidad) {
	        $unidad = ($bo_tipo === 'euros') ? '€' : '%';
	        return [
	            'valid' => false, 
	            'max' => $max_cantidad, 
	            'tipo' => $bo_tipo, 
	            'texto' => $bo_texto,
	            'mensaje' => "El beneficio máximo oficial de esta marca es {$max_cantidad}{$unidad}. " .
	                         (!empty($bo_texto) ? "Promoción actual: {$bo_texto}" : '')
	        ];
	    }
	    
	    return ['valid' => true, 'max' => $max_cantidad, 'tipo' => $bo_tipo, 'texto' => $bo_texto];
	}

	/**
	 * Función unificada para crear códigos nuevos
	 * Reemplaza todas las formas anteriores de crear códigos
	 * 
	 * @param array $datos Datos del código
	 * @param string $user_id ID del usuario que crea el código
	 * @return array|false Datos del código creado o false si hay error
	 */
	function createNewCode($datos, $user_id) {
	    try {
	        // Validar campos requeridos
	        $required_fields = ['marca', 'descripcion', 'codigo'];
	        foreach ($required_fields as $field) {
	            if (empty($datos[$field])) {
	                log_warning("Campo requerido faltante en createNewCode", ['field' => $field, 'user_id' => $user_id]);
	                return false;
	            }
	        }

	        // Descripción mínima 50 caracteres
	        if (mb_strlen(trim($datos['descripcion']), 'UTF-8') < 50) {
	            $_SESSION['msg_error'] = 'La descripción debe tener al menos 50 caracteres para aportar valor a la comunidad.';
	            log_info("Descripción demasiado corta en createNewCode", ['user_id' => $user_id, 'len' => mb_strlen(trim($datos['descripcion']), 'UTF-8')]);
	            return false;
	        }

	        // Normalizar el código antes de verificar
	        $codigo_normalizado = trim($datos['codigo']);
	        
	        // Verificar que el usuario no tiene ya ese código
	        if (checkCodeExists($codigo_normalizado, $user_id)) {
	            log_info("Usuario ya tiene este código", ['codigo' => $codigo_normalizado, 'user_id' => $user_id]);
	            return false;
	        }

	        $collection_codigos = getCollectionCodigos();
	        
        // Normalizar marca y buscar/crear marca existente
        $marca_normalizada = normalizeMarcaName($datos['marca']);
        error_log("createNewCode - Datos recibidos: marca=" . $datos['marca'] . ", url_imagen=" . ($datos['url_imagen'] ?? 'null') . ", categoria_valor=" . ($datos['categoria_valor'] ?? 'null') . ", categoria_clave=" . ($datos['categoria_clave'] ?? 'null'));
        $marca_existente = findOrCreateMarca(
            $datos['marca'],
            $marca_normalizada,
            $datos['url_imagen'] ?? null,
            $datos['categoria_valor'] ?? null,
            $datos['categoria_clave'] ?? null
        );
	        
	        // Verificar si ya existe un código activo de esta marca para este usuario.
	        // Filtra por estado activo para permitir re-publicar tras eliminar (estado -2).
	        $codigo_existente = $collection_codigos->findOne([
	            'marca' => $marca_existente['nombre_clave'],
	            'id_usuario' => new MongoDB\BSON\ObjectId($user_id),
	            'estado' => ['$in' => [0, -1, 1]]
	        ]);
	        
	        if ($codigo_existente) {
	            log_info("Usuario ya tiene código de esta marca", ['marca' => $marca_existente['nombre_clave'], 'user_id' => $user_id]);
	            return false;
	        }

	        // Validar beneficio contra el oficial de la marca
	        $num_beneficio = floatval($datos['num_beneficio'] ?? $datos['numerobeneficio'] ?? 0);
	        $tipo_beneficio = $datos['tipo_beneficio'] ?? $datos['tipo_descuento'] ?? $datos['descuentos'] ?? 'euros';
	        $validacion_beneficio = validarBeneficioOficial($marca_existente, $num_beneficio, $tipo_beneficio);
	        
	        if (!$validacion_beneficio['valid']) {
	            $_SESSION['msg_error'] = $validacion_beneficio['mensaje'];
	            log_warning("Beneficio rechazado por exceder oficial", [
	                'marca' => $marca_existente['nombre_clave'],
	                'beneficio_usuario' => $num_beneficio,
	                'beneficio_oficial' => $validacion_beneficio['max'],
	                'user_id' => $user_id
	            ]);
	            return false;
	        }

	        // Preparar datos del código con estructura unificada
	        $codigo_data = [
	            // Identificación
	            'id_usuario' => new MongoDB\BSON\ObjectId($user_id),
	            'marca' => $marca_existente['nombre_clave'],
	            
	            // Contenido del código
	            'codigo' => $codigo_normalizado,
	            'codigo_descuento' => $datos['codigo_descuento'] ?? $codigo_normalizado,
	            'descripcion' => $datos['descripcion'],
	            
	            // Beneficios
	            'num_beneficio' => floatval($datos['num_beneficio'] ?? $datos['numerobeneficio'] ?? 0),
	            'tipo_descuento' => $datos['tipo_descuento'] ?? $datos['descuentos'] ?? '',
	            'tipo_beneficio' => $datos['tipo_beneficio'] ?? 'euros',
	            
	            // Ubicación
	            'provincia' => strtoupper($datos['provincia'] ?? ''),
	            'localidad' => strtoupper($datos['localidad'] ?? ''),
	            
	            // Fechas
	            'fecha_publicacion' => date('Y-m-d H:i:s'),
	            'fecha_validez' => $datos['fecha_validez'] ?? $datos['fecha_caducidad'] ?? $datos['fecha'] ?? '',
	            
	            // Estado y visibilidad
	            'estado' => 0, // 0 = activo, -1 = pendiente, -2 = eliminado
	            'visibilidad' => $datos['visibilidad'] ?? 'baja', // Los códigos nuevos empiezan con baja visibilidad
	            
	            // Destacados (nuevos campos unificados)
	            'destacado' => 0, // Destacado normal
	            'destacado_social' => 0, // Destacado premium (aparece en home)
	            
	            // Estadísticas
	            'totalclicks' => 0, // Sistema anterior
	            'clicks' => 0, // Sistema nuevo
	            
	            // Categoría
	            'clave_categoria' => $marca_existente['categoria_clave'] ?? 'general',
	            
	            // Información adicional
	            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
	            'fecha_modificacion' => date('Y-m-d H:i:s'),
	            'updated_at' => new MongoDB\BSON\UTCDateTime()
	        ];

	        // Insertar el código
	        $result = $collection_codigos->insertOne($codigo_data);
	        
	        if ($result->getInsertedId()) {
	            // Actualizar la visibilidad basada en la posición real
	            $codigo_id = (string)$result->getInsertedId();
	            updateCodeVisibilityByPosition($codigo_id, $marca_existente['nombre_clave']);

	            // Actualizar la visibilidad de todos los códigos de la marca para mantener consistencia
	            updateAllCodesVisibilityInBrand($marca_existente['nombre_clave']);

	            // Bonus +1€ al primer código publicado (onboarding incentive).
	            // Solo se aplica una vez por usuario, controlado por flag bonus_primer_codigo.
	            try {
	                otorgar_bonus_primer_codigo($user_id, $codigo_id, $marca_existente['nombre_clave']);
	            } catch (Throwable $bonus_e) {
	                log_error("Error otorgando bonus primer código", ['user_id' => $user_id, 'error' => $bonus_e->getMessage()]);
	            }

	            // Obtener el código actualizado para devolverlo
	            $codigo_actualizado = $collection_codigos->findOne(['_id' => $result->getInsertedId()]);
	            return iterator_to_array($codigo_actualizado);
	        } else {
	            log_error("Error al insertar código en createNewCode", ['user_id' => $user_id, 'marca' => $datos['marca']]);
	            return false;
	        }
	        
	    } catch (Exception $e) {
	        log_error("Error en createNewCode", ['error' => $e->getMessage(), 'user_id' => $user_id]);
	        return false;
	    }
	}

	// La función checkCodeExists() ya está definida anteriormente en el archivo

?>