<?php 

    $collection_usuarios = getCollectionUsuarios();    
    $lista_usuarios = $collection_usuarios->find(['estado' => 1], ['sort' => ['_id' => -1]]);
    $array_usuarios = iterator_to_array($lista_usuarios);
    
    foreach ($array_usuarios as $user) {
    
        $id_f = $user["id_facebook"];
        if($id_f != "") {
    
            $img = $user["img"];
            echo $img; echo "<br>";    
            if(!strpos($img, "img_usuarios_facebook")) {    
                $id_u = $user["_id"];
                $c = $user["mail"];
                echo $id_u;
                echo "<br>";
                echo $c;
                echo "<br>";
                $path_facebook = "img_usuarios_facebook/";
                $image = file_get_contents('https://graph.facebook.com/'.$id_f.'/picture?type=large');
                $ruta = $_SERVER["DOCUMENT_ROOT"]."/uploads/".$path_facebook.$id_f.'.jpg';
                $url_foto_facebook = "https://".$_SERVER['SERVER_NAME']."/uploads/".$path_facebook.$id_f.'.jpg';
                $resposta_subida = file_put_contents($ruta, $image);
                echo $url_foto_facebook;
                echo "<br>";
                $updateResult = $collection_usuarios->updateOne(
                    ['mail' => $c],
                    ['$set' => ['img' => $url_foto_facebook]]
                );
                echo "Usuario actualizado";
                echo "--------------------";
                echo "<br>";        
            }
    
        }
    
    }

?>