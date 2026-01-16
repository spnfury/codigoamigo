<?php
// Página de prueba para verificar el orden por fecha
header('Content-Type: text/html; charset=utf-8');

// Incluir archivos necesarios
include_once __DIR__ . '/inc/includes.php';
include_once __DIR__ . '/myphp/funciones.php';
include_once __DIR__ . '/myphp/funciones_modern.php';

// Obtener algunos códigos para verificar el orden
$array_filtro = array("estado" => 0, "destacado" => 0);
$array_opciones = array(
    'limit' => 10,
    'sort' => array('fecha_publicacion' => -1)
);

$lista_codigos_pre = get_all_listado_codigos_array($array_filtro, $array_opciones);
$lista_codigos = isset($lista_codigos_pre["results"]) ? $lista_codigos_pre["results"] : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Test Orden Fecha - CodigoAmigo</title>
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
            margin: 10px 0;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #E30613;
        }
        .codigo-marca {
            font-weight: bold;
            color: #333;
            font-size: 1.1em;
        }
        .codigo-fecha {
            color: #666;
            font-size: 0.9em;
        }
        .codigo-descripcion {
            margin-top: 8px;
            color: #555;
        }
        .debug-info {
            background: #e9ecef;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            font-family: monospace;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test Orden por Fecha</h1>
        <p>Esta página verifica que los códigos estén ordenados correctamente por fecha de publicación (más recientes primero).</p>

        <div class="debug-info">
            <strong>Consulta realizada:</strong><br>
            <code>
                $array_filtro = array("estado" => 0, "destacado" => 0);<br>
                $array_opciones = array(<br>
                &nbsp;&nbsp;&nbsp;&nbsp;'limit' => 10,<br>
                &nbsp;&nbsp;&nbsp;&nbsp;'sort' => array('fecha_publicacion' => -1)<br>
                );
            </code>
        </div>

        <h2>📋 Códigos Obtenidos (<?php echo count($lista_codigos); ?> códigos):</h2>

        <?php if(!empty($lista_codigos)): ?>
            <?php foreach($lista_codigos as $index => $codigo): ?>
                <div class="codigo-item">
                    <div class="codigo-marca">
                        <?php echo htmlspecialchars($codigo['marca'] ?? 'Marca desconocida'); ?>
                        <?php if(isset($codigo['destacado']) && $codigo['destacado'] == 1): ?>
                            <span style="color: #E30613; font-size: 0.8em;">⭐ DESTACADO</span>
                        <?php endif; ?>
                    </div>
                    <div class="codigo-fecha">
                        <?php
                        if(isset($codigo['fecha_publicacion'])) {
                            $fecha = $codigo['fecha_publicacion'];
                            if($fecha instanceof MongoDB\BSON\UTCDateTime) {
                                $timestamp = $fecha->toDateTime()->getTimestamp();
                                echo '📅 ' . date('d/m/Y H:i:s', $timestamp) . ' (timestamp: ' . $timestamp . ')';
                            } else {
                                echo '📅 Fecha: ' . htmlspecialchars($fecha);
                            }
                        } else {
                            echo '📅 Sin fecha de publicación';
                        }
                        ?>
                    </div>
                    <div class="codigo-descripcion">
                        <?php echo htmlspecialchars(substr($codigo['descripcion'] ?? 'Sin descripción', 0, 100)) . '...'; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No se encontraron códigos para mostrar.</p>
        <?php endif; ?>

        <h2>✅ Verificaciones:</h2>
        <ul>
            <li><strong>Orden por fecha:</strong> Los códigos deberían estar ordenados por fecha_publicacion DESC (más recientes primero)</li>
            <li><strong>Solo códigos normales:</strong> No deberían aparecer códigos destacados (destacado != 1)</li>
            <li><strong>Límite aplicado:</strong> Máximo 10 códigos mostrados</li>
        </ul>

        <p><a href="/" style="color: #E30613; text-decoration: none; font-weight: bold;">← Volver a la página principal</a></p>
    </div>
</body>
</html>
