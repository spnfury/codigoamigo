<?php
// Test para verificar los códigos destacados
session_start();

// Simular sesión de administrador para testing
$_SESSION['user_id'] = '58bd851da54e295b8b52f702';

// Incluir archivos necesarios
include_once __DIR__ . '/../public_html/inc/includes.php';
include_once __DIR__ . '/../public_html/myphp/funciones.php';
include_once __DIR__ . '/../inc/funciones.php';

$collection_codigos = getCollectionCodigos();

echo "<h1>Test de Códigos Destacados</h1>";

// Obtener códigos destacados con información de usuario
$pipeline = [
    [
        '$match' => [
            'estado' => 0,
            'destacado' => ['$ne' => 0]
        ]
    ],
    [
        '$lookup' => [
            'from' => 'usuarios',
            'localField' => 'id_usuario',
            'foreignField' => '_id',
            'as' => 'usuario_info'
        ]
    ],
    [
        '$addFields' => [
            'username' => ['$arrayElemAt' => ['$usuario_info.username', 0]],
            'destacado_date' => [
                '$dateToString' => [
                    'format' => '%Y-%m-%d %H:%M:%S',
                    'date' => ['$toDate' => ['$multiply' => ['$destacado', 1000]]]
                ]
            ]
        ]
    ],
    [
        '$project' => [
            'marca' => 1,
            'username' => 1,
            'destacado' => 1,
            'destacado_date' => 1,
            'num_beneficio' => 1,
            'descripcion' => 1
        ]
    ],
    [
        '$sort' => ['destacado' => -1]
    ],
    [
        '$limit' => 20
    ]
];

$result = $collection_codigos->aggregate($pipeline)->toArray();

echo "<h2>Códigos Destacados (Últimos 20):</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Marca</th><th>Usuario</th><th>Destacado</th><th>Fecha</th><th>Beneficio</th><th>Descripción</th></tr>";

foreach ($result as $codigo) {
    $marca = $codigo['marca'] ?? 'N/A';
    $username = $codigo['username'] ?? 'N/A';
    $destacado = $codigo['destacado'] ?? 0;
    $fecha = $codigo['destacado_date'] ?? 'N/A';
    $beneficio = $codigo['num_beneficio'] ?? 0;
    $descripcion = substr($codigo['descripcion'] ?? 'N/A', 0, 50) . '...';
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($marca) . "</td>";
    echo "<td>" . htmlspecialchars($username) . "</td>";
    echo "<td>" . $destacado . "</td>";
    echo "<td>" . htmlspecialchars($fecha) . "</td>";
    echo "<td>" . $beneficio . "€</td>";
    echo "<td>" . htmlspecialchars($descripcion) . "</td>";
    echo "</tr>";
}

echo "</table>";

// Contar por usuario
echo "<h2>Conteo por Usuario:</h2>";
$pipeline_count = [
    [
        '$match' => [
            'estado' => 0,
            'destacado' => ['$ne' => 0]
        ]
    ],
    [
        '$lookup' => [
            'from' => 'usuarios',
            'localField' => 'id_usuario',
            'foreignField' => '_id',
            'as' => 'usuario_info'
        ]
    ],
    [
        '$group' => [
            '_id' => ['$arrayElemAt' => ['$usuario_info.username', 0]],
            'count' => ['$sum' => 1]
        ]
    ],
    [
        '$sort' => ['count' => -1]
    ]
];

$count_result = $collection_codigos->aggregate($pipeline_count)->toArray();

echo "<ul>";
foreach ($count_result as $user) {
    echo "<li>" . htmlspecialchars($user['_id']) . ": " . $user['count'] . " códigos destacados</li>";
}
echo "</ul>";

echo "<h2>Test Completado</h2>";
?>
