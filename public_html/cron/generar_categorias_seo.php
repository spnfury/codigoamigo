<?php
/**
 * Genera contenido SEO (intro HTML + FAQs) para las páginas de categoría
 * (/{slug}-comparte-y-gana), que hoy son thin content. Escribe en la colección
 * `categorias`: campos `seo_html` y `seo_faqs` (array de {q,a}).
 *
 * Las 16-17 páginas de categoría apuntan a términos de cabecera de alto volumen
 * ("códigos descuento {categoría}") y diversifican el tráfico, hoy concentrado
 * en unas pocas marcas.
 *
 * Uso:
 *   php cron/generar_categorias_seo.php                 → todas las que falten
 *   php cron/generar_categorias_seo.php --cat=cursos     → solo una (nombre_clave)
 *   php cron/generar_categorias_seo.php --force          → regenera aunque ya tenga
 *   php cron/generar_categorias_seo.php --dry-run        → no escribe, muestra
 */

define('CRON_MODE', true);
require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../config/ai_config.php';

$opts  = getopt('', ['cat:', 'force', 'dry-run']);
$solo  = $opts['cat'] ?? null;
$force = isset($opts['force']);
$dry   = isset($opts['dry-run']);

$db = createConnection();

$filtro = ['estado' => 1];
if ($solo) {
    $filtro['nombre_clave'] = $solo;
}
$cats = $db->categorias->find($filtro, ['projection' => ['nombre_clave' => 1, 'nombre' => 1, 'seo_html' => 1]])->toArray();

echo "Categorías a revisar: " . count($cats) . "\n";

function generar_categoria_seo(string $nombre): ?array {
    $prompt = "Eres un redactor SEO experto en un portal español de códigos de descuento y referido (CodigoAmigo). "
        . "Genera contenido para la página de la categoría \"$nombre\".\n\n"
        . "Devuelve EXCLUSIVAMENTE un JSON con esta estructura exacta:\n"
        . "{\n"
        . "  \"intro_html\": \"2-3 párrafos en HTML (<p>, <strong>) , 150-220 palabras, explicando qué tipo de marcas y ahorros hay en esta categoría y cómo los códigos amigo/descuento de la comunidad ayudan a ahorrar. Tono natural, español de España, sin inventar marcas ni porcentajes concretos.\",\n"
        . "  \"faqs\": [ {\"q\": \"pregunta\", \"a\": \"respuesta\"}, ... 5 FAQs útiles y específicas de la categoría sobre cómo usar códigos, ahorrar, validez, etc. ]\n"
        . "}\n"
        . "Sin markdown, sin texto fuera del JSON.";

    $res = groq_request([
        'model'       => 'llama-3.3-70b-versatile',
        'messages'    => [['role' => 'user', 'content' => $prompt]],
        'max_tokens'  => 1800,
        'temperature' => 0.7,
        'response_format' => ['type' => 'json_object'],
    ]);
    if (empty($res['content'])) return null;

    $data = json_decode($res['content'], true);
    if (!is_array($data)) return null;

    $intro = trim($data['intro_html'] ?? '');
    if (stripos($intro, '<p') === false || strlen(strip_tags($intro)) < 120) return null;

    $faqs = [];
    foreach (($data['faqs'] ?? []) as $f) {
        $q = trim($f['q'] ?? '');
        $a = trim($f['a'] ?? '');
        if ($q !== '' && $a !== '') $faqs[] = ['q' => $q, 'a' => $a];
    }
    if (count($faqs) < 3) return null;

    return ['seo_html' => $intro, 'seo_faqs' => $faqs];
}

$ok = 0; $skip = 0; $err = 0;
foreach ($cats as $c) {
    $slug   = $c['nombre_clave'] ?? '';
    $nombre = $c['nombre'] ?? $slug;
    if ($slug === '') continue;

    if (!$force && !empty(trim($c['seo_html'] ?? ''))) {
        echo "  [$slug] ya tiene seo_html — skip\n";
        $skip++;
        continue;
    }

    echo "[$slug] ($nombre) generando...\n";
    $gen = generar_categoria_seo($nombre);
    if ($gen === null) {
        echo "  ✗ Groq falló / salida inválida\n";
        $err++;
        sleep(2);
        continue;
    }

    if ($dry) {
        echo "  ⟶ " . substr(strip_tags($gen['seo_html']), 0, 140) . "... (" . count($gen['seo_faqs']) . " FAQs)\n";
    } else {
        $r = $db->categorias->updateMany(
            ['nombre_clave' => $slug, 'estado' => 1],
            ['$set' => [
                'seo_html'  => $gen['seo_html'],
                'seo_faqs'  => $gen['seo_faqs'],
                'seo_generado_at' => new MongoDB\BSON\UTCDateTime(),
            ]]
        );
        echo "  ✓ guardado (" . strlen(strip_tags($gen['seo_html'])) . "ch, " . count($gen['seo_faqs']) . " FAQs) mod=" . $r->getModifiedCount() . "\n";
        $ok++;
    }
    sleep(2);
}

echo "\n=== Fin. OK=$ok skip=$skip err=$err" . ($dry ? " (dry-run)" : "") . " ===\n";
log_info("[generar_categorias_seo] ok=$ok skip=$skip err=$err");
