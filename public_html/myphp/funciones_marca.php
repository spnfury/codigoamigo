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

// Incluir utilidad de cache
require_once __DIR__ . '/SimpleCache.php';

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
    $array_skip = array(); // Inicializar variable $array_skip

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
    $array_skip = array(); // Inicializar variable $array_skip

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

        /* CREAR REDIRECCIÓN 301 ANTES DE BORRAR LA MARCA ORIGEN */
        $redirect_created = create_brand_redirect(
            $marca_obj_origen["nombre_clave"],
            $marca_obj_destino["nombre_clave"],
            'fusion'
        );

        /* BORRO ORIGEN */
        $datos_a_borrar["id_marca"] = $data["id_marca"];
        borrar_marca($datos_a_borrar);

        echo "Movido y marca eliminada correctamente ".count($lista_codigos_patrocinados_origen)." codigos";
        if ($redirect_created) {
            echo ". Redirección 301 creada de /de-" . $marca_obj_origen["nombre_clave"] . " hacia /de-" . $marca_obj_destino["nombre_clave"];
        }

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

/******************************************************
 *  GESTIÓN DE REDIRECCIONES 301 PARA MARCAS
 * ***************************************************/

function getCollectionRedirects() {
    $db = createConnection();
    $collection_redirects = $db->selectCollection('redirects');
    return $collection_redirects;
}

function create_brand_redirect($old_brand_key, $new_brand_key, $reason = 'fusion') {
    $collection_redirects = getCollectionRedirects();

    // Verificar si ya existe una redirección para esta marca antigua
    $existing_redirect = $collection_redirects->findOne([
        'old_brand_key' => $old_brand_key,
        'type' => 'marca'
    ]);

    if (!$existing_redirect) {
        $redirect_data = [
            'type' => 'marca',
            'old_brand_key' => $old_brand_key,
            'new_brand_key' => $new_brand_key,
            'reason' => $reason,
            'redirect_url' => '/de-' . $new_brand_key,
            'created_at' => date('Y-m-d H:i:s'),
            'is_active' => true
        ];

        $result = $collection_redirects->insertOne($redirect_data);

        if ($result->getInsertedId()) {
            return true;
        }
    }

    return false;
}

function get_brand_redirect($brand_key) {
    $collection_redirects = getCollectionRedirects();

    $redirect = $collection_redirects->findOne([
        'old_brand_key' => $brand_key,
        'type' => 'marca',
        'is_active' => true
    ]);

    return $redirect;
}

function get_all_redirects($limit = 100) {
    $collection_redirects = getCollectionRedirects();

    $redirects = $collection_redirects->find(
        [],
        [
            'sort' => ['created_at' => -1],
            'limit' => $limit
        ]
    )->toArray();

    return $redirects;
}

function delete_redirect($redirect_id) {
    $collection_redirects = getCollectionRedirects();

    $result = $collection_redirects->deleteOne([
        '_id' => new \MongoDB\BSON\ObjectId($redirect_id)
    ]);

    return $result->getDeletedCount() > 0;
}

function toggle_redirect_status($redirect_id) {
    $collection_redirects = getCollectionRedirects();

    // Primero obtener el estado actual
    $redirect = $collection_redirects->findOne(['_id' => new \MongoDB\BSON\ObjectId($redirect_id)]);
    $current_status = $redirect['is_active'] ?? true;

    $result = $collection_redirects->updateOne(
        ['_id' => new \MongoDB\BSON\ObjectId($redirect_id)],
        ['$set' => ['is_active' => !$current_status]]
    );

    return $result->getModifiedCount() > 0;
}

function cleanup_orphan_redirects() {
    $collection_redirects = getCollectionRedirects();
    $collection_marcas = getCollectionMarcas();

    // Obtener todas las marcas existentes
    $existing_brands = [];
    $marcas = $collection_marcas->find([], ['projection' => ['nombre_clave' => 1]])->toArray();
    foreach ($marcas as $marca) {
        $existing_brands[] = $marca['nombre_clave'];
    }

    // Obtener todas las redirecciones activas
    $redirects = $collection_redirects->find(['is_active' => true])->toArray();

    $cleaned_count = 0;
    foreach ($redirects as $redirect) {
        // Verificar si la marca destino existe
        if (!in_array($redirect['new_brand_key'], $existing_brands)) {
            // Desactivar la redirección huérfana
            $collection_redirects->updateOne(
                ['_id' => $redirect['_id']],
                ['$set' => ['is_active' => false]]
            );
            $cleaned_count++;
        }
    }

    return $cleaned_count;
}

/******************************************************
 *  DETECCIÓN DE MARCAS DUPLICADAS
 * ***************************************************/

function detectar_marcas_duplicadas($umbral_similitud = 80) {
    $collection_marcas = getCollectionMarcas();

    // Obtener todas las marcas activas
    $marcas = $collection_marcas->find(['estado' => 1], [
        'sort' => ['nombre' => 1],
        'projection' => ['nombre' => 1, 'nombre_clave' => 1, '_id' => 1]
    ])->toArray();

    $grupos_duplicados = [];
    $procesadas = [];

    foreach ($marcas as $marca_actual) {
        if (in_array($marca_actual['_id'], $procesadas)) {
            continue;
        }

        $grupo_actual = [$marca_actual];
        $procesadas[] = $marca_actual['_id'];

        foreach ($marcas as $marca_comparar) {
            if (in_array($marca_comparar['_id'], $procesadas)) {
                continue;
            }

            // Calcular similitud usando similar_text
            similar_text(
                strtolower($marca_actual['nombre']),
                strtolower($marca_comparar['nombre']),
                $porcentaje
            );

            // Si la similitud es mayor al umbral, considerar duplicadas
            if ($porcentaje >= $umbral_similitud) {
                $grupo_actual[] = $marca_comparar;
                $procesadas[] = $marca_comparar['_id'];
            }
        }

        // Solo incluir grupos con más de una marca
        if (count($grupo_actual) > 1) {
            $grupos_duplicados[] = $grupo_actual;
        }
    }

    return $grupos_duplicados;
}

function obtener_marcas_duplicadas_para_panel($umbral_similitud = 80) {
    $grupos_duplicados = detectar_marcas_duplicadas($umbral_similitud);

    $resultado = [];
    foreach ($grupos_duplicados as $grupo) {
        $grupo_info = [
            'grupo_id' => uniqid(),
            'marcas' => [],
            'similitud_promedio' => 0,
            'total_codigos' => 0
        ];

        $similitudes = [];
        foreach ($grupo as $marca) {
            $codigos_count = getCollectionCodigos()->countDocuments(['marca' => $marca['nombre_clave']]);

            $grupo_info['marcas'][] = [
                'id' => (string)$marca['_id'],
                'nombre' => $marca['nombre'],
                'nombre_clave' => $marca['nombre_clave'],
                'codigos_count' => $codigos_count
            ];

            $grupo_info['total_codigos'] += $codigos_count;

            // Calcular similitudes entre esta marca y las demás del grupo
            foreach ($grupo as $marca_comparar) {
                if ($marca['_id'] != $marca_comparar['_id']) {
                    similar_text(
                        strtolower($marca['nombre']),
                        strtolower($marca_comparar['nombre']),
                        $porcentaje
                    );
                    $similitudes[] = $porcentaje;
                }
            }
        }

        if (!empty($similitudes)) {
            $grupo_info['similitud_promedio'] = round(array_sum($similitudes) / count($similitudes), 1);
        }

        $resultado[] = $grupo_info;
    }

    // Ordenar grupos por similitud promedio (más similares primero)
    usort($resultado, function($a, $b) {
        return $b['similitud_promedio'] <=> $a['similitud_promedio'];
    });

    return $resultado;
}

function fusionar_marcas_masiva($marcas_origen_ids, $marca_destino_id) {
    $collection_marcas = getCollectionMarcas();
    $collection_codigos = getCollectionCodigos();

    // Obtener información de la marca destino
    $marca_destino = $collection_marcas->findOne(['_id' => new MongoDB\BSON\ObjectId($marca_destino_id)]);
    if (!$marca_destino) {
        return ['error' => 'Marca destino no encontrada'];
    }

    $resultados = [
        'fusionadas' => 0,
        'codigos_transferidos' => 0,
        'redirecciones_creadas' => 0,
        'errores' => []
    ];

    foreach ($marcas_origen_ids as $marca_origen_id) {
        // Obtener información de la marca origen
        $marca_origen = $collection_marcas->findOne(['_id' => new MongoDB\BSON\ObjectId($marca_origen_id)]);

        if (!$marca_origen) {
            $resultados['errores'][] = "Marca origen {$marca_origen_id} no encontrada";
            continue;
        }

        if ($marca_origen_id === $marca_destino_id) {
            $resultados['errores'][] = "No se puede fusionar una marca consigo misma: {$marca_origen['nombre']}";
            continue;
        }

        try {
            // Contar códigos antes de la transferencia
            $codigos_count = $collection_codigos->countDocuments(['marca' => $marca_origen['nombre_clave']]);

            // Transferir códigos
            $update_result = $collection_codigos->updateMany(
                ['marca' => $marca_origen['nombre_clave']],
                ['$set' => ['marca' => $marca_destino['nombre_clave']]]
            );

            // Crear redirección 301
            $redirect_created = create_brand_redirect(
                $marca_origen['nombre_clave'],
                $marca_destino['nombre_clave'],
                'fusion_masiva'
            );

            // Eliminar marca origen
            $collection_marcas->deleteOne(['_id' => new MongoDB\BSON\ObjectId($marca_origen_id)]);

            $resultados['fusionadas']++;
            $resultados['codigos_transferidos'] += $update_result->getModifiedCount();
            if ($redirect_created) {
                $resultados['redirecciones_creadas']++;
            }

        } catch (Exception $e) {
            $resultados['errores'][] = "Error al fusionar {$marca_origen['nombre']}: " . $e->getMessage();
        }
    }

    return $resultados;
}

/**
 * Obtiene todas las marcas activas para mostrar en el listado
 */
function getMarcas($limit = null, $categoria = null, $excluye = null) {
    // Generar una clave de cache basada en los parámetros
    $cacheKey = "getMarcas_" . ($limit ?? 'all') . "_" . ($categoria ?? 'none') . "_" . (is_array($excluye) ? implode(',', $excluye) : ($excluye ?? 'none'));
    
    $cachedResult = SimpleCache::get($cacheKey);
    if ($cachedResult !== null) {
        return $cachedResult;
    }

    $array_final_marcas = array();
    $collection_marcas = getCollectionMarcas();
    $collection_codigos = getCollectionCodigos();

    // Construir filtro de búsqueda para marcas
    // inactiva_seo: excluye marcas sin código nuevo en >12m para no mostrarlas en listados públicos
    $filtro = ['estado' => 1, 'inactiva_seo' => ['$ne' => true]];
    if ($categoria) {
        $filtro['categoria_clave'] = $categoria;
    }
    
    if ($excluye && !empty($excluye)) {
        if (is_string($excluye)) {
            $excluye = [$excluye];
        }
        $excluye_valid = array_filter((array)$excluye, function($item) {
            return !empty($item) && is_string($item);
        });
        if (!empty($excluye_valid)) {
            $filtro['nombre_clave'] = ['$nin' => array_values($excluye_valid)];
        }
    }

    // 1. Obtener todas las marcas que cumplen el criterio
    $lista_marcas = $collection_marcas->find($filtro);
    $marcas_obj = iterator_to_array($lista_marcas);
    if (empty($marcas_obj)) {
        return [];
    }

    // 2. Obtener conteos de códigos agrupados por marca en UNA SOLA consulta (Evita N+1)
    $pipeline = [
        ['$match' => ['estado' => 0]], // Solo códigos activos
        ['$group' => ['_id' => '$marca', 'count' => ['$sum' => 1]]]
    ];
    $cursor_counts = $collection_codigos->aggregate($pipeline);
    $counts_map = [];
    foreach ($cursor_counts as $doc) {
        $counts_map[$doc['_id']] = $doc['count'];
    }

    // 3. Procesar resultados
    foreach ($marcas_obj as $item) {
        $marca_key = $item["nombre_clave"];
        $num_codes = $counts_map[$marca_key] ?? 0;

        // Solo incluir marcas que tengan códigos activos
        if ($num_codes > 0) {
            $item_auxiliar = array();
            $item_auxiliar["numero_codigos"] = $num_codes;
            $item_auxiliar["id"] = $item["_id"];
            $item_auxiliar["fecha"] = $item["fecha_publicacion"] ?? '';
            $item_auxiliar["nombre"] = $item["nombre"] ?? '';
            $item_auxiliar["nombre_clave"] = $marca_key;
            $item_auxiliar["categoria"] = $item["categoria"] ?? '';
            $item_auxiliar["categoria_clave"] = $item["categoria_clave"] ?? '';
            
            // Imagen procesada centralizada
            $imagen_raw = $item["imagen"] ?? '';
            $imagen_procesada = $imagen_raw;
            if ($imagen_raw && $imagen_raw != 'Sin imagen') {
                $imagen_procesada = str_replace("http://", "https://", $imagen_raw);
                // CloudFront CDN is down - convert S3/CloudFront URLs to direct server URLs
                if (strpos($imagen_procesada, 'https://cdn-codigoamigo.s3-eu-west-1.amazonaws.com/') !== false) {
                    $imagen_procesada = str_replace("https://cdn-codigoamigo.s3-eu-west-1.amazonaws.com/", "https://www.codigoamigo.com/img/", $imagen_procesada);
                }
                if (strpos($imagen_procesada, 'https://d3hcf0nbuqjt3g.cloudfront.net/') !== false) {
                    $imagen_procesada = str_replace("https://d3hcf0nbuqjt3g.cloudfront.net/", "https://www.codigoamigo.com/img/", $imagen_procesada);
                }
                // cdn.codigoamigo.com devuelve 401 (servicio caído) — servir desde
                // el propio dominio, donde los ficheros existen en /img/
                if (strpos($imagen_procesada, 'https://cdn.codigoamigo.com/') !== false) {
                    $imagen_procesada = str_replace("https://cdn.codigoamigo.com/", "https://www.codigoamigo.com/img/", $imagen_procesada);
                }

                if (strpos($imagen_procesada, 'http') !== 0) {
                    $imagen_procesada = 'https://www.codigoamigo.com' . (strpos($imagen_procesada, '/') === 0 ? '' : '/') . $imagen_procesada;
                }
            } else {
                $imagen_procesada = '';
            }
            
            $item_auxiliar["imagen"] = $imagen_procesada;
            $item_auxiliar["descripción"] = $item["descripción"] ?? '';
            $item_auxiliar["descripción_larga"] = $item["descripción_larga"] ?? '';

            $array_final_marcas[] = $item_auxiliar;
        }
    }

    // 4. Ordenar y limitar
    usort($array_final_marcas, function($a, $b) {
        return $b['numero_codigos'] - $a['numero_codigos'];
    });

    if ($limit && $limit > 0) {
        $array_final_marcas = array_slice($array_final_marcas, 0, $limit);
    }

    SimpleCache::set($cacheKey, $array_final_marcas);
    return $array_final_marcas;
}


// ============================================================================
// PROMOCIONES POR TIEMPO LIMITADO (referido boost de marcas tipo N26, Revolut…)
// ============================================================================

/**
 * Devuelve los datos de promoción activa de una marca, o null si no hay.
 * Una promoción está activa cuando:
 *   - promo_activa == true
 *   - promo_fecha_fin >= hoy (formato YYYY-MM-DD)
 */
function getPromocionMarca($marca_doc_o_clave) {
    if (is_string($marca_doc_o_clave)) {
        $marca = getObjectMarca('nombre_clave', $marca_doc_o_clave);
    } else {
        $marca = $marca_doc_o_clave;
    }
    if (!$marca || empty($marca['promo_activa'])) {
        return null;
    }
    $fin = $marca['promo_fecha_fin'] ?? '';
    $hoy = date('Y-m-d');
    if (!$fin || $fin < $hoy) {
        return null;
    }
    $dias_restantes = max(0, (int)floor((strtotime($fin) - strtotime($hoy)) / 86400));
    return [
        'titulo'         => $marca['promo_titulo']   ?? '',
        'bono'           => $marca['promo_bono']     ?? '',
        'fecha_fin'      => $fin,
        'url'            => $marca['promo_url']      ?? '',
        'dias_restantes' => $dias_restantes,
        'nombre'         => $marca['nombre']         ?? '',
        'nombre_clave'   => $marca['nombre_clave']   ?? '',
        'logo'           => $marca['logo']           ?? '',
    ];
}

/**
 * Devuelve array de marcas con promoción vigente, ordenadas por fecha_fin asc
 * (urgencia primero).
 */
function getMarcasConPromocionActiva() {
    static $cache = null;
    if ($cache !== null) return $cache;

    $hoy = date('Y-m-d');
    $col = getCollectionMarcas();
    $cursor = $col->find(
        [
            'promo_activa' => true,
            'promo_fecha_fin' => ['$gte' => $hoy],
        ],
        ['sort' => ['promo_fecha_fin' => 1]]
    );
    $out = [];
    foreach ($cursor as $m) {
        $promo = getPromocionMarca($m);
        if ($promo) $out[] = $promo;
    }
    $cache = $out;
    return $out;
}

?>