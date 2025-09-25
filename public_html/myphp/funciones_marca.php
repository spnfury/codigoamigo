<?php

use Aws\Common\Aws;
use Aws\S3\S3Client;

// Incluir autoloader de Composer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Incluir funciones de conexión a MongoDB
if (!function_exists('createConnection')) {
    include_once __DIR__ . '/funciones.php';
}

// Incluir funciones de códigos
if (!function_exists('getCollectionCodigos')) {
    include_once __DIR__ . '/funciones_codigo.php';
}

/******************************************************
 *  LISTADO DE MARCAS
 * ***************************************************/

function getCollectionMarcas() {

    $db = createConnection();
    $collection_marcas = $db->selectCollection('marcas');
    return $collection_marcas;

}

function get_all_marcas_panel_control($limit = 9999,$aviso='',$solo='') {

    $array_final_marcas = array();
    $collection_marcas = getCollectionMarcas();

    if($aviso=='Marca nueva estado 0'){

        $lista_marcas = $collection_marcas->find(
            [
                'estado' => 0,
                'aviso' => $aviso

            ],
            [
              'limit' => $limit

            ]);

    }elseif($aviso=='Marca nueva'){

        $lista_marcas = $collection_marcas->find(
            [
                'aviso' => $aviso

            ],
            [
              'limit' => $limit

            ]);

    }elseif($aviso=='sin_categoria'){

        $lista_marcas = $collection_marcas->find(
            [
                'categoria' => 'select'

            ],
            [
                'limit' => $limit

            ]);

    }elseif($aviso=='sin_imagen'){

        $lista_marcas = $collection_marcas->find(
            [
                'imagen' => 'Sin imagen'
            ],
            [
                'limit' => $limit
            ]);

    }else{

        $lista_marcas = $collection_marcas->find(
            [
                'estado' => 1,
            ],
            [
                'limit' => $limit

            ]);


    }
    $array_marcas = iterator_to_array($lista_marcas);
    //print_r($array_marcas);die;
    foreach ($array_marcas as $item) {

        $item_auxiliar = array();

        $array_filtro = array("marca"=>$item["nombre_clave"]);
        $array_filtro = array_merge($array_filtro, array("estado"=>0));
        $item_auxiliar["numero_codigos"] = count_all_listado_codigos_array($array_filtro, $array_skip,0);


        if(($solo==1 && $item_auxiliar["numero_codigos"]==1) || ($solo=='no' && $item_auxiliar["numero_codigos"]==0) || !$solo){

            $item_auxiliar["id"] = $item["_id"];

            $item_auxiliar["fecha"] = ($item["fecha_publicacion"]);

            $item_auxiliar["nombre"] = $item["nombre"];
            $item_auxiliar["nombre_clave"] = $item["nombre_clave"];
            $item_auxiliar["categoria"] = $item["categoria"];
            $item_auxiliar["categoria_clave"] = $item["categoria_clave"];
            $item_auxiliar["url_imagen"] = $item["imagen"];

            $item_auxiliar["descripción"] = $item["descripción"];
            $item_auxiliar["descripción_larga"] = $item["descripción_larga"];

            $array_final_marcas[] = $item_auxiliar;

        }

    }

    return $array_final_marcas;

}

/******************************************************
 *  RECUPERAR OBJECTO MARCA
 * ***************************************************/

function get_object_marca ($parameter, $value) {

    $collection_marcas = getCollectionMarcas();
    $marca = $collection_marcas->findOne([$parameter => $value]);

    return $marca;

}

function sube_imagen_marca($datos){


    if($datos["id_marca"]){


//         error_reporting(E_ALL);
//         ini_set("display_errors", "on");

        $folder = "/var/www/vhosts/codigoamigo.com/httpdocs/img/panel_marcas/new/";
        $folder_ext = "https://www.codigoamigo.com/img/panel_marcas/new/";


        //DESDE EL PANEL
        if(isset($datos['image']) && $datos['image']!=''){
            $data = $datos['image'];
            list($type, $data) = explode(';', $data);
            list(, $data)      = explode(',', $data);

            $data = base64_decode($data);

        }elseif($datos['url_imagen']){
            $data = file_get_contents($datos['url_imagen']);
        }

        $imageName = time().'.png';


        // Create new imagick object
        //echo "zipo3";
        $im = new Imagick();
        $im->setFormat('PNG');
        $im->readImageBlob($data);
        $im = $im->flattenImages();
        //echo "zipo4";
        // Optimize the image layers
        $im->optimizeImageLayers();

        // Compression and quality
        $im->setImageCompression(Imagick::COMPRESSION_JPEG);
        $im->setImageCompressionQuality(75);

        // Write the image back
        $im->writeImages($folder.$imageName, true);
        //echo "si estoy";

        /*$SYSTEM_CONF["AMAZON_KEY"] = 'AKIAIBGD6UYYDE7LWM3A';
        $SYSTEM_CONF["AMAZON_SECRET"] = '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';

        $SYSTEM_CONF["AMAZON_BUCKET"] = "cdn-codigoamigo";*/

        /*$SYSTEM_CONF["AMAZON_KEY"] = 'AKIASARKPDPLQVRVKCFK';
        $SYSTEM_CONF["AMAZON_SECRET"] = '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';*/
        
        $SYSTEM_CONF["AMAZON_KEY"] = 'd5092bd5c6f9b8f617a683dcb24d63df';
        $SYSTEM_CONF["AMAZON_SECRET"] = 'aecdd53e2e31d664a5a602df52a41e1e';
        
        

        $SYSTEM_CONF["AMAZON_BUCKET"] = "codigoamigo-bucket";


        
        $s3 = S3Client::factory(array(
            'credentials' => array(
                'key'    =>  $SYSTEM_CONF["AMAZON_KEY"],
                'secret' =>  $SYSTEM_CONF["AMAZON_SECRET"],
            ),
            'region' => 'eu2',
            'version' => 'latest',
            'endpoint' => 'https://eu2.contabostorage.com/',
            'use_path_style_endpoint' => true
        ));
        
        

        $bucket = $SYSTEM_CONF["AMAZON_BUCKET"];
        
        
        try {
            $s3->putObject(array(
                'Bucket'       => $bucket,
                'Key'          => "panel_marcas/new/".$imageName,
                'ContentType' => 'image',
                'Body' => ($data)
            ));

            //$ruta_imagen = "https://cdn-codigoamigo.s3-eu-west-1.amazonaws.com//panel_marcas/new/".$imageName;
            //$ruta_imagen = "https://d3hcf0nbuqjt3g.cloudfront.net/panel_marcas/new/".$imageName;
            $ruta_imagen = "https://cdn.codigoamigo.com/panel_marcas/new/".$imageName;
            
            
        } catch (Exception $e) {
            echo 'Ha habido una excepción: ' . $e->getMessage() . "<br>";

            $ruta_imagen = $folder_ext.$imageName;
        }
        

        //print_r($datos);
        $collection = getCollectionMarcas();
        try {
            $updateResult = $collection->updateOne(
                ['_id' => new \MongoDB\BSON\ObjectId($datos["id_marca"]) ],
                ['$set' =>
                    [
                        'imagen' => $ruta_imagen,
                        'aviso' => 'revisada'
                    ]
                ]
                );
            
            
            echo "imagen updateada en la marca id ".$datos["id_marca"].": ".$ruta_imagen;
            
            
            
        } catch(MongoDB\Driver\Exception\WriteException $e) {
            $writeResult = $e->getWriteResult();
            echo "Errores en MongoDB\n";
        }


        if(!$datos['url_imagen']){//SI NO VIENE DEL PANEL
            echo $folder.$imageName;
        }
        


    }
}

function fusiona_marcas($data) {



    /*
     *
     * MIRO SI EXISTE LA MARCA 1
     */

    /*
     *
     */





    $data["id_marca"] = trim($data["id_marca"]);
    $data["id_marca_fusiona"] = trim($data["id_marca_fusiona"]);

    //MIRO LA MARCA A MIGRAR EXISTE
    $marca_obj_origen = get_object_marca("_id",new \MongoDB\BSON\ObjectId($data["id_marca"]));

    //MIRO LA MARCA DESTINO EXISTE
    $marca_obj_destino = get_object_marca("_id",new \MongoDB\BSON\ObjectId($data["id_marca_fusiona"]));

//     echo $valor["_id"]."**destino:";
//     echo $data["id_marca_fusiona"];
//     echo $marca_obj_destino["nombre_clave"];die;

    //print_r($marca_obj_origen);

    if($marca_obj_origen && $marca_obj_destino){

        $collection_codigos = getCollectionCodigos();




        /* TOMO TODOS LOS CODIGOS DE LA MARCA ORIGEN */
        $array_filtro = array("marca"=>$marca_obj_origen["nombre_clave"]);
        $array_skip = array();

        //TOMO LOS CODIGOS
        $lista_codigos_patrocinados_origen_pre = get_all_listado_codigos_array($array_filtro, $array_skip);
        $lista_codigos_patrocinados_origen = $lista_codigos_patrocinados_origen_pre["results"];

        /* RECORRO ORIGEN */


        foreach($lista_codigos_patrocinados_origen as $campo=>$valor){



            try {

                $updateResult = $collection_codigos->updateOne(
                    ['_id' => new \MongoDB\BSON\ObjectId($valor["_id"]) ],
                    ['$set' =>
                        [
                            'marca' => $marca_obj_destino["nombre_clave"]
                        ]
                    ]
                    );

//                 echo "asdads";
//                 print_r($updateResult);
//                 die;

            } catch(MongoDB\Driver\Exception\WriteException $e) {
                $writeResult = $e->getWriteResult();
                echo "Errores en MongoDB\n";
            }


//             echo $valor["_id"];
//             echo $marca_obj_destino["nombre_clave"];
//             echo "***";
//             print_r($lista_codigos_patrocinados_origen);die;

        }

        /* BORRO ORIGEN */
        $datos_a_borrar["id_marca"] = $data["id_marca"];
        borrar_marca($datos_a_borrar);

        echo "Movido y marca eliminada correctamente ".count($lista_codigos_patrocinados_origen)." codigos";

    }else{

        if(!$marca_obj_destino){
            echo "marca de destino no encontrada\n";
        }

        if(!$marca_obj_origen){
            echo "marca de origen no encontrada\n";
        }

    }


    $collection = getCollectionMarcas();

}

function actualizar_marca($data) {

    $collection = getCollectionMarcas();
    try {
        $updateResult = $collection->updateOne(
            ['_id' => new \MongoDB\BSON\ObjectId($data["id_marca"]) ],
            ['$set' =>
                [
                    /*'nombre' => $data["nombre"],*/
                    'descripción' => $data["descripcion"],
                    'descripción_larga' => $data["descripcion_larga"],
                    'aviso' => 'revisada'
                    /*'nombre_clave' => $data["nombre_clave"],
                     'categoria' => $data["categoria"],
                     'categoria_clave' => $data["categoria_clave"],
                     'imagen' => $data["url_imagen"]*/
                ]
            ]
            );
    } catch(MongoDB\Driver\Exception\WriteException $e) {
        $writeResult = $e->getWriteResult();
        echo "Errores en MongoDB\n";
    }

}

function borrar_marca($datos) {

    $collection = getCollectionMarcas();
    $object_id_marca = new \MongoDB\BSON\ObjectId($datos["id_marca"]);

    if ($object_id_marca instanceof \MongoDB\BSON\ObjectID) {
        $data = ['_id' => $object_id_marca];
        $collection->deleteOne($data);
    }

}

/**
 * Obtiene todas las marcas activas para mostrar en el listado
 */
function getMarcas($limit = null, $categoria = null, $excluye = null) {
    $array_final_marcas = array();
    $collection_marcas = getCollectionMarcas();

    // Construir filtro de búsqueda
    $filtro = ['estado' => 1]; // Solo marcas activas
    
    if ($categoria) {
        $filtro['categoria_clave'] = $categoria;
    }
    
    if ($excluye && !empty($excluye)) {
        // Asegurar que $excluye sea un array
        if (is_string($excluye)) {
            $excluye = [$excluye];
        } elseif (!is_array($excluye)) {
            $excluye = (array)$excluye;
        }
        
        // Verificar que el array no esté vacío y contenga elementos válidos
        if (!empty($excluye) && is_array($excluye)) {
            // Filtrar elementos nulos o vacíos
            $excluye = array_filter($excluye, function($item) {
                return !empty($item) && is_string($item);
            });
            
            // Solo aplicar $nin si hay elementos válidos
            if (!empty($excluye)) {
                $filtro['nombre_clave'] = ['$nin' => array_values($excluye)];
            }
        }
    }

    // Opciones de consulta
    $opciones = [];
    if ($limit) {
        $opciones['limit'] = $limit;
    }
    
    // Ordenar por nombre
    $opciones['sort'] = ['nombre' => 1];

    $lista_marcas = $collection_marcas->find($filtro, $opciones);
    $array_marcas = iterator_to_array($lista_marcas);

    foreach ($array_marcas as $item) {
        $item_auxiliar = array();
        
        // Contar códigos activos de esta marca
        $array_filtro = array("marca" => $item["nombre_clave"], "estado" => 0);
        $item_auxiliar["numero_codigos"] = count_all_listado_codigos_array($array_filtro, [], 0);

        // Solo incluir marcas que tengan códigos activos
        if ($item_auxiliar["numero_codigos"] > 0) {
            $item_auxiliar["id"] = $item["_id"];
            $item_auxiliar["fecha"] = $item["fecha_publicacion"];
            $item_auxiliar["nombre"] = $item["nombre"];
            $item_auxiliar["nombre_clave"] = $item["nombre_clave"];
            $item_auxiliar["categoria"] = $item["categoria"];
            $item_auxiliar["categoria_clave"] = $item["categoria_clave"];
            $item_auxiliar["imagen"] = $item["imagen"];
            $item_auxiliar["descripción"] = $item["descripción"];
            $item_auxiliar["descripción_larga"] = $item["descripción_larga"];

            $array_final_marcas[] = $item_auxiliar;
        }
    }

    return $array_final_marcas;
}

?>