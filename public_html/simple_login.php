<?php
// Archivo simple para login que no pasa por Slim
header('Content-Type: text/plain');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "POST recibido correctamente\n";
    echo "Datos: " . print_r($_POST, true);
} else {
    echo "Solo se aceptan peticiones POST";
}
?>
