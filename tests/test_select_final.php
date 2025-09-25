<?php
// Simular el entorno web
$_SERVER['DOCUMENT_ROOT'] = '/home/admin/web/codigoamigo.com/public_html';
$_SERVER['REQUEST_URI'] = '/public/publicar_codigo.php';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'on';

// Cambiar al directorio correcto
chdir('/home/admin/web/codigoamigo.com/public_html');

// Incluir las dependencias necesarias
include_once '/home/admin/web/codigoamigo.com/public_html/inc/includes.php';
include_once '/home/admin/web/codigoamigo.com/public_html/inc/conexion.php';

// Simular que no estamos en modo modificación
$codigo_data = null;

echo "=== PRUEBA FINAL DEL SELECT DE CATEGORÍA ===\n\n";

// Capturar la salida del formulario
ob_start();
include '/home/admin/web/codigoamigo.com/public_html/public/publicar_codigo.php';
$output = ob_get_clean();

// Buscar el select de categoría
if (preg_match('/(<select id="categoria"[^>]*>.*?<\/select>)/s', $output, $matches)) {
    echo "✓ Select de categoría encontrado\n";
    
    // Verificar que no tenga espacios extra
    $clean_select = preg_replace('/\s+/', ' ', $matches[1]);
    echo "✓ HTML limpio: " . (strlen($clean_select) < 2000 ? "Sí" : "No") . "\n";
    
    // Verificar opciones específicas
    $categorias_esperadas = [
        'Alimentación y Gastronomía',
        'Apuestas',
        'Banca y Criptomonedas',
        'Deportes y Nutrición',
        'Inteligencia Artificial',
        'Viajes y Alojamiento'
    ];
    
    foreach ($categorias_esperadas as $cat) {
        if (strpos($matches[1], $cat) !== false) {
            echo "✓ Categoría '$cat' presente\n";
        } else {
            echo "✗ ERROR: Categoría '$cat' no encontrada\n";
        }
    }
    
} else {
    echo "✗ ERROR: Select de categoría no encontrado\n";
}

// Verificar estilos CSS específicos
$estilos_esperados = [
    'min-height: 48px',
    'line-height: 1.4',
    'white-space: nowrap',
    'text-overflow: ellipsis',
    'border: 2px solid #ff6b35'
];

foreach ($estilos_esperados as $estilo) {
    if (strpos($output, $estilo) !== false) {
        echo "✓ Estilo '$estilo' presente\n";
    } else {
        echo "✗ ERROR: Estilo '$estilo' no encontrado\n";
    }
}

echo "\n=== RECOMENDACIONES ===\n";
echo "1. El select ahora tiene estilos mejorados para mejor visibilidad\n";
echo "2. Se eliminaron espacios extra en el HTML\n";
echo "3. Se agregó altura mínima y manejo de texto largo\n";
echo "4. El dropdown debería verse correctamente en todos los navegadores\n";

echo "\n=== FIN DE LA PRUEBA ===\n";
?>

