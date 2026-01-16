<?php
/**
 * Página de favoritos del usuario
 */

// Incluir funciones necesarias
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../myphp/funciones_usuario.php';
include_once __DIR__ . '/../myphp/funciones_codigo.php';
include_once __DIR__ . '/../myphp/funciones_favoritos.php';
include_once __DIR__ . '/../myphp/funciones_modern.php';
include_once __DIR__ . '/../myphp/funciones_chollos.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    header("Location: /login");
    exit;
}

$usuario_id = $_SESSION["user_id"];

// Obtener parámetros de paginación
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$skip = ($page - 1) * $limit;

// Obtener favoritos del usuario
$favoritos = obtener_favoritos_usuario($usuario_id, $limit, $skip);
$total_favoritos = contar_favoritos_usuario($usuario_id);
$total_pages = ceil($total_favoritos / $limit);

// Obtener información del usuario
$usuario = getObjectUser('_id', new MongoDB\BSON\ObjectId($usuario_id));
$datos_usuario = get_array_de_usuario($usuario);
$username = $datos_usuario['username'] ?? 'Usuario';

// Configurar variables para el header
$title = "Mis Favoritos";
$description = "Tus códigos favoritos en " . (isset($GLOBALS['author_web']) ? $GLOBALS['author_web'] : 'CodigoAmigo');
$title_social = $title;
$description_social = $description;

// Incluir el header estándar
get_header_modern($title, $description, $title_social, $description_social);
$GLOBALS['header_modern_used'] = true; // Marcar que se usó el header moderno para el footer correspondiente
?>

<link rel="stylesheet" href="/css/favoritos.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="/css/chollos-voting.css?v=<?php echo time(); ?>">

<div class="favoritos-container">
    <div class="container">
        <!-- Header de la página -->
        <div class="favoritos-header">
            <h1><i class="fas fa-heart"></i> Mis Favoritos</h1>
            <p class="favoritos-subtitle">Gestiona tus códigos y chollos guardados</p>
        </div>
        
        <?php
        $view = $_GET['view'] ?? ($_GET['tipo'] === 'chollo' ? 'chollos' : 'codigos');
        $active_codigos = $view === 'codigos' ? 'active' : '';
        $active_chollos = $view === 'chollos' ? 'active' : '';
        
        // Contadores
        $total_codigos = contar_favoritos_usuario($usuario_id, 'codigo');
        $total_chollos = contar_favoritos_usuario($usuario_id, 'chollo');
        
        // Obtener datos según vista
        $limit = 20;
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $skip = ($page - 1) * $limit;
        
        $tipo_actual = $view === 'chollos' ? 'chollo' : 'codigo';
        $favoritos = obtener_favoritos_usuario($usuario_id, $tipo_actual, $limit, $skip);
        $total_actual = $view === 'chollos' ? $total_chollos : $total_codigos;
        
        $total_pages = ceil($total_actual / $limit);
        ?>

        <!-- Tabs de navegación -->
        <div class="favoritos-tabs">
            <a href="/mis-favoritos?view=codigos" class="tab-item <?php echo $active_codigos; ?>">
                <i class="fas fa-tags"></i> Códigos (<?php echo $total_codigos; ?>)
            </a>
            <a href="/mis-favoritos?view=chollos" class="tab-item <?php echo $active_chollos; ?>">
                <i class="fas fa-fire"></i> Chollos (<?php echo $total_chollos; ?>)
            </a>
        </div>

        <?php if ($total_actual > 0): ?>
            <!-- Lista de favoritos -->
            
                <?php 
                if (!empty($favoritos)) {
                    if ($view === 'chollos') {
                        // Renderizar grid de chollos (ya incluye su propio contenedor grid)
                        imprimir_grid_chollos($favoritos, 3); // 3 columnas
                    } else {
                        // Renderizar grid de códigos (necesita contenedor)
                        echo '<div class="favoritos-grid">';
                        echo generate_modern_code_cards($favoritos);
                        echo '</div>';
                    }
                } else {
                    echo '<div class="no-favoritos-message">';
                    echo '<i class="fas fa-heart-broken"></i>';
                    echo '<h3>No hay más elementos en esta página</h3>';
                    echo '</div>';
                }
                ?>

            <!-- Paginación -->
            <?php if ($total_pages > 1): ?>
            <div class="favoritos-pagination">
                <?php if ($page > 1): ?>
                    <a href="/mis-favoritos?view=<?php echo $view; ?>&page=<?php echo $page - 1; ?>" class="pagination-btn">
                        <i class="fas fa-chevron-left"></i> Anterior
                    </a>
                <?php endif; ?>
                
                <span class="pagination-info">
                    Página <?php echo $page; ?> de <?php echo $total_pages; ?>
                </span>
                
                <?php if ($page < $total_pages): ?>
                    <a href="/mis-favoritos?view=<?php echo $view; ?>&page=<?php echo $page + 1; ?>" class="pagination-btn">
                        Siguiente <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Mensaje cuando no hay favoritos -->
            <div class="no-favoritos-container">
                <div class="no-favoritos-message">
                    <i class="fas fa-heart"></i>
                    <h2>Aún no tienes <?php echo $view; ?> favoritos</h2>
                    <p>Cuando añadas <?php echo $view; ?> a tus favoritos, aparecerán aquí</p>
                    <a href="<?php echo $view === 'chollos' ? '/chollos' : '/'; ?>" class="btn-explorar">
                        <i class="fas fa-search"></i> Explorar <?php echo ucfirst($view); ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.favoritos-container {
    min-height: 60vh;
    padding: 2rem 0;
    background: #f8f9fa;
}

.favoritos-header {
    text-align: center;
    margin-bottom: 3rem;
    padding: 2rem;
    background: linear-gradient(135deg, #E30613 0%, #C40510 100%);
    color: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(227, 6, 19, 0.3);
}

.favoritos-header h1 {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    font-weight: bold;
}

.favoritos-tabs {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.tab-item {
    padding: 12px 25px;
    background: white;
    color: #666;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    border: 1px solid transparent;
}

.tab-item:hover {
    color: #E30613;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.tab-item.active {
    background: #E30613;
    color: white;
    box-shadow: 0 4px 15px rgba(227, 6, 19, 0.3);
}

.favoritos-subtitle {
    font-size: 1.1rem;
    opacity: 0.95;
    margin: 0;
}

.favoritos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.no-favoritos-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 400px;
}

.no-favoritos-message {
    text-align: center;
    padding: 3rem;
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.no-favoritos-message i {
    font-size: 4rem;
    color: #E30613;
    margin-bottom: 1rem;
}

.no-favoritos-message h2 {
    color: #333;
    margin-bottom: 1rem;
}

.no-favoritos-message p {
    color: #666;
    margin-bottom: 2rem;
}

.btn-explorar {
    display: inline-block;
    padding: 12px 30px;
    background: #E30613;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-explorar:hover {
    background: #C40510;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(227, 6, 19, 0.3);
}

.favoritos-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    margin-top: 3rem;
    padding: 2rem;
}

.pagination-btn {
    padding: 10px 20px;
    background: white;
    color: #E30613;
    text-decoration: none;
    border-radius: 8px;
    border: 2px solid #E30613;
    transition: all 0.3s ease;
    font-weight: 600;
}

.pagination-btn:hover {
    background: #E30613;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(227, 6, 19, 0.3);
}

.pagination-info {
    padding: 10px 20px;
    color: #666;
    font-weight: 600;
}

@media (max-width: 768px) {
    .favoritos-header h1 {
        font-size: 2rem;
    }
    
    .favoritos-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .favoritos-pagination {
        flex-direction: column;
    }
}

/* Estilos para tarjetas de chollos en favoritos */
.chollo-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    border: 1px solid #f0f0f0;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.chollo-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    border-color: #eea2a6;
}

.chollo-image {
    height: 200px;
    background: #f9f9f9;
    position: relative;
    overflow: hidden;
}

.chollo-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 10px;
    transition: transform 0.3s ease;
}

.chollo-card:hover .chollo-image img {
    transform: scale(1.05);
}

.chollo-content {
    padding: 15px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.chollo-title {
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 8px;
    color: #333;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.chollo-description {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 12px;
    line-height: 1.5;
    flex-grow: 1;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.chollo-prices {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}

.chollo-price-discount {
    font-size: 1.2rem;
    font-weight: 800;
    color: #E30613;
}

.chollo-price-original {
    font-size: 0.9rem;
    color: #999;
    text-decoration: line-through;
}

.chollo-meta {
    display: flex;
    gap: 12px;
    margin-bottom: 12px;
    font-size: 0.8rem;
    color: #888;
}

.chollo-meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

.chollo-buttons {
    display: flex;
    gap: 8px;
    margin-top: auto;
}

.chollo-button {
    flex: 1;
    padding: 8px;
    text-align: center;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    transition: all 0.2s;
}

.chollo-button-primary {
    background: #E30613;
    color: white;
}

.chollo-button-primary:hover {
    background: #C40510;
    color: white;
}

.chollo-button-secondary {
    background: #f0f0f0;
    color: #333;
}

.chollo-button-secondary:hover {
    background: #e0e0e0;
    color: #333;
}

.chollo-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #E30613;
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: bold;
    z-index: 2;
}
</style>

<?php
// Incluir el footer estándar
get_footer();
?>

