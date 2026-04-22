<?php



    function getCollectionHistorial () {

        $db = createConnection();
        $collection_codigos = $db->selectCollection('historial');
        return $collection_codigos;

    }


    function getCollectionZumbidos () {

        $db = createConnection();
        $collection_codigos = $db->selectCollection('registro_zumbidos');
        return $collection_codigos;

    }

    /******************************************************
     *  LISTADO DE CÓDIGOS
     * ***************************************************/

    function getCollectionCodigos () {

        $db = createConnection();

        $collection_codigos = $db->selectCollection('codigos');

        return $collection_codigos;

    }

    function getCollectionVotos () {

        $db = createConnection();

        $collection_votos = $db->selectCollection('votos');

        return $collection_votos;

    }

    function get_code_position ($marca,$mi_id){
        
        // Buscar TODOS los códigos activos de la marca (destacados + normales)
        // ordenados igual que la página de marca: destacados primero, luego por _id descendente
        $nombre_clave = '';
        if (is_object($marca) && isset($marca->nombre_clave)) {
            $nombre_clave = $marca->nombre_clave;
        } elseif (is_array($marca) && isset($marca['nombre_clave'])) {
            $nombre_clave = $marca['nombre_clave'];
        }
        
        if (empty($nombre_clave)) {
            return 999;
        }

        $array_filtro = array("marca" => $nombre_clave, "estado" => 0);
        $array_skip = array(
            "limit" => 50,
            "sort" => array('destacado' => -1, 'destacado_social' => -1, '_id' => -1)
        );

        $lista_codigos_pre = get_all_listado_codigos_array($array_filtro, $array_skip);
        $lista_codigos = isset($lista_codigos_pre["results"]) ? $lista_codigos_pre["results"] : [];

        $posicion = 1;
        foreach ($lista_codigos as $item) {
            $item_id = '';
            if (is_object($item) && isset($item->_id)) {
                $item_id = (string)$item->_id;
            } elseif (is_array($item) && isset($item['_id'])) {
                $item_id = (string)$item['_id'];
            }
            
            if ((string)$mi_id == $item_id) {
                return $posicion;
            }
            $posicion++;
        }

        return $posicion; // Si no se encontró entre los primeros 50
    }

    function get_all_codigos_panel_control() {

        $array_final_codigos = array();
        $collection_codigos = getCollectionCodigos();

        $lista_codigos = $collection_codigos->find(['estado' => 0], ['limit' => 10000]);
        $array_codigos = iterator_to_array($lista_codigos);
        return get_array_codigos_formateada($array_codigos);

    }

    function get_codigos_by_filters($parameter, $value, $limit = '', $skip = '') {

        $collection_codigos = getCollectionCodigos();
        $count = $collection_codigos->count(['estado' => 0, $parameter => $value]);

        if($count > 0) {
            $lista_codigos = $collection_codigos->find(
                ['estado' => 0, $parameter => $value],
                ['limit' => $limit, 'skip' => $skip, 'sort' => ['_id' => -1]]
            );
            $array_codigos = iterator_to_array($lista_codigos);
            return get_array_codigos_formateada($array_codigos);
        }

    }


    function get_codigos_by_user_brand($brand_name, $id_usuario) {

        $id_usuario = new MongoDB\BSON\ObjectId($_SESSION["user_id"]);

        $collection_codigos = getCollectionCodigos();
        $count = $collection_codigos->count(['estado' => 0, 'marca' => $brand_name, 'id_usuario' => $id_usuario]);


        if($count > 0) {
            $lista_codigos = $collection_codigos->find(
                ['estado' => 0, 'marca' => $brand_name, 'id_usuario' => $id_usuario],
                ['limit' => 1, 'skip' => 0, 'sort' => ['_id' => -1]]
                );

            $array_codigos = iterator_to_array($lista_codigos);
            return get_array_codigos_formateada($array_codigos);
        }

    }


    function get_brands_by_user_dont_have_by_category($category_name, $id_usuario) {

        $id_usuario = new MongoDB\BSON\ObjectId($_SESSION["user_id"]);

        $collection_codigos = getCollectionCodigos();
        $count = $collection_codigos->count(['estado' => 0,
            'clave_categoria' => $category_name]);

        echo $category_name;die;
        if($count > 0) {

            $lista_codigos = $collection_codigos->find(
                ['estado' => 0, 'marca' => $brand_name, 'id_usuario' => [ '$not' => $id_usuario]],
                ['limit' => 1, 'skip' => 0, 'sort' => ['_id' => -1]]
                );

            $array_codigos = iterator_to_array($lista_codigos);
            return get_array_codigos_formateada($array_codigos);
        }

    }

    function get_array_codigos_formateada($array_codigos) {

        $array_final_codigos = array();

        foreach ($array_codigos as $item) {

            $item_auxiliar = array();
            $item_auxiliar["id"] = $item["_id"];
            $item_auxiliar["fecha"] = $item["fecha_publicacion"];

            /*
             *
             *  0 = publicado
             *  -1 = desactivado por administrador
             *  -2 = desactivado por usuario
             *
             * */
            $item_auxiliar["destacado"] = $item["destacado"];
            $item_auxiliar["estado"] = $item["estado"];
            if($item_auxiliar["estado"] == 0) {
                $item_auxiliar["estado_string"] = "ACTIVO";
                $item_auxiliar["estado_class"] = "label-success";
            } else if($item_auxiliar["estado"] == -1) {
                $item_auxiliar["estado_string"] = "DESACTIVADO POR ADMINISTRADOR";
                $item_auxiliar["estado_class"] = "label-danger";
            } else if($item_auxiliar["estado"] == -2) {
                $item_auxiliar["estado_string"] = "DESACTIVADO POR USUARIO";
                $item_auxiliar["estado_class"] = "label-warning";
            } else if($item_auxiliar["estado"] == -3) {
                $item_auxiliar["estado_string"] = "DESACTIVADO POR ANTIGÜEDAD";
                $item_auxiliar["estado_class"] = "label-info";
            } else {
                $item_auxiliar["estado_string"] = "REVISAR URGENTE";
                $item_auxiliar["estado_class"] = "label-warning";
            }

            $item_auxiliar["id_usuario"] = $item["id_usuario"];
            if ($item_auxiliar["id_usuario"] instanceof \MongoDB\BSON\ObjectID) {
                $item_auxiliar["id_usuario_string"] = "Object ID";
                $item_auxiliar["id_usuario_class"] = "label-success";
            } else {
                $item_auxiliar["id_usuario_string"] = "String";
                $item_auxiliar["id_usuario_class"] = "label-danger";
            }

            $item_auxiliar["marca"] = $item["marca"];
//             $object_marca = get_object_marca("nombre_clave", $item_auxiliar["marca"]);
//             if($object_marca != "") {
//                 $item_auxiliar["marca_class"] = "label-success";
//                 $item_auxiliar["marca_string"] = "In BD";
//             } else {
//                 $item_auxiliar["marca_class"] = "label-danger";
//                 $item_auxiliar["marca_string"] = "Not saved in BD";
//             }

            $item_auxiliar["codigo"] = $item["codigo"];
            $item_auxiliar["descripcion"] = $item["descripcion"];
            $item_auxiliar["totalclicks"] = $item["totalclicks"];

            if (strpos($item_auxiliar["descripcion"], $item_auxiliar["codigo"]) !== false) {
                $item_auxiliar["validacion_string"] = "Código dentro de descripción";
                $item_auxiliar["validacion_class"] = "label-danger";
            }

            $array_final_codigos[] = $item_auxiliar;

        }

        return $array_final_codigos;

    }

    /******************************************************
     * VOTAR CÓDIGO
     * ***************************************************/




    function votar_codigo($datos) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Verificar que el usuario esté logueado
        if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
            return ['success' => false, 'message' => 'Debes iniciar sesión para votar'];
        }

        $user_id = $_SESSION["user_id"];
        $codigo_id = $datos["id_codigo"] ?? '';
        $votos_positivos = intval($datos["votos_positivos"] ?? 0);
        $votos_negativos = intval($datos["votos_negativos"] ?? 0);

        if (empty($codigo_id)) {
            return ['success' => false, 'message' => 'ID de código no válido'];
        }

        $collection_codigos = getCollectionCodigos();
        $collection_votos = getCollectionVotos();

        try {
            // Verificar si el usuario ya votó este código
            $voto_existente = $collection_votos->findOne([
                'usuario_id' => $user_id,
                'codigo_id' => $codigo_id
            ]);

            if ($voto_existente) {
                // Si ya votó, verificar si está cambiando el voto
                $voto_anterior = $voto_existente['tipo_voto']; // 'positivo' o 'negativo'
                $nuevo_tipo = $votos_positivos > 0 ? 'positivo' : 'negativo';

                if ($voto_anterior === $nuevo_tipo) {
                    return ['success' => false, 'message' => 'Ya has votado este código'];
                }

                // Cambiar el voto
                if ($voto_anterior === 'positivo') {
                    // Quitar voto positivo, añadir negativo
                    $collection_codigos->updateOne(
                        ['_id' => new \MongoDB\BSON\ObjectId($codigo_id)],
                        ['$inc' => ['votos_positivos' => -1, 'votos_negativos' => 1]]
                    );
                } else {
                    // Quitar voto negativo, añadir positivo
                    $collection_codigos->updateOne(
                        ['_id' => new \MongoDB\BSON\ObjectId($codigo_id)],
                        ['$inc' => ['votos_positivos' => 1, 'votos_negativos' => -1]]
                    );
                }

                // Actualizar el voto en la colección
                $collection_votos->updateOne(
                    ['_id' => $voto_existente['_id']],
                    ['$set' => [
                        'tipo_voto' => $nuevo_tipo,
                        'fecha_modificacion' => date('Y-m-d H:i:s'),
                        'updated_at' => new MongoDB\BSON\UTCDateTime()
                    ]]
                );
            } else {
                // Nuevo voto
                $updateResult = $collection_codigos->updateOne(
                    ['_id' => new \MongoDB\BSON\ObjectId($codigo_id)],
                    ['$inc' => [
                        'votos_positivos' => $votos_positivos,
                        'votos_negativos' => $votos_negativos
                    ]]
                );

                // Guardar el voto del usuario
                $collection_votos->insertOne([
                    'usuario_id' => $user_id,
                    'codigo_id' => $codigo_id,
                    'tipo_voto' => $votos_positivos > 0 ? 'positivo' : 'negativo',
                    'fecha_creacion' => date('Y-m-d H:i:s'),
                    'fecha_modificacion' => date('Y-m-d H:i:s'),
                    'created_at' => new MongoDB\BSON\UTCDateTime(),
                    'updated_at' => new MongoDB\BSON\UTCDateTime()
                ]);
            }

            // Obtener los nuevos contadores
            $codigo = $collection_codigos->findOne(['_id' => new \MongoDB\BSON\ObjectId($codigo_id)]);
            $nuevos_positivos = $codigo['votos_positivos'] ?? 0;
            $nuevos_negativos = $codigo['votos_negativos'] ?? 0;
            $nuevo_total = $nuevos_positivos - $nuevos_negativos;

            return [
                'success' => true,
                'message' => 'Voto registrado correctamente',
                'votos_positivos' => $nuevos_positivos,
                'votos_negativos' => $nuevos_negativos,
                'total' => $nuevo_total
            ];

        } catch(MongoDB\Driver\Exception\WriteException $e) {
            if (function_exists('log_error')) {
                log_error("Error al votar código", ['error' => $e->getMessage(), 'codigo_id' => $codigo_id, 'user_id' => $user_id]);
            }
            return ['success' => false, 'message' => 'Error al registrar el voto'];
        } catch(Exception $e) {
            if (function_exists('log_error')) {
                log_error("Error al votar código", ['error' => $e->getMessage(), 'codigo_id' => $codigo_id, 'user_id' => $user_id]);
            }
            return ['success' => false, 'message' => 'Error al registrar el voto'];
        }
    }

    /******************************************************
     * AÑADIR NUEVO CÓDIGO
     * ***************************************************/




    function publicar_nuevo_codigo($datos) {

        session_start();

        $collection_codigos = getCollectionCodigos();
        $collection_marcas = getCollectionMarcas();
        $optimize_name_marca = optimizeUrlPath($datos["marca"]);
        $optimize_name_marca = str_replace("-", "", $optimize_name_marca);

        /* Buscamos la marca en BD, si no esta, la creamos */
        $marca = get_object_marca("nombre_clave", $optimize_name_marca);

        if($marca == "") {
            try {
                $data = [
                    "estado" => 1,
                    "nombre" => $datos["marca"],
                    "nombre_clave" => $optimize_name_marca,
                    "categoria" => $datos["categoria"],
                    "categoria_clave" => str_replace(" ", "-", strtolower($datos["categoria"])),
                    "imagen" => $datos["url_imagen"],
                    "descripción" => "",
                    "descripción_larga" => "",
                    "fecha_publicacion" => date("d-m-Y H:i", strtotime("now")),
                    "url" => "",
                    "url_register" => "",
                    "aviso" => "Marca nueva"
                ];
                $collection_marcas->insertOne($data);
                sleep(0.1);
                $marca = get_object_marca("nombre_clave", $optimize_name_marca);


                /* DATOS PARA SUBIR LA IMAGEN A s3 */


                /*echo "**".$marca["_id"]."**";
                echo "**".$marca["_id"]->oid."**";
                echo "**".$marca["_id"]."**";*/

                //print_r($marca);die;

                /*
                 * ESTA PARTE PETA
                 */

                /* IMAGEN */
//                 $datos['id_marca'] = $marca["_id"];
//                 $datos['url_imagen'] = $marca["url_imagen"];
                //sube_imagen_marca($datos);


                //echo "si";die;

            } catch(MongoCursorException $e) {

            }

        }


        /* HARDCODED */
        if($optimize_name_marca == "repsolwaylet") { $optimize_name_marca = "repsol-waylet"; }
        if($datos["fecha"] == "") { $fechafinal = ""; } else { $fechafinal = $datos["fecha"]; }

        /* CONTROL TO PUBLISH CODE */
        $usuario = get_object_user("mail", $_SESSION["mail"]);

        /* CHECK IF THE USER HAVE CODE OF THE BRAND OF CODE HIMSELF*/

        $codigo_existente = get_codigos_by_user_brand($optimize_name_marca, $_SESSION["user_id"]);


        if(!$codigo_existente){

        if($usuario == "") { echo "NO_BD"; }
        else {
            if($usuario["estado"] == -1) { echo "baneado_temporalmente"; }
            else if($usuario["estado"] == -2) { echo "baneado_definitivamente"; }
            else {
                try {
                    // Preparar datos para la función unificada
                    $datos_codigo = [
                        'marca' => $datos["marca"],
                        'num_beneficio' => $datos["numerobeneficio"],
                        'tipo_descuento' => $datos["descuentos"],
                        'codigo' => $datos["codigo"],
                        'descripcion' => $datos["descripcion"],
                        'provincia' => $datos["provincia"],
                        'localidad' => $datos["localidad"],
                        'fecha_caducidad' => $fechafinal,
                        'visibilidad' => 'baja' // Los códigos nuevos empiezan con baja visibilidad
                    ];
                    
                    // Usar función unificada para crear el código
                    $resultado = createNewCode($datos_codigo, $_SESSION["user_id"]);
                    
                    if ($resultado) {
                        $id = $resultado['_id'];
                        echo link_codigo($id, $optimize_name_marca)."&nuevo_codigo=1";
                    } else {
                        echo "error_insert_data";
                    }
                } catch(MongoCursorException $e) {
                    echo "error_insert_data";
                }
            }
        }

        $datos["_id"] = $id;

        enviar_mail_codigo_publicado($datos, $_SESSION);
        
        
        sendToTelegram($datos);

        }else{
            echo "codigo_repetido";
        }



    }

    /******************************************************
     * PANEL DE ANALISTA
     * ***************************************************/

    function actualizar_codigo($datos) {

        $collection_codigos = getCollectionCodigos();

        try {
            $updateResult = $collection_codigos->updateOne(
                ['_id' => new \MongoDB\BSON\ObjectId($datos["id_codigo"]) ],
                ['$set' =>
                    [
                        'estado' => intval($datos["estado"]),
                        'destacado' => intval($datos["destacado"]),
                        'marca' => $datos["marca"],
                        'descripcion' => $datos["descripcion"]
                    ]
                ]
            );
        } catch(MongoDB\Driver\Exception\WriteException $e) {
            $writeResult = $e->getWriteResult();
            echo "Errores en MongoDB\n";
        }

    }


    function desactivar_codigo($datos) {

        $collection_codigos = getCollectionCodigos();

        try {
            $updateResult = $collection_codigos->updateOne(
                ['_id' => new \MongoDB\BSON\ObjectId($datos["id_codigo"]) ],
                ['$set' => ['estado' => -1] ]
            );
        } catch(MongoDB\Driver\Exception\WriteException $e) {
            $writeResult = $e->getWriteResult();
            echo "Errores en MongoDB\n";
        }

    }

    function desactivar_codigo_usuario($datos) {

        session_start();

        if($_SESSION["user_id"]){

            //TOMO EL CODIGO
            $obj_id_codigo = new \MongoDB\BSON\ObjectId($datos["id_codigo"]);
            $lista_codigos = getCodeByID_prelista($obj_id_codigo);


            //SECURITY CHECK
            if($_SESSION["user_id"] == (string) $lista_codigos[0]->id_usuario){
                $collection_codigos = getCollectionCodigos();

                try {
                    $updateResult = $collection_codigos->updateOne(
                        ['_id' => new \MongoDB\BSON\ObjectId($datos["id_codigo"]) ],
                        ['$set' => ['estado' => -2] ]
                        );
                } catch(MongoDB\Driver\Exception\WriteException $e) {
                    $writeResult = $e->getWriteResult();
                    echo "Errores en MongoDB\n";
                }
            }else{
                $txt = print_r($datos,1);
                mail("thevega82@gmail.com","intento borrar codigoamigo falso hacking",$txt);
            }





        }



    }


    function restaurar_codigo_usuario($datos) {

        session_start();


        if($_SESSION["user_id"]){

            //TOMO EL CODIGO
            $obj_id_codigo = new \MongoDB\BSON\ObjectId($datos["id_codigo"]);
            $lista_codigos = getCodeByID_prelista($obj_id_codigo);

            //SECURITY CHECK - supports restoring estado -2 (user deactivated) and -3 (age deactivated)
            if($_SESSION["user_id"] == (string) $lista_codigos[0]->id_usuario){
                $collection_codigos = getCollectionCodigos();


                try {
                    $updateResult = $collection_codigos->updateOne(
                        ['_id' => new \MongoDB\BSON\ObjectId($datos["id_codigo"]) ],
                        ['$set' => [
                            'estado' => 0,
                            'fecha_publicacion' => date('Y-m-d H:i:s'), // Renovar fecha al reactivar
                        ]]
                        );
                } catch(MongoDB\Driver\Exception\WriteException $e) {
                    $writeResult = $e->getWriteResult();
                    echo "Errores en MongoDB\n";
                }

                echo "Código restaurado correctamente";


            }else{
                $txt = print_r($datos,1);
                mail("thevega82@gmail.com","intento restaurar codigoamigo falso hacking",$txt);
            }





        }

    }




    function borrar_codigo($datos) {
        // Limpiar cualquier output previo
        if (ob_get_level() > 0) {
            ob_clean();
        }
        
        // Iniciar sesión si no está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Verificar que el usuario esté logueado
        if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
            exit;
        }

        // Verificar que se proporcionó el ID del código
        if (!isset($datos["id_codigo"]) || empty($datos["id_codigo"])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'ID de código no válido']);
            exit;
        }

        try {
            // Obtener el código para verificar permisos
            $object_id_codigo = new \MongoDB\BSON\ObjectId($datos["id_codigo"]);
            $codigo = getCodeByID($object_id_codigo);

            if (!$codigo) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Código no encontrado']);
                exit;
            }

            // Verificar que el código pertenece al usuario
            if ((string)$codigo['id_usuario'] !== $_SESSION["user_id"]) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'No tienes permisos para eliminar este código']);
                exit;
            }

            // Eliminar el código
            $collection_codigos = getCollectionCodigos();
            $result = $collection_codigos->deleteOne(['_id' => $object_id_codigo]);

            if ($result->getDeletedCount() > 0) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Código eliminado correctamente']);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Error al eliminar el código']);
            }
            exit;

        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error interno: ' . $e->getMessage()]);
            exit;
        }
    }









    function get_publish_codes() {

        $collection_codigos = getCollectionCodigos();

        $lista_codigos = $collection_codigos->find(['estado' => 0], ['sort' => ['_id' => -1]]);
        $array_codigos = iterator_to_array($lista_codigos);
        return $array_codigos;

    }










    function buscar_marcas($datos) {
        $query = isset($datos['query']) ? strtolower(trim($datos['query'])) : '';
        
        if (strlen($query) < 2) {
            echo json_encode([]);
            return;
        }
        
        // Cargar el archivo JSON de marcas
        $json_file = __DIR__ . '/../datos.json';
        if (!file_exists($json_file)) {
            echo json_encode([]);
            return;
        }
        
        $marcas = json_decode(file_get_contents($json_file), true);
        if (!$marcas) {
            echo json_encode([]);
            return;
        }
        
        $resultados = [];
        $limite = 10; // Máximo 10 resultados
        
        foreach ($marcas as $marca) {
            if (count($resultados) >= $limite) break;
            
            $nombre = strtolower($marca['nombre']);
            $nombre_clave = strtolower($marca['nombre_clave']);
            
            // Buscar coincidencias en nombre o nombre_clave
            if (strpos($nombre, $query) !== false || strpos($nombre_clave, $query) !== false) {
                $resultados[] = [
                    'nombre' => $marca['nombre'],
                    'nombre_clave' => $marca['nombre_clave'],
                    'categoria' => $marca['categoria'],
                    'imagen' => $marca['imagen']
                ];
            }
        }
        
        echo json_encode($resultados);
    }

?>