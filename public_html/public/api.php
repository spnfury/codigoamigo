<?php 

header('Access-Control-Allow-Origin: *');

if($_REQUEST["method"] == 'destacados'){
    
    $lista_destacados = dameDestacados();
    
    $lista_destacados = json_encode($lista_destacados);
    
    echo $lista_destacados;
    
}elseif($_REQUEST["method"] == 'dameDetalle'){
    
    $lista_codigos = dameDetalle($_REQUEST['id']);
    
    $lista_codigos = json_encode($lista_codigos);
    
    echo $lista_codigos;
    
}elseif($_REQUEST["method"] == 'login'){
    
    $mail_ = $_REQUEST["mail"];
    $pass_ = $_REQUEST["pass"];
    
    $collection_usuarios = getCollectionUsuarios();
    $usuario = $collection_usuarios->findOne(
        [
            'mail' => $mail_,
            'pass' => $pass_,
        ]);
    
    if($usuario){
        echo $usuario["_id"];
//         print_r($usuario);
//         print_r($usuario->id);
    }else{
        echo "error usuario no encontrado";
    }
    
}elseif($_REQUEST["method"] == 'test_recarga'){
    
    header('Content-Type: application/json');
    
    $paquete = $_REQUEST['paquete'] ?? '';
    $precio = $_REQUEST['precio'] ?? '';
    $saldo = $_REQUEST['saldo'] ?? '';
    $usuario_id = $_REQUEST['usuario_id'] ?? '';
    
    echo json_encode([
        'test' => 'success',
        'data' => [
            'paquete' => $paquete,
            'precio' => $precio,
            'saldo' => $saldo,
            'usuario_id' => $usuario_id
        ],
        'message' => 'API funciona correctamente'
    ]);
    
}

function dameDetalle($id){
    

    $obj_id_codigo = new \MongoDB\BSON\ObjectId($id);
    $lista_codigos = getCodeByID_prelista($obj_id_codigo);

    return $lista_codigos;
}

function dameDestacados(){
    
    $lista_codigos_patrocinados = get_all_listado_codigos_destacados('', '', 10, $skip);
    
    foreach($lista_codigos_patrocinados as $field => $item){
    
        $marca = getObjectMarca('nombre_clave', $item["marca"]);
        $marca["nombre"] = ucfirst(strtolower($marca["nombre"]));
        
        $usuario = getObjectUser('_id', new \MongoDB\BSON\ObjectId($item["id_usuario"]));
        
        
        $item->marca = $marca;
        
        if(substr($item->marca["imagen"], 0, 1)  == '/' ){
            $item->marca["imagen"] = "https://www.codigoamigo.com".$item->marca["imagen"];
        }
        
        $item->usuario->username = $usuario->username;
        
        $item->fecha_publicacion = transformafechaV2($item["fecha_publicacion"]);
       
// echo "<pre>";
//         print_r($item);die;
        
        
    }
    
    return $lista_codigos_patrocinados;
}


?>