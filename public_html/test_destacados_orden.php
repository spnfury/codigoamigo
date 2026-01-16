<?php
// Página de prueba para verificar el orden de códigos destacados
header('Content-Type: text/html; charset=utf-8');

// Incluir archivos necesarios
include_once __DIR__ . '/inc/includes.php';
include_once __DIR__ . '/myphp/funciones.php';
include_once __DIR__ . '/myphp/funciones_modern.php';

// Obtener códigos destacados ordenados por destacado_social
$array_filtro = array("estado" => 0);
$array_filtro = array_merge($array_filtro, array(
    '$or' => array(
        array("destacado" => array('$ne' => 0)),
        array("destacado_social" => array('$exists' => true, '$ne' => 0))
    )
));

$array_opciones = array(
    'limit' => 20,
    'sort' => array('destacado_social' => -1, 'destacado' => -1, 'fecha_publicacion' => -1)
);

$lista_codigos_pre = get_all_listado_codigos_array($array_filtro, $array_opciones);
$lista_codigos = isset($lista_codigos_pre["results"]) ? $lista_codigos_pre["results"] : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Test Destacados Orden - CodigoAmigo</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .codigo-item {
            background: #f8f9fa;
            margin: 15px 0;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #E30613;
        }
        .codigo-marca {
            font-weight: bold;
            color: #333;
            font-size: 1.2em;
            margin-bottom: 8px;
        }
        .codigo-tipo {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
            margin-right: 10px;
            margin-bottom: 8px;
        }
        .tipo-destacado { background: #ffc107; color: #212529; }
        .tipo-social { background: #e91e63; color: white; }
        .codigo-fecha {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        .codigo-descripcion {
            margin-top: 8px;
            color: #555;
            line-height: 1.5;
        }
        .debug-info {
            background: #e9ecef;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            font-family: monospace;
            font-size: 0.9em;
        }
        .orden-indicator {
            background: #007bff;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8em;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test Orden Destacados</h1>
        <p>Esta página verifica que los códigos destacados estén ordenados correctamente por <code>destacado_social</code> primero.</p>

        <div class="debug-info">
            <strong>Consulta realizada:</strong><br>
            <code>
                $array_filtro = array("estado" => 0);<br>
                $array_filtro = array_merge($array_filtro, array(<br>
                &nbsp;&nbsp;&nbsp;&nbsp;'$or' => array(<br>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;array("destacado" => array('$ne' => 0)),<br>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;array("destacado_social" => array('$exists' => true, '$ne' => 0))<br>
                &nbsp;&nbsp;&nbsp;&nbsp;)<br>
                ));<br><br>
                $array_opciones = array(<br>
                &nbsp;&nbsp;&nbsp;&nbsp;'limit' => 20,<br>
                &nbsp;&nbsp;&nbsp;&nbsp;'sort' => array('destacado_social' => -1, 'destacado' => -1, 'fecha_publicacion' => -1)<br>
                );
            </code>
        </div>

        <h2>📋 Códigos Destacados (<?php echo count($lista_codigos); ?> códigos):</h2>

        <?php if(!empty($lista_codigos)): ?>
            <?php $index = 1; ?>
            <?php foreach($lista_codigos as $codigo): ?>
                <?php
                $destacado = isset($codigo['destacado']) ? $codigo['destacado'] : 0;
                $destacado_social = isset($codigo['destacado_social']) ? $codigo['destacado_social'] : 0;
                $tipo = $destacado_social > 0 ? 'social' : 'destacado';
                $tipo_label = $destacado_social > 0 ? 'Destacado Social' : 'Destacado';
                ?>
                <div class="codigo-item">
                    <div class="codigo-marca">
                        <?php echo htmlspecialchars($codigo['marca'] ?? 'Marca desconocida'); ?>
                        <span class="orden-indicator">#<?php echo $index; ?></span>
                    </div>
                    <div>
                        <span class="codigo-tipo tipo-<?php echo $tipo; ?>"><?php echo $tipo_label; ?></span>
                        <span class="codigo-tipo">destacado_social: <?php echo $destacado_social; ?></span>
                        <span class="codigo-tipo">destacado: <?php echo $destacado; ?></span>
                    </div>
                    <div class="codigo-fecha">
                        <?php
                        if(isset($codigo['fecha_publicacion'])) {
                            $fecha = $codigo['fecha_publicacion'];
                            if($fecha instanceof MongoDB\BSON\UTCDateTime) {
                                $timestamp = $fecha->toDateTime()->getTimestamp();
                                echo '📅 ' . date('d/m/Y H:i:s', $timestamp);
                            } else {
                                echo '📅 Fecha: ' . htmlspecialchars($fecha);
                            }
                        } else {
                            echo '📅 Sin fecha de publicación';
                        }
                        ?>
                    </div>
                    <div class="codigo-descripcion">
                        <?php echo htmlspecialchars(substr($codigo['descripcion'] ?? 'Sin descripción', 0, 150)) . '...'; ?>
                    </div>
                </div>
                <?php $index++; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No se encontraron códigos destacados para mostrar.</p>
        <?php endif; ?>

        <h2>✅ Verificaciones:</h2>
        <ul>
            <li><strong>Orden destacado_social:</strong> Los códigos con destacado_social > 0 aparecen primero</li>
            <li><strong>Orden destacado:</strong> Luego los códigos con destacado > 0</li>
            <li><strong>Orden fecha:</strong> Finalmente ordenados por fecha_publicacion DESC</li>
            <li><strong>Solo códigos activos:</strong> estado = 0 (activos)</li>
        </ul>

        <h2>🎯 Página Principal con Destacados:</h2>
        <p>Ve la <a href="/" target="_blank">página principal</a> para ver cómo se muestran las tarjetas destacadas mejoradas.</p>

        <div class="debug-info">
            <strong>Características implementadas:</strong><br>
            ✅ Orden por destacado_social primero<br>
            ✅ Tarjetas más atractivas con gradientes<br>
            ✅ Badges diferenciados (normal vs social)<br>
            ✅ Animaciones y efectos hover mejorados<br>
            ✅ Logos interactivos con efectos<br>
            ✅ Botones con efectos de brillo
        </div>

        <p><em>💡 Consejo: Los códigos con <code>destacado_social > 0</code> aparecen primero, seguidos de códigos con <code>destacado > 0</code>, y finalmente ordenados por fecha.</em></p>
    </div>
</body>
</html>
