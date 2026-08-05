<?php
/**
 * Script para re-procesar códigos patrocinados expirados de un usuario.
 * Resetea aviso_expirado_enviado y actualiza fecha_fin_destacado a -1 minuto
 * para que el cron los detecte y envíe notificaciones.
 * 
 * Uso: php expire_legacy_user.php [--dry-run]
 */

set_time_limit(120);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/includes.php';

$dry_run = in_array('--dry-run', $argv ?? []);
$email_usuario = 'thevega82@gmail.com';

echo "=== Re-procesar códigos patrocinados expirados ===\n";
echo "Usuario: $email_usuario\n";
echo $dry_run ? "MODO: DRY RUN\n" : "MODO: EJECUCIÓN REAL\n";
echo "---\n";

$col_usuarios = getCollectionUsuarios();
$col_codigos = getCollectionCodigos();

// 1. Buscar usuario
$usuario = $col_usuarios->findOne(['mail' => $email_usuario]);
if (!$usuario) {
    echo "ERROR: Usuario no encontrado\n";
    exit(1);
}
echo "Usuario: " . $usuario['username'] . " (ID: " . (string)$usuario['_id'] . ")\n";
echo "Saldo: " . ($usuario['saldo'] ?? 0) . "€\n\n";

// 2. Buscar TODOS los códigos del usuario
$todos_codigos = $col_codigos->find([
    'id_usuario' => $usuario['_id'],
    'estado' => 0
])->toArray();

echo "Total códigos activos: " . count($todos_codigos) . "\n\n";

// 3. Filtrar los patrocinados expirados
$expirados = [];
$activos = [];
$legacy = [];
$now_ts = time();

foreach ($todos_codigos as $c) {
    $dest = isset($c['destacado']) ? (int)$c['destacado'] : 0;
    $dest_social = isset($c['destacado_social']) ? (int)$c['destacado_social'] : 0;
    
    if ($dest <= 0 && $dest_social <= 0) continue; // No es patrocinado
    
    $tiene_fecha_fin = isset($c['fecha_fin_destacado']) 
        && $c['fecha_fin_destacado'] instanceof MongoDB\BSON\UTCDateTime;
    
    if (!$tiene_fecha_fin) {
        $legacy[] = $c;
    } elseif ($c['fecha_fin_destacado']->toDateTime()->getTimestamp() < $now_ts) {
        $expirados[] = $c;
    } else {
        $activos[] = $c;
    }
}

// Mostrar resumen
echo "Patrocinados ACTIVOS: " . count($activos) . "\n";
foreach ($activos as $c) {
    $fin = $c['fecha_fin_destacado']->toDateTime()->format('Y-m-d H:i');
    echo "  ✅ " . $c['marca'] . " | expira: $fin\n";
}

echo "\nPatrocinados EXPIRADOS (a re-procesar): " . count($expirados) . "\n";
foreach ($expirados as $c) {
    $fin = $c['fecha_fin_destacado']->toDateTime()->format('Y-m-d H:i');
    $notificado = isset($c['aviso_expirado_enviado']) && $c['aviso_expirado_enviado'] ? 'SÍ' : 'NO';
    echo "  ❌ " . $c['marca'] . " | expiró: $fin | ya notificado: $notificado\n";
}

if (!empty($legacy)) {
    echo "\nPatrocinados LEGACY (sin fecha_fin): " . count($legacy) . "\n";
    foreach ($legacy as $c) {
        echo "  ⚠️  " . $c['marca'] . "\n";
    }
}

// 4. Re-procesar expirados: resetear para que el cron los detecte
$codigos_a_procesar = array_merge($expirados, $legacy);

if (empty($codigos_a_procesar)) {
    echo "\n✅ No hay códigos que re-procesar.\n";
    exit(0);
}

echo "\n--- Preparando " . count($codigos_a_procesar) . " códigos para el cron ---\n";

// Fecha expirada = 30 minutos atrás (dentro de la ventana de 24h del cron)
$fecha_reciente = new MongoDB\BSON\UTCDateTime(($now_ts - 1800) * 1000);

foreach ($codigos_a_procesar as $c) {
    $id = (string)$c['_id'];
    echo "  → " . $c['marca'] . " ($id)\n";
    
    if (!$dry_run) {
        $update = [
            'aviso_expirado_enviado' => false  // Resetear para que el cron lo detecte
        ];
        
        // Si es legacy (sin fecha_fin), asignar una fecha ya expirada
        if (!isset($c['fecha_fin_destacado']) || !($c['fecha_fin_destacado'] instanceof MongoDB\BSON\UTCDateTime)) {
            $update['fecha_fin_destacado'] = $fecha_reciente;
            echo "    + Asignada fecha_fin_destacado (expirada)\n";
        } else {
            // Ya tiene fecha_fin pero expirada hace mucho — moverla a hace 30 min para la ventana del cron
            $update['fecha_fin_destacado'] = $fecha_reciente;
            echo "    + Actualizada fecha_fin_destacado a ventana del cron\n";
        }
        
        $col_codigos->updateOne(
            ['_id' => $c['_id']],
            ['$set' => $update]
        );
        echo "    ✅ Actualizado\n";
    } else {
        echo "    [DRY RUN]\n";
    }
}

echo "\n=== Completado ===\n";
if (!$dry_run) {
    echo "Ahora ejecuta el cron para enviar las notificaciones:\n";
    echo "  php /home/admin/web/codigoamigo.com/public_html/cron/cron_destacados_expiracion.php\n";
} else {
    echo "Ejecuta sin --dry-run para aplicar los cambios.\n";
}
