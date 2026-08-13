<?php
/**
 * Script para crear todos los índices necesarios en MongoDB
 * 
 * Ejecutar: php scripts/create_indexes.php
 * 
 * Los índices se crean en background y son idempotentes (si ya existen, no pasa nada).
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Europe/Madrid');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/conexion.php';
require_once __DIR__ . '/../myphp/funciones_codigo.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';

$db = createConnection();

echo "=== Creando índices MongoDB para CodigoAmigo ===" . PHP_EOL;
echo "Fecha: " . date('Y-m-d H:i:s') . PHP_EOL . PHP_EOL;

// ============================================================
// COLECCIÓN: usuarios
// Actualmente SOLO tiene _id. Muchas queries buscan por mail.
// ============================================================
echo "--- USUARIOS ---" . PHP_EOL;
$col_usuarios = $db->selectCollection('usuarios');

$indexes_usuarios = [
    // Login y búsqueda por email (MUY frecuente)
    ['key' => ['mail' => 1], 'options' => ['name' => 'mail_1', 'unique' => true, 'sparse' => true]],
    // Filtros admin y listados
    ['key' => ['estado' => 1], 'options' => ['name' => 'estado_1']],
    // Búsqueda por username
    ['key' => ['username' => 1], 'options' => ['name' => 'username_1']],
    // Filtro admin: usuarios con saldo
    ['key' => ['saldo' => 1], 'options' => ['name' => 'saldo_1', 'sparse' => true]],
    // Autologin token lookup
    ['key' => ['autologin_token' => 1], 'options' => ['name' => 'autologin_token_1', 'sparse' => true]],
    // Listados admin ordenados por fecha
    ['key' => ['estado' => 1, '_id' => -1], 'options' => ['name' => 'estado_1__id_-1']],
];

foreach ($indexes_usuarios as $idx) {
    try {
        $col_usuarios->createIndex($idx['key'], $idx['options']);
        echo "  ✅ " . $idx['options']['name'] . PHP_EOL;
    } catch (Exception $e) {
        echo "  ❌ " . $idx['options']['name'] . ": " . $e->getMessage() . PHP_EOL;
    }
}

// ============================================================
// COLECCIÓN: codigos
// Ya tiene bastantes índices, pero faltan algunos clave
// ============================================================
echo PHP_EOL . "--- CODIGOS ---" . PHP_EOL;
$col_codigos = getCollectionCodigos();

$indexes_codigos = [
    // Búsqueda por código textual (checkCodeExists)
    ['key' => ['codigo' => 1], 'options' => ['name' => 'codigo_1']],
    // Compuesto: buscar código de un usuario específico
    ['key' => ['codigo' => 1, 'id_usuario' => 1], 'options' => ['name' => 'codigo_1_id_usuario_1']],
    // Compuesto: marca + estado + destacado + _id (query principal de listado de marca)
    ['key' => ['marca' => 1, 'estado' => 1, 'destacado' => -1, '_id' => -1], 'options' => ['name' => 'marca_1_estado_1_destacado_-1__id_-1']],
    // Compuesto: marca + estado + destacado + destacado_social + _id (orden de get_code_position)
    ['key' => ['marca' => 1, 'estado' => 1, 'destacado' => -1, 'destacado_social' => -1, '_id' => -1], 'options' => ['name' => 'marca_1_estado_1_dest_-1_destsoc_-1__id_-1']],
    // Compuesto: estado + marca + id_usuario (buscar códigos por usuario en una marca)
    ['key' => ['estado' => 1, 'marca' => 1, 'id_usuario' => 1], 'options' => ['name' => 'estado_1_marca_1_id_usuario_1']],
    // Para el cron de códigos antiguos: estado + aviso_antiguedad_enviado
    ['key' => ['estado' => 1, 'aviso_antiguedad_enviado' => 1], 'options' => ['name' => 'estado_1_aviso_antiguedad_1']],
    // Compuesto: id_usuario + estado (para "mis anuncios" y perfil)
    ['key' => ['id_usuario' => 1, 'estado' => 1, '_id' => -1], 'options' => ['name' => 'id_usuario_1_estado_1__id_-1']],
    // marca_id + estado (usado en marca.php nueva)
    ['key' => ['marca_id' => 1, 'estado' => 1], 'options' => ['name' => 'marca_id_1_estado_1', 'sparse' => true]],
];

foreach ($indexes_codigos as $idx) {
    try {
        $col_codigos->createIndex($idx['key'], $idx['options']);
        echo "  ✅ " . $idx['options']['name'] . PHP_EOL;
    } catch (Exception $e) {
        echo "  ❌ " . $idx['options']['name'] . ": " . $e->getMessage() . PHP_EOL;
    }
}

// ============================================================
// COLECCIÓN: transacciones
// Actualmente SOLO tiene _id. Muchas queries en admin.
// ============================================================
echo PHP_EOL . "--- TRANSACCIONES ---" . PHP_EOL;
$col_transacciones = $db->selectCollection('transacciones');

$indexes_transacciones = [
    // Verificar duplicados de Stripe (MUY frecuente)
    ['key' => ['stripe_session_id' => 1], 'options' => ['name' => 'stripe_session_id_1', 'unique' => true, 'sparse' => true]],
    // Buscar por usuario
    ['key' => ['usuario_id' => 1], 'options' => ['name' => 'usuario_id_1']],
    // Filtros admin por estado
    ['key' => ['estado' => 1], 'options' => ['name' => 'estado_1']],
    // Listado ordenado por fecha
    ['key' => ['fecha_creacion' => -1], 'options' => ['name' => 'fecha_creacion_-1']],
    // Compuesto: usuario + tipo + fecha (historial de recargas)
    ['key' => ['usuario_id' => 1, 'tipo' => 1, 'fecha_creacion' => -1], 'options' => ['name' => 'usuario_1_tipo_1_fecha_-1']],
    // Admin: estado + fecha
    ['key' => ['estado' => 1, 'fecha_creacion' => -1], 'options' => ['name' => 'estado_1_fecha_-1']],
    // Buscar por codigo_id
    ['key' => ['codigo_id' => 1], 'options' => ['name' => 'codigo_id_1', 'sparse' => true]],
];

foreach ($indexes_transacciones as $idx) {
    try {
        $col_transacciones->createIndex($idx['key'], $idx['options']);
        echo "  ✅ " . $idx['options']['name'] . PHP_EOL;
    } catch (Exception $e) {
        echo "  ❌ " . $idx['options']['name'] . ": " . $e->getMessage() . PHP_EOL;
    }
}

// ============================================================
// COLECCIÓN: vistas
// Necesita índice en id_codigo
// ============================================================
echo PHP_EOL . "--- VISTAS ---" . PHP_EOL;
$col_vistas = $db->selectCollection('vistas');

$indexes_vistas = [
    ['key' => ['id_codigo' => 1], 'options' => ['name' => 'id_codigo_1']],
    ['key' => ['id_usuario' => 1], 'options' => ['name' => 'id_usuario_1']],
];

foreach ($indexes_vistas as $idx) {
    try {
        $col_vistas->createIndex($idx['key'], $idx['options']);
        echo "  ✅ " . $idx['options']['name'] . PHP_EOL;
    } catch (Exception $e) {
        echo "  ❌ " . $idx['options']['name'] . ": " . $e->getMessage() . PHP_EOL;
    }
}

// ============================================================
// COLECCIÓN: mensajes
// ============================================================
echo PHP_EOL . "--- MENSAJES ---" . PHP_EOL;
$col_mensajes = $db->selectCollection('mensajes');

$indexes_mensajes = [
    ['key' => ['id_usuario_destino' => 1, '_id' => -1], 'options' => ['name' => 'id_usuario_destino_1__id_-1']],
    ['key' => ['id_usuario_origen' => 1, '_id' => -1], 'options' => ['name' => 'id_usuario_origen_1__id_-1']],
];

foreach ($indexes_mensajes as $idx) {
    try {
        $col_mensajes->createIndex($idx['key'], $idx['options']);
        echo "  ✅ " . $idx['options']['name'] . PHP_EOL;
    } catch (Exception $e) {
        echo "  ❌ " . $idx['options']['name'] . ": " . $e->getMessage() . PHP_EOL;
    }
}

// ============================================================
// COLECCIÓN: logs
// ============================================================
echo PHP_EOL . "--- LOGS ---" . PHP_EOL;
$col_logs = $db->selectCollection('logs');

$indexes_logs = [
    ['key' => ['fecha' => -1], 'options' => ['name' => 'fecha_-1']],
    ['key' => ['tipo' => 1, 'fecha' => -1], 'options' => ['name' => 'tipo_1_fecha_-1']],
];

foreach ($indexes_logs as $idx) {
    try {
        $col_logs->createIndex($idx['key'], $idx['options']);
        echo "  ✅ " . $idx['options']['name'] . PHP_EOL;
    } catch (Exception $e) {
        echo "  ❌ " . $idx['options']['name'] . ": " . $e->getMessage() . PHP_EOL;
    }
}

// ============================================================
// RESUMEN: Listar todos los índices finales
// ============================================================
echo PHP_EOL . "=== RESUMEN DE ÍNDICES FINALES ===" . PHP_EOL;

$collections = ['usuarios', 'codigos', 'marcas', 'transacciones', 'votos', 'vistas', 'mensajes', 'logs'];
foreach ($collections as $col_name) {
    $col = $db->selectCollection($col_name);
    $indexes = $col->listIndexes();
    $count = 0;
    foreach ($indexes as $idx) { $count++; }
    echo "  $col_name: $count índices" . PHP_EOL;
}

echo PHP_EOL . "✅ Proceso completado." . PHP_EOL;
