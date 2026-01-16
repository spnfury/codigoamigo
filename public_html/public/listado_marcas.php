<?php 
// Definir variables sociales para evitar errores
$title_social = $title ?? "Listado de marcas - CodigoAmigo.com";
$description_social = $description ?? "Descubre todas las marcas disponibles en CodigoAmigo.com";
$imagen_social = "https://www.codigoamigo.com/img/logo_codigoamigo_real4.png";

get_header_new($title, $description, $title_social, $description_social, $imagen_social); 
?>

<?php 
// Obtener TODAS las marcas una sola vez al inicio
$lista_marcas = getMarcas(null); 

// Procesar imágenes usando getObjectMarca para asegurar que se procesen correctamente
foreach($lista_marcas as &$marca_item) {
    if (!empty($marca_item["nombre_clave"])) {
        $marca_obj = getObjectMarca('nombre_clave', $marca_item["nombre_clave"]);
        if ($marca_obj && !empty($marca_obj["imagen"])) {
            $marca_item["imagen"] = $marca_obj["imagen"];
        }
    }
}
unset($marca_item); // Liberar referencia

$marcas_destacadas = array_slice($lista_marcas, 0, 12);
?>

<div class="container-fluid main_entremedio brands-page">
    <div class="container text-center bloque_titulo_home">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-12 mensaje">
                    <h1>Todas las Marcas</h1>
                    <p>Descubre todas las marcas disponibles con códigos de descuento</p><br>
                </div>
            </div>
        </div>
    </div>

    <div class="row empieza_home">
        <div class="container">
            <div class="col-md-12 columns small-12 slider">
                <div class="title">
                    <h2>¡Destacadas!</h2>
                </div>

                <div class="destacado_div">
                    <div class="listado_codigos">
                        <?php 
                        // $lista_marcas y $marcas_destacadas ya están definidas arriba
                        
                        foreach($marcas_destacadas as $marca) {
                            // Las imágenes ya vienen procesadas desde getMarcas()
                            $marca_imagen_processed = $marca["imagen"] ?? '';
                            
                            // Solo usar fallback si no hay imagen válida
                            if (empty($marca_imagen_processed) || $marca_imagen_processed == 'Sin imagen' || trim($marca_imagen_processed) == '') {
                                $marca_imagen_processed = 'https://www.codigoamigo.com/img/logo_codigoamigo_real4.png';
                            }
                            
                            if($marca_imagen_processed && $marca_imagen_processed != 'Sin imagen') {
                                $marca_url = link_marca($marca["nombre_clave"]);
                                $marca_nombre = htmlspecialchars($marca["nombre"]);
                                $marca_descripcion = htmlspecialchars($marca["descripción"] ?? '');
                                $marca_imagen = htmlspecialchars($marca_imagen_processed);
                                $codigos_count = $marca["numero_codigos"] ?? 0;
                        ?>
                        <div class="brand-card-modern">
                            <a href="<?php echo $marca_url; ?>" class="brand-card-link-modern" title="Códigos descuento <?php echo $marca_nombre; ?>">
                                <!-- Header con logo de marca -->
                                <div class="brand-header-modern">
                                    <div class="brand-logo-container">
                                        <img src="<?php echo $marca_imagen; ?>" 
                                             alt="<?php echo $marca_nombre; ?>" 
                                             class="brand-logo-modern"
                                             loading="lazy"
                                             onerror="if(this.src.indexOf('logo_codigoamigo_real4') === -1) { this.src='https://www.codigoamigo.com/img/logo_codigoamigo_real4.png'; this.style.display='block'; this.style.visibility='visible'; }"
                                             onload="this.style.display='block'; this.style.visibility='visible';">
                                    </div>
                                    <span class="brand-badge-modern"><?php echo $marca_nombre; ?></span>
                                    <div class="brand-date-modern">
                                        <i class="far fa-clock"></i>
                                        <span>Disponible</span>
                                    </div>
                                </div>
                                
                                <!-- Contenido de la marca -->
                                <div class="brand-content-modern">
                                    <div class="brand-info-modern">
                                        <div class="brand-stats-modern">
                                            <i class="fas fa-tag"></i>
                                            <span><?php echo $codigos_count; ?> códigos</span>
                                        </div>
                                    </div>
                                    
                                    <p class="brand-description-modern">
                                        <?php echo $marca_descripcion ?: 'Descubre los mejores códigos de descuento para ' . $marca_nombre . '. Ahorra dinero en tus compras favoritas.'; ?>
                                    </p>
                                    
                                    <!-- Barra de beneficio -->
                                    <div class="brand-benefit-modern">
                                        <i class="fas fa-gift"></i>
                                        <span><?php echo $codigos_count; ?> códigos disponibles</span>
                                    </div>
                                    
                                    <!-- Botón de acción -->
                                    <div class="brand-button-modern">
                                        <i class="fas fa-eye"></i>
                                        <span>VER CÓDIGOS</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <?php 
                            } // Cierra el if de línea 67
                        } // Cierra el foreach de línea 40
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="container">
            <div class="col-md-12">
                <div class="cd-home-title titulo_zona_home">Todas las marcas disponibles</div>
                <div class="brands-grid-all">
                    <?php 
                    // Usar $lista_marcas ya obtenida arriba (todas las marcas)
                    // Excluir las marcas ya mostradas en destacadas
                    $marcas_destacadas_claves = array();
                    foreach($marcas_destacadas as $marca_dest) {
                        $marcas_destacadas_claves[] = $marca_dest["nombre_clave"];
                    }
                    
                    // Filtrar las marcas destacadas de la lista completa
                    $marcas_restantes = array_filter($lista_marcas, function($marca) use ($marcas_destacadas_claves) {
                        return !in_array($marca["nombre_clave"], $marcas_destacadas_claves);
                    });
                    
                    // Mostrar todas las marcas restantes
                    foreach($marcas_restantes as $marca) {
                        // Las imágenes ya vienen procesadas desde getMarcas()
                        $marca_imagen_processed = $marca["imagen"] ?? '';
                        
                        // Solo usar fallback si no hay imagen válida
                        if (empty($marca_imagen_processed) || $marca_imagen_processed == 'Sin imagen' || trim($marca_imagen_processed) == '') {
                            $marca_imagen_processed = 'https://www.codigoamigo.com/img/logo_codigoamigo_real4.png';
                        }
                        
                        $marca_nombre = htmlspecialchars($marca["nombre"] ?? '');
                        $marca_clave = $marca["nombre_clave"] ?? '';
                        $marca_descripcion = htmlspecialchars($marca["descripción"] ?? '');
                        $codigos_count = $marca["numero_codigos"] ?? 0;
                        $marca_url = link_marca($marca_clave);
                        
                        // Mostrar todas las marcas, incluso si no tienen imagen (usarán fallback)
                        if($marca_nombre && $marca_clave) {
                            $marca_imagen = htmlspecialchars($marca_imagen_processed);
                    ?>
                    <div class="brand-card-modern">
                        <a href="<?php echo $marca_url; ?>" class="brand-card-link-modern" title="Códigos descuento <?php echo $marca_nombre; ?>">
                            <!-- Header con logo de marca -->
                            <div class="brand-header-modern">
                                <div class="brand-logo-container">
                                    <img src="<?php echo $marca_imagen; ?>" 
                                         alt="<?php echo $marca_nombre; ?>" 
                                         class="brand-logo-modern"
                                         loading="lazy"
                                         onerror="if(this.src.indexOf('logo_codigoamigo_real4') === -1) { this.src='https://www.codigoamigo.com/img/logo_codigoamigo_real4.png'; this.style.display='block'; this.style.visibility='visible'; }"
                                         onload="this.style.display='block'; this.style.visibility='visible';">
                                </div>
                                <span class="brand-badge-modern"><?php echo $marca_nombre; ?></span>
                                <div class="brand-date-modern">
                                    <i class="far fa-clock"></i>
                                    <span>Disponible</span>
                                </div>
                            </div>
                            
                            <!-- Contenido de la marca -->
                            <div class="brand-content-modern">
                                <div class="brand-info-modern">
                                    <div class="brand-stats-modern">
                                        <i class="fas fa-tag"></i>
                                        <span><?php echo $codigos_count; ?> códigos</span>
                                    </div>
                                </div>
                                
                                <p class="brand-description-modern">
                                    <?php echo mb_substr($marca_descripcion ?: 'Descubre los mejores códigos de descuento para ' . $marca_nombre . '. Ahorra dinero en tus compras favoritas.', 0, 120); ?><?php echo mb_strlen($marca_descripcion ?: '') > 120 ? '...' : ''; ?>
                                </p>
                                
                                <!-- Barra de beneficio -->
                                <div class="brand-benefit-modern">
                                    <i class="fas fa-gift"></i>
                                    <span><?php echo $codigos_count; ?> códigos disponibles</span>
                                </div>
                                
                                <!-- Botón de acción -->
                                <div class="brand-button-modern">
                                    <i class="fas fa-eye"></i>
                                    <span>VER CÓDIGOS</span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php 
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS personalizado para la página de marcas -->
<style>
/* Reset y base */
.brands-page {
    background: #2a2a2a;
    min-height: 100vh;
}

.brands-page .bloque_titulo_home {
    background: #2a2a2a;
    padding: 40px 0;
}

.brands-page .bloque_titulo_home h1 {
    color: #ffffff;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 10px;
}

.brands-page .bloque_titulo_home p {
    color: #cccccc;
    font-size: 1.1rem;
}

.brands-page .title h2 {
    color: #E30613;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 30px;
    text-align: left;
}

.brands-page .titulo_zona_home {
    color: #ffffff;
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid #E30613;
}

/* Grid moderno para marcas */
.listado_codigos {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 25px;
    margin-top: 20px;
}

.brands-grid-all {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 25px;
    margin-top: 30px;
    padding: 0;
}

/* Card moderna de marca */
.brand-card-modern {
    background: #2a2a2a;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.brand-card-modern:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.4);
    border-color: #E30613;
}

.brand-card-link-modern {
    text-decoration: none;
    color: inherit;
    display: block;
}

/* Header con logo de marca */
.brand-header-modern {
    position: relative;
    height: 180px;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 30px 20px;
    border-bottom: 3px solid #E30613;
}

/* Variaciones de color para el borde inferior */
.brand-card-modern:nth-child(3n+1) .brand-header-modern {
    border-bottom-color: #E30613;
}

.brand-card-modern:nth-child(3n+2) .brand-header-modern {
    border-bottom-color: #28a745;
}

.brand-card-modern:nth-child(3n+3) .brand-header-modern {
    border-bottom-color: #3466ff;
}

.brand-logo-container {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #ffffff;
}

.brand-logo-modern {
    max-width: 80%;
    max-height: 80%;
    width: auto;
    height: auto;
    object-fit: contain;
    display: block !important;
    margin: 0 auto;
    visibility: visible !important;
    opacity: 1 !important;
}

.brand-badge-modern {
    position: absolute;
    top: 12px;
    right: 12px;
    background: rgba(42, 42, 42, 0.95);
    color: #ffffff;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    z-index: 10;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.brand-date-modern {
    position: absolute;
    bottom: 12px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(42, 42, 42, 0.95);
    color: #ffffff;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    gap: 6px;
    z-index: 10;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.brand-date-modern i {
    font-size: 0.7rem;
}

/* Contenido de la marca */
.brand-content-modern {
    background: #2a2a2a;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.brand-info-modern {
    display: flex;
    align-items: center;
    gap: 10px;
}

.brand-stats-modern {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #ffffff;
    font-size: 0.9rem;
    font-weight: 600;
}

.brand-stats-modern i {
    color: #E30613;
    font-size: 1rem;
}

.brand-description-modern {
    color: #ffffff;
    font-size: 0.95rem;
    line-height: 1.6;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Barra de beneficio */
.brand-benefit-modern {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: #ffffff;
    padding: 12px 16px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    font-size: 0.95rem;
    margin-top: 5px;
}

.brand-benefit-modern i {
    font-size: 1.1rem;
}

/* Botón de acción */
.brand-button-modern {
    background: linear-gradient(135deg, #E30613 0%, #C40510 100%);
    color: #ffffff;
    padding: 14px 20px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-weight: 700;
    font-size: 1rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 5px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.brand-card-modern:hover .brand-button-modern {
    background: linear-gradient(135deg, #ff7d4d 0%, #E30613 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(227, 6, 19, 0.4);
}

.brand-button-modern i {
    font-size: 1.1rem;
}

/* Responsive para la página de marcas */
@media (max-width: 768px) {
    .listado_codigos,
    .brands-grid-all {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }
    
    .brand-header-modern {
        height: 150px;
        padding: 20px 15px;
    }
    
    .brand-content-modern {
        padding: 16px;
        gap: 12px;
    }
    
    .brand-description-modern {
        font-size: 0.9rem;
        -webkit-line-clamp: 2;
    }
    
    .brands-page .bloque_titulo_home h1 {
        font-size: 2rem;
    }
    
    .brands-page .title h2 {
        font-size: 1.5rem;
    }
}

@media (max-width: 480px) {
    .listado_codigos,
    .brands-grid-all {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .brand-header-modern {
        height: 140px;
        padding: 15px 10px;
    }
    
    .brand-logo-modern {
        max-width: 70%;
        max-height: 70%;
    }
    
    .brand-badge-modern {
        font-size: 0.7rem;
        padding: 5px 10px;
    }
    
    .brand-date-modern {
        font-size: 0.7rem;
        padding: 5px 10px;
    }
    
    .brand-content-modern {
        padding: 14px;
        gap: 10px;
    }
    
    .brand-description-modern {
        font-size: 0.85rem;
        -webkit-line-clamp: 2;
    }
    
    .brand-button-modern {
        padding: 12px 16px;
        font-size: 0.9rem;
    }
    
    .brand-benefit-modern {
        padding: 10px 14px;
        font-size: 0.85rem;
    }
    
    .brands-page .bloque_titulo_home h1 {
        font-size: 1.75rem;
    }
    
    .brands-page .title h2 {
        font-size: 1.3rem;
    }
}

/* Script para verificar y corregir imágenes que fallan */
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Función para verificar si una imagen carga correctamente
    function checkImage(img) {
        return new Promise(function(resolve, reject) {
            if (!img.src || img.src === '') {
                reject('No src');
                return;
            }
            
            var tester = new Image();
            tester.onload = function() { resolve(true); };
            tester.onerror = function() { reject('Failed to load'); };
            tester.src = img.src;
        });
    }
    
    // Verificar todas las imágenes de marcas
    var brandImages = document.querySelectorAll('.brand-logo-modern');
    brandImages.forEach(function(img) {
        // Verificar que la imagen tenga src válido
        if (!img.src || img.src === '' || img.src.includes('data:')) {
            img.src = 'https://www.codigoamigo.com/img/logo_codigoamigo_real4.png';
            img.style.display = 'block';
            return;
        }
        
        checkImage(img).catch(function() {
            // Si la imagen falla, intentar alternativas
            var originalSrc = img.src;
            var fallbackSrc = 'https://www.codigoamigo.com/img/logo_codigoamigo_real4.png';
            
            // Si ya es el fallback, no hacer nada
            if (originalSrc === fallbackSrc || originalSrc.includes('logo_codigoamigo_real4')) {
                img.style.display = 'block';
                return;
            }
            
            // Intentar convertir .webp a .png si es posible
            if (originalSrc.includes('.webp')) {
                var pngSrc = originalSrc.replace('.webp', '.png');
                var tester = new Image();
                tester.onload = function() {
                    img.src = pngSrc;
                    img.style.display = 'block';
                };
                tester.onerror = function() {
                    img.src = fallbackSrc;
                    img.style.display = 'block';
                };
                tester.src = pngSrc;
            } else {
                img.src = fallbackSrc;
                img.style.display = 'block';
            }
        });
    });
});
</script>

<?php get_footer(); ?>