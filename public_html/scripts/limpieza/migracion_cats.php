<?php 

ini_set("display_errors", "on");
error_reporting(1);


include_once("../../inc/includes.php");

// $db = createConnection();
// $collection = $db->selectCollection('codigos');

/* BUSCO LOS CODIGOS SIN EL NUEVO SISTEMA DE CATEGORIAS */
/*$count = $collection->count(
    ['_id' => new \MongoDB\BSON\ObjectId('60232fc1837c4e1eae3aaa92')],
    [
        'skip' => 0,
        'sort' => ['_id' => -1]
    ]);
*/

// $find = $collection->find(
//     ['_id' => new \MongoDB\BSON\ObjectId('60232fc1837c4e1eae3aaa92')],
//     [
//         'skip' => 0,
//         'sort' => ['_id' => -1]
//     ]);
// /*print_r($find);die;
// echo count($find)." códigos sin categoria por actualizar";*/

// $array_codigos = iterator_to_array($find);


/* CATEGORIAS NUEVAS*/
try {
    $collection_cats_pre = getCategorias_new();
    

    $collection_cats = iterator_to_array($collection_cats_pre);

//     echo "<pre>";
//     print_r($collection_cats);die;

}
catch (MongoCursorException $e) {
    echo "error message: ".$e->getMessage()."\n";
    echo "error code: ".$e->getCode()."\n";
}


$find_array = array();
foreach ( $collection_cats as $id => $cat )
{
    
    try {
        
        $db = createConnection();
        
        $collection = $db->selectCollection('codigos');
        $find_array = $collection->find(
        ['clave_categoria' => $cat->nombre_clave]);
        
        $array_codigos[$cat->nombre_clave] = iterator_to_array($find_array);
        
    
    }
    catch (MongoCursorException $e) {
        echo "error message: ".$e->getMessage()."\n";
        echo "error code: ".$e->getCode()."\n";
    }

    $assoc_cats[$cat->nombre_clave] = $cat->_id;
    
}

echo "<pre>";
print_r($assoc_cats);die;

foreach ( $array_codigos as $id => $codigo )
{

    
    
    //$marca = $collection_marcas->findOne(["nombre_clave" => $codigo["marca"]]);   
    
    
    $updateResult = $collection->updateOne(
        
        ['_id' => new \MongoDB\BSON\ObjectId($codigo["_id"]) ],
        ['$set' => [
            'category_1'=>new \MongoDB\BSON\ObjectId($codigo["_id"]),
            'category_2'=>'',
            'category_3'=>'']]
        );

    
    $collectionArray[] = $codigo;
}
