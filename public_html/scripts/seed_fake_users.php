<?php
/**
 * Script para generar usuarios fake con fotos y nombres realistas
 * Ejecutar UNA sola vez: php scripts/seed_fake_users.php
 */

// Incluir dependencias
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';

// Nombres españoles comunes (60 nombres)
$nombres = [
    'María', 'Carmen', 'Ana', 'Laura', 'Lucía', 'Elena', 'Paula', 'Sara', 'Marta', 'Isabel',
    'Cristina', 'Patricia', 'Silvia', 'Andrea', 'Sofía', 'Clara', 'Raquel', 'Eva', 'Beatriz', 'Rosa',
    'Carlos', 'Manuel', 'José', 'David', 'Pablo', 'Javier', 'Daniel', 'Alejandro', 'Miguel', 'Antonio',
    'Francisco', 'Fernando', 'Roberto', 'Alberto', 'Jorge', 'Sergio', 'Luis', 'Rafael', 'Pedro', 'Diego',
    'Alicia', 'Nuria', 'Mónica', 'Alba', 'Irene', 'Natalia', 'Sandra', 'Rocío', 'Adriana', 'Lorena',
    'Marcos', 'Iván', 'Rubén', 'Óscar', 'Víctor', 'Hugo', 'Adrián', 'Álvaro', 'Mario', 'Gonzalo'
];

// Apellidos españoles comunes (50 apellidos)
$apellidos = [
    'García', 'Rodríguez', 'Martínez', 'López', 'González', 'Hernández', 'Pérez', 'Sánchez', 'Ramírez', 'Torres',
    'Flores', 'Rivera', 'Gómez', 'Díaz', 'Reyes', 'Morales', 'Jiménez', 'Ruiz', 'Álvarez', 'Romero',
    'Muñoz', 'Fernández', 'Navarro', 'Domínguez', 'Gil', 'Vázquez', 'Serrano', 'Blanco', 'Molina', 'Moreno',
    'Suárez', 'Ortega', 'Castro', 'Delgado', 'Medina', 'Ramos', 'Santos', 'Iglesias', 'Marín', 'Núñez',
    'Cano', 'Herrera', 'Vargas', 'Pascual', 'Aguilar', 'Guerrero', 'Campos', 'Prieto', 'Cabrera', 'Fuentes'
];

// Función para generar username único
function generarUsername($nombre, $apellido, $index) {
    $opciones = [
        strtolower(str_replace(' ', '', $nombre)) . rand(10, 99),
        strtolower(str_replace(' ', '_', $nombre . ' ' . $apellido)),
        strtolower(str_replace(' ', '', $nombre)) . '_' . strtolower(substr($apellido, 0, 3)),
        strtolower(str_replace(' ', '', $apellido)) . rand(1, 999),
        strtolower(str_replace(' ', '', $nombre)) . strtolower(substr($apellido, 0, 1)) . rand(1, 99)
    ];
    return $opciones[array_rand($opciones)];
}

// Función para normalizar caracteres (quitar acentos para URLs)
function normalizarParaUrl($texto) {
    $originales = ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ', 'Ü'];
    $reemplazos = ['a', 'e', 'i', 'o', 'u', 'n', 'u', 'a', 'e', 'i', 'o', 'u', 'n', 'u'];
    return str_replace($originales, $reemplazos, $texto);
}

// Función para generar URL de avatar
function generarAvatarUrl($username, $index) {
    $username_normalizado = normalizarParaUrl($username);
    $estilos = ['avataaars', 'bottts', 'micah', 'adventurer', 'lorelei', 'notionists'];
    $estilo = $estilos[$index % count($estilos)];
    
    // Alternar entre diferentes servicios de avatares
    if ($index % 3 === 0) {
        return "https://api.dicebear.com/7.x/{$estilo}/svg?seed=" . urlencode($username_normalizado);
    } elseif ($index % 3 === 1) {
        return "https://i.pravatar.cc/150?u=" . urlencode($username_normalizado);
    } else {
        return "https://ui-avatars.com/api/?name=" . urlencode($username_normalizado) . "&background=random&color=fff&size=150";
    }
}

// Conectar a MongoDB
$collection_usuarios = getCollectionUsuarios();

if (!$collection_usuarios) {
    die("Error: No se pudo conectar a la colección de usuarios\n");
}

// Verificar si ya existen usuarios fake
$existentes = $collection_usuarios->countDocuments(['tipo' => 'fake']);
if ($existentes > 0) {
    echo "⚠️ Ya existen {$existentes} usuarios fake en la base de datos.\n";
    echo "¿Desea continuar y añadir más? (s/N): ";
    $respuesta = trim(fgets(STDIN));
    if (strtolower($respuesta) !== 's') {
        echo "Operación cancelada.\n";
        exit;
    }
}

echo "🚀 Iniciando generación de usuarios fake...\n\n";

$usuarios_creados = 0;
$errores = 0;
$total_usuarios = 50;

for ($i = 0; $i < $total_usuarios; $i++) {
    try {
        // Seleccionar nombre y apellido aleatorios
        $nombre = $nombres[array_rand($nombres)];
        $apellido = $apellidos[array_rand($apellidos)];
        $username = generarUsername($nombre, $apellido, $i);
        
        // Verificar que el username no exista
        $existe = $collection_usuarios->findOne(['username' => $username]);
        if ($existe) {
            $username = $username . '_' . rand(100, 999);
        }
        
        // Generar datos del usuario
        $usuario_fake = [
            'username' => $nombre . ' ' . $apellido,
            'mail' => 'fake_' . str_pad($i + 1, 3, '0', STR_PAD_LEFT) . '@codigoamigo.local',
            'pass' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
            'confirm_password' => '',
            'img' => generarAvatarUrl($username, $i),
            'estado' => 1,
            'tipo' => 'fake',
            'es_bot' => true,
            'type' => 'fake',
            'fecha_registro' => date("d-m-Y H:i"),
            'saldo' => 0,
            'descripcion' => '',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ];
        
        // Insertar en la base de datos
        $resultado = $collection_usuarios->insertOne($usuario_fake);
        
        if ($resultado->getInsertedId()) {
            $usuarios_creados++;
            echo "✅ [{$usuarios_creados}/{$total_usuarios}] Creado: {$usuario_fake['username']}\n";
        } else {
            $errores++;
            echo "❌ Error al crear usuario: {$nombre} {$apellido}\n";
        }
        
    } catch (Exception $e) {
        $errores++;
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 RESUMEN DE GENERACIÓN\n";
echo str_repeat("=", 50) . "\n";
echo "✅ Usuarios creados: {$usuarios_creados}\n";
echo "❌ Errores: {$errores}\n";
echo "📁 Total usuarios fake en BD: " . $collection_usuarios->countDocuments(['tipo' => 'fake']) . "\n";
echo str_repeat("=", 50) . "\n\n";

echo "🎉 ¡Generación completada!\n";
echo "Los usuarios fake se han marcado con tipo='fake' y es_bot=true\n";
