<?php
// Página de marca moderna y optimizada
get_header_modern($title, $description, $title_social, $description_social, $imagen_social, $links_meta);

global $detect_device, $codigo_existente, $u;
$numero_codigos_format = number_format($numero_codigos, 0, ',', '.');

// Función para mostrar descripción corta de la marca
function show_short_desc_modern($marca) {
    $descripcion = '';
    
    if ($marca["descripción"]) {
        $descripcion = $marca["descripción"];
    } else {
        // Textos específicos por marca
        $textos_especificos = [
            'airbnb' => 'Airbnb es la plataforma líder mundial de alojamiento vacacional que conecta viajeros con anfitriones locales.',
            'eltenedor' => 'ElTenedor es la plataforma de reservas de restaurantes más popular de España.',
            'bookingcom' => 'Booking.com es el líder mundial en reservas de hoteles y alojamientos.',
            'cabify' => 'Cabify es la plataforma de movilidad urbana que conecta pasajeros con conductores.',
            'mytaxi' => 'MyTaxi es la aplicación de taxis más utilizada en Europa.',
            'yugo' => 'Yugo es la plataforma de carsharing urbano más innovadora.',
            'muving' => 'Muving es el servicio de motosharing eléctrico más sostenible.',
            'repsol-waylet' => 'Waylet es la app de Repsol para pagar combustible y obtener descuentos.',
            'initiativeq' => 'InitiativeQ es la nueva moneda digital del futuro.'
        ];
        
        if (isset($textos_especificos[$marca["nombre_clave"]])) {
            $descripcion = $textos_especificos[$marca["nombre_clave"]];
        } else {
            $descripcion = 'Descubre los mejores códigos promocionales y descuentos para ' . $marca["nombre"] . '.';
        }
    }
    
    return $descripcion;
}

// Función para mostrar descripción larga
function show_long_desc_modern($marca) {
    $descripcion_larga = '';
    
    if ($marca["descripción_larga"]) {
        $descripcion_larga = $marca["descripción_larga"];
    } else {
        $descripcion_larga = 'Encuentra los mejores códigos promocionales, cupones de descuento y ofertas especiales para ' . $marca["nombre"] . '. Ahorra dinero en tus compras con nuestros códigos verificados y actualizados diariamente.';
    }
    
    return $descripcion_larga;
}

// Procesar notificaciones de usuario
if ($codigo_to_show && $u["notis"] == 1) {
    $actual_link = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $m = getObjectMarca("nombre_clave", $codigo_to_show["marca"]);
    enviar_mail_apertura_codigo($codigo_to_show, $u["mail"], $u["username"], $actual_link, $m["nombre"], $m["imagen"]);
}

$link_usuario = str_replace("&nuevo_codigo=1", "", $GLOBALS["actual_url"]);
$link_usuario_de_session = "https://www.codigoamigo.com/usuario_" . strtolower($_SESSION["username"]) . "_" . $_SESSION["user_id"];

// Modal para nuevo código
if ($_GET["nuevo_codigo"] == 1) { ?>
    <div id="modal_publicar_codigo" class="modal fade" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <i class="fas fa-times close-btn" data-dismiss="modal"></i>
                    <h3>¡Código publicado con éxito!</h3>
                    <p>Tu código ha sido publicado y está disponible para todos los usuarios.</p>
                    
                    <div class="social-buttons">
                        <a class="btn btn-facebook" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($actual_link); ?>" target="_blank">
                            <i class="fab fa-facebook-f"></i> Compartir en Facebook
                        </a>
                        <a class="btn btn-twitter" href="https://twitter.com/intent/tweet?url=<?php echo urlencode($actual_link); ?>&text=¡Nuevo código para <?php echo $marca["nombre"]; ?>!" target="_blank">
                            <i class="fab fa-twitter"></i> Compartir en Twitter
                        </a>
                        <a class="btn btn-whatsapp" href="https://wa.me/?text=¡Nuevo código para <?php echo $marca["nombre"]; ?>! <?php echo urlencode($actual_link); ?>" target="_blank">
                            <i class="fab fa-whatsapp"></i> Compartir en WhatsApp
                        </a>
                    </div>
                    
                    <div class="divider">
                        <span>o</span>
                    </div>
                    
                    <div class="destacar-section">
                        <div class="destacar-info">
                            <h4>¿Quieres destacar tu código?</h4>
                            <p>Posiciona tu código en las primeras posiciones y multiplica por 10 tu visibilidad</p>
                        </div>
                        <a class="btn btn-destacar" title="Destacar el código" href="<?php echo link_codigo($_REQUEST["codigo"], $marca["nombre_clave"], '1'); ?>">
                            <i class="fas fa-star"></i> Destacar código
                        </a>
                    </div>
                    
                    <div class="divider">
                        <span>o</span>
                    </div>
                    
                    <a class="btn btn-primary" href="<?php echo $link_usuario_de_session; ?>">
                        <i class="fas fa-user"></i> Ver tu perfil
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    $(document).ready(function(){
        $('#modal_publicar_codigo').modal({
            backdrop: 'static',
            keyboard: false,
            show: true
        });
    });
    </script>
<?php } ?>

<!-- HEADER DE MARCA -->
<section class="brand-header">
    <div class="container">
        <div class="brand-header-content">
            <div class="brand-logo-section">
                <a href="<?php echo link_marca($marca["nombre_clave"]); ?>" title="Códigos amigo de <?php echo $marca["nombre"]; ?>">
                    <img class="brand-logo" src="<?php echo $marca["imagen"]; ?>" alt="Código promocional <?php echo $marca["nombre"] ?>">
                </a>
            </div>
            <div class="brand-info">
                <h1 class="brand-title">
                    <?php if ($h1 != '') { ?>
                        <?php echo $h1; ?>
                    <?php } else { ?>
                        Código descuento <?php echo $marca["nombre"]; ?>
                    <?php } ?>
                </h1>
                <p class="brand-description"><?php echo show_short_desc_modern($marca); ?></p>
                <?php if ($marca["categoria_clave"]) { ?>
                    <div class="brand-category">
                        <a href="<?php echo link_categoria($marca["categoria_clave"]); ?>">
                            <?php echo ucfirst(str_replace("-", " ", $marca["categoria"])); ?>
                        </a> > <?php echo $marca["nombre"]; ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</section>

<?php if (!$_GET["codigo"]) { ?>
    <!-- CONTENIDO PRINCIPAL -->
    <div class="container">
        <div class="brand-content">
            <!-- DESCRIPCIÓN LARGA -->
            <div class="brand-description-section">
                <p class="brand-long-description"><?php echo show_long_desc_modern($marca); ?></p>
            </div>
            
            <!-- VIDEO O PUBLICIDAD -->
            <div class="brand-media-section">
                <?php if ($marca["nombre_clave"] == 'n26') { ?>
                    <div class="video-container">
                        <iframe src="https://www.youtube.com/embed/uDmSoH-t4No" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                <?php } elseif ($marca["video"]) { ?>
                    <div class="row">
                        <div class="col-md-6">
                            <?php echo $marca["video"]; ?>
                        </div>
                        <div class="col-md-6">
                            <div class="ad-container">
                                <!-- AdSense -->
                                <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
                                <ins class="adsbygoogle"
                                     style="display:block"
                                     data-ad-slot="2215822301"
                                     data-ad-format="auto"
                                     data-full-width-responsive="true"></ins>
                                <script>
                                     (adsbygoogle = window.adsbygoogle || []).push({});
                                </script>
                            </div>
                        </div>
                    </div>
                <?php } else { ?>
                    <div class="ad-container">
                        <!-- AdSense -->
                        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
                        <ins class="adsbygoogle"
                             style="display:block"
                             data-ad-slot="2215822301"
                             data-ad-format="auto"
                             data-full-width-responsive="true"></ins>
                        <script>
                             (adsbygoogle = window.adsbygoogle || []).push({});
                        </script>
                    </div>
                <?php } ?>
            </div>
            
            <!-- NAVEGACIÓN -->
            <nav class="brand-navigation">
                <?php if ($marca["nombre_clave"] != 'repsol-waylet') { ?>
                    <a class="nav-link" href="#Que_es_<?php echo $marca["nombre_clave"]; ?>">
                        📲 ¿Qué es <?php echo $marca["nombre_clave"]; ?>?
                    </a>
                <?php } else { ?>
                    <a class="nav-link" href="#Que_es_<?php echo $marca["nombre_clave"]; ?>">
                        📲 ¿Waylet Repsol cómo funciona?
                    </a>
                <?php } ?>
                
                <!-- Enlaces específicos por marca -->
                <?php if ($marca["nombre_clave"] == 'royalq') { ?>
                    <a class="nav-link" href="https://opinionesde.org/royalqbot_review" title="Review y Opiniones de Royal Q">
                        <i class="fas fa-comments"></i> Opiniones de <?php echo $marca["nombre_clave"]; ?>
                    </a>
                <?php } ?>
                
                <?php if ($marca["nombre_clave"] == 'goin') { ?>
                    <a class="nav-link" href="https://opinionesde.org/goin_review" title="Review y Opiniones de goin">
                        <i class="fas fa-comments"></i> Opiniones de <?php echo $marca["nombre_clave"]; ?>
                    </a>
                <?php } ?>
                
                <?php if ($marca["nombre_clave"] == 'holaluz') { ?>
                    <a class="nav-link" href="https://opinionesde.org/holaluz_review" title="Review y Opiniones de HolaLuz">
                        <i class="fas fa-comments"></i> Opiniones de <?php echo $marca["nombre_clave"]; ?>
                    </a>
                <?php } ?>
                
                <?php if ($marca["nombre_clave"] == 'openbank') { ?>
                    <a class="nav-link btn btn-primary" href="https://track.adtraction.com/t/t?a=1312208159&as=1434209805&t=2&tk=1" target="_blank">
                        Registrarse en OpenBank
                    </a>
                <?php } ?>
                
                <?php if ($marca["nombre_clave"] == 'getitclub') { ?>
                    <a class="nav-link btn btn-primary" href="https://getitclub.org/" target="_blank">
                        Get It Club
                    </a>
                <?php } ?>
                
                <?php if ($marca["nombre_clave"] == 'royalq') { ?>
                    <a class="nav-link btn btn-primary" href="http://royalq.wiki/" target="_blank">
                        RoyalQ
                    </a>
                <?php } ?>
                
                <a class="nav-link" href="#codigos_promocionales_<?php echo $marca["nombre_clave"]; ?>">
                    💰 Encuentra tu código promocional <?php echo $marca["nombre"]; ?>
                </a>
                <a class="nav-link" href="#alternativas_a_<?php echo $marca["nombre_clave"]; ?>">
                    ♻ Alternativas a <?php echo $marca["nombre_clave"]; ?>
                </a>
                
                <?php if ($marca["nombre_clave"] == 'repsol-waylet') { ?>
                    <a class="nav-link" href="#waylet_repsol_como_funciona">
                        📱 Waylet Repsol cómo funciona
                    </a>
                <?php } ?>
            </nav>
            
            <!-- CÓDIGOS PROMOCIONALES -->
            <section class="codes-section" id="codigos_promocionales_<?php echo $marca["nombre_clave"]; ?>">
                <div class="codes-header">
                    <?php if ($marca["nombre_clave"] != 'bookingcom') { ?>
                        <h2 class="codes-title">
                            <?php echo $numero_codigos_format; ?> Cupones y Códigos amigo para <?php echo $marca["nombre"]; ?>
                        </h2>
                    <?php } ?>
                    
                    <?php if ($_GET["page"] != "") { ?>
                        <h3 class="codes-subtitle">
                            Mostrando del <?php echo $num_inicio; ?> al <?php echo $num_fin; ?> de un total de <?php echo $numero_codigos_format; ?> códigos
                        </h3>
                    <?php } ?>
                </div>
                
                <div class="codes-content">
                    <?php if ($marca["nombre_clave"] != 'bookingcom') { ?>
                        <?php
                        // Dividir códigos en columnas
                        if (!$_GET["codigo"]) {
                            $tamanio_sublista = intdiv(count($lista_codigos), 2);
                            $indice1 = $tamanio_sublista;
                            $indice2 = $tamanio_sublista * 2;
                            
                            $sublista1 = array_slice($lista_codigos, 0, $indice1);
                            $sublista2 = array_slice($lista_codigos, $indice1, $indice2 - $indice1);
                            $sublista3 = array_slice($lista_codigos, $indice2);
                        } else {
                            $sublista1 = $lista_codigos;
                        }
                        ?>
                        
                        <div class="codes-grid">
                            <?php 
                            // Usar la función moderna de códigos si está disponible
                            if (function_exists('generate_modern_code_cards')) {
                                echo generate_modern_code_cards($sublista1);
                            } else {
                                block_listado_codigos($sublista1, $a_printar);
                            }
                            ?>
                        </div>
                        
                        <?php if ($_GET["codigo"] && !$detect_device->isMobile()) { ?>
                            <div class="sidebar-ad">
                                <!-- AdSense Lateral -->
                                <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
                                <ins class="adsbygoogle"
                                     style="display:block"
                                     data-ad-slot="2861865272"
                                     data-ad-format="auto"
                                     data-full-width-responsive="true"></ins>
                                <script>
                                     (adsbygoogle = window.adsbygoogle || []).push({});
                                </script>
                            </div>
                        <?php } ?>
                        
                    <?php } else { // BOOKING.COM ?>
                        <div class="booking-section">
                            <h3>Booking ya no ofrece Códigos de amigo, pero tenemos grandes ofertas para ti de booking!</h3>
                            
                            <?php if ($marca["nombre_clave"] == 'bookingcom') { ?>
                                <ins class="bookingaff" data-aid="1917518" data-target_aid="1917518" data-prod="dfl2" data-width="100%" data-height="auto" data-lang="es" data-df_num_properties="9">
                                    <a href="//www.booking.com?aid=1917518">Booking.com</a>
                                </ins>
                                <script type="text/javascript">
                                    (function(d, sc, u) {
                                      var s = d.createElement(sc), p = d.getElementsByTagName(sc)[0];
                                      s.type = 'text/javascript';
                                      s.async = true;
                                      s.src = u + '?v=' + (+new Date());
                                      p.parentNode.insertBefore(s,p);
                                      })(document, 'script', '//aff.bstatic.com/static/affiliate_base/js/flexiproduct.js');
                                </script>
                            <?php } ?>
                            
                            <?php if ($_GET["codigo"] && !$detect_device->isMobile()) { ?>
                                <div class="sidebar-ad">
                                    <!-- AdSense Lateral -->
                                    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
                                    <ins class="adsbygoogle"
                                         style="display:block"
                                         data-ad-slot="2861865272"
                                         data-ad-format="auto"
                                         data-full-width-responsive="true"></ins>
                                    <script>
                                         (adsbygoogle = window.adsbygoogle || []).push({});
                                    </script>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            </section>
        </div>
    </div>
<?php } ?>

<!-- CSS específico para la página de marca -->
<style>
/* Estilos para la página de marca moderna */
.brand-header {
    background: linear-gradient(135deg, var(--primary-orange) 0%, #E55A2B 100%);
    color: white;
    padding: 3rem 0;
    margin-bottom: 2rem;
}

.brand-header-content {
    display: flex;
    align-items: center;
    gap: 2rem;
    flex-wrap: wrap;
}

.brand-logo-section {
    flex-shrink: 0;
}

.brand-logo {
    width: 120px;
    height: 120px;
    object-fit: contain;
    background: white;
    border-radius: 15px;
    padding: 1rem;
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
}

.brand-info {
    flex: 1;
    min-width: 300px;
}

.brand-title {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 1rem;
    line-height: 1.2;
}

.brand-description {
    font-size: 1.2rem;
    margin-bottom: 1rem;
    opacity: 0.9;
}

.brand-category {
    font-size: 1rem;
    opacity: 0.8;
}

.brand-category a {
    color: white;
    text-decoration: underline;
}

.brand-content {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.brand-description-section {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #eee;
}

.brand-long-description {
    font-size: 1.1rem;
    line-height: 1.6;
    color: #555;
}

.brand-media-section {
    margin-bottom: 2rem;
}

.video-container {
    position: relative;
    width: 100%;
    height: 0;
    padding-bottom: 56.25%; /* 16:9 */
    margin-bottom: 2rem;
}

.video-container iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-radius: 10px;
}

.ad-container {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1rem;
    text-align: center;
    min-height: 250px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.brand-navigation {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 10px;
}

.brand-navigation .nav-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: white;
    color: var(--primary-orange);
    text-decoration: none;
    border-radius: 25px;
    font-weight: 500;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.brand-navigation .nav-link:hover {
    background: var(--primary-orange);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
}

.brand-navigation .nav-link.btn {
    background: var(--primary-orange);
    color: white;
}

.brand-navigation .nav-link.btn:hover {
    background: #E55A2B;
}

.codes-section {
    margin-top: 2rem;
}

.codes-header {
    text-align: center;
    margin-bottom: 2rem;
}

.codes-title {
    font-size: 2rem;
    font-weight: bold;
    color: var(--primary-orange);
    margin-bottom: 1rem;
}

.codes-subtitle {
    font-size: 1.1rem;
    color: #666;
    font-weight: normal;
}

.codes-content {
    position: relative;
}

.codes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.sidebar-ad {
    position: sticky;
    top: 2rem;
    margin-left: 2rem;
}

.booking-section {
    text-align: center;
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 10px;
}

.booking-section h3 {
    color: var(--primary-orange);
    margin-bottom: 2rem;
}

/* Modal mejorado */
#modal_publicar_codigo .modal-content {
    border-radius: 15px;
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

#modal_publicar_codigo .modal-body {
    padding: 2rem;
    text-align: center;
}

#modal_publicar_codigo .close-btn {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 1.5rem;
    color: #999;
    cursor: pointer;
    z-index: 1;
}

#modal_publicar_codigo .close-btn:hover {
    color: #333;
}

#modal_publicar_codigo h3 {
    color: var(--primary-orange);
    font-weight: bold;
    margin-bottom: 1rem;
}

.social-buttons {
    margin: 2rem 0;
}

.social-buttons .btn {
    display: block;
    width: 100%;
    margin-bottom: 1rem;
    padding: 1rem;
    border-radius: 25px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-facebook {
    background: #3b5998;
    color: white;
}

.btn-twitter {
    background: #1da1f2;
    color: white;
}

.btn-whatsapp {
    background: #25d366;
    color: white;
}

.btn-destacar {
    background: #f1c40f;
    color: white;
    margin: 1rem 0;
}

.social-buttons .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.divider {
    margin: 2rem 0;
    text-align: center;
    position: relative;
}

.divider:before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: #eee;
    z-index: -1;
}

.divider span {
    background: white;
    padding: 0 15px;
    color: #999;
}

.destacar-section {
    margin: 2rem 0;
}

.destacar-info h4 {
    color: var(--primary-orange);
    margin-bottom: 0.5rem;
}

.destacar-info p {
    color: #666;
    margin-bottom: 1rem;
}

/* Responsive */
@media (max-width: 768px) {
    .brand-header-content {
        flex-direction: column;
        text-align: center;
    }
    
    .brand-logo {
        width: 100px;
        height: 100px;
    }
    
    .brand-title {
        font-size: 2rem;
    }
    
    .brand-navigation {
        flex-direction: column;
    }
    
    .brand-navigation .nav-link {
        justify-content: center;
    }
    
    .codes-grid {
        grid-template-columns: 1fr;
    }
    
    .sidebar-ad {
        position: static;
        margin-left: 0;
        margin-top: 2rem;
    }
}
</style>

<!-- JavaScript para funcionalidades de códigos -->
<script>
// Función para copiar código al portapapeles
function copiarCodigo(button) {
    const codeElement = button.parentElement.querySelector('.code-text');
    const codigo = codeElement.getAttribute('data-codigo');
    
    // Crear elemento temporal para copiar
    const tempInput = document.createElement('input');
    tempInput.value = codigo;
    document.body.appendChild(tempInput);
    tempInput.select();
    tempInput.setSelectionRange(0, 99999); // Para móviles
    
    try {
        // Intentar copiar al portapapeles
        const successful = document.execCommand('copy');
        if (successful) {
            // Cambiar texto del botón temporalmente
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i> Copiado!';
            button.style.background = '#28a745';
            
            // Restaurar después de 2 segundos
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.background = '';
            }, 2000);
            
            // Mostrar notificación
            mostrarNotificacion('¡Código copiado al portapapeles!', 'success');
        } else {
            mostrarNotificacion('No se pudo copiar el código', 'error');
        }
    } catch (err) {
        // Fallback para navegadores modernos
        if (navigator.clipboard) {
            navigator.clipboard.writeText(codigo).then(() => {
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i> Copiado!';
                button.style.background = '#28a745';
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.background = '';
                }, 2000);
                
                mostrarNotificacion('¡Código copiado al portapapeles!', 'success');
            }).catch(() => {
                mostrarNotificacion('No se pudo copiar el código', 'error');
            });
        } else {
            mostrarNotificacion('No se pudo copiar el código', 'error');
        }
    }
    
    document.body.removeChild(tempInput);
}

// Función para compartir código
function compartirCodigo(codigo) {
    const texto = `¡Encuentra este código promocional: ${codigo}`;
    const url = window.location.href;
    
    if (navigator.share) {
        // API nativa de compartir
        navigator.share({
            title: 'Código Promocional',
            text: texto,
            url: url
        }).catch(err => {
            console.log('Error al compartir:', err);
            compartirFallback(codigo, texto, url);
        });
    } else {
        // Fallback para navegadores sin soporte
        compartirFallback(codigo, texto, url);
    }
}

// Función de fallback para compartir
function compartirFallback(codigo, texto, url) {
    const shareText = `${texto} ${url}`;
    
    // Crear modal de compartir
    const modal = document.createElement('div');
    modal.className = 'share-modal';
    modal.innerHTML = `
        <div class="share-modal-content">
            <h3>Compartir código</h3>
            <p>${texto}</p>
            <div class="share-buttons">
                <a href="https://wa.me/?text=${encodeURIComponent(shareText)}" target="_blank" class="btn btn-whatsapp">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
                <a href="https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}" target="_blank" class="btn btn-facebook">
                    <i class="fab fa-facebook-f"></i> Facebook
                </a>
                <a href="https://twitter.com/intent/tweet?text=${encodeURIComponent(shareText)}" target="_blank" class="btn btn-twitter">
                    <i class="fab fa-twitter"></i> Twitter
                </a>
            </div>
            <button class="btn btn-secondary" onclick="this.closest('.share-modal').remove()">Cerrar</button>
        </div>
    `;
    
    document.body.appendChild(modal);
}

// Función para dar like a un código
function likeCodigo(codigoId) {
    const button = event.target.closest('.like-btn');
    
    // Cambiar estado visual inmediatamente
    button.classList.toggle('liked');
    const icon = button.querySelector('i');
    icon.classList.toggle('far');
    icon.classList.toggle('fas');
    
    // Enviar petición al servidor
    fetch('/ajax_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=like_codigo&codigo_id=${codigoId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacion('¡Código marcado como favorito!', 'success');
        } else {
            // Revertir cambio visual si falla
            button.classList.toggle('liked');
            icon.classList.toggle('far');
            icon.classList.toggle('fas');
            mostrarNotificacion('Error al marcar como favorito', 'error');
        }
    })
    .catch(error => {
        // Revertir cambio visual si falla
        button.classList.toggle('liked');
        icon.classList.toggle('far');
        icon.classList.toggle('fas');
        mostrarNotificacion('Error de conexión', 'error');
    });
}

// Función para mostrar notificaciones
function mostrarNotificacion(mensaje, tipo = 'info') {
    const notificacion = document.createElement('div');
    notificacion.className = `notificacion notificacion-${tipo}`;
    notificacion.textContent = mensaje;
    
    // Estilos de la notificación
    notificacion.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 10000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        max-width: 300px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    `;
    
    // Colores según tipo
    const colores = {
        success: '#28a745',
        error: '#dc3545',
        info: '#17a2b8',
        warning: '#ffc107'
    };
    
    notificacion.style.background = colores[tipo] || colores.info;
    
    document.body.appendChild(notificacion);
    
    // Animar entrada
    setTimeout(() => {
        notificacion.style.transform = 'translateX(0)';
    }, 100);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        notificacion.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notificacion.parentNode) {
                notificacion.parentNode.removeChild(notificacion);
            }
        }, 300);
    }, 3000);
}

// Estilos para el modal de compartir
const shareModalStyles = `
<style>
.share-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.share-modal-content {
    background: white;
    padding: 2rem;
    border-radius: 15px;
    max-width: 400px;
    width: 90%;
    text-align: center;
    color: #333;
}

.share-modal-content h3 {
    margin-bottom: 1rem;
    color: var(--primary-orange);
}

.share-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin: 1.5rem 0;
}

.share-buttons .btn {
    padding: 0.75rem 1rem;
    border-radius: 8px;
    text-decoration: none;
    color: white;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-whatsapp { background: #25d366; }
.btn-facebook { background: #3b5998; }
.btn-twitter { background: #1da1f2; }
.btn-secondary { background: #6c757d; }

.share-buttons .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}
</style>
`;

// Agregar estilos al head
document.head.insertAdjacentHTML('beforeend', shareModalStyles);
</script>

<?php get_footer(); ?>
