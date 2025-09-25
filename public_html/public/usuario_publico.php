<?php 
get_header_modern($title, $description); 
?>

<style>
/* Estilos para la página pública de usuario con diseño consistente */
.user-page-container {
    background: #222222;
    min-height: 100vh;
    padding: 0;
}

.user-page-header {
    background: #f8f8f8;
    border-radius: 15px;
    margin: 20px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.3);
}

.user-profile-section {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 30px;
}

.user-avatar-large {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #ff6b35;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: bold;
    box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
}

.user-info h1 {
    color: #333;
    font-size: 2.5rem;
    margin: 0;
    font-weight: 700;
}

.user-info p {
    color: #666;
    font-size: 1.1rem;
    margin: 5px 0 0 0;
}

.stats-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-item {
    background: white;
    border-radius: 15px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 3px 15px rgba(0,0,0,0.1);
}

.stat-number {
    font-size: 2rem;
    font-weight: 800;
    color: #ff6b35;
    margin: 0;
}

.stat-label {
    color: #666;
    font-size: 0.9rem;
    margin: 5px 0 0 0;
}

.filters-section {
    background: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 3px 15px rgba(0,0,0,0.1);
}

.filters-title {
    color: #333;
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 15px;
}

.filter-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 8px 16px;
    border: 2px solid #e0e0e0;
    background: white;
    color: #666;
    border-radius: 25px;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

.filter-btn:hover {
    border-color: #ff6b35;
    color: #ff6b35;
}

.filter-btn.active {
    background: #ff6b35;
    border-color: #ff6b35;
    color: white;
}

.codes-section {
    background: #f8f8f8;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    margin: 20px;
}

.codes-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e0e0e0;
}

.codes-title {
    color: #ff6b35;
    font-size: 1.8rem;
    font-weight: 700;
    margin: 0;
}

.codes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.code-card {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 3px 15px rgba(0,0,0,0.1);
    border-left: 5px solid #ff6b35;
    transition: all 0.3s ease;
    position: relative;
}

.code-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}

.code-card.featured {
    border-left-color: #ffd700;
    background: linear-gradient(135deg, #fff9e6 0%, #ffffff 100%);
}

.featured-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: #ffd700;
    color: #000;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 700;
}

.code-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.code-brand h4 {
    color: #333;
    font-size: 1.2rem;
    font-weight: 700;
    margin: 0 0 5px 0;
}

.code-brand p {
    color: #666;
    font-size: 0.9rem;
    margin: 0;
    line-height: 1.4;
}

.code-reward {
    background: #ff6b35;
    color: white;
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.9rem;
}

.code-details {
    margin: 15px 0;
}

.code-details p {
    margin: 5px 0;
    color: #555;
    font-size: 0.9rem;
}

.code-code {
    background: #e0e0e0;
    padding: 8px 12px;
    border-radius: 8px;
    font-family: 'Courier New', monospace;
    font-weight: 600;
    color: #333;
    border: 2px dashed #ccc;
    font-size: 0.9rem;
}

.code-position {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 15px;
    font-weight: 600;
    font-size: 0.8rem;
}

.code-position.high {
    background: #d4edda;
    color: #155724;
}

.code-position.medium {
    background: #fff3cd;
    color: #856404;
}

.code-position.low {
    background: #f8d7da;
    color: #721c24;
}

.code-actions {
    display: flex;
    gap: 8px;
    margin-top: 15px;
    flex-wrap: wrap;
}

.btn-action {
    padding: 8px 15px;
    border: none;
    border-radius: 20px;
    cursor: pointer;
    font-size: 0.8rem;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.btn-view-code {
    background: #ff6b35;
    color: white;
    box-shadow: 0 3px 10px rgba(255, 107, 53, 0.3);
}

.btn-view-code:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 107, 53, 0.4);
    color: white;
    text-decoration: none;
}

.btn-share {
    background: #6f42c1;
    color: white;
    box-shadow: 0 3px 10px rgba(111, 66, 193, 0.3);
}

.btn-share:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(111, 66, 193, 0.4);
    color: white;
    text-decoration: none;
}

.no-codes {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.no-codes i {
    font-size: 4rem;
    color: #ddd;
    margin-bottom: 20px;
    display: block;
}

.no-codes h3 {
    color: #333;
    margin-bottom: 15px;
}

.no-codes p {
    font-size: 1.1rem;
    margin-bottom: 30px;
}

/* Responsive */
@media (max-width: 768px) {
    .user-page-header {
        margin: 10px;
        padding: 20px;
    }
    
    .user-profile-section {
        flex-direction: column;
        text-align: center;
    }
    
    .stats-section {
        grid-template-columns: 1fr;
    }
    
    .codes-section {
        margin: 10px;
        padding: 20px;
    }
    
    .codes-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-buttons {
        justify-content: center;
    }
}
</style>

<div class="user-page-container">
    <div class="user-page-header">
        <div class="user-profile-section">
            <div class="user-avatar-large">
                <?php echo strtoupper(substr($data_usuario["username"] ?? "U", 0, 2)); ?>
            </div>
            <div class="user-info">
                <h1><?php echo htmlspecialchars($data_usuario["username"] ?? "Usuario"); ?></h1>
                <p>
                    <?php 
                    if(isset($data_usuario["fecha_registro"])) {
                        $fecha_registro = new DateTime($data_usuario["fecha_registro"]);
                        $hoy = new DateTime();
                        $dias = $hoy->diff($fecha_registro)->days;
                        echo "Miembro desde hace " . $dias . " días";
                    } else {
                        echo "Miembro de la comunidad";
                    }
                    ?>
                </p>
            </div>
        </div>
        
        <div class="stats-section">
            <div class="stat-item">
                <div class="stat-number"><?php echo $num_codigos; ?></div>
                <div class="stat-label">Códigos compartidos</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo count($categorias_usuario); ?></div>
                <div class="stat-label">Categorías</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo isset($data_usuario["total_clicks"]) ? $data_usuario["total_clicks"] : "0"; ?></div>
                <div class="stat-label">Clicks totales</div>
            </div>
        </div>
        
        <?php if(count($categorias_usuario) > 0): ?>
        <div class="filters-section">
            <div class="filters-title">Filtrar por categoría</div>
            <div class="filter-buttons">
                <button class="filter-btn active" data-category="all" onclick="filterByCategory('all')">
                    Todas las categorías
                </button>
                <?php foreach($categorias_usuario as $categoria): ?>
                    <button class="filter-btn" data-category="<?php echo htmlspecialchars($categoria); ?>" onclick="filterByCategory('<?php echo htmlspecialchars($categoria); ?>')">
                        <?php echo ucwords(str_replace('-', ' ', $categoria)); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="codes-section">
        <div class="codes-header">
            <h3 class="codes-title" id="codesTitle">Códigos compartidos (<?php echo $num_codigos; ?>)</h3>
        </div>
        
        <?php if($listado_codigos && count($listado_codigos) > 0): ?>
            <div class="codes-grid" id="codesGrid">
                <?php foreach($listado_codigos as $codigo): ?>
                    <?php
                    // Verificar que $codigo['marca'] existe y no es null
                    if (!isset($codigo['marca']) || $codigo['marca'] === null) {
                        continue;
                    }
                    
                    $marca = getObjectMarca('nombre_clave', $codigo['marca']);
                    if (!$marca) {
                        continue;
                    }
                    
                    $posicion = get_posicion_codigo_en_marca($codigo['_id'], $codigo['marca']);
                    $is_destacado = isset($codigo['destacado']) && $codigo['destacado'] > 0;
                    
                    // Determinar clase de visibilidad
                    $visibilidad_class = '';
                    if($posicion == 1) $visibilidad_class = 'high';
                    elseif($posicion == 2) $visibilidad_class = 'medium';
                    else $visibilidad_class = 'low';
                    
                    // Obtener categoría del código
                    $categoria_codigo = isset($codigo['clave_categoria']) ? $codigo['clave_categoria'] : 'sin-categoria';
                    ?>
                    
                    <div class="code-card <?php echo $is_destacado ? 'featured' : ''; ?>" data-category="<?php echo htmlspecialchars($categoria_codigo); ?>">
                        <?php if($is_destacado): ?>
                            <div class="featured-badge">
                                <i class="fas fa-star"></i> Destacado
                            </div>
                        <?php endif; ?>
                        
                        <div class="code-header">
                            <div class="code-brand">
                                <h4><?php echo htmlspecialchars($marca['nombre'] ?? $codigo['marca']); ?></h4>
                                <p><?php echo htmlspecialchars($codigo['descripcion'] ?? 'Código de descuento válido'); ?></p>
                            </div>
                            <div class="code-reward">
                                <?php echo $codigo['num_beneficio'] ?? '10'; ?>€
                            </div>
                        </div>
                        
                        <div class="code-details">
                            <p><strong>Código:</strong> <span class="code-code"><?php echo htmlspecialchars($codigo['codigo'] ?? ''); ?></span></p>
                            <p><strong>Posición:</strong> <span class="code-position <?php echo $visibilidad_class; ?>">#<?php echo $posicion; ?></span></p>
                            <p><strong>Fecha:</strong> <?php echo isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'N/A'; ?></p>
                            <p><strong>Clicks:</strong> <?php echo $codigo['totalclicks'] ?? '0'; ?></p>
                        </div>
                        
                        <div class="code-actions">
                            <button class="btn-action btn-view-code" onclick="viewCode('<?php echo $codigo['_id']; ?>', '<?php echo $codigo['marca']; ?>')">
                                <i class="fas fa-eye"></i> Ver Código
                            </button>
                            <button class="btn-action btn-share" onclick="shareCode('<?php echo $codigo['_id']; ?>')">
                                <i class="fas fa-share"></i> Compartir
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-codes">
                <i class="fas fa-code"></i>
                <h3>No hay códigos compartidos</h3>
                <p>Este usuario aún no ha compartido ningún código de descuento.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Función para filtrar por categoría
function filterByCategory(category) {
    // Remover clase active de todos los botones
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Agregar clase active al botón clickeado
    event.target.classList.add('active');
    
    // Filtrar códigos
    const codeCards = document.querySelectorAll('.code-card');
    let visibleCount = 0;
    
    codeCards.forEach(card => {
        const cardCategory = card.getAttribute('data-category');
        
        if (category === 'all' || cardCategory === category) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Actualizar contador
    document.getElementById('codesTitle').textContent = `Códigos compartidos (${visibleCount})`;
}

// Función para ver código
function viewCode(codigoId, marca) {
    // Redirigir a la página de detalle del código
    window.location.href = `/de-${marca}?codigo=${codigoId}`;
}

// Función para compartir código
function shareCode(codigoId) {
    // Implementar funcionalidad de compartir
    if (navigator.share) {
        navigator.share({
            title: 'Código de descuento',
            text: 'Mira este código de descuento',
            url: window.location.href
        });
    } else {
        // Fallback para navegadores que no soportan Web Share API
        const url = window.location.href;
        navigator.clipboard.writeText(url).then(() => {
            alert('Enlace copiado al portapapeles');
        });
    }
}
</script>

<?php get_footer(); ?>
