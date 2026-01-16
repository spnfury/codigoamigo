<?php
// Script para listar y gestionar fuentes de chollos
require_once __DIR__ . '/myphp/funciones.php';
require_once __DIR__ . '/myphp/funciones_chollos_fuentes.php';

echo "--- Fuentes Actuales ---\n";
$fuentes = obtenerFuentesActivas('telegram');
$existe_chollogamers = false;

if (empty($fuentes)) {
    echo "No hay fuentes de Telegram activas.\n";
} else {
    foreach ($fuentes as $f) {
        echo "- [ID: {$f['id']}] {$f['nombre']} ({$f['url']})\n";
        if (strtolower($f['url']) === 'chollogamers' || strpos(strtolower($f['url']), 'chollogamers') !== false) {
            $existe_chollogamers = true;
        }
    }
}

echo "\n--- Añadiendo CholloGamers ---\n";
if (!$existe_chollogamers) {
    echo "Añadiendo CholloGamers...\n";
    $nueva_fuente = [
        'nombre' => 'CholloGamers',
        'tipo' => 'telegram',
        'url' => 'chollogamers', // Username del canal
        'activo' => true,
        'configuracion' => [
            'categoria_defecto' => 'videojuegos',
            'estado_defecto' => 0, // Pendiente de revisión
            'reescribir_automatico' => true
        ]
    ];
    
    $resultado = crearFuente($nueva_fuente);
    if ($resultado['success']) {
        echo "¡Fuente añadida correctamente! ID: " . $resultado['id'] . "\n";
    } else {
        echo "Error al añadir fuente: " . $resultado['error'] . "\n";
    }
} else {
    echo "CholloGamers ya existe en las fuentes.\n";
}
