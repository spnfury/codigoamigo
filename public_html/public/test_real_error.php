<?php
// Test para generar errores reales
echo "<h1>Generando errores reales</h1>";

// Error 1: Variable indefinida
$result = $undefined_variable;

// Error 2: Función inexistente  
$data = non_existent_function();

// Error 3: Acceso a array inexistente
$array = [];
$value = $array['non_existent_key'];

// Error 4: División por cero
$number = 10 / 0;

echo "<p>Errores generados</p>";
?>
