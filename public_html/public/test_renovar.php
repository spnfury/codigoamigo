<?php
$codigo_id_str = $_REQUEST["codigo"] ?? '';
echo "codigo_id_str = '$codigo_id_str'\n";
if (empty($codigo_id_str)) {
    echo "Redirigiendo a home porque esta vacio\n";
} else {
    echo "Todo correcto, codigo = $codigo_id_str\n";
}
