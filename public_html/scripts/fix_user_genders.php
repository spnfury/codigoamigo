<?php
/**
 * Script para corregir el género y fotos de usuarios fake existentes.
 * Ejecutar: php scripts/fix_user_genders.php
 */

require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';

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

function normalizarParaUrl($texto) {
    $originales = ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ', 'Ü'];
    $reemplazos = ['a', 'e', 'i', 'o', 'u', 'n', 'u', 'a', 'e', 'i', 'o', 'u', 'n', 'u'];
    return str_replace($originales, $reemplazos, $texto);
}

function generarAvatarUrl($username, $gender, $index) {
    $username_normalizado = normalizarParaUrl($username);
    if ($index % 2 === 0) {
        $gender_letter = ($gender === 'female') ? 'women' : 'men';
        // Usamos index para que el avatar sea determinista por usuario si se repite el script
        return "https://randomuser.me/api/portraits/{$gender_letter}/" . ($index % 95) . ".jpg";
    } else {
        $estilos = ['avataaars', 'lorelei', 'notionists', 'adventurer'];
        $estilo = $estilos[$index % count($estilos)];
        return "https://api.dicebear.com/7.x/{$estilo}/svg?seed=" . urlencode($username_normalizado);
    }
}

$collection_usuarios = getCollectionUsuarios();
$fake_users = $collection_usuarios->find(['tipo' => 'fake'])->toArray();

echo "🚀 Corrigiendo géneros de " . count($fake_users) . " usuarios...\n";

$actualizados = 0;
foreach ($fake_users as $index => $user) {
    $fullname = $user['username'] ?? '';
    if (!$fullname) continue;
    
    $parts = explode(' ', $fullname);
    $firstname = $parts[0];
    
    $gender = 'male'; // fallback
    if (in_array($firstname, $nombres_mujeres)) {
        $gender = 'female';
    } elseif (in_array($firstname, $nombres_hombres)) {
        $gender = 'male';
    }
    
    $new_img = generarAvatarUrl($fullname, $gender, $index);
    
    $collection_usuarios->updateOne(
        ['_id' => $user['_id']],
        ['$set' => [
            'gender' => $gender,
            'img' => $new_img
        ]]
    );
    
    echo "✅ [{$actualizados}] {$fullname} -> {$gender} ({$new_img})\n";
    $actualizados++;
}

echo "\n✨ ¡Proceso completado! $actualizados usuarios actualizados.\n";
