<?php
/**
 * Script para agregar la fuente de datos CholloGamers de Telegram
 * Ejecutar desde línea de comandos: php agregar_fuente_chollogamers.php
 */

// Incluir archivos necesarios
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_chollos_fuentes.php';

// Datos de la fuente
$datos_fuente = [
    'nombre' => 'CholloGamers 🎮',
    'tipo' => 'telegram',
    'url' => 'CholloGamers', // Username del canal sin @
    'configuracion' => [
        'descripcion' => 'Los mejores chollos, ofertas y descuentos en artículos para gamers',
        'categoria_sugerida' => 'gaming' // Categoría sugerida si existe
    ],
    'activo' => true
];

// Verificar si la fuente ya existe
$fuentes_existentes = obtenerFuentes(['tipo' => 'telegram']);
$existe = false;
foreach ($fuentes_existentes as $fuente) {
    if (strtolower($fuente['url']) === strtolower($datos_fuente['url'])) {
        $existe = true;
        echo "⚠️  La fuente ya existe:\n";
        echo "   ID: {$fuente['id']}\n";
        echo "   Nombre: {$fuente['nombre']}\n";
        echo "   URL: {$fuente['url']}\n";
        echo "   Activa: " . ($fuente['activo'] ? 'Sí' : 'No') . "\n";
        break;
    }
}

if (!$existe) {
    // Crear la fuente
    $resultado = crearFuente($datos_fuente);
    
    if ($resultado['success']) {
        echo "✅ Fuente creada exitosamente!\n";
        echo "   ID: {$resultado['id']}\n";
        echo "   Nombre: {$datos_fuente['nombre']}\n";
        echo "   URL: {$datos_fuente['url']}\n";
        echo "   Tipo: {$datos_fuente['tipo']}\n";
        echo "\n";
        echo "📝 La fuente se sincronizará automáticamente con Jenkins cada 30 minutos.\n";
        echo "   Puedes verificar el estado en el panel de administración de chollos.\n";
    } else {
        echo "❌ Error al crear la fuente: {$resultado['error']}\n";
        exit(1);
    }
} else {
    echo "\n💡 Si quieres reactivarla, puedes hacerlo desde el panel de administración.\n";
}

echo "\n";

