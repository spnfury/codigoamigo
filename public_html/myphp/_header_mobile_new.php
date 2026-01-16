<?php
/**
 * HEADER MÓVIL COMPLETAMENTE NUEVO
 *
 * Funcionalidades incluidas:
 * - Logo y navegación principal
 * - Campo de búsqueda funcional
 * - Menú lateral responsive con opciones completas
 * - Soporte para usuarios logueados y no logueados
 * - Diseño moderno con gradientes y animaciones
 */
?>
<header class="mobile-header-new">
    <?php global $data_usuario,$nombre_pag; ?>
        <style>
            /* Reset y variables */
            :root {
                --mobile-primary: #E30613;
                --mobile-secondary: #2C2C2C;
                --mobile-bg: #1a1a1a;
                --mobile-text: #ffffff;
                --mobile-border: #333333;
                --mobile-shadow: 0 2px 20px rgba(0,0,0,0.5);
                --mobile-radius: 12px;
                --mobile-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            /* Header principal */
            .mobile-header-new {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 1000;
                background: linear-gradient(135deg, var(--mobile-secondary) 0%, var(--mobile-bg) 100%);
                box-shadow: var(--mobile-shadow);
                height: 56px;
                display: flex;
                align-items: center;
                padding: 0 16px;
                backdrop-filter: blur(10px);
                border-bottom: 1px solid rgba(227, 6, 19, 0.1);
            }

            /* Logo */
            .mobile-logo {
                display: flex;
                align-items: center;
                text-decoration: none;
                color: var(--mobile-text);
                font-weight: 800;
                font-size: 18px;
                letter-spacing: -0.5px;
                flex-shrink: 0;
            }

            .mobile-logo .codigo {
                color: var(--mobile-primary);
            }

            .mobile-logo .amigo {
                color: var(--mobile-text);
            }

            /* Contenedor central - búsqueda */
            .mobile-search-container {
                flex: 1;
                max-width: 280px;
                margin: 0 12px;
                position: relative;
            }

            .mobile-search {
                width: 100%;
                height: 36px;
                background: rgba(255, 255, 255, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 18px;
                padding: 0 16px 0 40px;
                color: var(--mobile-text);
                font-size: 14px;
                outline: none;
                transition: var(--mobile-transition);
                backdrop-filter: blur(5px);
            }

            .mobile-search:focus {
                background: rgba(255, 255, 255, 0.15);
                border-color: var(--mobile-primary);
                box-shadow: 0 0 0 3px rgba(227, 6, 19, 0.2);
            }

            .mobile-search::placeholder {
                color: rgba(255, 255, 255, 0.6);
            }

            .mobile-search-icon {
                position: absolute;
                left: 12px;
                top: 50%;
                transform: translateY(-50%);
                color: rgba(255, 255, 255, 0.6);
                font-size: 16px;
                pointer-events: none;
            }

            /* Botones de acción */
            .mobile-actions {
                display: flex;
                align-items: center;
                gap: 8px;
                flex-shrink: 0;
            }

            .mobile-action-btn {
                width: 36px;
                height: 36px;
                border: none;
                border-radius: 10px;
                background: var(--mobile-primary);
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: var(--mobile-transition);
                font-size: 14px;
            }

            .mobile-action-btn:hover {
                background: #C40510;
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(227, 6, 19, 0.3);
            }

            .mobile-action-btn.user {
                background: rgba(255, 255, 255, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.2);
            }

            .mobile-action-btn.user:hover {
                background: rgba(255, 255, 255, 0.2);
            }

            .mobile-action-btn.user img {
                width: 100%;
                height: 100%;
                border-radius: 50%;
                object-fit: cover;
            }

            /* Menú lateral */
            .mobile-sidebar {
                position: fixed;
                top: 56px;
                left: 0;
                width: 300px;
                height: calc(100vh - 56px);
                background: linear-gradient(135deg, var(--mobile-secondary) 0%, var(--mobile-bg) 100%);
                transform: translateX(-100%);
                transition: var(--mobile-transition);
                z-index: 999;
                overflow-y: auto;
                box-shadow: 2px 0 20px rgba(0,0,0,0.5);
            }

            .mobile-sidebar.open {
                transform: translateX(0);
            }

            .mobile-sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 998;
                opacity: 0;
                visibility: hidden;
                transition: var(--mobile-transition);
            }

            .mobile-sidebar-overlay.show {
                opacity: 1;
                visibility: visible;
            }

            .mobile-sidebar-header {
                background: var(--mobile-primary);
                padding: 24px 20px;
                color: white;
                font-weight: 700;
                font-size: 20px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }

            .mobile-sidebar-close {
                background: none;
                border: none;
                color: white;
                font-size: 24px;
                cursor: pointer;
                padding: 0;
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                transition: var(--mobile-transition);
            }

            .mobile-sidebar-close:hover {
                background: rgba(255, 255, 255, 0.2);
            }

            .mobile-sidebar-nav {
                padding: 20px 0;
            }

            .mobile-sidebar-link {
                display: flex;
                align-items: center;
                padding: 16px 20px;
                color: var(--mobile-text);
                text-decoration: none;
                font-size: 16px;
                transition: var(--mobile-transition);
                border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            }

            .mobile-sidebar-link:hover {
                background: rgba(227, 6, 19, 0.1);
                color: var(--mobile-primary);
                padding-left: 24px;
            }

            .mobile-sidebar-link i {
                margin-right: 16px;
                width: 20px;
                text-align: center;
                color: var(--mobile-primary);
                font-size: 18px;
            }

            .mobile-sidebar-highlight {
                background: rgba(227, 6, 19, 0.15) !important;
                color: var(--mobile-primary) !important;
                font-weight: 600 !important;
            }

            .mobile-sidebar-divider {
                height: 1px;
                background: rgba(227, 6, 19, 0.2);
                margin: 12px 0;
            }

            /* Botón hamburguesa */
            .mobile-hamburger {
                display: flex;
                flex-direction: column;
                justify-content: center;
                cursor: pointer;
                padding: 8px;
                background: none;
                border: none;
                transition: var(--mobile-transition);
                border-radius: 8px;
            }

            .mobile-hamburger:hover {
                background: rgba(255, 255, 255, 0.1);
            }

            .mobile-hamburger span {
                display: block;
                width: 20px;
                height: 2px;
                background: var(--mobile-text);
                margin: 2px 0;
                transition: var(--mobile-transition);
                border-radius: 1px;
                transform-origin: center;
            }

            .mobile-hamburger.active span:nth-child(1) {
                transform: rotate(45deg) translate(4px, 4px);
            }

            .mobile-hamburger.active span:nth-child(2) {
                opacity: 0;
                transform: scale(0);
            }

            .mobile-hamburger.active span:nth-child(3) {
                transform: rotate(-45deg) translate(4px, -4px);
            }

            /* Responsive */
            @media (max-width: 480px) {
                .mobile-header-new {
                    height: 50px;
                    padding: 0 12px;
                }

                .mobile-sidebar {
                    width: 280px;
                    top: 50px;
                    height: calc(100vh - 50px);
                }

                .mobile-search-container {
                    max-width: 200px;
                    margin: 0 8px;
                }

                .mobile-search {
                    height: 32px;
                    font-size: 13px;
                    padding: 0 12px 0 32px;
                }

                .mobile-action-btn {
                    width: 32px;
                    height: 32px;
                }

                .mobile-sidebar-header {
                    padding: 20px 16px;
                    font-size: 18px;
                }

                .mobile-sidebar-link {
                    padding: 14px 16px;
                    font-size: 14px;
                }
            }

            @media (max-width: 360px) {
                .mobile-header-new {
                    height: 48px;
                    padding: 0 10px;
                }

                .mobile-sidebar {
                    width: 260px;
                    top: 48px;
                    height: calc(100vh - 48px);
                }

                .mobile-search-container {
                    max-width: 160px;
                    margin: 0 6px;
                }

                .mobile-search {
                    height: 30px;
                    font-size: 12px;
                    padding: 0 10px 0 28px;
                }

                .mobile-action-btn {
                    width: 30px;
                    height: 30px;
                }

                .mobile-sidebar-header {
                    padding: 16px 14px;
                    font-size: 16px;
                }

                .mobile-sidebar-link {
                    padding: 12px 14px;
                    font-size: 13px;
                }
            }

            /* Ajustar body para evitar scroll cuando el menú está abierto */
            body.menu-open {
                overflow: hidden;
            }

            /* Ocultar header desktop en móvil */
            @media (max-width: 768px) {
                .container-fluid:not(.mobile-header-new) {
                    margin-top: 56px !important;
                }
            }
        </style>

        <!-- Logo -->
        <a href="/" class="mobile-logo">
            <span class="codigo">codigo</span><span class="amigo">amigo</span>
        </a>

        <!-- Búsqueda -->
        <div class="mobile-search-container">
            <div class="mobile-search-icon">
                <i class="fa fa-search"></i>
            </div>
            <input type="text" class="mobile-search" placeholder="Buscar códigos...">
        </div>

        <!-- Acciones -->
        <div class="mobile-actions">
            <?php if(!empty($_SESSION["user_id"])){ ?>
                <button class="mobile-action-btn" onclick="window.location.href='nuevo_codigo'" title="Publicar código">
                    <i class="fa fa-plus"></i>
                </button>
                <button class="mobile-action-btn user" onclick="toggleProfileMenu()" title="Perfil">
                    <img src="<?php echo $data_usuario["img"] ?? '/img/po.png'; ?>" alt="Perfil">
                </button>
            <?php } else { ?>
                <button class="mobile-action-btn" onclick="if(typeof openLoginModalWithRedirect === 'function'){ openLoginModalWithRedirect('/nuevo_codigo'); } else { window.location.href='/nuevo_codigo'; }" title="Publicar código">
                    <i class="fa fa-plus"></i>
                </button>
                <button class="mobile-action-btn" onclick="if(typeof openLoginModalWithRedirect === 'function'){ openLoginModalWithRedirect(window.location.href); } else { window.location.href='/login.php'; }" title="Iniciar sesión">
                    <i class="fa fa-user"></i>
                </button>
            <?php } ?>

            <!-- Botón hamburguesa -->
            <button class="mobile-hamburger" onclick="toggleMobileMenu()">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>

        <!-- Sidebar -->
        <div class="mobile-sidebar-overlay" onclick="toggleMobileMenu()"></div>
        <nav class="mobile-sidebar" id="mobileSidebar">
            <div class="mobile-sidebar-header">
                <span>Códigoamigo.com</span>
                <button class="mobile-sidebar-close" onclick="closeMobileMenu()">&times;</button>
            </div>
            <div class="mobile-sidebar-nav">
                <a href="https://www.codigoamigo.com" class="mobile-sidebar-link">
                    <i class="fa fa-home"></i> Inicio
                </a>
                <a href="https://www.codigoamigo.com/listado-categorias" class="mobile-sidebar-link">
                    <i class="fa fa-list"></i> Categorías
                </a>
                <a href="https://www.codigoamigo.com/listado-marcas" class="mobile-sidebar-link">
                    <i class="fa fa-tags"></i> Todas las Marcas
                </a>
                <a href="https://www.codigoamigo.com/busqueda" class="mobile-sidebar-link">
                    <i class="fa fa-search"></i> Buscar códigos
                </a>
                <a href="https://www.codigoamigo.com/destaca" class="mobile-sidebar-link">
                    <i class="fa fa-star"></i> Códigos destacados
                </a>
                <a href="https://www.codigoamigo.com/contacto" class="mobile-sidebar-link">
                    <i class="fa fa-envelope"></i> Contacto
                </a>

                <?php if(!empty($_SESSION["user_id"])) { ?>
                    <div class="mobile-sidebar-divider"></div>
                    <a href="<?php echo link_nuevo_codigo(); ?>" class="mobile-sidebar-link mobile-sidebar-highlight">
                        <i class="fa fa-plus"></i> Publicar código
                    </a>
                    <a href="usuario" class="mobile-sidebar-link">
                        <i class="fa fa-user"></i> Editar datos
                    </a>
                    <a href="<?php echo link_usuario_nuevas_marcas($data_usuario["username"], (string)($data_usuario["id_string"]));?>" class="mobile-sidebar-link">
                        <i class="fas fa-exclamation"></i> Descubrir nuevas marcas
                    </a>
                    <a href="<?php echo link_usuario($_SESSION["username"], (string)($_SESSION["user_id"]));?>" class="mobile-sidebar-link">
                        <i class="fa fa-code"></i> Mis códigos
                    </a>
                    <a href="/invitar-amigos" class="mobile-sidebar-link" style="background: linear-gradient(135deg, #E30613, #FF4D4D); color: white; font-weight: 600;">
                        <i class="fa fa-gift"></i> Invita a tus amigos y gana dinero
                    </a>
                    <a href="usuario_marcas_que_no_tienes" class="mobile-sidebar-link">
                        <i class="fa fa-trophy"></i> Descubrir marcas
                    </a>
                    <a href="estadisticas" class="mobile-sidebar-link">
                        <i class="fa fa-chart-bar"></i> Estadísticas
                    </a>
                    
                    <?php 
                    // Verificar si el usuario es admin
                    $array_admins = [
                        "58bd851da54e295b8b52f702", //thevega82@gmail.com
                        "5e78170e6b68e6519b7c5df2", //edna
                        "639899bc6321ee0d0e4010d2", //aron
                        "5c8a10ce2f55c86d6e707d82"  //jose
                    ];
                    $es_admin = in_array($_SESSION["user_id"], $array_admins) || 
                                (isset($_SESSION["mail"]) && $_SESSION["mail"] === "thevega82@gmail.com");
                    
                    if($es_admin) { ?>
                    <a href="/admin" class="mobile-sidebar-link" style="color: #E30613; font-weight: 700;">
                        <i class="fa fa-cogs"></i> Panel de Admin
                    </a>
                    <?php } ?>
                    
                    <a href="logout" class="mobile-sidebar-link" onclick="signOut();">
                        <i class="fa fa-sign-out-alt"></i> Cerrar sesión
                    </a>
                <?php } else {?>
                    <div class="mobile-sidebar-divider"></div>
                    <a href="registro" class="mobile-sidebar-link mobile-sidebar-highlight">
                        <i class="fa fa-user-plus"></i> Crear cuenta
                    </a>
                    <a href="#" class="mobile-sidebar-link" onclick="if(typeof openLoginModalWithRedirect === 'function'){ openLoginModalWithRedirect(window.location.href); } else { window.location.href='/login.php'; }">
                        <i class="fa fa-sign-in-alt"></i> Iniciar sesión
                    </a>
                <?php } ?>
            </div>
        </nav>

        <!-- JavaScript -->
        <script>
            // Función principal para abrir/cerrar menú móvil
            function toggleMobileMenu() {
                const sidebar = document.getElementById('mobileSidebar');
                const overlay = document.querySelector('.mobile-sidebar-overlay');
                const hamburger = document.querySelector('.mobile-hamburger');

                if (sidebar && overlay && hamburger) {
                    sidebar.classList.toggle('open');
                    overlay.classList.toggle('show');
                    hamburger.classList.toggle('active');
                    document.body.classList.toggle('menu-open');

                    console.log('Menú móvil toggled:', sidebar.classList.contains('open') ? 'abierto' : 'cerrado');
                } else {
                    console.error('Elementos del menú móvil no encontrados');
                }
            }

            // Función específica para cerrar el menú móvil (prioridad alta)
            function closeMobileMenu() {
                const sidebar = document.getElementById('mobileSidebar');
                const overlay = document.querySelector('.mobile-sidebar-overlay');
                const hamburger = document.querySelector('.mobile-hamburger');

                if (sidebar) {
                    sidebar.classList.remove('open');
                    sidebar.style.transform = 'translateX(-100%)';
                    sidebar.style.transition = 'transform 0.3s ease';
                }
                if (overlay) {
                    overlay.classList.remove('show');
                    overlay.style.opacity = '0';
                    overlay.style.visibility = 'hidden';
                }
                if (hamburger) {
                    hamburger.classList.remove('active');
                }
                if (document.body.classList.contains('menu-open')) {
                    document.body.classList.remove('menu-open');
                    document.body.style.overflow = '';
                }

                // Prevenir conflictos con otros sistemas de menú
                if (typeof window.originalCloseMobileMenus === 'function') {
                    window.originalCloseMobileMenus();
                }

                console.log('Menú móvil cerrado completamente (prioridad alta)');
            }

            // Función específica para abrir el menú móvil (prioridad alta)
            function openMobileMenu() {
                const sidebar = document.getElementById('mobileSidebar');
                const overlay = document.querySelector('.mobile-sidebar-overlay');
                const hamburger = document.querySelector('.mobile-hamburger');

                if (sidebar) {
                    sidebar.classList.add('open');
                    sidebar.style.transform = 'translateX(0)';
                }
                if (overlay) {
                    overlay.classList.add('show');
                }
                if (hamburger) {
                    hamburger.classList.add('active');
                }
                document.body.classList.add('menu-open');

                console.log('Menú móvil abierto (prioridad alta)');
            }

            function toggleProfileMenu() {
                // Implementar menú de perfil si es necesario
                console.log('Toggle profile menu');
            }

            // Cerrar menú al hacer clic en enlaces
            document.querySelectorAll('.mobile-sidebar-link').forEach(link => {
                link.addEventListener('click', () => {
                    closeMobileMenu();
                });
            });

            // Cerrar menú al hacer clic fuera
            document.addEventListener('click', function(event) {
                const sidebar = document.getElementById('mobileSidebar');
                const overlay = document.querySelector('.mobile-sidebar-overlay');
                const hamburger = document.querySelector('.mobile-hamburger');

                if (!sidebar.contains(event.target) &&
                    !hamburger.contains(event.target) &&
                    sidebar.classList.contains('open')) {
                    closeMobileMenu();
                }
            });

            // Cerrar menú con tecla Escape
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    const sidebar = document.getElementById('mobileSidebar');
                    if (sidebar.classList.contains('open')) {
                        closeMobileMenu();
                    }
                }
            });

            // Búsqueda
            document.querySelector('.mobile-search').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    const query = this.value.trim();
                    if (query) {
                        window.location.href = '/busqueda?q=' + encodeURIComponent(query);
                    }
                }
            });
        </script>
    </header>
