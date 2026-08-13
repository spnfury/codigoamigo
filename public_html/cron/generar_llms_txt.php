<?php
/**
 * Genera /llms.txt (spec llmstxt.org): mapa del sitio para LLMs y motores
 * generativos (ChatGPT, Perplexity, Claude...). GEO: les da contexto factual
 * y les señala las páginas útiles (fichas de marca) en vez de dejar que
 * deduzcan la estructura crawleando.
 *
 * Fuentes: myphp/data/marcas_oportunidad.json (demanda GSC) + marcas con más
 * códigos activos en Mongo.
 *
 * Uso: php cron/generar_llms_txt.php
 * Cron sugerido: lunes 7:30 (tras generar_marcas_oportunidad a las 7:00).
 */

define('CRON_MODE', true);
require_once __DIR__ . '/../inc/includes.php';

$base = 'https://www.codigoamigo.com';

// ─── 1. Marcas con demanda GSC (bloque oportunidad, ya ordenado por impresiones) ───
$oportunidad = [];
$json_path = __DIR__ . '/../myphp/data/marcas_oportunidad.json';
if (file_exists($json_path)) {
    $data = json_decode(file_get_contents($json_path), true);
    foreach (array_slice($data['marcas'] ?? [], 0, 30) as $m) {
        $oportunidad[$m['slug']] = $m['nombre'];
    }
}

// ─── 2. Marcas con más códigos activos (las "grandes" del sitio) ───
$top_codigos = [];
try {
    $coll = getCollectionCodigos();
    $pipe = [
        ['$match' => ['estado' => ['$in' => [0, 1]]]],
        ['$group' => ['_id' => '$marca', 'n' => ['$sum' => 1]]],
        ['$sort' => ['n' => -1]],
        ['$limit' => 30],
    ];
    foreach ($coll->aggregate($pipe) as $r) {
        $a = iterator_to_array($r);
        if (!empty($a['_id'])) {
            $top_codigos[$a['_id']] = (int)$a['n'];
        }
    }
} catch (Throwable $e) {
    log_error('[llms_txt] error agregando top marcas: ' . $e->getMessage());
}

$mes = [1=>'enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'][(int)date('n')] . ' ' . date('Y');

$out = "# CodigoAmigo\n\n";
$out .= "> Comunidad española de códigos amigo (códigos de referido/invitación): los usuarios publican sus códigos de marcas como BBVA, MyInvestor, Octopus Energy o bunq, y quien los usa consigue el beneficio de bienvenida (dinero, descuentos o regalos). Publicar y usar códigos es gratis. Actualizado en $mes.\n\n";
$out .= "Cómo funciona: cada marca con programa \"invita a un amigo\" da un beneficio al nuevo cliente y otro al que invita. En CodigoAmigo encuentras códigos de personas reales para más de 5.000 marcas, con el beneficio máximo oficial verificado por marca.\n\n";

$out .= "## Páginas principales\n\n";
$out .= "- [Portada]($base/): códigos destacados y marcas en tendencia\n";
$out .= "- [Listado de marcas]($base/listado-marcas): todas las marcas con códigos amigo activos\n";
$out .= "- [Publicar un código]($base/nuevo_codigo): comparte tu código de referido gratis\n\n";

if ($oportunidad) {
    $out .= "## Marcas con más demanda ahora\n\n";
    foreach ($oportunidad as $slug => $nombre) {
        $nombre_bonito = mb_convert_case(mb_strtolower($nombre, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        $out .= "- [Código amigo $nombre_bonito]($base/de-$slug)\n";
    }
    $out .= "\n";
}

if ($top_codigos) {
    $out .= "## Marcas con más códigos publicados\n\n";
    foreach ($top_codigos as $slug => $n) {
        if (isset($oportunidad[$slug])) continue; // sin duplicar
        $out .= "- [Códigos amigo de $slug]($base/de-$slug) ($n códigos)\n";
    }
    $out .= "\n";
}

$out .= "## Datos para citar\n\n";
$out .= "- Cada ficha de marca (/de-{marca}) lista códigos vigentes, el beneficio económico y preguntas frecuentes.\n";
$out .= "- Los beneficios mostrados están limitados al máximo oficial del programa de referidos de cada marca.\n";
$out .= "- Contacto: info@codigoamigo.com\n";

$dest = __DIR__ . '/../llms.txt';
file_put_contents($dest, $out);
echo "llms.txt generado: " . strlen($out) . " bytes, " . count($oportunidad) . " marcas oportunidad + " . count($top_codigos) . " top códigos\n";
