<?php 

    try {
        $collection_usuarios = getCollectionUsuarios();
        $updateResult = $collection_usuarios->updateOne(
            ['mail' => $_POST["mail"] ],
            ['$set' => ['img' => ""]]
            );
    } catch(MongoCursorException $e) {
        echo "Error al modificar datos\n";
    }
    
?>