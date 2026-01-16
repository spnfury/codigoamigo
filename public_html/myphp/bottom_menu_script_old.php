<?php
// Script para el menú inferior - archivo separado
header('Content-Type: application/javascript');

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir solo las dependencias necesarias
require_once dirname(__FILE__) . '/funciones.php';
require_once dirname(__FILE__) . '/funciones_modern.php';
require_once dirname(__FILE__) . '/funciones_usuario.php';
require_once dirname(__FILE__) . '/funciones_codigo.php';
require_once dirname(__FILE__) . '/../inc/conexion.php';

// Datos de sesión del servidor
$serverUserData = 'null';
if (isset($_SESSION["user_id"]) && !empty($_SESSION["user_id"])) {
    try {
        $usuario_completo = getObjectUserWithSession('_id', new MongoDB\BSON\ObjectId($_SESSION["user_id"]));
        if ($usuario_completo) {
            $userData = [
                'id' => $_SESSION["user_id"],
                'username' => $usuario_completo['username'] ?? $_SESSION["username"] ?? 'Usuario',
                'mail' => $usuario_completo['mail'] ?? $_SESSION["mail"] ?? '',
                'img' => $usuario_completo['img'] ?? $_SESSION["img"] ?? 'https://www.codigoamigo.com/img/utilidades/usuario_sin_foto.jpg',
                'avatar' => $usuario_completo['img'] ?? $_SESSION["img"] ?? 'https://www.codigoamigo.com/img/utilidades/usuario_sin_foto.jpg'
            ];
            $serverUserData = json_encode($userData);
        }
    } catch (Exception $e) {
        $serverUserData = 'null';
    }
}
?>




// Datos del servidor
window.serverUserData = <?php echo $serverUserData; ?>;

// Lógica del botón de login
const profileToggle = document.getElementById('profile-toggle');
if (profileToggle) {
    profileToggle.addEventListener('click', function(e) {
        e.preventDefault();
        console.log('Botón Inicia sesión clickeado (móvil)');

        if (!window.serverUserData || window.serverUserData === null) {
            console.log('Usuario no logueado - mostrando modal de login');
            localStorage.setItem('redirectAfterLogin', window.location.href);
            console.log('URL de redirección guardada:', window.location.href);
            $('.modal').modal('hide');
            setTimeout(function() {
                $('#modal_login').modal('show');
            }, 300);
        }
    });
}

// Funcionalidad de los botones de navegación
const homeNav = document.getElementById('home-nav');
const categoriesNav = document.getElementById('categories-nav');
const brandsNav = document.getElementById('brands-nav');
const favoritesNav = document.getElementById('favorites-nav');

if (homeNav) {
    homeNav.addEventListener('click', function(e) {
        e.preventDefault();
        console.log('Botón Inicio clickeado (móvil)');
        window.location.href = '/';
    });
}

if (categoriesNav) {
    categoriesNav.addEventListener('click', function(e) {
        e.preventDefault();
        console.log('Botón Categorías clickeado (móvil)');
        window.location.href = '/categorias';
    });
}

if (brandsNav) {
    brandsNav.addEventListener('click', function(e) {
        e.preventDefault();
        console.log('Botón Marcas clickeado (móvil)');
        window.location.href = '/listado_marcas';
    });
}

// Botón de favoritos - requiere login
if (favoritesNav) {
    favoritesNav.addEventListener('click', function(e) {
        e.preventDefault();
        console.log('Botón Favoritos clickeado (móvil)');

        if (window.serverUserData && window.serverUserData !== null) {
            console.log('Usuario logueado - redirigiendo a /favoritos');
            window.location.href = '/favoritos';
        } else {
            console.log('Usuario no logueado - guardando URL y mostrando modal');
            localStorage.setItem('redirectAfterLogin', '/favoritos');
            console.log('URL de redirección guardada: /favoritos');
            $('.modal').modal('hide');
            setTimeout(function() {
                $('#modal_login').modal('show');
            }, 300);
        }
    });
}

// Inicializar
checkUserSession();
window.updateBottomMenu = function() { checkUserSession(); };

// Funcionalidad del botón Publicar código
document.addEventListener('DOMContentLoaded', function() {
    const publishCodeBtn = document.getElementById('publish-code-btn');
    if (publishCodeBtn) {
        console.log('Botón Publicar Código encontrado en DOM');
        publishCodeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Botón Publicar Código clickeado (móvil)');
            console.log('Estado del usuario:', window.serverUserData);

            if (window.serverUserData && window.serverUserData !== null && window.serverUserData.id) {
                console.log('Usuario logueado - redirigiendo a /nuevo_codigo');
                window.location.href = '/nuevo_codigo';
            } else {
                console.log('Usuario no logueado - guardando URL y mostrando modal');
                localStorage.setItem('redirectAfterLogin', '/nuevo_codigo');
                console.log('URL de redirección guardada: /nuevo_codigo');

                // Cerrar todos los modales y menús antes de mostrar el modal de login
                $('.modal').modal('hide');
                closeMobileMenus();

                setTimeout(function() {
                    console.log('Mostrando modal de login');
                    $('#modal_login').modal('show');
                }, 300);
            }
        });
    } else {
        console.error('Botón Publicar Código NO encontrado en DOM con ID: publish-code-btn');
    }
});

// Funcionalidad del menú hamburguesa
const menuToggle = document.getElementById('menu-toggle');
const hamburgerMenu = document.getElementById('mobile-hamburger-menu');
const closeHamburgerMenu = document.getElementById('close-hamburger-menu');

if (menuToggle && hamburgerMenu) {
    menuToggle.addEventListener('click', function(e) {
        e.preventDefault();
        hamburgerMenu.classList.add('show');
        document.body.style.overflow = 'hidden';
    });

    if (closeHamburgerMenu) {
        closeHamburgerMenu.addEventListener('click', function() {
            hamburgerMenu.classList.remove('show');
            document.body.style.overflow = '';
        });
    }

    hamburgerMenu.addEventListener('click', function(e) {
        if (e.target === hamburgerMenu) {
            hamburgerMenu.classList.remove('show');
            document.body.style.overflow = '';
        }
    });
}


// Función para manejar login/logout y actualizar menú
function updateMenuSession() {
    localStorage.setItem('user_session_changed', Date.now());
    checkUserSession();
}

// Escuchar cambios en el almacenamiento local para detectar cambios de sesión
window.addEventListener('storage', function(e) {
    if (e.key === 'user_session_changed') {
        checkUserSession();
    }
});

// Función para cerrar menús desplegables
function closeMobileMenus() {
    // Cerrar menú hamburguesa del footer
    const hamburgerMenu = document.getElementById('mobile-hamburger-menu');
    const profileMenu = document.getElementById('mobile-profile-menu');
    if (hamburgerMenu) hamburgerMenu.classList.remove('show');
    if (profileMenu) profileMenu.classList.remove('show');

    // Cerrar menú móvil del header
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
    if (mobileMenuToggle) mobileMenuToggle.classList.remove('active');
    if (mobileMenu) mobileMenu.classList.remove('show');
    if (mobileMenuOverlay) mobileMenuOverlay.classList.remove('show');

    document.body.style.overflow = '';
}

// Cerrar menús cuando se hace clic en enlaces del menú hamburguesa
document.addEventListener('DOMContentLoaded', function() {
    const hamburgerMenuItems = document.querySelectorAll('.hamburger-menu-item');
    hamburgerMenuItems.forEach(function(item) {
        item.addEventListener('click', function() {
            closeMobileMenus();
        });
    });
});

// Cerrar menús cuando se hace clic en enlaces del menú de perfil
const profileMenuItems = document.querySelectorAll('.profile-menu-item');
profileMenuItems.forEach(function(item) {
    item.addEventListener('click', function() {
        closeMobileMenus();
    });
});

// Cerrar menús al presionar tecla Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeMobileMenus();
    }
});

// Función checkUserSession
function checkUserSession() {
    const profileIcon = document.getElementById('profile-icon');
    const profileImage = document.getElementById('profile-image');
    const profileText = document.getElementById('profile-text');
    const profileMenuImage = document.getElementById('profile-menu-image');
    const profileMenuName = document.getElementById('profile-menu-name');
    const profileMenuEmail = document.getElementById('profile-menu-email');

    if (window.serverUserData && window.serverUserData !== null) {
        const userData = window.serverUserData;
        if (profileIcon) profileIcon.style.display = 'none';
        if (profileImage) {
            profileImage.style.display = 'block';
            profileImage.src = userData.img;
            profileImage.alt = userData.username;
        }
        if (profileText) profileText.textContent = 'Perfil';
        if (profileMenuImage) {
            profileMenuImage.src = userData.img;
            profileMenuImage.alt = userData.username;
            profileMenuImage.style.display = 'block';
        }
        if (profileMenuName) profileMenuName.textContent = userData.username;
        if (profileMenuEmail) profileMenuEmail.textContent = userData.mail || 'No hay email disponible';
    } else {
        if (profileIcon) profileIcon.style.display = 'block';
        if (profileImage) profileImage.style.display = 'none';
        if (profileText) profileText.textContent = 'Inicia sesión';
        if (profileMenuImage) profileMenuImage.style.display = 'none';
        if (profileMenuName) profileMenuName.textContent = 'Invitado';
        if (profileMenuEmail) profileMenuEmail.textContent = 'Inicia sesión para ver tu perfil';
    }
}
