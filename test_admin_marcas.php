<?php
// Simular exactamente la URL que está causando problemas
$_GET['estado'] = '';
$_GET['categoria'] = '';
$_GET['busqueda'] = 'n26';
$_GET['limit'] = '20';

// Incluir los archivos necesarios
require_once __DIR__ . '/public_html/inc/includes.php';

// Inicializar las colecciones como en admin_marcas.php
$collection_marcas = getCollectionMarcas();
$collection_codigos = getCollectionCodigos();
$collection_categorias = getCollectionCategoriasEvo();
$collection_redirects = getCollectionRedirects();

// Simular la lógica de filtros de admin_marcas.php
$filtro_estado = $_GET['estado'] ?? '';
$filtro_categoria = $_GET['categoria'] ?? '';
$filtro_busqueda = $_GET['busqueda'] ?? '';

$sort_field = $_GET['sort'] ?? 'fecha_publicacion';
$sort_direction = $_GET['dir'] ?? 'desc';

// Construir filtros para la consulta
$filtros = [];
if ($filtro_estado !== '') {
    $filtros['estado'] = (int)$filtro_estado;
}
if ($filtro_categoria) {
    $filtros['categoria'] = $filtro_categoria;
}
if ($filtro_busqueda) {
    $filtros['$or'] = [
        ['nombre' => ['$regex' => $filtro_busqueda, '$options' => 'i']],
        ['descripcion' => ['$regex' => $filtro_busqueda, '$options' => 'i']]
    ];
}

// Obtener marcas con paginación
$page = (int)($_GET['page'] ?? 1);
$limit = (int)($_GET['limit'] ?? 20);

echo "Parámetros recibidos:\n";
echo "estado: '$filtro_estado'\n";
echo "categoria: '$filtro_categoria'\n";
echo "busqueda: '$filtro_busqueda'\n";
echo "limit: $limit\n";
echo "\n";

echo "Filtros construidos:\n";
var_dump($filtros);

try {
    if ($sort_field === 'codigos') {
        // Usar aggregation pipeline para ordenar por códigos
        $pipeline = [];

        if (!empty($filtros)) {
            $pipeline[] = ['$match' => $filtros];
        }

        $pipeline[] = [
            '$lookup' => [
                'from' => 'codigos',
                'localField' => 'nombre_clave',
                'foreignField' => 'marca',
                'as' => 'codigos_relacionados'
            ]
        ];

        $pipeline[] = [
            '$addFields' => [
                'codigos_count' => ['$size' => '$codigos_relacionados']
            ]
        ];

        $sort_direction_value = ($sort_direction === 'asc') ? 1 : -1;
        $pipeline[] = ['$sort' => ['codigos_count' => $sort_direction_value]];

        $pipeline[] = ['$limit' => $limit];

        $marcas = $collection_marcas->aggregate($pipeline)->toArray();
    } else {
        // Consulta normal
        $sort_options = [];
        $sort_direction_value = ($sort_direction === 'asc') ? 1 : -1;

        switch ($sort_field) {
            case 'nombre':
                $sort_options['nombre'] = $sort_direction_value;
                break;
            case 'categoria':
                $sort_options['categoria'] = $sort_direction_value;
                break;
            case 'estado':
                $sort_options['estado'] = $sort_direction_value;
                break;
            case 'fecha_publicacion':
                $sort_options['fecha_publicacion'] = $sort_direction_value;
                break;
            default:
                $sort_options['fecha_publicacion'] = -1;
                break;
        }

        $marcas = $collection_marcas->find($filtros, [
            'sort' => $sort_options,
            'limit' => $limit
        ])->toArray();
    }

    echo "\nConsulta exitosa. Encontradas " . count($marcas) . " marcas\n";

    if (count($marcas) > 0) {
        echo "Primeras marcas encontradas:\n";
        foreach (array_slice($marcas, 0, 3) as $marca) {
            echo "- {$marca['nombre']} ({$marca['categoria']})\n";
        }
    }

} catch (Exception $e) {
    echo "\nError en la consulta: " . $e->getMessage() . "\n";
    echo "Código de error: " . $e->getCode() . "\n";
}
?>
