<?php
/**
 * HEADER MÓVIL - ICONOTIPO + BUSCADOR + HAMBURGUESA
 *
 * Layout estilo Wallapop:
 * [Iconotipo] [Buscador expandido] [☰ Hamburguesa]
 *
 * - Iconotipo (moneda) en vez de logo texto
 * - Buscador ocupa todo el espacio central
 * - Menú hamburguesa a la derecha del buscador
 * - Panel deslizante con navegación completa
 */
?>
<header class="mobile-header-new">
    <?php global $data_usuario, $nombre_pag; ?>

        <!-- Iconotipo (logo compacto) -->
        <a href="/" class="mobile-iconotype">
            <img src="/img/favicon_moneda_real.png" alt="CodigoAmigo" class="iconotype-img">
        </a>

        <!-- Búsqueda expandida -->
        <div class="mobile-search-container">
            <div class="mobile-search-icon">
                <i class="fa fa-search"></i>
            </div>
            <input type="text" class="mobile-search" placeholder="Buscar códigos...">
        </div>

        <!-- Botón Hamburguesa -->
        <button class="mobile-hamburger-btn" id="mobileHamburgerBtn" aria-label="Menú">
            <span></span>
            <span></span>
            <span></span>
        </button>

    <!-- Overlay -->
    <div class="mobile-slide-overlay" id="mobileSlideOverlay"></div>

    <!-- Menú Deslizante -->
    <nav class="mobile-slide-menu" id="mobileSlideMenu">
        <div class="mobile-slide-header">
            <a href="/" class="mobile-slide-logo">
                <img src="/img/favicon_moneda_real.png" alt="CodigoAmigo" class="slide-logo-img">
                <span class="slide-logo-text">
                    <span class="sl-codigo">codigo</span><span class="sl-amigo">amigo</span>
                </span>
            </a>
            <button class="mobile-slide-close" id="mobileSlideClose" aria-label="Cerrar menú">
                <i class="fa fa-times"></i>
            </button>
        </div>

        <!-- Perfil de usuario o Login -->
        <div class="mobile-slide-user">
            <?php if(!empty($_SESSION["user_id"])){ ?>
                <div class="slide-user-info">
                    <img src="<?php echo $data_usuario["img"] ?? '/img/po.png'; ?>" alt="Perfil" class="slide-user-avatar">
                    <div class="slide-user-details">
                        <span class="slide-user-name"><?php echo $data_usuario["username"] ?? 'Usuario'; ?></span>
                        <a href="/usuario" class="slide-user-link">Ver perfil →</a>
                    </div>
                </div>
            <?php } else { ?>
                <button class="slide-login-btn" onclick="if(typeof openLoginModalWithRedirect === 'function'){ openLoginModalWithRedirect(window.location.href); } else { window.location.href='/login.php'; }">
                    <i class="fa fa-user"></i> Iniciar sesión
                </button>
            <?php } ?>
        </div>

        <!-- Links de navegación -->
        <div class="mobile-slide-nav">
            <a href="/" class="slide-nav-item">
                <i class="fas fa-home"></i> Inicio
            </a>
            <?php if(!empty($_SESSION["user_id"])){ ?>
            <a href="/nuevo_codigo" class="slide-nav-item slide-nav-highlight">
                <i class="fas fa-plus-circle"></i> Publicar Código
            </a>
            <?php } ?>
            <a href="/marcas" class="slide-nav-item">
                <i class="fas fa-tags"></i> Marcas
            </a>
            <a href="/categorias" class="slide-nav-item">
                <i class="fas fa-list"></i> Categorías
            </a>
            <?php
            if (!function_exists('get_active_super_landings')) {
                $sl_path = __DIR__ . '/_super_landing_functions.php';
                if (file_exists($sl_path)) include_once $sl_path;
            }
            if (function_exists('get_active_super_landings')) {
                $guias = get_active_super_landings();
                if (!empty($guias)) { ?>
                    <div class="slide-nav-divider"></div>
                    <span class="slide-nav-section">Guías</span>
                    <?php foreach($guias as $guia) { ?>
                        <a href="/guias/<?php echo $guia['slug']; ?>" class="slide-nav-item">
                            <i class="fas fa-book"></i> <?php echo $guia['title']; ?>
                        </a>
                    <?php }
                }
            }
            ?>
            <div class="slide-nav-divider"></div>
            <a href="https://www.malprecio.com/chollos-shorts" target="_blank" class="slide-nav-item slide-nav-shorts">
                <i class="fas fa-play-circle"></i> Shorts <span class="slide-badge-new">NUEVO</span>
            </a>
        </div>

        <!-- Acciones de usuario logueado -->
        <?php if(!empty($_SESSION["user_id"])){ ?>
        <div class="mobile-slide-nav" style="margin-top: 0;">
            <div class="slide-nav-divider"></div>
            <span class="slide-nav-section">Mi Cuenta</span>
            <a href="/mis-anuncios" class="slide-nav-item">
                <i class="fas fa-code"></i> Mis Códigos
            </a>
            <a href="/afiliados" class="slide-nav-item">
                <i class="fas fa-link"></i> Mis URLs Afiliados
            </a>
            <a href="/chat" class="slide-nav-item">
                <i class="fas fa-comments"></i> Chat
            </a>
            <a href="/invitar-amigos" class="slide-nav-item" style="color: #E30613;">
                <i class="fas fa-gift"></i> Invitar Amigos
            </a>
        </div>
        <?php } ?>
    </nav>

    <!-- JavaScript -->
    <script>
        (function() {
            var hamburgerBtn = document.getElementById('mobileHamburgerBtn');
            var slideMenu = document.getElementById('mobileSlideMenu');
            var slideOverlay = document.getElementById('mobileSlideOverlay');
            var slideClose = document.getElementById('mobileSlideClose');

            function openMenu() {
                hamburgerBtn.classList.add('active');
                slideMenu.classList.add('open');
                slideOverlay.classList.add('open');
                document.body.style.overflow = 'hidden';
            }

            function closeMenu() {
                hamburgerBtn.classList.remove('active');
                slideMenu.classList.remove('open');
                slideOverlay.classList.remove('open');
                document.body.style.overflow = '';
            }

            hamburgerBtn.addEventListener('click', function() {
                if (slideMenu.classList.contains('open')) {
                    closeMenu();
                } else {
                    openMenu();
                }
            });

            slideOverlay.addEventListener('click', closeMenu);
            slideClose.addEventListener('click', closeMenu);

            // Búsqueda
            document.querySelector('.mobile-search').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    var query = this.value.trim();
                    if (query) {
                        window.location.href = '/busqueda?q=' + encodeURIComponent(query);
                    }
                }
            });
        })();
    </script>
</header>
