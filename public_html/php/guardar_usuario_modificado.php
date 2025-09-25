<?php 

    try {
        $collection_usuarios = getCollectionUsuarios();
        $updateResult = $collection_usuarios->updateOne(
            ['mail' => $_POST["mail"] ],
            ['$set' => ['username' => $_POST['username'], 'pass' => $_POST['pass'], 'confirm_password' => $_POST['pass']]]
            );
    } catch(MongoCursorException $e) {
        echo "Error al modificar datos\n";
    }
?>