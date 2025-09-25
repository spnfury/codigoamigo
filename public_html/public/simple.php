<?php
echo "<h1>CodigoAmigo.com - Test</h1>";
echo "<p>PHP funciona correctamente</p>";
echo "<p>Fecha: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>MongoDB: " . (extension_loaded('mongodb') ? 'Disponible' : 'No disponible') . "</p>";
?>
