<?php 

/* Actualizar categoria */
foreach ($lista_codigos_total as $in=>$c) {

    $clave_categoria = $c["clave_categoria"];
    if($clave_categoria != "-") {

        if($num_ < 20) {

            echo "INDEX: ".$in.": ".$c["_id"];
            echo "<br>";
            $user_id = $c["id_usuario"];
            echo "user_id: ".$user_id;
            echo "<br><br>";
            $clave_categoria = $c["clave_categoria"];

            $num_ = $num_ + 1;

            try {
                $updateResult = $collection_codigos->updateOne(
                    ['_id' => new \MongoDB\BSON\ObjectId($c["_id"]) ],
                    ['$set' => ['clave_categoria' => "-"]]
                    );
            } catch(MongoDB\Driver\Exception\WriteException $e) {
                $writeResult = $e->getWriteResult();
                echo "Errores en MongoDB\n";
            }

        }

    }

}

?>