<?php
// Test para verificar que los errores se loguean correctamente
echo "<h1>Test de Logging de Errores</h1>";

// Generar errores de prueba más específicos
echo "<p>Generando errores de prueba...</p>";

// Error 1: Variable indefinida
$undefined_variable = $non_existent_variable;

// Error 2: Función inexistente
$result = non_existent_function();

// Error 3: Acceso a array inexistente
$array = [];
$value = $array['non_existent_key'];

echo "<p>Errores generados. Revisa el panel de logs.</p>";
echo "<p>Timestamp: " . date('Y-m-d H:i:s') . "</p>";
?>
