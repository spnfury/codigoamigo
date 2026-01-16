<?php
/**
 * Script para agregar la fuente de datos CHOLLOS100X100 de Telegram
 * Ejecutar desde línea de comandos: php agregar_fuente_chollos100x100.php
 */

// Incluir archivos necesarios
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_chollos_fuentes.php';

// Datos de la fuente
$datos_fuente = [
    'nombre' => 'CHOLLOS100X100 🔥',
    'tipo' => 'telegram',
    'url' => 'chollos100x100', // Username del canal sin @
    'configuracion' => [
        'descripcion' => 'Los mejores chollos y descuentos - Programa Afiliados Amazon EU',
        'categoria_defecto' => 'general',
        'estado_defecto' => 1,
        'reescribir_automatico' => true
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

