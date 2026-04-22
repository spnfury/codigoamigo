<?php
/**
 * Script para generar usuarios fake con fotos y nombres realistas
 * Ejecutar UNA sola vez: php scripts/seed_fake_users.php
 */

// Incluir dependencias
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';

// Nombres españoles comunes categorizados
$nombres_mujeres = [
    'María', 'Carmen', 'Ana', 'Laura', 'Lucía', 'Elena', 'Paula', 'Sara', 'Marta', 'Isabel',
    'Cristina', 'Patricia', 'Silvia', 'Andrea', 'Sofía', 'Clara', 'Raquel', 'Eva', 'Beatriz', 'Rosa',
    'Alicia', 'Nuria', 'Mónica', 'Alba', 'Irene', 'Natalia', 'Sandra', 'Rocío', 'Adriana', 'Lorena'
];

$nombres_hombres = [
    'Carlos', 'Manuel', 'José', 'David', 'Pablo', 'Javier', 'Daniel', 'Alejandro', 'Miguel', 'Antonio',
    'Francisco', 'Fernando', 'Roberto', 'Alberto', 'Jorge', 'Sergio', 'Luis', 'Rafael', 'Pedro', 'Diego',
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

// Función para generar URL de avatar basada en género
function generarAvatarUrl($username, $gender, $index) {
    $username_normalizado = normalizarParaUrl($username);
    
    // Usamos randomuser.me para realismo y control de género
    // gender puede ser 'male' o 'female'
    $rand_id = rand(1, 99);
    
    if ($index % 2 === 0) {
        // Pravatar con género (si lo soporta el endpoint /u/...) - Pravatar no es muy fiable para género.
        // Mejor usamos randomuser.me portraits
        $gender_letter = ($gender === 'female') ? 'women' : 'men';
        return "https://randomuser.me/api/portraits/{$gender_letter}/" . ($index % 95) . ".jpg";
    } else {
        // Dicebear con seed y género
        $estilos = ['avataaars', 'lorelei', 'notionists', 'adventurer'];
        $estilo = $estilos[$index % count($estilos)];
        return "https://api.dicebear.com/7.x/{$estilo}/svg?seed=" . urlencode($username_normalizado);
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
        // Seleccionar género aleatorio y nombre
        $es_mujer = (rand(1, 100) <= 50);
        $gender = $es_mujer ? 'female' : 'male';
        $nombre = $es_mujer ? $nombres_mujeres[array_rand($nombres_mujeres)] : $nombres_hombres[array_rand($nombres_hombres)];
        
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
            'img' => generarAvatarUrl($username, $gender, $i),
            'estado' => 1,
            'tipo' => 'fake',
            'es_bot' => true,
            'gender' => $gender, // Guardamos género para futuras referencias
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
