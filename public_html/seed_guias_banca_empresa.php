<?php
/**
 * Seed del clúster de guías de banca para empresas/profesionales.
 *
 *   1. mejores-bancos-llc                      -> Bancos para tu LLC en EE.UU.
 *   2. mejores-bancos-empresa-offshore         -> Bancos para empresa offshore
 *   3. cuentas-multidivisa-autonomos-freelance -> Cuentas multidivisa freelance
 *
 * Las tres están interlinkadas entre sí (intro con "Guías relacionadas") para
 * reforzar el enlazado interno SEO. Comparten el motor super_landings y vinculan
 * marcas existentes + las creadas en seed_marcas_fintech.php.
 *
 * Uso: php seed_guias_banca_empresa.php   (idempotente, upsert por slug)
 */

require_once __DIR__ . '/inc/conexion.php';

$db = createConnection();
$collection = $db->selectCollection('super_landings');
$marcas_coll = $db->selectCollection('marcas');

// Resuelve slugs -> ObjectIds de marca (solo las que existan)
function brand_ids_for(array $slugs, $marcas_coll) {
    $ids = [];
    foreach ($slugs as $slug) {
        $m = $marcas_coll->findOne(['nombre_clave' => $slug]);
        if ($m) {
            $ids[] = $m['_id'];
        } else {
            echo "  AVISO: marca '$slug' no encontrada (se omite del vínculo)\n";
        }
    }
    return $ids;
}

// Bloque HTML reutilizable de guías relacionadas (interlinking)
function relacionadas_html(array $otras) {
    $html = '<div style="margin-top:24px;padding:18px 22px;background:#f0f4ff;border:1px solid #d5dff0;border-radius:12px;">';
    $html .= '<strong>📚 Guías relacionadas:</strong><ul style="margin:10px 0 0;padding-left:20px;">';
    foreach ($otras as $o) {
        $html .= '<li><a href="/guias/' . $o['slug'] . '" style="color:#2a5298;font-weight:600;">' . $o['texto'] . '</a></li>';
    }
    $html .= '</ul></div>';
    return $html;
}

$g_llc       = ['slug' => 'mejores-bancos-llc',                      'texto' => 'Mejores bancos para tu LLC en EE.UU.'];
$g_offshore  = ['slug' => 'mejores-bancos-empresa-offshore',        'texto' => 'Mejores bancos para empresa offshore'];
$g_freelance = ['slug' => 'cuentas-multidivisa-autonomos-freelance','texto' => 'Cuentas multidivisa para autónomos y freelance'];

$guias = [];

// ============================================================
// GUÍA 1: Bancos para tu LLC en EE.UU.
// ============================================================
$guias[] = [
    'slug' => 'mejores-bancos-llc',
    'title' => 'Mejores Bancos y Neobancos para tu LLC en EE.UU. (2026)',
    'type' => 'collection',
    'meta_title' => 'Mejores Bancos para LLC en EE.UU. 2026 | Comparativa y Códigos',
    'meta_description' => 'Comparativa de los mejores bancos online para abrir cuenta de tu LLC en Estados Unidos sin viajar: Mercury, Relay, Brex, Wise y Payoneer. Apertura remota y códigos amigo.',
    'status' => 'active',
    'hero_image' => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?auto=format&fit=crop&w=1200&q=80',
    'linked_brand_slugs' => ['mercury', 'relay', 'brex', 'wise', 'payoneer'],
    'sections' => [
        [
            'type' => 'intro',
            'title' => '¿Cuál es el mejor banco para una LLC en Estados Unidos?',
            'content' => '<p>Si has constituido (o estás a punto de constituir) una <strong>LLC en Estados Unidos</strong> siendo no residente, el siguiente paso crítico es abrir una <strong>cuenta bancaria de empresa</strong>. La buena noticia: ya no necesitas viajar a EE.UU. Existen neobancos que permiten la apertura <strong>100% online</strong>, en dólares y sin comisiones de mantenimiento.</p><p>En esta guía comparamos las mejores opciones reales para fundadores internacionales —<strong>Mercury, Relay, Brex, Wise y Payoneer</strong>— y recopilamos los <strong>códigos amigo activos</strong> para que ahorres o ganes dinero al registrarte.</p>' . relacionadas_html([$g_offshore, $g_freelance]),
        ],
        [
            'type' => 'steps',
            'title' => 'Top bancos para tu LLC',
            'steps' => [
                ['title' => '1. Mercury — El favorito de las startups', 'text' => 'Apertura 100% remota para LLC y C-Corp, cuenta en USD con routing ABA, tarjetas virtuales ilimitadas y cero comisión mensual. La opción más popular entre fundadores tech.'],
                ['title' => '2. Relay — El mejor para organizar el dinero', 'text' => 'Permite crear hasta 20 cuentas y 50 tarjetas, ideal para presupuestar con el método Profit First (impuestos, nómina, beneficios). Sin comisiones.'],
                ['title' => '3. Brex — Para empresas con volumen', 'text' => 'Cuenta de tesorería y tarjeta corporativa con límites basados en ingresos, no en historial personal. Pensado para startups con tracción.'],
                ['title' => '4. Wise Business — Multidivisa y bajo coste', 'text' => 'Cuenta multidivisa con datos bancarios en USD, EUR, GBP y más. Perfecta como complemento para cobrar y pagar internacionalmente al mejor cambio.'],
                ['title' => '5. Payoneer — Para cobrar de marketplaces y clientes', 'text' => 'Recibe pagos en dólares con datos bancarios locales. Muy útil si tu LLC factura a Amazon, Upwork u otros marketplaces.'],
            ],
        ],
        [
            'type' => 'pros_cons',
            'title' => 'Ventajas e inconvenientes de la banca online para LLC',
            'items' => [
                'pros' => [
                    'Apertura totalmente remota, sin viajar a EE.UU.',
                    'Cuenta en dólares con routing y account number reales',
                    'Sin comisiones de mantenimiento en la mayoría',
                    'Tarjetas virtuales y físicas para gastos del negocio',
                    'Integración con software de contabilidad (QuickBooks, Xero)',
                ],
                'cons' => [
                    'Pueden pedir EIN y documentación de la LLC',
                    'Algunos no admiten ciertos países de residencia',
                    'El soporte suele ser solo en inglés',
                    'No todos ofrecen ingreso de efectivo',
                ],
            ],
        ],
        [
            'type' => 'faq',
            'title' => 'Preguntas frecuentes sobre bancos para LLC',
            'faqs' => [
                ['question' => '¿Puedo abrir una cuenta para mi LLC sin ir a Estados Unidos?', 'answer' => 'Sí. Neobancos como Mercury o Relay permiten abrir cuenta de empresa de forma 100% online aportando los documentos de tu LLC y el EIN, sin necesidad de viajar.'],
                ['question' => '¿Necesito un EIN para abrir la cuenta?', 'answer' => 'En la mayoría de casos sí. El EIN es el número de identificación fiscal de tu empresa ante el IRS y casi todos los bancos lo requieren para abrir la cuenta business.'],
                ['question' => '¿Mercury y Relay son bancos reales?', 'answer' => 'Son fintech que operan con bancos asegurados por la FDIC. Tus fondos se mantienen en bancos partner con cobertura FDIC, normalmente hasta 250.000$.'],
                ['question' => '¿Cuál es la diferencia entre Mercury y Relay?', 'answer' => 'Mercury destaca por su experiencia premium y herramientas de tesorería; Relay destaca por permitir múltiples cuentas y tarjetas para organizar el dinero por categorías.'],
            ],
        ],
    ],
];

// ============================================================
// GUÍA 2: Bancos para empresa offshore
// ============================================================
$guias[] = [
    'slug' => 'mejores-bancos-empresa-offshore',
    'title' => 'Mejores Bancos para Empresa Offshore (2026)',
    'type' => 'collection',
    'meta_title' => 'Mejores Bancos para Empresa Offshore 2026 | Cuentas Multidivisa',
    'meta_description' => 'Las mejores cuentas y neobancos para tu empresa internacional u offshore: Wise, Payoneer, Qonto y Revolut Business. Multidivisa, IBAN y apertura online.',
    'status' => 'active',
    'hero_image' => 'https://images.unsplash.com/photo-1507679799987-c73779587ccf?auto=format&fit=crop&w=1200&q=80',
    'linked_brand_slugs' => ['wise', 'payoneer', 'qonto', 'revolut'],
    'sections' => [
        [
            'type' => 'intro',
            'title' => '¿Qué banco usar para una empresa internacional u offshore?',
            'content' => '<p>Gestionar una <strong>empresa internacional</strong> implica cobrar y pagar en varias divisas, a menudo a clientes y proveedores de distintos países. La banca tradicional castiga estas operaciones con comisiones altas y cambios de divisa desfavorables.</p><p>Los <strong>neobancos multidivisa</strong> —<strong>Wise, Payoneer, Qonto y Revolut Business</strong>— resuelven esto con cuentas en múltiples monedas, datos bancarios locales y tipos de cambio reales. En esta guía los comparamos y recopilamos los <strong>códigos amigo</strong> disponibles.</p><p><em>Nota: esta guía es informativa. Cumple siempre con tus obligaciones fiscales y de reporte (CRS, modelo 720, etc.) en tu país de residencia.</em></p>' . relacionadas_html([$g_llc, $g_freelance]),
        ],
        [
            'type' => 'steps',
            'title' => 'Top cuentas para empresa internacional',
            'steps' => [
                ['title' => '1. Wise Business — La referencia multidivisa', 'text' => 'Cuenta con datos bancarios locales en USD, EUR, GBP y más de 9 monedas. Cambio al tipo medio del mercado y comisiones transparentes.'],
                ['title' => '2. Payoneer — Cobros globales', 'text' => 'Receiving accounts en varias divisas para cobrar como local. Muy extendido en marketplaces y plataformas freelance.'],
                ['title' => '3. Qonto — Cuenta business europea', 'text' => 'IBAN europeo, tarjetas para el equipo y contabilidad integrada. Ideal si tu empresa opera dentro de la UE.'],
                ['title' => '4. Revolut Business — Flexible y digital', 'text' => 'Cuentas multidivisa, cambio competitivo, tarjetas y herramientas de gasto para equipos. Apertura rápida.'],
            ],
        ],
        [
            'type' => 'pros_cons',
            'title' => 'Lo bueno y lo mejorable de la banca para empresa offshore',
            'items' => [
                'pros' => [
                    'Cuentas en múltiples divisas con datos bancarios locales',
                    'Cambio de divisa a tipos reales, sin sobrecoste oculto',
                    'Apertura online y gestión desde el móvil',
                    'Tarjetas para gastos y equipo',
                ],
                'cons' => [
                    'No sustituyen siempre a un banco local tradicional',
                    'Pueden requerir documentación reforzada (KYC) de la empresa',
                    'Disponibilidad según país de constitución y residencia',
                    'Obligaciones fiscales y de reporte siguen siendo tuyas',
                ],
            ],
        ],
        [
            'type' => 'faq',
            'title' => 'Preguntas frecuentes sobre banca offshore',
            'faqs' => [
                ['question' => '¿Es legal tener una cuenta para una empresa offshore?', 'answer' => 'Tener una empresa internacional y su cuenta bancaria es legal siempre que declares tus ingresos y cumplas las obligaciones fiscales y de reporte (como el CRS o el modelo 720 en España) de tu país de residencia.'],
                ['question' => '¿Qué cuenta multidivisa tiene mejor cambio?', 'answer' => 'Wise es conocida por aplicar el tipo de cambio medio del mercado con una comisión transparente, normalmente más barato que la banca tradicional y muchos competidores.'],
                ['question' => '¿Puedo cobrar en dólares y euros con la misma cuenta?', 'answer' => 'Sí. Wise, Payoneer y Revolut Business ofrecen datos bancarios locales en varias divisas, por lo que puedes recibir USD y EUR (entre otras) en la misma cuenta.'],
            ],
        ],
    ],
];

// ============================================================
// GUÍA 3: Cuentas multidivisa para autónomos y freelance
// ============================================================
$guias[] = [
    'slug' => 'cuentas-multidivisa-autonomos-freelance',
    'title' => 'Mejores Cuentas Multidivisa para Autónomos y Freelance (2026)',
    'type' => 'collection',
    'meta_title' => 'Cuentas Multidivisa para Autónomos y Freelance 2026 | Comparativa',
    'meta_description' => 'Las mejores cuentas para freelancers que cobran de clientes internacionales: Wise, Revolut, Payoneer, Qonto, Deel y N26. Cobra en USD, EUR y GBP al mejor cambio.',
    'status' => 'active',
    'hero_image' => 'https://images.unsplash.com/photo-1521791136064-7986c2920216?auto=format&fit=crop&w=1200&q=80',
    'linked_brand_slugs' => ['wise', 'revolut', 'payoneer', 'qonto', 'deel', 'n26'],
    'sections' => [
        [
            'type' => 'intro',
            'title' => '¿Cuál es la mejor cuenta para un autónomo o freelance que cobra del extranjero?',
            'content' => '<p>Si eres <strong>autónomo o freelance</strong> y trabajas con clientes internacionales, cada transferencia en moneda extranjera puede comerse una parte de tus ingresos por comisiones y mal cambio. Una buena <strong>cuenta multidivisa</strong> te permite cobrar como local en USD, EUR o GBP y convertir al mejor tipo.</p><p>Comparamos las mejores opciones para perfiles freelance —<strong>Wise, Revolut, Payoneer, Qonto, Deel y N26</strong>— con sus <strong>códigos amigo</strong> para empezar ahorrando.</p>' . relacionadas_html([$g_llc, $g_offshore]),
        ],
        [
            'type' => 'steps',
            'title' => 'Top cuentas para freelancers',
            'steps' => [
                ['title' => '1. Wise — El mejor cambio de divisa', 'text' => 'Datos bancarios locales en múltiples monedas y cambio al tipo medio del mercado. Imprescindible para cobrar de fuera de la zona euro.'],
                ['title' => '2. Payoneer — Para marketplaces y plataformas', 'text' => 'Cobra de Upwork, Fiverr, Amazon o clientes directos con receiving accounts en USD, EUR y GBP.'],
                ['title' => '3. Deel — Para contractors internacionales', 'text' => 'Si trabajas como contractor para empresas extranjeras, Deel gestiona contratos, facturas y pagos, con tarjeta y retirada en moneda local.'],
                ['title' => '4. Revolut — Multidivisa para el día a día', 'text' => 'Cambio competitivo, tarjetas y subcuentas por divisa. Cómodo para gastar y viajar.'],
                ['title' => '5. Qonto — Si quieres cuenta business europea', 'text' => 'IBAN, facturación y contabilidad integrada, pensado para autónomos y pymes en la UE.'],
                ['title' => '6. N26 — Cuenta principal con IBAN español', 'text' => 'Cuenta sencilla con Bizum e IBAN ES, útil como cuenta principal complementaria.'],
            ],
        ],
        [
            'type' => 'pros_cons',
            'title' => 'Ventajas e inconvenientes para freelancers',
            'items' => [
                'pros' => [
                    'Cobra de clientes internacionales como si fueras local',
                    'Tipos de cambio reales, sin sorpresas',
                    'Tarjetas para gastos del negocio',
                    'Apps modernas y gestión 100% móvil',
                    'Muchas integran facturación y contabilidad',
                ],
                'cons' => [
                    'Algunas cobran comisión por retirada o conversión rápida',
                    'No todas tienen IBAN español (revisa Bizum)',
                    'Debes seguir declarando tus ingresos como autónomo',
                ],
            ],
        ],
        [
            'type' => 'faq',
            'title' => 'Preguntas frecuentes para autónomos y freelance',
            'faqs' => [
                ['question' => '¿Cómo cobro a un cliente de Estados Unidos siendo autónomo en España?', 'answer' => 'Con una cuenta multidivisa como Wise o Payoneer obtienes datos bancarios en USD; tu cliente paga como una transferencia local estadounidense y tú recibes los dólares, que puedes convertir a euros al mejor cambio.'],
                ['question' => '¿Qué cuenta tiene Bizum para autónomos?', 'answer' => 'Las cuentas con IBAN español, como N26, suelen incluir Bizum. Wise y Revolut con IBAN europeo no siempre lo soportan, por lo que muchos freelancers combinan una cuenta multidivisa con una cuenta española.'],
                ['question' => '¿Tengo que declarar lo que cobro en estas cuentas?', 'answer' => 'Sí. Todos tus ingresos como autónomo deben declararse, independientemente de la cuenta o divisa en la que los recibas.'],
            ],
        ],
    ],
];

// ============================================================
// Insertar / actualizar
// ============================================================
foreach ($guias as $data) {
    $data['linked_brand_ids'] = brand_ids_for($data['linked_brand_slugs'], $marcas_coll);
    $data['updated_at'] = new MongoDB\BSON\UTCDateTime();

    $existing = $collection->findOne(['slug' => $data['slug']]);
    if ($existing) {
        $collection->updateOne(['slug' => $data['slug']], ['$set' => $data]);
        echo "Actualizada guía: {$data['slug']} (" . count($data['linked_brand_ids']) . " marcas vinculadas)\n";
    } else {
        $data['created_at'] = new MongoDB\BSON\UTCDateTime();
        $r = $collection->insertOne($data);
        echo "Insertada guía: {$data['slug']} (" . count($data['linked_brand_ids']) . " marcas vinculadas) ID " . $r->getInsertedId() . "\n";
    }
}

echo "Hecho.\n";
