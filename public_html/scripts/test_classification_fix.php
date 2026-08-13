<?php
require_once __DIR__ . '/../cron/cron_fake_comments.php';

$titulos_prueba = [
    "Subsonic Dragon Ball Z Silla para Videojuegos",
    "Silla Gaming Drift DR100",
    "Teclado Mecánico Razer Blackwidow",
    "Ratón Logitech G502 Hero",
    "Juego Dragon Ball Sparking Zero PS5"
];

echo "--- TEST DE CLASIFICACIÓN SEMÁNTICA ---\n";

foreach ($titulos_prueba as $titulo) {
    echo "\nTitulo: " . $titulo . "\n";
    $tipo = detectarTipo($titulo);
    echo "Categoría detectada: [" . $tipo . "] ";
    
    if ($tipo === 'silla' && stripos($titulo, 'silla') !== false) {
        echo "✅ CORRECTO";
    } elseif ($tipo === 'periferico' && (stripos($titulo, 'teclado') !== false || stripos($titulo, 'ratón') !== false)) {
        echo "✅ CORRECTO";
    } elseif ($tipo === 'juego' && stripos($titulo, 'juego') !== false && stripos($titulo, 'silla') === false) {
         echo "✅ CORRECTO";
    } else {
        echo "❌ INCORRECTO (Posible falso positivo)";
    }
    echo "\n";
    
    // Ver un ejemplo de comentario
    if (isset($COMENTARIOS_CATEGORIA[$tipo])) {
        $ejemplo = $COMENTARIOS_CATEGORIA[$tipo][0];
        echo "Ejemplo comentario: \"$ejemplo\"\n";
    }
}
