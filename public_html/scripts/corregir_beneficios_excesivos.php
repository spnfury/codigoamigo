<?php
/**
 * Script de limpieza: Corrige códigos con beneficio superior al oficial de la marca.
 * 
 * Uso:
 *   php corregir_beneficios_excesivos.php          → modo dry-run (solo muestra)
 *   php corregir_beneficios_excesivos.php --apply   → aplica correcciones
 */

$doc_root = '/home/admin/web/codigoamigo.com/public_html';
require_once $doc_root . '/inc/includes.php';
require_once $doc_root . '/inc/conexion.php';

$apply = in_array('--apply', $argv ?? []);

echo $apply ? "⚡ MODO APLICAR - Se corregirán los códigos\n\n" : "👀 MODO DRY-RUN - Solo se muestran los afectados\n\n";

$db = createConnection();
$collection_marcas = $db->selectCollection('marcas');
$collection_codigos = $db->selectCollection('codigos');

// Obtener marcas con beneficio oficial definido
$marcas_con_bo = $collection_marcas->find([
    'beneficio_oficial.cantidad' => ['$exists' => true, '$gt' => 0]
]);

$total_afectados = 0;
$total_corregidos = 0;

foreach ($marcas_con_bo as $marca) {
    $nombre = $marca['nombre'] ?? $marca['nombre_clave'];
    $bo = $marca['beneficio_oficial'];
    $max = floatval($bo['cantidad']);
    $tipo = $bo['tipo'] ?? 'euros';
    $unidad = ($tipo === 'euros') ? '€' : '%';
    
    echo "━━━ {$nombre} (máx: {$max}{$unidad}) ━━━\n";
    
    // Buscar códigos de esta marca que excedan el beneficio oficial
    $codigos_excesivos = $collection_codigos->find([
        'marca' => $marca['nombre_clave'],
        'estado' => ['$gte' => -1], // Activos y pendientes
        'num_beneficio' => ['$gt' => $max]
    ]);
    
    $count = 0;
    foreach ($codigos_excesivos as $codigo) {
        $count++;
        $total_afectados++;
        $beneficio_actual = $codigo['num_beneficio'] ?? 0;
        $user_id = (string)($codigo['id_usuario'] ?? 'desconocido');
        $codigo_id = (string)$codigo['_id'];
        
        echo "  ❌ Código {$codigo_id}: {$beneficio_actual}{$unidad} (usuario: {$user_id})\n";
        
        if ($apply) {
            $collection_codigos->updateOne(
                ['_id' => $codigo['_id']],
                ['$set' => [
                    'num_beneficio' => $max,
                    'beneficio_corregido' => true,
                    'beneficio_original' => $beneficio_actual,
                    'fecha_correccion' => date('Y-m-d H:i:s')
                ]]
            );
            echo "    ✅ Corregido a {$max}{$unidad}\n";
            $total_corregidos++;
        }
    }
    
    if ($count === 0) {
        echo "  ✅ Sin códigos excesivos\n";
    }
    echo "\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Total afectados: {$total_afectados}\n";
if ($apply) {
    echo "Total corregidos: {$total_corregidos}\n";
} else {
    echo "Ejecuta con --apply para corregirlos.\n";
}
