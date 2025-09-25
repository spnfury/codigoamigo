<?php
// Incluir archivos necesarios para conexión a la base de datos
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';

// Obtener el ID del código desde la URL
$codigo_id = $_GET['codigo'] ?? '';

if (empty($codigo_id)) {
    echo '<div class="error-message">Error: No se especificó el código</div>';
    exit;
}

// Obtener información del código
$obj_id_codigo = new \MongoDB\BSON\ObjectId($codigo_id);
$codigo = getCodeByID($obj_id_codigo);

if (!$codigo) {
    echo '<div class="error-message">Error: Código no encontrado</div>';
    exit;
}

// Obtener información de la marca
$marca = getObjectMarca('nombre_clave', $codigo["marca"]);
$marca_nombre = $marca['nombre'] ?? $codigo['marca'];

// Obtener estadísticas reales de visualizaciones
$visitas = getVistasCodeById($obj_id_codigo);
$total_visualizaciones = count($visitas);
$total_clicks = $codigo['totalclicks'] ?? 0;

// Calcular tasa de conversión
$conversion_rate = $total_visualizaciones > 0 ? round(($total_clicks / $total_visualizaciones) * 100, 2) : 0;

// Obtener estadísticas diarias de los últimos 5 días
$estadisticas_diarias = [];
$total_visualizaciones_periodo = 0;

for ($i = 4; $i >= 0; $i--) {
    $fecha = date('Y-m-d', strtotime("-{$i} days"));
    $fecha_formato = date('d-m-y', strtotime($fecha));
    
    // Contar visualizaciones de ese día
    $visualizaciones_dia = 0;
    foreach ($visitas as $visita) {
        if (isset($visita['fecha_vista'])) {
            $fecha_vista = explode(' ', $visita['fecha_vista'])[0]; // Solo la fecha, sin la hora
            if ($fecha_vista === $fecha_formato) {
                $visualizaciones_dia++;
            }
        }
    }
    
    $total_visualizaciones_periodo += $visualizaciones_dia;
    $estadisticas_diarias[] = [
        'fecha' => $fecha,
        'visualizaciones' => $visualizaciones_dia,
        'clicks' => 0 // Se calculará después
    ];
}

// Calcular clicks diarios de manera proporcional
if ($total_visualizaciones_periodo > 0 && $total_clicks > 0) {
    $factor_conversion = $total_clicks / $total_visualizaciones_periodo;
    
    foreach ($estadisticas_diarias as &$dia) {
        $dia['clicks'] = round($dia['visualizaciones'] * $factor_conversion);
    }
}

// Crear array de estadísticas reales
$estadisticas = [
    'codigo_id' => $codigo_id,
    'marca' => $marca_nombre,
    'codigo' => $codigo['codigo'] ?? 'N/A',
    'fecha_creacion' => isset($codigo['fecha_creacion']) ? $codigo['fecha_creacion'] : 'N/A',
    'total_visualizaciones' => $total_visualizaciones,
    'total_clicks' => $total_clicks,
    'conversion_rate' => $conversion_rate,
    'estadisticas_diarias' => $estadisticas_diarias
];
?>

<style>
.estadisticas-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    box-sizing: border-box;
}

.estadisticas-content {
    background: white;
    border-radius: 12px;
    padding: 30px;
    max-width: 800px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.estadisticas-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
}

.estadisticas-title {
    font-size: 1.8rem;
    font-weight: 700;
    color: #333;
    margin: 0;
}

.close-modal {
    background: #ff6b35;
    color: white;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    font-size: 1.2rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.close-modal:hover {
    background: #e55a2b;
    transform: scale(1.1);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    border-left: 4px solid #ff6b35;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #ff6b35;
    margin: 0 0 10px 0;
}

.stat-label {
    font-size: 0.9rem;
    color: #666;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.chart-section {
    margin-top: 30px;
}

.chart-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 20px;
}

.chart-container {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.chart-bar {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    padding: 10px;
    background: white;
    border-radius: 6px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.chart-date {
    width: 100px;
    font-weight: 600;
    color: #333;
    font-size: 0.9rem;
}

.chart-bar-container {
    flex: 1;
    height: 20px;
    background: #e9ecef;
    border-radius: 10px;
    margin: 0 15px;
    position: relative;
    overflow: hidden;
}

.chart-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #ff6b35, #ff8c42);
    border-radius: 10px;
    transition: width 0.3s ease;
}

.chart-values {
    display: flex;
    gap: 20px;
    font-size: 0.9rem;
    color: #666;
}

.chart-visualizaciones {
    color: #ff6b35;
    font-weight: 600;
}

.chart-clicks {
    color: #28a745;
    font-weight: 600;
}

@media (max-width: 768px) {
    .estadisticas-content {
        padding: 20px;
        margin: 10px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    .stat-card {
        padding: 15px;
    }
    
    .stat-number {
        font-size: 1.5rem;
    }
    
    .chart-bar {
        flex-direction: column;
        gap: 10px;
    }
    
    .chart-date {
        width: auto;
    }
    
    .chart-bar-container {
        margin: 0;
        width: 100%;
    }
    
    .chart-values {
        justify-content: space-between;
        width: 100%;
    }
}
</style>

<div class="estadisticas-modal" id="estadisticasModal">
    <div class="estadisticas-content">
        <div class="estadisticas-header">
            <h2 class="estadisticas-title">Estadísticas del Código</h2>
            <button class="close-modal" onclick="cerrarEstadisticas()">&times;</button>
        </div>
        
        <div class="codigo-info" style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
            <h3 style="margin: 0 0 10px 0; color: #333;"><?php echo htmlspecialchars($estadisticas['marca']); ?></h3>
            <p style="margin: 0; color: #666; font-family: monospace; font-size: 1.1rem;"><?php echo htmlspecialchars($estadisticas['codigo']); ?></p>
            <p style="margin: 5px 0 0 0; color: #999; font-size: 0.9rem;">Creado: <?php echo $estadisticas['fecha_creacion']; ?></p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($estadisticas['total_visualizaciones']); ?></div>
                <div class="stat-label">Visualizaciones</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($estadisticas['total_clicks']); ?></div>
                <div class="stat-label">Clicks</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $estadisticas['conversion_rate']; ?>%</div>
                <div class="stat-label">Tasa de Conversión</div>
            </div>
        </div>
        
        <div class="chart-section">
            <h3 class="chart-title">Actividad de los Últimos 5 Días</h3>
            <div class="chart-container">
                <?php 
                $max_visualizaciones = max(array_column($estadisticas['estadisticas_diarias'], 'visualizaciones'));
                foreach ($estadisticas['estadisticas_diarias'] as $dia): 
                    $porcentaje = $max_visualizaciones > 0 ? ($dia['visualizaciones'] / $max_visualizaciones) * 100 : 0;
                ?>
                <div class="chart-bar">
                    <div class="chart-date"><?php echo date('d/m', strtotime($dia['fecha'])); ?></div>
                    <div class="chart-bar-container">
                        <div class="chart-bar-fill" style="width: <?php echo $porcentaje; ?>%"></div>
                    </div>
                    <div class="chart-values">
                        <span class="chart-visualizaciones"><?php echo $dia['visualizaciones']; ?> vistas</span>
                        <span class="chart-clicks"><?php echo $dia['clicks']; ?> clicks</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
function cerrarEstadisticas() {
    document.getElementById('estadisticasModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Cerrar modal al hacer click fuera del contenido
document.getElementById('estadisticasModal').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarEstadisticas();
    }
});

// Cerrar modal con tecla ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarEstadisticas();
    }
});

// Prevenir scroll del body cuando el modal está abierto
document.body.style.overflow = 'hidden';
</script>
