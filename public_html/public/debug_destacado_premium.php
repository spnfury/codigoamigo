<?php
/**
 * Script para mostrar los valores exactos de destacado y premium en la tabla
 */
session_start();

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';

// Verificar permisos de administrador
$array_codigos_acceso[] = "58bd851da54e295b8b52f702"; //thevega82@gmail.com
$array_codigos_acceso[] = "5e78170e6b68e6519b7c5df2"; //edna
$array_codigos_acceso[] = "639899bc6321ee0d0e4010d2"; //aron
$array_codigos_acceso[] = "5c8a10ce2f55c86d6e707d82"; //jose

// Verificar que el usuario esté logueado y tenga permisos
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]) || !in_array($_SESSION["user_id"], $array_codigos_acceso)) {
    header('Location: https://www.codigoamigo.com');
    die();
}

$collection_codigos = getCollectionCodigos();

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Debug Destacado y Premium</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        .highlight-destacado { background-color: #fff3cd !important; }
        .highlight-premium { background-color: #d1ecf1 !important; }
        .highlight-both { background-color: #d4edda !important; }
    </style>
</head>
<body>
<div class='container-fluid mt-4'>
    <h1>🔍 Debug: Campos Destacado y Premium en MongoDB</h1>
    <p class='text-muted'>Mostrando los valores exactos de los campos destacado y destacado_social</p>
    
    <div class='row mb-3'>
        <div class='col-md-3'>
            <div class='card bg-warning text-white'>
                <div class='card-body text-center'>
                    <h5>Destacado Normal</h5>
                    <p class='mb-0'>Campo: <code>destacado</code></p>
                </div>
            </div>
        </div>
        <div class='col-md-3'>
            <div class='card bg-info text-white'>
                <div class='card-body text-center'>
                    <h5>Premium</h5>
                    <p class='mb-0'>Campo: <code>destacado_social</code></p>
                </div>
            </div>
        </div>
        <div class='col-md-3'>
            <div class='card bg-success text-white'>
                <div class='card-body text-center'>
                    <h5>Ambos</h5>
                    <p class='mb-0'>Destacado + Premium</p>
                </div>
            </div>
        </div>
        <div class='col-md-3'>
            <div class='card bg-secondary text-white'>
                <div class='card-body text-center'>
                    <h5>Ninguno</h5>
                    <p class='mb-0'>Sin destacar</p>
                </div>
            </div>
        </div>
    </div>";

// Obtener algunos códigos de ejemplo
$codigos = $collection_codigos->find([], [
    'sort' => ['_id' => -1],
    'limit' => 20
])->toArray();

echo "<div class='table-responsive'>
    <table class='table table-striped table-hover'>
        <thead class='table-dark'>
            <tr>
                <th>ID</th>
                <th>Marca</th>
                <th>Código</th>
                <th>Estado</th>
                <th>Destacado<br><small>(destacado)</small></th>
                <th>Premium<br><small>(destacado_social)</small></th>
                <th>Tipo</th>
                <th>Valores Raw</th>
            </tr>
        </thead>
        <tbody>";

foreach ($codigos as $codigo) {
    $destacado = isset($codigo['destacado']) ? $codigo['destacado'] : 0;
    $destacado_social = isset($codigo['destacado_social']) ? $codigo['destacado_social'] : 0;
    
    // Determinar el tipo y clase CSS
    $tipo = '';
    $css_class = '';
    
    if ($destacado > 0 && $destacado_social > 0) {
        $tipo = 'Ambos';
        $css_class = 'highlight-both';
    } elseif ($destacado > 0) {
        $tipo = 'Destacado';
        $css_class = 'highlight-destacado';
    } elseif ($destacado_social > 0) {
        $tipo = 'Premium';
        $css_class = 'highlight-premium';
    } else {
        $tipo = 'Ninguno';
        $css_class = '';
    }
    
    echo "<tr class='$css_class'>
        <td><small>" . substr($codigo['_id'], 0, 8) . "...</small></td>
        <td>" . htmlspecialchars($codigo['marca'] ?? 'Sin marca') . "</td>
        <td><code>" . htmlspecialchars(substr($codigo['codigo'] ?? '', 0, 15)) . "...</code></td>
        <td><span class='badge bg-" . ($codigo['estado'] == 0 ? 'success' : 'danger') . "'>" . ($codigo['estado'] == 0 ? 'Activo' : 'Inactivo') . "</span></td>
        <td><strong>" . $destacado . "</strong></td>
        <td><strong>" . $destacado_social . "</strong></td>
        <td><span class='badge bg-secondary'>" . $tipo . "</span></td>
        <td><small>destacado: " . $destacado . "<br>destacado_social: " . $destacado_social . "</small></td>
    </tr>";
}

echo "</tbody>
    </table>
</div>";

// Estadísticas
$total_codigos = $collection_codigos->countDocuments([]);
$destacados = $collection_codigos->countDocuments(['destacado' => ['$ne' => 0]]);
$premium = $collection_codigos->countDocuments(['destacado_social' => ['$ne' => 0]]);
$ambos = $collection_codigos->countDocuments([
    'destacado' => ['$ne' => 0],
    'destacado_social' => ['$ne' => 0]
]);

echo "<div class='row mt-4'>
    <div class='col-md-12'>
        <h3>📊 Estadísticas</h3>
        <div class='row'>
            <div class='col-md-3'>
                <div class='card'>
                    <div class='card-body text-center'>
                        <h4 class='text-primary'>" . number_format($total_codigos) . "</h4>
                        <p class='mb-0'>Total Códigos</p>
                    </div>
                </div>
            </div>
            <div class='col-md-3'>
                <div class='card'>
                    <div class='card-body text-center'>
                        <h4 class='text-warning'>" . number_format($destacados) . "</h4>
                        <p class='mb-0'>Destacados (destacado > 0)</p>
                    </div>
                </div>
            </div>
            <div class='col-md-3'>
                <div class='card'>
                    <div class='card-body text-center'>
                        <h4 class='text-info'>" . number_format($premium) . "</h4>
                        <p class='mb-0'>Premium (destacado_social > 0)</p>
                    </div>
                </div>
            </div>
            <div class='col-md-3'>
                <div class='card'>
                    <div class='card-body text-center'>
                        <h4 class='text-success'>" . number_format($ambos) . "</h4>
                        <p class='mb-0'>Ambos</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>";

echo "<div class='mt-4'>
    <h3>🔍 Explicación de los Campos</h3>
    <div class='alert alert-info'>
        <h5>Campo <code>destacado</code> (Destacado Normal):</h5>
        <ul>
            <li><strong>0</strong> = No destacado</li>
            <li><strong>1 o timestamp</strong> = Destacado (puede ser 1 o un timestamp)</li>
        </ul>
        
        <h5>Campo <code>destacado_social</code> (Premium):</h5>
        <ul>
            <li><strong>0</strong> = No premium</li>
            <li><strong>1 o timestamp</strong> = Premium (aparece en Home)</li>
        </ul>
        
        <h5>Colores en la tabla:</h5>
        <ul>
            <li><span class='badge bg-warning'>Amarillo</span> = Solo Destacado</li>
            <li><span class='badge bg-info'>Azul</span> = Solo Premium</li>
            <li><span class='badge bg-success'>Verde</span> = Ambos (Destacado + Premium)</li>
            <li><span class='badge bg-secondary'>Gris</span> = Ninguno</li>
        </ul>
    </div>
</div>";

echo "</div>
</body>
</html>";
?>

