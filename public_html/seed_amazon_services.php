<?php
// seed_amazon_services.php
require_once __DIR__ . '/inc/conexion.php';

try {
    $db = createConnection();
    $linksCollection = $db->selectCollection('affiliate_links');
    
    $services = [
        [
            'slug' => 'prime',
            'title' => 'Amazon Prime',
            'description' => 'Envíos rápidos y GRATIS · Prime Video: Películas y Series · Prime Music: 100 millones de canciones · Ofertas exclusivas Prime',
            'cta_text' => 'Prueba Prime Gratis',
            'destination_url' => 'https://www.amazon.es/prime?tag=spnfuryy-21',
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/d/de/Amazon_Prime_Logo.svg',
            'category' => 'general',
            'active' => true,
            'order' => 1
        ],
        [
            'slug' => 'audible',
            'title' => 'Amazon Audible',
            'description' => 'Audiolibros y Podcasts exclusivos · Escucha donde quieras, incluso offline · Miles de títulos incluidos · Narraciones por voces famosas',
            'cta_text' => 'Probar Audible',
            'destination_url' => 'https://www.amazon.es/hz/audible/mlp/membership/premiumplus?tag=spnfuryy-21',
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/0/02/Audible_logo.svg',
            'category' => 'books',
            'active' => true,
            'order' => 2
        ],
        [
            'slug' => 'music',
            'title' => 'Music Unlimited',
            'description' => '100 millones de canciones en HD · Sin anuncios y sin límites · Escucha offline · Audio espacial de alta calidad',
            'cta_text' => 'Probar Music',
            'destination_url' => 'https://www.amazon.es/music/unlimited?tag=spnfuryy-21',
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/b/b3/Amazon_Music_logo.svg',
            'category' => 'music',
            'active' => true,
            'order' => 3
        ],
        [
            'slug' => 'kindle',
            'title' => 'Kindle Unlimited',
            'description' => 'Acceso ilimitado a más de 1 millón de eBooks · Lee en cualquier dispositivo con la App Kindle · Revistas y cómics incluidos',
            'cta_text' => 'Probar Kindle',
            'destination_url' => 'https://www.amazon.es/kindle-dbs/hz/signup?tag=spnfuryy-21',
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/e0/Amazon_Kindle_Logo.png',
            'category' => 'books',
            'active' => true,
            'order' => 4
        ],
        [
            'slug' => 'student',
            'title' => 'Prime Student',
            'description' => 'Ventajas de Prime al 50% para estudiantes · 6 meses de prueba gratis · Ofertas específicas para universitarios',
            'cta_text' => 'Prueba Student',
            'destination_url' => 'https://www.amazon.es/joinstudent?tag=spnfuryy-21',
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/7/77/Amazon_Prime_Student_Logo.png',
            'category' => 'general',
            'active' => true,
            'order' => 5
        ],
        [
            'slug' => 'video',
            'title' => 'Prime Video Channels',
            'description' => 'Suscríbete a canales como MGM, Eurosport o MUBI · Sin contratos de permanencia · Todo en una sola App',
            'cta_text' => 'Explorar Canales',
            'destination_url' => 'https://www.amazon.es/gp/video/storefront/ref=atv_sc_avs_lp?tag=spnfuryy-21',
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/1/11/Amazon_Prime_Video_logo.svg',
            'category' => 'video',
            'active' => true,
            'order' => 6
        ]
    ];

    foreach ($services as $srv) {
        $srv['destination_url'] = trim($srv['destination_url']);
        $srv['image_url'] = trim($srv['image_url']);
        $srv['slug'] = trim($srv['slug']);
        
        $linksCollection->updateOne(
            ['slug' => $srv['slug']],
            ['$set' => array_merge($srv, ['updated_at' => new MongoDB\BSON\UTCDateTime()])],
            ['upsert' => true]
        );
        echo "Upserted service: " . $srv['slug'] . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
