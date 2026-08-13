<?php
/**
 * Seed de marcas fintech para guías de banca de empresa (LLC / offshore / autónomos).
 *
 * Crea (o actualiza) marcas que aún no existían en CodigoAmigo y que se usan en
 * las guías SEO de banca para empresas. Así los publicadores pueden subir sus
 * códigos referidos para estas marcas y se auto-incrustan en las guías.
 *
 * Uso: php seed_marcas_fintech.php
 *
 * Idempotente: upsert por nombre_clave. estado=1 + sin inactiva_seo => marca activa
 * y visible en listados públicos.
 */

require_once __DIR__ . '/inc/conexion.php';

$db = createConnection();
$marcas = $db->selectCollection('marcas');

$categoria = 'Banca y Criptomonedas';
$categoria_clave = 'banca y criptomonedas';
$hoy = date('d-m-Y H:i');

$nuevas = [
    [
        'nombre_clave' => 'mercury',
        'nombre' => 'MERCURY',
        'imagen' => 'https://logo.clearbit.com/mercury.com',
        'descripción' => '<p><strong>Mercury</strong> es la banca online favorita de las startups y empresas con <strong>LLC en Estados Unidos</strong>. Ofrece cuentas en USD con números de routing y account ABA, tarjetas virtuales y físicas, y todo 100% online sin necesidad de viajar a EE.UU.</p>',
        'descripción_larga' => '<p>Mercury se ha convertido en el banco de referencia para fundadores que constituyen una <strong>LLC</strong> o <strong>C-Corp</strong> en Estados Unidos. Permite abrir una cuenta bancaria estadounidense de forma totalmente remota, sin coste de mantenimiento y con una experiencia de usuario pensada para emprendedores tecnológicos. Incluye cuentas en dólares, tarjetas de débito virtuales ilimitadas, integración con herramientas contables y, en planes superiores, gestión de tesorería. Es especialmente popular entre creadores de SaaS, agencias y solopreneurs internacionales que facturan en dólares.</p>',
        'descripción_seo' => '<p>Mercury es la cuenta bancaria online para tu LLC en EE.UU.: apertura 100% remota, cuenta en dólares, tarjetas virtuales y cero comisiones de mantenimiento.</p>',
    ],
    [
        'nombre_clave' => 'payoneer',
        'nombre' => 'PAYONEER',
        'imagen' => 'https://logo.clearbit.com/payoneer.com',
        'descripción' => '<p><strong>Payoneer</strong> es una plataforma de pagos globales ideal para <strong>autónomos, freelancers y empresas</strong> que cobran de clientes internacionales. Permite recibir pagos en USD, EUR y GBP con datos bancarios locales en cada divisa.</p>',
        'descripción_larga' => '<p>Payoneer es uno de los servicios de cobro internacional más usados del mundo. Ofrece cuentas de recepción (receiving accounts) con IBAN europeo, routing estadounidense y sort code británico, lo que te permite cobrar como si fueras local en cada país. Es muy popular entre freelancers de plataformas como Upwork o Fiverr, marketplaces (Amazon, Etsy) y empresas con clientes en el extranjero. Incluye tarjeta Mastercard, transferencias entre usuarios sin comisión y conversión de divisas a tipos competitivos.</p>',
        'descripción_seo' => '<p>Payoneer te permite cobrar de clientes internacionales en USD, EUR y GBP con datos bancarios locales. La opción favorita de freelancers y empresas globales.</p>',
    ],
    [
        'nombre_clave' => 'qonto',
        'nombre' => 'QONTO',
        'imagen' => 'https://logo.clearbit.com/qonto.com',
        'descripción' => '<p><strong>Qonto</strong> es el neobanco para empresas y autónomos líder en Europa. Cuenta business con IBAN, tarjetas para el equipo, gestión de gastos y contabilidad integrada, todo desde una app.</p>',
        'descripción_larga' => '<p>Qonto está diseñado específicamente para <strong>autónomos, pymes y startups</strong> europeas. Ofrece una cuenta business con IBAN español/europeo, múltiples tarjetas físicas y virtuales para el equipo, gestión de permisos y presupuestos, captura de facturas, integración con herramientas de contabilidad y conciliación automática. Es una alternativa moderna a la banca tradicional para empresas, con apertura rápida y soporte en español. Ideal para quienes quieren separar finanzas personales y de negocio con orden.</p>',
        'descripción_seo' => '<p>Qonto es la cuenta business para autónomos y empresas en Europa: IBAN, tarjetas para el equipo y contabilidad integrada en una sola app.</p>',
    ],
    [
        'nombre_clave' => 'brex',
        'nombre' => 'BREX',
        'imagen' => 'https://logo.clearbit.com/brex.com',
        'descripción' => '<p><strong>Brex</strong> ofrece cuentas de empresa y tarjetas corporativas para startups con <strong>LLC o C-Corp en EE.UU.</strong>, con límites altos, recompensas y gestión de gastos sin necesidad de garantía personal.</p>',
        'descripción_larga' => '<p>Brex es una plataforma financiera para empresas estadounidenses, muy usada por startups respaldadas por capital riesgo. Combina cuenta de tesorería, tarjeta corporativa con límites basados en los ingresos de la empresa (no en historial crediticio personal), software de gestión de gastos y pagos. Ofrece recompensas, integraciones contables y control de gasto por departamento. Es una alternativa premium a Mercury para empresas con mayor volumen.</p>',
        'descripción_seo' => '<p>Brex es la cuenta y tarjeta corporativa para startups con empresa en EE.UU.: límites altos, recompensas y gestión de gastos sin garantía personal.</p>',
    ],
    [
        'nombre_clave' => 'relay',
        'nombre' => 'RELAY',
        'imagen' => 'https://logo.clearbit.com/relayfi.com',
        'descripción' => '<p><strong>Relay</strong> es una banca online para pequeñas empresas y LLC en EE.UU. que permite abrir <strong>hasta 20 cuentas</strong> y 50 tarjetas para organizar el dinero por categorías, ideal para el método "Profit First".</p>',
        'descripción_larga' => '<p>Relay es un neobanco estadounidense pensado para pequeñas empresas y dueños de <strong>LLC</strong> que quieren control granular de su dinero. Su gran diferencia es poder crear hasta 20 cuentas individuales y 50 tarjetas de débito, perfecto para presupuestar con metodologías como Profit First (separar impuestos, nómina, beneficios, etc.). Apertura remota, sin comisiones mensuales y con integración con QuickBooks y Xero. Una alternativa sólida a Mercury para quienes priorizan la organización financiera.</p>',
        'descripción_seo' => '<p>Relay es la banca online para tu LLC con hasta 20 cuentas y 50 tarjetas: organiza el dinero por categorías sin comisiones. Ideal para Profit First.</p>',
    ],
    [
        'nombre_clave' => 'deel',
        'nombre' => 'DEEL',
        'imagen' => 'https://logo.clearbit.com/deel.com',
        'descripción' => '<p><strong>Deel</strong> es la plataforma global para contratar y cobrar como contractor internacional. Permite a <strong>autónomos y freelancers</strong> facturar a empresas de todo el mundo y recibir el pago en su moneda o en una tarjeta Deel.</p>',
        'descripción_larga' => '<p>Deel es líder mundial en contratación y pagos internacionales. Para empresas, facilita contratar trabajadores y contractors en más de 150 países cumpliendo la normativa local. Para <strong>freelancers y autónomos</strong>, ofrece una cuenta para recibir pagos de clientes extranjeros, retirar en moneda local, una tarjeta Deel y herramientas para gestionar contratos y facturas. Muy útil para perfiles remotos que trabajan con empresas internacionales.</p>',
        'descripción_seo' => '<p>Deel permite a freelancers cobrar de empresas de todo el mundo y a las empresas contratar talento global cumpliendo la normativa local.</p>',
    ],
];

foreach ($nuevas as $m) {
    $doc = [
        'estado' => 1,
        'nombre' => $m['nombre'],
        'nombre_clave' => $m['nombre_clave'],
        'categoria' => $categoria,
        'categoria_clave' => $categoria_clave,
        'imagen' => $m['imagen'],
        'descripción' => $m['descripción'],
        'descripción_larga' => $m['descripción_larga'],
        'descripción_seo' => $m['descripción_seo'],
        'url' => '',
        'url_register' => '',
        'aviso' => 'revisada',
        'inactiva_seo' => false,
        'fecha_publicacion' => $hoy,
    ];

    $existing = $marcas->findOne(['nombre_clave' => $m['nombre_clave']]);
    if ($existing) {
        // No pisar campos que pudiera haber editado un admin: solo set de los nuestros
        $marcas->updateOne(['nombre_clave' => $m['nombre_clave']], ['$set' => $doc]);
        echo "Actualizada marca: {$m['nombre_clave']}\n";
    } else {
        $r = $marcas->insertOne($doc);
        echo "Creada marca: {$m['nombre_clave']} (" . $r->getInsertedId() . ")\n";
    }
}

echo "Hecho.\n";
