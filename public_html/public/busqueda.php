<?php 

// Obtener el criterio de ordenamiento
$orden = isset($_GET['orden']) ? $_GET['orden'] : 'relevancia';

get_header_new($title, $description, $title_social, $description_social, $imagen_social, $links_meta);

include_once $_SERVER['DOCUMENT_ROOT'].'/public/header.php'; ?>


<style>
/* Estilos para la página de búsqueda */
.search-results {
    margin-top: 50px; /* Margen para móvil por defecto */
    padding: 20px 0;
}

@media (min-width: 992px) {
    .search-results {
        margin-top: 100px; /* Margen para desktop */
    }
}

.search-results h1 {
    font-size: 24px;
    color: #333;
    margin: 0 0 30px 0;
    padding: 15px 20px;
    background: #f8f9fa;
    border-radius: 10px;
    border-left: 4px solid #6c5ce7;
}

.search-results .card {
    border-radius: 12px;
    border: 1px solid #eee;
    transition: all 0.3s ease;
    margin-bottom: 20px;
}

.search-results .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.1);
}

.search-results .card-img-wrapper {
    padding: 20px;
    background: #fff;
    border-radius: 12px 12px 0 0;
}

.search-results .card-img-top {
    max-height: 120px;
    width: auto;
    object-fit: contain;
}

.search-results .card-body {
    padding: 20px;
}

.search-results .card-title {
    font-size: 1.2rem;
    margin-bottom: 1rem;
    font-weight: 600;
}

.search-results .card-text {
    color: #6c757d;
    font-size: 0.9rem;
    line-height: 1.5;
}

/* Estilos para los filtros */
.filters-card {
    position: sticky;
    top: 20px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    padding: 20px;
}

.filters-card .card-title {
    color: #333;
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
}

.filters-card .form-label {
    font-weight: 500;
    color: #495057;
}

.filters-card .form-select {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background-color: #fff;
    font-size: 14px;
    color: #333;
    height: auto;
    cursor: pointer;
    appearance: auto;
    -webkit-appearance: auto;
    -moz-appearance: auto;
}

.filters-card .form-select:focus {
    border-color: #6c5ce7;
    box-shadow: 0 0 0 0.2rem rgba(108, 92, 231, 0.25);
    outline: 0;
}

.filters-card .form-select option {
    padding: 10px;
    background-color: #fff;
    color: #333;
}

.filters-card .form-select option:checked {
    background-color: #6c5ce7;
    color: #fff;
}

/* Estilos para las secciones */
.search-section {
    margin-bottom: 3rem;
}

.search-section h2 {
    font-size: 20px;
    color: #333;
    margin: 30px 0 20px 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #eee;
}

/* Mensaje de no resultados */
.alert-info {
    background-color: #f8f9fa;
    border: none;
    border-left: 4px solid #17a2b8;
    color: #495057;
}

.search-results .btn-primary {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    background: #6c5ce7;
    border: none;
    transition: all 0.3s ease;
}

.search-results .btn-primary:hover {
    background: #5849e0;
    transform: translateY(-2px);
}

/* Mejora del contenedor del filtro */
.filters-card .mb-3 {
    margin-bottom: 1rem !important;
}

.filters-card .form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #333;
}

.listado_codigos {
    margin-top: 20px;
}

.card_real {
    margin-bottom: 20px;
}

.card_real .card {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.card_real .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.imagen_principal {
    max-width: 100%;
    height: auto;
    border-radius: 4px;
}

.texto_codigo h3 {
    font-size: 16px;
    margin-bottom: 10px;
    color: #333;
}

.texto_codigo p {
    font-size: 14px;
    color: #666;
    margin-bottom: 0;
}

.btn_codigo_amigo {
    background: #6c5ce7;
    color: white;
    padding: 10px 20px;
    border-radius: 6px;
    margin-top: 10px;
    display: inline-block;
    transition: all 0.3s ease;
}

.btn_codigo_amigo:hover {
    background: #5849e0;
    color: white;
    text-decoration: none;
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .texto_codigo h3 {
        font-size: 14px;
    }
    
    .texto_codigo p {
        font-size: 12px;
    }
    
    .btn_codigo_amigo {
        width: 100%;
        margin-top: 15px;
    }
}

.fecha_publicacion {
    font-size: 12px;
    color: #888;
    margin-top: 8px;
    margin-bottom: 0;
}

.fecha_publicacion i {
    margin-right: 5px;
    font-size: 11px;
}

@media (max-width: 768px) {
    .fecha_publicacion {
        font-size: 11px;
    }
}
</style>
<div class="container-fluid mt-4 search-results">
    <div class="row">
        <!-- Sidebar/Filtros -->
        <div class="col-lg-3">
            <div class="card filters-card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Filtros de búsqueda</h5>
                    <form id="filtrosForm" method="GET" action="">
                        <div class="mb-3">
                            <label class="form-label">Ordenar por:</label>
                            <select class="form-select" name="orden" onchange="this.form.submit()">
                                <option value="relevancia" <?php echo ($orden === 'relevancia') ? 'selected' : ''; ?>>Relevancia</option>
                                <option value="alfabetico" <?php echo ($orden === 'alfabetico') ? 'selected' : ''; ?>>Alfabético</option>
                                <option value="reciente" <?php echo ($orden === 'reciente') ? 'selected' : ''; ?>>Más reciente</option>
                            </select>
                        </div>
                        <input type="hidden" name="q" value="<?php echo htmlspecialchars($termino); ?>">
                    </form>
                </div>
            </div>
        </div>

        <!-- Contenido principal -->
        <div class="col-lg-9">
            <h1 class="mb-4">Resultados de búsqueda para: "<?php echo htmlspecialchars($termino); ?>"</h1>
            
            <?php if (empty($lista_marcas) && empty($lista_codigos['results'])): ?>
                <div class="alert alert-info">
                    <h4>No se encontraron resultados</h4>
                    <p>Sugerencias:</p>
                    <ul>
                        <li>Revisa que las palabras estén bien escritas</li>
                        <li>Prueba con palabras más generales</li>
                        <li>Utiliza menos palabras o palabras diferentes</li>
                    </ul>
                </div>
            <?php else: ?>
                
                <?php if (!empty($lista_marcas)): ?>
                    <section class="search-section">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2>Marcas encontradas</h2>
                        </div>
                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                            <?php foreach ($lista_marcas as $marca): ?>
                                <div class="col">
                                    <div class="card h-100 shadow-sm">
                                        <?php if ($marca['imagen']): ?>
                                            <div class="card-img-wrapper">
                                                <img src="<?php echo htmlspecialchars($marca['imagen']); ?>" 
                                                     class="card-img-top p-3" 
                                                     alt="<?php echo htmlspecialchars($marca['nombre']); ?>"
                                                     style="height: 150px; object-fit: contain;">
                                            </div>
                                        <?php endif; ?>
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <a href="/de-<?php echo htmlspecialchars($marca['nombre_clave']); ?>" 
                                                   class="text-dark text-decoration-none stretched-link">
                                                    <?php echo htmlspecialchars($marca['nombre']); ?>
                                                </a>
                                            </h5>
                                            <?php if ($marca['descripcion']): ?>
                                                <p class="card-text small text-muted"><?php echo htmlspecialchars(substr($marca['descripcion'], 0, 100)); ?>...</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
                
                <?php if (!empty($lista_codigos['results'])): ?>
                    <section class="search-section">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2>Códigos encontrados</h2>
                        </div>
                        <div class="row row-cols-1 row-cols-md-2 g-4">
                            <?php foreach ($lista_codigos['results'] as $codigo): ?>
                                <div class="col">
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-body d-flex">
                                            <!-- Logo de la marca -->
                                            <div class="me-3" style="min-width: 80px;">
                                                <?php if (!empty($codigo['imagen'])): ?>
                                                    <img src="<?php echo htmlspecialchars($codigo['imagen']); ?>" 
                                                         alt="Logo <?php echo htmlspecialchars($codigo['marca']); ?>"
                                                         class="img-fluid"
                                                         style="width: 80px; height: 80px; object-fit: contain;">
                                                <?php endif; ?>
                                            </div>
                                            <!-- Contenido del código -->
                                            <div class="d-flex flex-column w-100">
                                                <h5 class="card-title mb-2"><?php echo htmlspecialchars($codigo['marca']); ?></h5>
                                                <p class="card-text text-muted mb-2"><?php echo htmlspecialchars($codigo['descripcion']); ?></p>
                                                <p class="fecha_publicacion mb-3">
                                                    <i class="fas fa-calendar-alt"></i>
                                                    <?php echo date('d/m/Y', strtotime($codigo['fecha_publicacion'])); ?>
                                                </p>
                                                <div class="mt-auto">
                                                    <a href="/de-<?php echo htmlspecialchars($codigo['marca']); ?>?codigo=<?php echo $codigo['_id']; ?>" 
                                                       class="btn btn-primary stretched-link">
                                                        Ver código
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
                
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>