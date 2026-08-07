<?php
/**
 * Detecta marcas con demanda en Google para las que NO tenemos ficha /de-{slug}.
 *
 * Google nos enseña para ~2.100 queries de marca que no tienen página propia.
 * Al no encontrar ficha sirve la home, que sale en posición 60-80 y no se lleva
 * ni un clic (24.237 impresiones / 90 días con 0 clics a 2026-08-07). Cada una
 * de esas marcas es una ficha que falta.
 *
 * El cruce no puede ser por igualdad de slug: la query "codigo descuento honest
 * greens" normaliza a "honestgreens" pero la ficha real es
 * /de-honestgreenrestaurant, y "codigo descuento emma colchón" normaliza a
 * "emmacolchon" cuando la marca en BD es "emma". Por eso se hace matching
 * difuso y la salida separa tres cubos:
 *
 *   - CON FICHA      -> ya cubierta, no se toca
 *   - POSIBLE ALIAS  -> hay marca parecida; candidata a redirect 301, revisar
 *   - HUERFANA       -> no existe nada parecido; candidata a alta de marca
 *
 * Uso:
 *   php cron/detectar_marcas_huerfanas.php [--dias=90] [--min-impr=100]
 *                                          [--limit=60] [--json] [--todas]
 * Cron sugerido: semanal (lunes, tras el informe GSC).
 */

define('CRON_MODE', true);
require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones_marca.php';

$opts      = getopt('', ['dias:', 'min-impr:', 'limit:', 'json', 'todas']);
$dias      = isset($opts['dias'])     ? max(7, (int)$opts['dias'])   : 90;
$min_impr  = isset($opts['min-impr']) ? max(1, (int)$opts['min-impr']) : 100;
$limit     = isset($opts['limit'])    ? max(1, (int)$opts['limit'])  : 60;
$como_json = isset($opts['json']);
$todas     = isset($opts['todas']); // no recorta al limit

// ─── Normalización ───

/** minúsculas + sin tildes + solo alfanumérico. "Emma Colchón" -> "emmacolchon" */
function mh_normalizar($txt) {
    $txt = mb_strtolower(trim((string)$txt), 'UTF-8');
    $txt = strtr($txt, [
        'á'=>'a','à'=>'a','ä'=>'a','â'=>'a','ã'=>'a',
        'é'=>'e','è'=>'e','ë'=>'e','ê'=>'e',
        'í'=>'i','ì'=>'i','ï'=>'i','î'=>'i',
        'ó'=>'o','ò'=>'o','ö'=>'o','ô'=>'o','õ'=>'o',
        'ú'=>'u','ù'=>'u','ü'=>'u','û'=>'u',
        'ñ'=>'n','ç'=>'c',
    ]);
    return preg_replace('/[^a-z0-9]/', '', $txt);
}

// Palabras que forman la intención de búsqueda, no la marca. Se descartan al
// extraer el nombre: de "código promocional bunq 2026" queda "bunq".
$RUIDO = [
    'codigo','codigos','cupon','cupones','descuento','descuentos','promocional',
    'promocionales','promo','promocion','promociones','oferta','ofertas','gratis',
    'amigo','amigos','invitacion','referido','referidos','rebajas','black','friday',
    'code','coupon','discount','vale','vales','bono','bonos','primera','compra',
    'nuevos','nuevo','clientes','cliente','actual','actuales','activo','activos',
    'valido','validos','hoy','mes','ano','anos',
    '2019','2020','2021','2022','2023','2024','2025','2026','2027',
    'de','del','la','el','los','las','para','en','y','o','con','sin','por','a','al',
    'que','como','cual','donde','mi','tu','su','un','una','es','se','me','te',
];

// Restos que no son marca aunque sobrevivan al filtro anterior.
$GENERICOS = [
    'mejores','mejor','todos','todas','ninguno','web','webs','pagina','paginas',
    'app','apps','online','espana','tienda','tiendas','envio','envios','saldo',
    'dinero','euros','euro','ahorro','ahorrar','trucos','truco','listado','lista',
    'sitio','sitios','buscar','busqueda','nuevo','nueva','actualizado','funciona',
    'funcionan','sirve','sirven','conseguir','usar','canjear','aplicar','poner',
];

/**
 * Busca la marca existente más parecida al candidato.
 * Devuelve [slug, motivo] o null.
 */
function mh_buscar_parecida($cand, array $indice) {
    // 1. Igualdad exacta de slug normalizado
    if (isset($indice[$cand])) {
        return [$indice[$cand], 'exacta'];
    }

    $mejor = null;
    $mejor_score = 0;

    foreach ($indice as $norm => $slug) {
        if ($norm === '' || $cand === '') continue;

        // 2. Prefijo común largo: cubre "honestgreens" -> "honestgreenrestaurant"
        //    y "ubereats" -> "ubereatsespana". Exigimos que el prefijo cubra casi
        //    todo el candidato para no casar "moo" con "moovit".
        $pref = 0;
        $max  = min(strlen($cand), strlen($norm));
        while ($pref < $max && $cand[$pref] === $norm[$pref]) $pref++;
        if ($pref >= 7 && $pref >= 0.75 * strlen($cand)) {
            $score = 100 * $pref / max(strlen($cand), strlen($norm));
            if ($score > $mejor_score) { $mejor = [$slug, "prefijo($pref)"]; $mejor_score = $score; }
            continue;
        }

        // 3. La marca en BD es prefijo del candidato: "emma" -> "emmacolchon".
        //    Mínimo 4 caracteres para que "moo" no se coma medio catálogo.
        if (strlen($norm) >= 4 && str_starts_with($cand, $norm)) {
            $score = 100 * strlen($norm) / strlen($cand);
            if ($score > $mejor_score) { $mejor = [$slug, 'marca-es-prefijo']; $mejor_score = $score; }
            continue;
        }

        // 4. Parecido global alto: erratas y separadores ("recordgo"/"record-go").
        if (abs(strlen($cand) - strlen($norm)) <= 3 && strlen($cand) >= 5) {
            similar_text($cand, $norm, $pct);
            if ($pct >= 88 && $pct > $mejor_score) { $mejor = [$slug, 'similar(' . round($pct) . '%)']; $mejor_score = $pct; }
        }
    }

    return $mejor;
}

// ─── 1. Índice de marcas que ya existen ───

$db  = createConnection();
$col = $db->selectCollection('marcas');

$indice = []; // slug normalizado => nombre_clave real
foreach ($col->find([], ['projection' => ['nombre_clave' => 1, 'nombre' => 1]]) as $m) {
    $clave = (string)($m['nombre_clave'] ?? '');
    if ($clave === '') continue;
    $indice[mh_normalizar($clave)] = $clave;
    if (!empty($m['nombre'])) {
        $n = mh_normalizar($m['nombre']);
        if ($n !== '' && !isset($indice[$n])) $indice[$n] = $clave;
    }
}

// Los redirects ya resueltos tampoco son huérfanas.
foreach ($db->selectCollection('redirects')->find([], ['projection' => ['old_brand_key' => 1, 'new_brand_key' => 1]]) as $r) {
    $old = mh_normalizar($r['old_brand_key'] ?? '');
    if ($old !== '' && !isset($indice[$old])) $indice[$old] = (string)($r['new_brand_key'] ?? $r['old_brand_key']);
}

$total_marcas = count($indice);

// ─── 2. Queries de GSC ───

$credsPath = dirname(__DIR__, 2) . '/private/google_credentials.json';
if (!file_exists($credsPath)) {
    log_error('[marcas_huerfanas] sin credenciales GSC');
    exit(1);
}

try {
    $client = new Google\Client();
    $client->setAuthConfig($credsPath);
    $client->addScope('https://www.googleapis.com/auth/webmasters.readonly');
    $svc = new Google\Service\SearchConsole($client);

    $req = new Google\Service\SearchConsole\SearchAnalyticsQueryRequest();
    // GSC arrastra ~3 días de lag; pedir hasta hoy devuelve días vacíos.
    $req->setStartDate(date('Y-m-d', strtotime("-" . ($dias + 3) . " days")));
    $req->setEndDate(date('Y-m-d', strtotime('-3 days')));
    $req->setDimensions(['query']);
    $req->setRowLimit(25000);
    $rows = $svc->searchanalytics->query('sc-domain:codigoamigo.com', $req)->getRows() ?: [];
} catch (\Throwable $e) {
    log_error('[marcas_huerfanas] error GSC: ' . $e->getMessage());
    exit(1);
}

// ─── 3. Agregar impresiones por marca candidata ───

$agg = [];
foreach ($rows as $row) {
    $query = mb_strtolower($row->getKeys()[0], 'UTF-8');

    // Solo intención transaccional de cupón: el resto no busca una ficha de marca.
    if (!preg_match('/(codigo|código|cupon|cupón|descuento|promocional|promo code)/u', $query)) {
        continue;
    }

    $palabras = preg_split('/[^\p{L}\p{N}]+/u', $query, -1, PREG_SPLIT_NO_EMPTY);
    $restantes = [];
    foreach ($palabras as $p) {
        $pn = mh_normalizar($p);
        if ($pn === '' || in_array($pn, $RUIDO, true)) continue;
        $restantes[] = $pn;
    }
    if (!$restantes) continue;

    $cand = implode('', $restantes);
    if (strlen($cand) < 4) continue;                       // "moo", "hsn" sueltos dan demasiado ruido
    if (count($restantes) === 1 && in_array($cand, $GENERICOS, true)) continue;

    if (!isset($agg[$cand])) {
        $agg[$cand] = ['impr' => 0, 'clicks' => 0, 'pos_pond' => 0, 'ejemplos' => []];
    }
    $agg[$cand]['impr']     += $row->getImpressions();
    $agg[$cand]['clicks']   += $row->getClicks();
    $agg[$cand]['pos_pond'] += $row->getPosition() * $row->getImpressions();
    if (count($agg[$cand]['ejemplos']) < 3) $agg[$cand]['ejemplos'][] = $row->getKeys()[0];
}

// ─── 4. Clasificar ───

$con_ficha = [];
$alias     = [];
$huerfanas = [];

foreach ($agg as $cand => $d) {
    if ($d['impr'] < $min_impr) continue;

    $d['clave']  = $cand;
    $d['pos']    = $d['impr'] ? round($d['pos_pond'] / $d['impr'], 1) : 0;
    unset($d['pos_pond']);

    $match = mh_buscar_parecida($cand, $indice);
    if ($match === null) {
        $huerfanas[] = $d;
    } elseif ($match[1] === 'exacta') {
        $d['ficha'] = $match[0];
        $con_ficha[] = $d;
    } else {
        $d['ficha']  = $match[0];
        $d['motivo'] = $match[1];
        $alias[] = $d;
    }
}

$por_impr = fn($a, $b) => $b['impr'] <=> $a['impr'];
usort($huerfanas, $por_impr);
usort($alias, $por_impr);
usort($con_ficha, $por_impr);

$suma = fn(array $l) => array_sum(array_column($l, 'impr'));

// ─── 5. Salida ───

if ($como_json) {
    echo json_encode([
        'generado'   => date('c'),
        'dias'       => $dias,
        'min_impr'   => $min_impr,
        'marcas_bd'  => $total_marcas,
        'huerfanas'  => $todas ? $huerfanas : array_slice($huerfanas, 0, $limit),
        'alias'      => $todas ? $alias     : array_slice($alias, 0, $limit),
        'resumen'    => [
            'huerfanas' => ['n' => count($huerfanas), 'impr' => $suma($huerfanas)],
            'alias'     => ['n' => count($alias),     'impr' => $suma($alias)],
            'con_ficha' => ['n' => count($con_ficha), 'impr' => $suma($con_ficha)],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    exit(0);
}

printf("Marcas en BD: %d | queries GSC analizadas: %d | ventana: %d días | corte: %d impresiones\n\n",
    $total_marcas, count($rows), $dias, $min_impr);

printf("=== HUÉRFANAS: %d marcas, %d impresiones sin ficha ===\n", count($huerfanas), $suma($huerfanas));
foreach (array_slice($huerfanas, 0, $limit) as $h) {
    printf("  %-26s impr=%-6d clk=%-4d pos=%-5.1f  %s\n", $h['clave'], $h['impr'], $h['clicks'], $h['pos'], $h['ejemplos'][0] ?? '');
}

printf("\n=== POSIBLE ALIAS (revisar antes de redirigir): %d, %d impresiones ===\n", count($alias), $suma($alias));
foreach (array_slice($alias, 0, $limit) as $a) {
    printf("  %-26s -> /de-%-24s impr=%-6d pos=%-5.1f [%s]\n", $a['clave'], $a['ficha'], $a['impr'], $a['pos'], $a['motivo']);
}

printf("\n=== YA CON FICHA: %d marcas, %d impresiones ===\n", count($con_ficha), $suma($con_ficha));

log_info('[marcas_huerfanas] análisis completado', [
    'huerfanas' => count($huerfanas),
    'alias'     => count($alias),
    'impr_perdidas' => $suma($huerfanas) + $suma($alias),
]);
