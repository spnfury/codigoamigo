<?php
require_once __DIR__ . '/inc/conexion.php';

echo "Conectando a MongoDB...\n";
$db = createConnection();
$collection = $db->selectCollection('super_landings');
$marcas_collection = $db->selectCollection('marcas');

// Buscar ID de ING Direct
$brand = $marcas_collection->findOne(['nombre_clave' => 'ing-direct']);
if (!$brand) {
    // Intentar buscar 'ing' si 'ing-direct' no existe
    $brand = $marcas_collection->findOne(['nombre_clave' => 'ing']);
}

if (!$brand) {
    die("Error: No se encontró la marca ING Direct para vincular.\n");
}

$brand_id = $brand['_id'];
echo "Marca encontrada: " . $brand['nombre'] . " (ID: " . $brand_id . ")\n";

// Datos de ejemplo para la Super Landing
$super_landing = [
    'slug' => 'ing-cuenta-nomina',
    'type' => 'brand',
    'title' => 'Cuenta Nómina ING: Análisis Completo, Ventajas y Códigos Amigo 2024',
    'meta_title' => 'Cuenta Nómina ING Opiniones y Código Amigo 40€ - 2024',
    'meta_description' => 'Descubre todo sobre la Cuenta Nómina de ING. Análisis detallado, pros y contras, y consigue 40€ gratis con los códigos amigo actualizados.',
    'hero_image' => 'https://www.codigoamigo.com/img/marcas/ing-hero-bg.jpg', // Placeholder
    'linked_brand_id' => $brand_id,
    'status' => 'active',
    'updated_at' => new MongoDB\BSON\UTCDateTime(),
    'sections' => [
        [
            'type' => 'intro',
            'title' => '¿Qué es la Cuenta Nómina de ING?',
            'content' => '<p>La <strong>Cuenta Nómina de ING</strong> es una de las cuentas bancarias más populares en España. Conocida por su ausencia de comisiones y su facilidad de uso, se ha convertido en la opción preferida para millones de usuarios. En este artículo analizaremos en profundidad si realmente merece la pena y cómo puedes aprovechar el <strong>Plan Amigo</strong> para ganar dinero al abrirla.</p>'
        ],
        [
            'type' => 'pros_cons',
            'title' => 'Ventajas y Desventajas',
            'items' => [
                'pros' => [
                    'Sin comisiones de mantenimiento ni administración.',
                    'Tarjetas de débito y crédito gratuitas.',
                    'Transferencias nacionales e internacionales (UE) gratuitas llega el mismo día.',
                    'Plan Amigo: Gana 40€ por invitar amigos (y ellos también).'
                ],
                'cons' => [
                    'Pocos cajeros propios (aunque muchos acuerdos).',
                    'Rentabilidad de la cuenta de ahorro ha bajado.',
                    'Requiere domiciliar nómina o ingresos recurrentes para máximos beneficios.'
                ]
            ]
        ],
        [
            'type' => 'steps',
            'title' => 'Cómo abrir la cuenta y usar el Código Amigo',
            'steps' => [
                [
                    'title' => 'Copia un código',
                    'text' => 'Elige uno de los códigos amigo verificados de nuestra lista superior.'
                ],
                [
                    'title' => 'Inicia el registro',
                    'text' => 'Ve a la web de ING y comienza el proceso de alta de la Cuenta Nómina.'
                ],
                [
                    'title' => 'Introduce el NIF del amigo',
                    'text' => 'Durante el proceso, en la casilla "¿Te ha invitado un amigo?", introduce el NIF del código que copiaste.'
                ],
                [
                    'title' => 'Cumple las condiciones',
                    'text' => 'Domicilia tu nómina, pensión o prestación por desempleo, o haz ingresos mensuales de 700€.'
                ]
            ]
        ],
        [
            'type' => 'faq',
            'title' => 'Preguntas Frecuentes',
            'faqs' => [
                [
                    'question' => '¿Cuánto tarda en llegar el incentivo del Plan Amigo?',
                    'answer' => 'Normalmente ING abona los 40€ a los pocos días de que cumplas las condiciones (primera nómina recibida).'
                ],
                [
                    'question' => '¿Tiene permanencia?',
                    'answer' => 'No, la Cuenta Nómina de ING no tiene compromiso de permanencia.'
                ]
            ]
        ]
    ]
];

// Insertar o actualizar
$result = $collection->updateOne(
    ['slug' => $super_landing['slug']],
    ['$set' => $super_landing],
    ['upsert' => true]
);

echo "Super Landing 'ing-cuenta-nomina' creada/actualizada correctamente.\n";
?>
