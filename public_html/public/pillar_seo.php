<?php
/**
 * Pillar pages SEO: /codigo-amigo, /codigo-referido, /codigo-descuento.
 * Espera $pillar_slug seteado por el router (uno de: codigo-amigo, codigo-referido, codigo-descuento).
 */

global $pillar_slug, $detect_device, $data_usuario;

$slug = $pillar_slug ?? 'codigo-amigo';

$config = [
    'codigo-amigo' => [
        'h1' => 'Códigos amigo: invita, comparte y gana en cientos de marcas',
        'kw' => 'código amigo',
        'kw_plural' => 'códigos amigo',
        'title' => 'Código Amigo: invita amigos y gana dinero en las mejores marcas',
        'description' => 'Comparte tu código amigo y gana saldo, descuentos o regalos. Códigos amigo verificados de Glovo, Uber, PayPal, AliExpress, Coinbase y más de 400 marcas.',
        'intro' => 'Un <strong>código amigo</strong> es el identificador único que cada usuario recibe al registrarse en un servicio para invitar a otros usuarios. Cuando alguien se registra usando tu código amigo, ambos recibís un beneficio: saldo, descuento, regalo o crédito gratis. En CódigoAmigo agregamos miles de códigos amigo de cientos de marcas españolas e internacionales.',
        'que_es' => 'El código amigo (también llamado código de referido o referral code) es la fórmula que usan apps y empresas para crecer mediante el boca-oreja. La marca recompensa tanto al que invita como al invitado: un win-win. Es 100% gratis para el invitado y nunca cuesta más que el precio normal del servicio.',
        'como_funciona' => 'El funcionamiento es sencillo: 1) elige una marca, 2) copia el código amigo, 3) introdúcelo durante el registro o tras la primera compra, 4) recibe tu beneficio. Cada marca tiene condiciones específicas: algunas exigen un pedido mínimo, otras un período de prueba, otras solo el alta. Comprueba siempre las condiciones en la página de cada marca.',
    ],
    'codigo-referido' => [
        'h1' => 'Códigos de referido: cómo funcionan y dónde conseguirlos',
        'kw' => 'código de referido',
        'kw_plural' => 'códigos de referido',
        'title' => 'Código de Referido: gana dinero y descuentos con códigos verificados',
        'description' => 'Códigos de referido verificados de las mejores marcas. Banca, viajes, movilidad, energía, e-commerce. Regístrate con un referral code y recibe tu bonus.',
        'intro' => 'Un <strong>código de referido</strong> (referral code) permite que apps y empresas premien a sus usuarios por invitar a otras personas. Es el mismo concepto que un código amigo: la marca te da un identificador único, lo compartes y, cuando alguien se registra con él, los dos recibís un beneficio.',
        'que_es' => 'El código de referido es la herramienta básica del marketing de afiliación entre usuarios. A diferencia de los códigos promocionales genéricos, el referral code es personal: identifica a quien invita para repartir la recompensa. Muchas startups (fintech, movilidad, delivery, banca digital) crecen casi exclusivamente con esta fórmula.',
        'como_funciona' => 'Para usar un código de referido: 1) abre la app o web de la marca, 2) inicia el registro, 3) en el campo "código de referido", "referral code" o "tienes un código amigo" pega el código, 4) completa los requisitos (depósito mínimo, primer viaje, etc) y recibirás el bonus en saldo o euros reales.',
    ],
    'codigo-descuento' => [
        'h1' => 'Códigos descuento verificados en tiendas y servicios',
        'kw' => 'código descuento',
        'kw_plural' => 'códigos descuento',
        'title' => 'Código Descuento: cupones verificados de las mejores tiendas',
        'description' => 'Códigos descuento verificados para ahorrar en compras online. Cupones de moda, viajes, tecnología, alimentación. Aplicables al instante con un solo clic.',
        'intro' => 'Un <strong>código descuento</strong> (también llamado cupón o promocode) es una cadena de letras y números que reduce el precio de tu compra. En CódigoAmigo encontrarás códigos descuento verificados de cientos de tiendas españolas: comprueba la fecha de validez y úsalos antes de pagar.',
        'que_es' => 'El código descuento es diferente del código amigo: no requiere invitación. Es un cupón genérico que la marca lanza por campañas estacionales (Black Friday, rebajas, lanzamientos). El descuento puede ser un porcentaje (-15%), una cantidad fija (-10€) o un regalo (envío gratis, segunda unidad gratis).',
        'como_funciona' => 'Para usar un código descuento: 1) elige el producto, 2) ve a la cesta o checkout, 3) busca el campo "código promocional" o "cupón", 4) introduce el código y pulsa "Aplicar". El descuento aparecerá reflejado en el total. Si no funciona, comprueba la fecha de validez y los productos incluidos.',
    ],
];

if (!isset($config[$slug])) {
    http_response_code(404);
    echo "Pillar no encontrado";
    exit;
}

$p = $config[$slug];
$title = $p['title'];
$description = $p['description'];
$title_social = $title;
$description_social = $description;
$imagen_social = '';
$links_meta = '';

// rel canonical
$canonical = 'https://www.codigoamigo.com/' . $slug;
$links_meta .= '<link rel="canonical" href="' . $canonical . '">' . "\n";

get_header_new($title, $description, $title_social, $description_social, $imagen_social, $links_meta);

// Top marcas activas con FAQs para mostrar como linking interno
$top_marcas = [];
try {
    $db = createConnection();
    $marcas_top = ['glovo','uber','paypal','coinbase','aliexpress','wish','trading212','holaluz','veepee','privalia','justeat','edreams','bolt','duolingo','elcorteingles','monese','bnext','tier','wind','imaginbank','openbank','octopusenergy','n26','tulotero','digi'];
    foreach ($marcas_top as $mc) {
        $doc = $db->marcas->findOne(['nombre_clave' => $mc]);
        if ($doc) {
            $codigos = $db->codigos->countDocuments(['marca' => $mc, 'estado' => 0]);
            if ($codigos > 0) {
                $top_marcas[] = [
                    'clave' => $mc,
                    'nombre' => $doc->nombre ?? ucfirst($mc),
                    'codigos' => $codigos,
                ];
            }
        }
        if (count($top_marcas) >= 16) break;
    }
} catch (Throwable $e) {}

// FAQs específicas del pillar
$faqs = [
    'codigo-amigo' => [
        ['¿Qué es un código amigo?', 'Es el identificador personal que apps y empresas dan a sus usuarios para invitar a otras personas. Cuando alguien se registra con tu código amigo, los dos recibís un beneficio (saldo, descuento, regalo).'],
        ['¿Es lo mismo código amigo que código de referido?', 'Sí. Código amigo, código de referido y referral code son tres formas de llamar a lo mismo: un identificador que permite trazar invitaciones y repartir recompensas.'],
        ['¿Cuesta dinero usar un código amigo?', 'No. Para el invitado es totalmente gratis: nunca pagas más que el precio normal del servicio. El gasto lo asume la marca como inversión en captación de usuarios.'],
        ['¿Dónde introduzco el código amigo?', 'Depende de la marca, pero suele estar durante el registro en un campo llamado "código de referido", "tienes un código amigo" o "promo code". Algunas marcas permiten introducirlo tras el primer pedido o pago.'],
        ['¿Cuántas veces puedo usar un código amigo?', 'Como invitado solo lo puedes usar una vez por servicio. Como invitador puedes compartir el tuyo todas las veces que quieras: cada nuevo registro suele generar una recompensa adicional.'],
        ['¿Por qué los códigos amigo de CódigoAmigo están verificados?', 'Cada código se publica por usuarios reales y pasa por moderación. Si algún código deja de funcionar, los demás usuarios lo reportan y se retira de la lista.'],
        ['¿Cuánto tarda en aplicarse el beneficio?', 'Varía según la marca: a veces es inmediato (bonus de bienvenida), a veces tras completar la primera compra, viaje o depósito mínimo. Cada marca tiene sus condiciones explicadas en su ficha.'],
        ['¿Puedo publicar mi código amigo en CódigoAmigo?', 'Sí. Regístrate gratis, accede a "Publicar código" y comparte el tuyo. Cada usuario que use tu código genera potencial de recompensa y visibilidad.'],
    ],
    'codigo-referido' => [
        ['¿Qué es un código de referido?', 'Es un identificador único que apps y empresas dan a sus usuarios para que inviten a otros. También se llama referral code o código amigo.'],
        ['¿Cómo encuentro mi código de referido?', 'Suele estar en tu perfil dentro de la app o web, en una sección llamada "Invitar amigos", "Tu código", "Referidos" o similar.'],
        ['¿Qué bonus consigo si me registro con un código de referido?', 'Depende de la marca: saldo gratis, primer mes gratis, descuento en la primera compra, regalo físico, créditos para gastar... Comprueba la ficha de cada marca para ver el beneficio exacto.'],
        ['¿Funcionan los códigos de referido en todas las apps?', 'No todas las apps tienen programa de referidos. Las que sí lo tienen suelen ser fintech (Bnext, N26, Trading212, Coinbase), movilidad (Uber, Bolt, Cabify), delivery (Glovo, JustEat) o suscripciones (Duolingo, HBO).'],
        ['¿Es legal compartir mi código de referido?', 'Sí, totalmente. Las marcas crean estos programas precisamente para que los compartas. Lo único que prohíben algunas es compartir códigos haciéndose pasar por la marca oficial o spamearlos.'],
        ['¿Caducan los códigos de referido?', 'Los códigos en sí no suelen caducar mientras la cuenta del invitador esté activa. Lo que puede caducar es el programa de referidos completo si la marca lo cierra.'],
        ['¿Puedo usar varios códigos de referido en la misma app?', 'No. Cada cuenta nueva solo puede usar un código de referido. Por eso elige bien antes de registrarte.'],
    ],
    'codigo-descuento' => [
        ['¿Qué es un código descuento?', 'Es un cupón en forma de cadena de letras y números que reduce el precio de tu compra al introducirlo en el checkout.'],
        ['¿En qué se diferencia un código descuento de un código amigo?', 'El código descuento es genérico y lo usa cualquier comprador sin invitación previa. El código amigo es personal y solo funciona cuando un usuario invita a otro.'],
        ['¿Cómo aplico un código descuento?', 'En la cesta o checkout busca un campo llamado "código promocional", "cupón" o "código de descuento". Introduce el código y pulsa "Aplicar". El descuento se reflejará en el total a pagar.'],
        ['¿Por qué algunos códigos descuento no funcionan?', 'Pueden estar caducados, agotados (algunos tienen usos limitados), no aplicables al producto que vas a comprar, o requerir compra mínima. Mira siempre las condiciones.'],
        ['¿Son seguros los códigos descuento de CódigoAmigo?', 'Sí. Los códigos los publican usuarios reales y la comunidad reporta los que ya no funcionan, de modo que la lista se mantiene viva y validada.'],
        ['¿Se pueden combinar varios códigos descuento?', 'Casi nunca. La mayoría de tiendas solo permiten un código por compra. Algunas excepciones combinan envío gratis con descuento porcentual.'],
        ['¿Dónde encuentro códigos descuento verificados?', 'En CódigoAmigo agregamos códigos descuento de cientos de tiendas españolas e internacionales. Filtra por marca o categoría para encontrar el que necesitas.'],
    ],
];

$pillar_faqs = $faqs[$slug] ?? [];

// Breadcrumb
$breadcrumb_schema = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => 'https://www.codigoamigo.com'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => $p['h1'], 'item' => $canonical],
    ],
];

$faq_schema = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array_map(function($f) {
        return [
            '@type' => 'Question',
            'name' => $f[0],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]],
        ];
    }, $pillar_faqs),
];

$article_schema = [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $p['h1'],
    'description' => $description,
    'url' => $canonical,
    'datePublished' => '2026-05-25',
    'dateModified' => date('Y-m-d'),
    'inLanguage' => 'es-ES',
    'author' => ['@type' => 'Organization', 'name' => 'CódigoAmigo'],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'CódigoAmigo',
        'logo' => ['@type' => 'ImageObject', 'url' => 'https://www.codigoamigo.com/img/logo.png'],
    ],
];

echo '<script type="application/ld+json">' . json_encode($breadcrumb_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
echo '<script type="application/ld+json">' . json_encode($article_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
if (!empty($pillar_faqs)) {
    echo '<script type="application/ld+json">' . json_encode($faq_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}
?>

<style>
.pillar-wrapper{max-width:900px;margin:0 auto;padding:24px 16px 60px;font-family:'Segoe UI',Tahoma,sans-serif;line-height:1.65;color:#1f2937}
.pillar-wrapper h1{font-size:2rem;font-weight:800;margin:0 0 16px;color:#0f172a;line-height:1.25}
.pillar-wrapper h2{font-size:1.5rem;font-weight:700;margin:36px 0 12px;color:#0f172a}
.pillar-wrapper h3{font-size:1.15rem;font-weight:700;margin:20px 0 8px;color:#0f172a}
.pillar-wrapper p{margin:0 0 14px}
.pillar-intro{font-size:1.1rem;color:#374151;padding:18px 22px;background:#f8fafc;border-left:4px solid #E30613;border-radius:6px;margin:0 0 28px}
.pillar-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin:16px 0 28px}
.pillar-card{display:block;background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:14px 16px;text-decoration:none;color:#0f172a;transition:.18s}
.pillar-card:hover{border-color:#E30613;box-shadow:0 4px 12px rgba(227,6,19,.1);transform:translateY(-2px)}
.pillar-card strong{display:block;font-size:1rem;color:#0f172a;margin-bottom:4px}
.pillar-card span{font-size:.85rem;color:#6b7280}
.pillar-faq{background:#fff;border:1px solid #e5e7eb;border-radius:10px;margin:10px 0;overflow:hidden}
.pillar-faq summary{cursor:pointer;padding:14px 18px;font-weight:600;color:#0f172a;list-style:none;display:flex;justify-content:space-between;align-items:center}
.pillar-faq summary::after{content:'+';font-size:1.4rem;color:#E30613;transition:.2s}
.pillar-faq[open] summary::after{transform:rotate(45deg)}
.pillar-faq p{padding:0 18px 18px;margin:0;color:#374151}
.pillar-related{margin-top:40px;padding:24px;background:#f1f5f9;border-radius:10px}
.pillar-related h3{margin-top:0}
.pillar-related a{color:#1d4ed8;font-weight:600;text-decoration:none;margin-right:14px}
.pillar-related a:hover{text-decoration:underline}
</style>

<main class="pillar-wrapper">
    <nav aria-label="breadcrumb" style="font-size:.85rem;color:#6b7280;margin-bottom:10px;">
        <a href="/" style="color:#6b7280;text-decoration:none;">Inicio</a> &rsaquo; <?php echo htmlspecialchars($p['h1']); ?>
    </nav>

    <h1><?php echo htmlspecialchars($p['h1']); ?></h1>

    <div class="pillar-intro"><?php echo $p['intro']; ?></div>

    <h2>¿Qué es un <?php echo htmlspecialchars($p['kw']); ?>?</h2>
    <p><?php echo htmlspecialchars($p['que_es']); ?></p>

    <h2>¿Cómo funciona un <?php echo htmlspecialchars($p['kw']); ?>?</h2>
    <p><?php echo htmlspecialchars($p['como_funciona']); ?></p>

    <?php if (!empty($top_marcas)): ?>
    <h2>Los mejores <?php echo htmlspecialchars($p['kw_plural']); ?> ahora mismo</h2>
    <p>Estas son las marcas con más <?php echo htmlspecialchars($p['kw_plural']); ?> activos verificados por la comunidad:</p>
    <div class="pillar-cards">
        <?php foreach ($top_marcas as $m): ?>
        <a class="pillar-card" href="/de-<?php echo htmlspecialchars($m['clave']); ?>">
            <strong><?php echo htmlspecialchars($m['nombre']); ?></strong>
            <span><?php echo (int)$m['codigos']; ?> códigos activos</span>
        </a>
        <?php endforeach; ?>
    </div>
    <p style="text-align:center;margin-top:8px;"><a href="/listado-marcas" style="color:#1d4ed8;font-weight:600;">Ver todas las marcas &rarr;</a></p>
    <?php endif; ?>

    <?php if (!empty($pillar_faqs)): ?>
    <h2>Preguntas frecuentes sobre <?php echo htmlspecialchars($p['kw_plural']); ?></h2>
    <?php foreach ($pillar_faqs as $f): ?>
    <details class="pillar-faq">
        <summary><?php echo htmlspecialchars($f[0]); ?></summary>
        <p><?php echo htmlspecialchars($f[1]); ?></p>
    </details>
    <?php endforeach; ?>
    <?php endif; ?>

    <div class="pillar-related">
        <h3>Relacionado</h3>
        <?php if ($slug !== 'codigo-amigo'): ?><a href="/codigo-amigo">¿Qué es un código amigo?</a><?php endif; ?>
        <?php if ($slug !== 'codigo-referido'): ?><a href="/codigo-referido">¿Qué es un código de referido?</a><?php endif; ?>
        <?php if ($slug !== 'codigo-descuento'): ?><a href="/codigo-descuento">¿Qué es un código descuento?</a><?php endif; ?>
        <a href="/listado-marcas">Todas las marcas</a>
    </div>
</main>

<?php
include_once __DIR__ . '/../myphp/_footer.php';
?>
</body>
</html>
