<?php
require_once __DIR__ . '/inc/conexion.php';

echo "Conectando a MongoDB para insertar Super Landing 'Mejores Neobancos'...\n";
$db = createConnection();
$collection = $db->selectCollection('super_landings');
$marcas_coll = $db->selectCollection('marcas');

// Buscar IDs de marcas relevantes
$marcas_slugs = ['n26', 'revolut', 'qonto', 'vivid-money', 'myinvestor'];
$brand_ids = [];

foreach ($marcas_slugs as $slug) {
    $marca = $marcas_coll->findOne(['nombre_clave' => $slug]);
    if ($marca) {
        $brand_ids[] = $marca['_id'];
        echo "Found brand: $slug (" . $marca['_id'] . ")\n";
    }
}

$data = [
    'slug' => 'mejores-neobancos-2026',
    'title' => 'Los Mejores Neobancos y Cuentas Online (2026)',
    'type' => 'collection', // Nuevo tipo 'collection'
    'meta_title' => 'Mejores Neobancos 2026: Comparativa y Códigos Promocionales',
    'meta_description' => 'Comparativa de los mejores neobancos en España: N26, Revolut, MyInvestor. Descubre sus ventajas y consigue dinero gratis con nuestros códigos amigo.',
    'status' => 'active',
    'created_at' => new MongoDB\BSON\UTCDateTime(),
    'updated_at' => new MongoDB\BSON\UTCDateTime(),
    'hero_image' => 'https://images.unsplash.com/photo-1563013544-824ae1b704d3?auto=format&fit=crop&w=1200&q=80',
    'linked_brand_ids' => $brand_ids, // Usar array de IDs para colecciones
    'linked_brand_slugs' => $marcas_slugs, // Fallback para buscar por slug 'marca'
    'sections' => [
        [
            'type' => 'intro',
            'title' => '¿Cuál es el mejor banco online en 2026?',
            'content' => '<p>La banca tradicional está cambiando. Los <strong>neobancos</strong> ofrecen cuentas sin comisiones, tarjetas gratuitas para viajar y aplicaciones móviles superiores. En esta guía seleccionamos las mejores opciones disponibles en España y recopilamos los <strong>códigos amigo activos</strong> para que ganes dinero al registrarte.</p>'
        ],
        [
            'type' => 'steps', // Reusing steps for list of banks
            'title' => 'Top 3 Neobancos Recomendados',
            'steps' => [
                [
                    'title' => '1. N26 - El mejor para el día a día',
                    'text' => 'Cuenta española (IBAN ES), Bizum integrado y sin comisiones ocultas. Ideal como cuenta principal o secundaria.'
                ],
                [
                    'title' => '2. Revolut - El rey de los viajes',
                    'text' => 'El mejor cambio de divisa del mercado. Imprescindible si viajas fuera de la zona euro.'
                ],
                [
                    'title' => '3. MyInvestor - Para rentabilizar ahorros',
                    'text' => 'La mejor cuenta remunerada del mercado (2.5% TAE) y acceso a fondos de inversión indexados.'
                ]
            ]
        ],
        [
            'type' => 'faq',
            'title' => 'Todo sobre Neobancos',
            'faqs' => [
                [
                    'question' => '¿Es seguro confiar mi dinero a un neobanco?',
                    'answer' => 'Sí, neobancos como N26 o Revolut tienen licencia bancaria europea y tus depósitos están garantizados hasta 100.000€ por el Fondo de Garantía de Depósitos.'
                ],
                [
                    'question' => '¿Puedo tener Bizum?',
                    'answer' => 'La mayoría de neobancos con IBAN español (como N26, BBVA Online, Openbank) ya incluyen Bizum.'
                ]
            ]
        ]
    ]
];

// Comprobar si ya existe para no duplicar
$existing = $collection->findOne(['slug' => $data['slug']]);

if ($existing) {
    $result = $collection->updateOne(['slug' => $data['slug']], ['$set' => $data]);
    echo "Actualizado 'mejores-neobancos-2026'.\n";
} else {
    $result = $collection->insertOne($data);
    echo "Insertado 'mejores-neobancos-2026' con ID: " . $result->getInsertedId() . "\n";
}
?>
