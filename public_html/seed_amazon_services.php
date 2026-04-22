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
            'seo_title' => 'Amazon Prime: Opiniones, Ventajas y Prueba Gratis de 30 Días',
            'meta_description' => 'Descubre todas las ventajas de Amazon Prime en España. Envíos gratis, Prime Video, Music y más. Aprovecha la prueba gratuita de 30 días hoy mismo.',
            'h1' => 'Amazon Prime: Mucho más que envíos rápidos y gratis',
            'intro_text' => 'Amazon Prime se ha convertido en el servicio de suscripción más completo del mundo. No solo te permite ahorrar dinero en tus compras online, sino que te abre las puertas a todo un ecosistema de entretenimiento digital de alta calidad.',
            'description' => 'Envíos rápidos y GRATIS · Prime Video: Películas y Series · Prime Music: 100 millones de canciones · Ofertas exclusivas Prime',
            'cta_text' => 'Prueba Prime Gratis',
            'destination_url' => 'https://www.amazon.es/prime?tag=spnfuryy-21',
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/d/de/Amazon_Prime_Logo.svg',
            'category' => 'general',
            'active' => true,
            'order' => 1,
            'features' => [
                ['title' => 'Envíos Gratuitos', 'text' => 'Envíos en 1 día gratis en millones de productos y entrega en el mismo día en códigos postales seleccionados.'],
                ['title' => 'Prime Video', 'text' => 'Acceso ilimitado a miles de películas y series galardonadas, incluyendo Amazon Originals.'],
                ['title' => 'Amazon Music', 'text' => 'Más de 100 millones de canciones sin anuncios y los mejores podcasts.'],
                ['title' => 'Prime Reading', 'text' => 'Cientos de eBooks disponibles para leer en cualquier dispositivo.'],
                ['title' => 'Ofertas Exclusivas', 'text' => 'Acceso prioritario a las Ofertas Flash 30 minutos antes de su inicio.']
            ],
            'faq' => [
                ['q' => '¿Cuánto cuesta Amazon Prime?', 'a' => 'Tras el periodo de prueba gratis, Amazon Prime cuesta 4,99€ al mes o 49,90€ al año.'],
                ['q' => '¿Puedo cancelar en cualquier momento?', 'a' => 'Sí, no hay compromiso de permanencia. Puedes cancelar tu suscripción fácilmente desde tu cuenta.'],
                ['q' => '¿Qué incluye Prime Video?', 'a' => 'Incluye series exclusivas como "Los Anillos de Poder", "The Boys" y miles de películas de estreno.']
            ]
        ],
        [
            'slug' => 'audible',
            'title' => 'Amazon Audible',
            'seo_title' => 'Amazon Audible: Audiolibros Ilimitados y Podcast Exclusivos',
            'meta_description' => 'Explora Amazon Audible España. Miles de audiolibros y podcasts originales narrados por voces profesionales. Prueba gratis y escucha donde quieras.',
            'h1' => 'Descubre la magia de escuchar con Amazon Audible',
            'intro_text' => 'Audible es la plataforma líder mundial en audiolibros y contenido de audio premium. Convierte tus trayectos al trabajo, tus sesiones de gimnasio o tus momentos de relax en una experiencia de aprendizaje y entretenimiento única.',
            'description' => 'Audiolibros y Podcasts exclusivos · Escucha donde quieras, incluso offline · Miles de títulos incluidos · Narraciones por voces famosas',
            'cta_text' => 'Probar Audible',
            'destination_url' => 'https://www.amazon.es/hz/audible/mlp/membership/premiumplus?tag=spnfuryy-21',
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/0/02/Audible_logo.svg',
            'category' => 'books',
            'active' => true,
            'order' => 2,
            'features' => [
                ['title' => 'Catálogo Ilimitado', 'text' => 'Acceso a más de 90.000 audiolibros y podcasts exclusivos de Amazon.'],
                ['title' => 'Escucha Offline', 'text' => 'Descarga tus títulos favoritos y escúchalos sin necesidad de conexión a internet.'],
                ['title' => 'Voces Famosas', 'text' => 'Disfruta de narraciones realizadas por actores y profesionales de voz reconocidos.'],
                ['title' => 'Sin Publicidad', 'text' => 'Experiencia de escucha totalmente fluida, sin interrupciones comerciales.'],
                ['title' => 'Multidispositivo', 'text' => 'Empieza a escuchar en tu móvil y continúa donde lo dejaste en tu tablet o ordenador.']
            ],
            'faq' => [
                ['q' => '¿Cuánto tiempo dura la prueba gratuita?', 'a' => 'La prueba estándar es de 30 días, aunque usuarios Prime suelen recibir 3 meses gratis.'],
                ['q' => '¿Me quedo con los libros si cancelo?', 'a' => 'Los títulos comprados con créditos son tuyos para siempre, incluso si cancelas la suscripción.'],
                ['q' => '¿Qué dispositivos son compatibles?', 'a' => 'Casi cualquier dispositivo: iOS, Android, tablets Fire, dispositivos Echo y navegadores web.']
            ]
        ],
        [
            'slug' => 'music',
            'title' => 'Music Unlimited',
            'seo_title' => 'Amazon Music Unlimited: Tu Música favorita sin límites',
            'meta_description' => 'Millones de canciones en HD y audio espacial. Prueba Amazon Music Unlimited gratis y disfruta de música sin anuncios y offline.',
            'h1' => 'Amazon Music Unlimited: La mayor biblioteca musical a tu alcance',
            'intro_text' => 'Lleva tu experiencia sonora al siguiente nivel con Amazon Music Unlimited. Disfruta de una calidad de sonido excepcional y un catálogo que abarca todos los géneros y épocas imaginables.',
            'description' => '100 millones de canciones en HD · Sin anuncios y sin límites · Escucha offline · Audio espacial de alta calidad',
            'cta_text' => 'Probar Music',
            'destination_url' => 'https://www.amazon.es/music/unlimited?tag=spnfuryy-21',
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/b/b3/Amazon_Music_logo.svg',
            'category' => 'music',
            'active' => true,
            'order' => 3,
            'features' => [
                ['title' => '100 Millones de Canciones', 'text' => 'Acceso ilimitado a cualquier canción de cualquier artista cuando quieras.'],
                ['title' => 'Audio en HD y Ultra HD', 'text' => 'Calidad de sonido superior que te permite escuchar cada detalle de la música.'],
                ['title' => 'Cero Anuncios', 'text' => 'Disfruta de tus playlists y álbumes favoritos sin ninguna interrupción.'],
                ['title' => 'Modo Offline', 'text' => 'Descarga música para escucharla en cualquier lugar sin gastar datos.'],
                ['title' => 'Integración con Alexa', 'text' => 'Controla tu música fácilmente con comandos de voz en tus dispositivos Echo.']
            ],
            'faq' => [
                ['q' => '¿Diferencia entre Music Prime y Unlimited?', 'a' => 'Unlimited ofrece el catálogo completo de 100M de canciones a la carta, mientras que Prime tiene limitaciones en la elección.'],
                ['q' => '¿Se puede usar en varios dispositivos?', 'a' => 'Sí, aunque el plan individual permite una sola transmisión a la vez. Existe un plan familiar para hasta 6 personas.'],
                ['q' => '¿Hay descuento para usuarios Prime?', 'a' => 'Sí, los miembros de Amazon Prime disfrutan de una tarifa mensual reducida en la suscripción anual.']
            ]
        ],
        [
            'slug' => 'kindle',
            'title' => 'Kindle Unlimited',
            'seo_title' => 'Kindle Unlimited: Lectura Ilimitada en cualquier dispositivo',
            'meta_description' => 'Lee sin límites con Kindle Unlimited. Más de un millón de títulos, revistas y audiolibros disponibles. Pruébalo gratis ahora.',
            'h1' => 'Kindle Unlimited: Tu biblioteca personal en la palma de tu mano',
            'intro_text' => 'Para los amantes de la lectura, Kindle Unlimited es el paraíso. Accede a una biblioteca masiva sin tener que comprar cada libro por separado. Lo mejor: no necesitas un eReader Kindle para disfrutarlo.',
            'description' => 'Acceso ilimitado a más de 1 millón de eBooks · Lee en cualquier dispositivo con la App Kindle · Revistas y cómics incluidos',
            'cta_text' => 'Probar Kindle',
            'destination_url' => 'https://www.amazon.es/kindle-dbs/hz/signup?tag=spnfuryy-21',
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/e0/Amazon_Kindle_Logo.png',
            'category' => 'books',
            'active' => true,
            'order' => 4,
            'features' => [
                ['title' => 'Millones de eBooks', 'text' => 'Desde bestsellers recientes hasta clásicos de siempre en múltiples idiomas.'],
                ['title' => 'App Kindle Gratuita', 'text' => 'Disponible para iOS, Android, PC y Mac. Sincroniza tus lecturas entre dispositivos.'],
                ['title' => 'Revistas Actuales', 'text' => 'Lee una selección de las revistas más populares sin coste adicional.'],
                ['title' => 'Préstamos Ilimitados', 'text' => 'Ten hasta 20 libros prestados a la vez en tu biblioteca personal.'],
                ['title' => 'Autoedición y Novedades', 'text' => 'Descubre nuevos talentos y lanzamientos exclusivos de Kindle Direct Publishing.']
            ],
            'faq' => [
                ['q' => '¿Necesito un dispositivo Kindle?', 'a' => 'No, puedes leer en cualquier móvil, tablet u ordenador usando la aplicación gratuita de Kindle.'],
                ['q' => '¿Qué tipos de libros incluye?', 'a' => 'Incluye una enorme variedad de géneros: novela, desarrollo personal, cocina, cómics y títulos infantiles.'],
                ['q' => '¿Cómo cancelo mi suscripción?', 'a' => 'Puedes hacerlo en cualquier momento desde la sección "Gestionar mi suscripción" en la web de Amazon.']
            ]
        ],
        [
            'slug' => 'student',
            'title' => 'Prime Student',
            'seo_title' => 'Amazon Prime Student: Descuentos y Prueba de 90 Días gratis',
            'meta_description' => 'Si eres estudiante universitario, Prime Student es para ti. Envíos gratis, Prime Video y Music con 90 días de prueba y 50% de descuento.',
            'h1' => 'Prime Student: Todas las ventajas de Prime a mitad de precio',
            'intro_text' => 'Sabemos que ser estudiante no es fácil para el bolsillo. Por eso, Prime Student ofrece las mismas ventajas premium de la suscripción estándar pero con condiciones especiales para universitarios.',
            'description' => 'Ventajas de Prime al 50% para estudiantes · 6 meses de prueba gratis · Ofertas específicas para universitarios',
            'cta_text' => 'Prueba Student',
            'destination_url' => 'https://www.amazon.es/joinstudent?tag=spnfuryy-21',
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/7/77/Amazon_Prime_Student_Logo.png',
            'category' => 'general',
            'active' => true,
            'order' => 5,
            'features' => [
                ['title' => '90 Días de Prueba', 'text' => 'Disfruta de 3 meses completos de servicio totalmente gratis para nuevos usuarios.'],
                ['title' => '50% de Descuento', 'text' => 'Tras la prueba, la suscripción cuesta solo la mitad que la versión estándar para adultos.'],
                ['title' => 'Envíos Rápidos', 'text' => 'Recibe tus libros de texto o cualquier pedido rápidamente para que no te falte nada.'],
                ['title' => 'Prime Gaming', 'text' => 'Juegos gratis cada mes y una suscripción gratuita a un canal de Twitch.'],
                ['title' => 'Descuentos Exclusivos', 'text' => 'Ofertas especiales en categorías seleccionadas para el entorno universitario.']
            ],
            'faq' => [
                ['q' => '¿Cómo me registro como estudiante?', 'a' => 'Necesitas una dirección de correo electrónico universitaria (.edu o similar) o una prueba de matriculación.'],
                ['q' => '¿Cuánto dura la suscripción de estudiante?', 'a' => 'Hasta que te gradúes o por un máximo de 4 años, lo que ocurra primero.'],
                ['q' => '¿Qué hago si no tengo email universitario?', 'a' => 'Puedes enviar una foto de tu carnet de estudiante o certificado de matrícula para verificar tu estado.']
            ]
        ],
        [
            'slug' => 'video',
            'title' => 'Prime Video Channels',
            'seo_title' => 'Prime Video Channels: Añade tus canales favoritos sin permanencia',
            'meta_description' => 'Personaliza tu experiencia en Prime Video con canales como MGM, Eurosport y MUBI. Pruébalos gratis y cancela cuando quieras.',
            'h1' => 'Prime Video Channels: La televisión a la carta definitiva',
            'intro_text' => '¿Por qué pagar por cientos de canales que no ves? Prime Video Channels te permite elegir exactamente qué quieres añadir a tu cuenta, centralizando todo tu contenido en una sola aplicación.',
            'description' => 'Suscríbete a canales como MGM, Eurosport o MUBI · Sin contratos de permanencia · Todo en una sola App',
            'cta_text' => 'Explorar Canales',
            'destination_url' => 'https://www.amazon.es/gp/video/storefront/ref=atv_sc_avs_lp?tag=spnfuryy-21',
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/1/11/Amazon_Prime_Video_logo.svg',
            'category' => 'video',
            'active' => true,
            'order' => 6,
            'features' => [
                ['title' => 'Selección de Canales', 'text' => 'Añade canales de cine, deportes, infantil y documentales según tus gustos.'],
                ['title' => 'Sin Cables ni Decodificadores', 'text' => 'Todo se transmite a través de internet dentro de la propia interfaz de Prime Video.'],
                ['title' => 'Pago por Canal', 'text' => 'Solo pagas por los canales adicionales que decidas contratar, con total transparencia.'],
                ['title' => 'Prueba Gratuita Individual', 'text' => 'Casi todos los canales ofrecen un periodo de prueba gratis de 7 o 14 días.'],
                ['title' => 'Cancelación Flexible', 'text' => 'Puedes dar de baja cualquier canal en cuestión de segundos, sin llamadas ni complicaciones.']
            ],
            'faq' => [
                ['q' => '¿Necesito ser Prime para contratar Channels?', 'a' => 'Sí, los canales son complementos exclusivos para usuarios con una suscripción activa de Amazon Prime.'],
                ['q' => '¿Los canales tienen publicidad?', 'a' => 'La mayoría de canales premium son libres de anuncios, aunque algunos de deportes pueden tener publicidad durante las pausas.'],
                ['q' => '¿Dónde puedo ver estos canales?', 'a' => 'En cualquier dispositivo compatible con Prime Video: Smart TV, consolas, móviles y sticks de streaming.']
            ]
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
