<?php
/**
 * Panel de apps móviles.
 *
 * Muestra el estado de publicación de todas las apps declaradas en
 * config/apps_moviles.json. No lee los proyectos directamente (el open_basedir
 * del pool PHP no alcanza /home/planazosbcn, /var/www ni ../mobile-app): pinta
 * el JSON que genera cron/recolectar_estado_apps.php.
 */

session_start();

include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';

// Mismos permisos que el resto del panel de administración
$array_codigos_acceso = [
    "58bd851da54e295b8b52f702", // thevega82@gmail.com
    "5e78170e6b68e6519b7c5df2", // edna
    "639899bc6321ee0d0e4010d2", // aron
    "5c8a10ce2f55c86d6e707d82", // jose
];

if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]) || !in_array($_SESSION["user_id"], $array_codigos_acceso, true)) {
    header('Location: https://www.codigoamigo.com');
    die();
}

$ruta_estado = __DIR__ . '/../myphp/data/apps_estado.json';
$estado = is_file($ruta_estado)
    ? json_decode((string)file_get_contents($ruta_estado), true)
    : null;

$apps      = $estado['apps'] ?? [];
$generado  = $estado['generado_human'] ?? null;
$obsoleto  = isset($estado['generado']) && (time() - strtotime($estado['generado'])) > 86400;

function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Apps móviles · CodigoAmigo</title>
<style>
    :root { --rojo:#E30613; --ok:#27ae60; --no:#c0392b; --gris:#6b7280; }
    * { box-sizing: border-box; }
    body { margin:0; background:#f4f5f7; color:#1f2937;
           font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; }
    header { background:#fff; border-bottom:1px solid #e5e7eb; padding:18px 24px; }
    h1 { margin:0; font-size:1.35rem; }
    .sub { color:var(--gris); font-size:.85rem; margin-top:4px; }
    .aviso { background:#fff8e1; border-left:4px solid #f0ad4e; padding:12px 16px;
             margin:16px 24px; border-radius:6px; font-size:.9rem; }
    .grid { display:grid; gap:18px; padding:24px;
            grid-template-columns:repeat(auto-fill,minmax(370px,1fr)); }
    .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px;
            padding:20px; box-shadow:0 1px 3px rgba(0,0,0,.06); }
    .card h2 { margin:0 0 2px; font-size:1.1rem; display:flex; align-items:center;
               justify-content:space-between; gap:10px; }
    .tipo { font-size:.7rem; text-transform:uppercase; letter-spacing:.04em;
            background:#eef2ff; color:#4338ca; padding:3px 8px; border-radius:20px; font-weight:700; }
    .meta { color:var(--gris); font-size:.82rem; margin-bottom:14px; }
    .barra { height:8px; background:#e5e7eb; border-radius:20px; overflow:hidden; margin:10px 0 4px; }
    .barra span { display:block; height:100%; background:var(--ok); }
    .pct { font-size:.8rem; color:var(--gris); margin-bottom:12px; }
    ul { list-style:none; margin:0; padding:0; font-size:.87rem; }
    li { padding:5px 0; display:flex; gap:8px; align-items:flex-start; }
    li .m { flex:0 0 16px; font-weight:700; }
    li.si .m { color:var(--ok); } li.no .m { color:var(--no); }
    li.no { color:#374151; }
    .nota { margin-top:12px; padding:10px 12px; background:#f9fafb; border-radius:8px;
            font-size:.82rem; color:#4b5563; }
    code { background:#f3f4f6; padding:2px 6px; border-radius:4px; font-size:.85em; }
    .cmds { margin:24px; background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px; }
    .cmds h3 { margin:0 0 6px; font-size:1rem; }
    .cmds p { color:var(--gris); font-size:.85rem; margin:0 0 12px; }
    pre { background:#111827; color:#e5e7eb; padding:14px 16px; border-radius:8px;
          overflow-x:auto; font-size:.82rem; line-height:1.6; margin:0; }
</style>
</head>
<body>

<header>
    <h1>Apps móviles</h1>
    <div class="sub">
        <?php if ($generado): ?>
            Estado recogido el <?= e($generado) ?>
        <?php else: ?>
            Sin datos todavía
        <?php endif; ?>
    </div>
</header>

<?php if (!$estado): ?>
    <div class="aviso">
        No hay datos de estado. Ejecuta <code>php cron/recolectar_estado_apps.php</code> para generarlos.
    </div>
<?php elseif ($obsoleto): ?>
    <div class="aviso">
        Estos datos tienen más de un día. Vuelve a ejecutar el recolector para refrescarlos.
    </div>
<?php endif; ?>

<div class="grid">
<?php foreach ($apps as $app): ?>
    <div class="card">
        <h2>
            <?= e($app['nombre']) ?>
            <span class="tipo"><?= e($app['tipo']) ?></span>
        </h2>
        <div class="meta">
            v<?= e($app['version'] ?? '?') ?>
            <?php if (!empty($app['android_package'])): ?>
                · <?= e($app['android_package']) ?>
            <?php endif; ?>
            <?php if (!empty($app['git']['ultimo_commit'])): ?>
                <br>Último commit <?= e($app['git']['ultimo_commit']) ?> (<?= e($app['git']['fecha']) ?>)
                <?php if (!empty($app['git']['sin_commitear'])): ?>
                    · <strong><?= (int)$app['git']['sin_commitear'] ?> sin commitear</strong>
                <?php endif; ?>
                <?php if (empty($app['git']['remote'])): ?>
                    · <strong>sin remoto</strong>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($app['total'])): ?>
            <div class="barra"><span style="width:<?= (int)$app['pct'] ?>%"></span></div>
            <div class="pct"><?= (int)$app['listos'] ?> de <?= (int)$app['total'] ?> requisitos cumplidos</div>
        <?php endif; ?>

        <ul>
        <?php foreach ($app['checklist'] as $c): ?>
            <li class="<?= !empty($c['ok']) ? 'si' : 'no' ?>">
                <span class="m"><?= !empty($c['ok']) ? '✓' : '✗' ?></span>
                <span><?= e($c['texto']) ?></span>
            </li>
        <?php endforeach; ?>
        </ul>

        <?php if (!empty($app['notas'])): ?>
            <div class="nota"><?= e($app['notas']) ?></div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
</div>

<div class="cmds">
    <h3>Publicar Código Amigo</h3>
    <p>Estos comandos se ejecutan a mano desde el servidor. Requieren haber iniciado sesión en EAS (<code>npx eas-cli login</code>), que aún no se ha hecho en esta máquina.</p>
<pre>cd /home/admin/web/codigoamigo.com/mobile-app

npx eas-cli login                      # una vez
npx eas-cli init                       # rellena extra.eas.projectId
npx eas-cli update:configure           # configura expo-updates

npx eas-cli build --platform android --profile production
npx eas-cli submit --platform android --profile production

# refrescar este panel
php /home/admin/web/codigoamigo.com/public_html/cron/recolectar_estado_apps.php</pre>
</div>

</body>
</html>
