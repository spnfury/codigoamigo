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




// Datos del servidor - MODO SILENCIOSO (solo logs en desarrollo)
var isDevHost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
var devLog = isDevHost ? console.log.bind(console) : function() {};
var devError = isDevHost ? console.error.bind(console) : function() {};

if (isDevHost) {
    devLog('=== BOTTOM MENU SCRIPT - MODO AGRESIVO ===');
    devLog('window.serverUserData ANTES:', window.serverUserData);
}

// Usar datos del servidor PHP o respaldo de header moderno si existe
var finalUserData = <?php echo $serverUserData; ?>;
if (!finalUserData && window.serverUserDataBasic) {
    finalUserData = window.serverUserDataBasic;
    if (isDevHost) {
        devLog('Usando datos de respaldo del header moderno');
    }
}

function setUserDataFromBottomMenu(user) {
    var updatedViaHeader = false;

    if (user && typeof window.applyUserSession === 'function') {
        window.applyUserSession(user);
        updatedViaHeader = true;
    } else if (!user && typeof window.clearUserSession === 'function') {
        window.clearUserSession();
        updatedViaHeader = true;
    } else {
        window.serverUserData = user || null;
        if (typeof window.serverUserDataBasic !== 'undefined') {
            window.serverUserDataBasic = user || null;
        }
    }

    if (!updatedViaHeader && typeof window.serverUserData === 'undefined') {
        window.serverUserData = user || null;
    }

    var bottomMenuHandled = typeof window.updateBottomMenu === 'function';
    if (!bottomMenuHandled && typeof checkUserSession === 'function') {
        checkUserSession();
    }
}

setUserDataFromBottomMenu(finalUserData);
if (isDevHost) {
    devLog('Datos de sesión establecidos por bottom_menu_script:', window.serverUserData);
}

// Función checkUserSession - MODO SILENCIOSO (solo logs en desarrollo)
function checkUserSession() {
    // Solo mostrar logs en desarrollo
    if (isDevHost) {
        devLog('=== checkUserSession - MODO AGRESIVO ===');
        devLog('window.serverUserData:', window.serverUserData);
        devLog('Tipo:', typeof window.serverUserData);
        devLog('Es null:', window.serverUserData === null);
        devLog('Es undefined:', typeof window.serverUserData === 'undefined');
    }

    const profileIcon = document.getElementById('profile-icon');
    const profileImage = document.getElementById('profile-image');
    const profileText = document.getElementById('profile-text');
    const profileMenuImage = document.getElementById('profile-menu-image');
    const profileMenuName = document.getElementById('profile-menu-name');
    const profileMenuEmail = document.getElementById('profile-menu-email');

    // Solo mostrar logs de elementos en desarrollo
    if (isDevHost) {
        devLog('Elementos encontrados:', {
            profileIcon: !!profileIcon,
            profileImage: !!profileImage,
            profileText: !!profileText,
            profileMenuImage: !!profileMenuImage,
            profileMenuName: !!profileMenuName,
            profileMenuEmail: !!profileMenuEmail
        });
    }

    if (window.serverUserData && window.serverUserData !== null && window.serverUserData.id) {
        const userData = window.serverUserData;
        // Solo mostrar logs en desarrollo
        if (isDevHost) {
            devLog('Usuario logueado detectado:', userData.username);
        }

        if (profileIcon) profileIcon.style.display = 'none';
        if (profileImage) {
            profileImage.style.display = 'block';
            profileImage.src = userData.img;
            profileImage.alt = userData.username;
        }
        if (profileText) {
            profileText.textContent = 'Perfil';
        }
        if (profileMenuImage) {
            profileMenuImage.src = userData.img;
            profileMenuImage.alt = userData.username;
            profileMenuImage.style.display = 'block';
        }
        if (profileMenuName) profileMenuName.textContent = userData.username;
        if (profileMenuEmail) profileMenuEmail.textContent = userData.mail || 'No hay email disponible';
    } else {
        // Solo mostrar logs en desarrollo
        if (isDevHost) {
            devLog('Usuario no logueado o datos incompletos');
        }
        if (profileIcon) profileIcon.style.display = 'block';
        if (profileImage) profileImage.style.display = 'none';
        if (profileText) profileText.textContent = 'Inicia sesión';
        if (profileMenuImage) profileMenuImage.style.display = 'none';
        if (profileMenuName) profileMenuName.textContent = 'Invitado';
        if (profileMenuEmail) profileMenuEmail.textContent = 'Inicia sesión para ver tu perfil';
    }
}

// Lógica del botón de login/perfil
const profileToggle = document.getElementById('profile-toggle');
if (profileToggle) {
    profileToggle.addEventListener('click', function(e) {
        e.preventDefault();
        devLog('Botón Perfil clickeado (móvil)');

        if (!window.serverUserData || window.serverUserData === null) {
            devLog('Usuario no logueado - mostrando modal de login');
            localStorage.setItem('redirectAfterLogin', window.location.href);
            devLog('URL de redirección guardada:', window.location.href);
            $('.modal').modal('hide');
            setTimeout(function() {
                $('#modal_login').modal('show');
            }, 300);
        } else {
            devLog('Usuario logueado - mostrando menú de perfil');
            const profileMenu = document.getElementById('mobile-profile-menu');
            if (profileMenu) {
                profileMenu.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        }
    });
}

// Funcionalidad de los botones de navegación
const homeNav = document.getElementById('home-nav');
const categoriesNav = document.getElementById('categories-nav');
const favoritesNav = document.getElementById('favorites-nav');

if (homeNav) {
    homeNav.addEventListener('click', function(e) {
        e.preventDefault();
        devLog('Botón Inicio clickeado (móvil)');
        window.location.href = '/';
    });
}

if (categoriesNav) {
    categoriesNav.addEventListener('click', function(e) {
        e.preventDefault();
        devLog('Botón Categorías clickeado (móvil)');
        window.location.href = '/categorias';
    });
}

// Botón de favoritos - requiere login
if (favoritesNav) {
    favoritesNav.addEventListener('click', function(e) {
        e.preventDefault();
        devLog('Botón Favoritos clickeado (móvil)');

        if (window.serverUserData && window.serverUserData !== null) {
            devLog('Usuario logueado - redirigiendo a /favoritos');
            window.location.href = '/favoritos';
        } else {
            devLog('Usuario no logueado - guardando URL y mostrando modal');
            if (typeof window.openLoginModalWithRedirect === 'function') {
                window.openLoginModalWithRedirect('/favoritos');
            } else {
                localStorage.setItem('redirectAfterLogin', '/favoritos');
                $('.modal').modal('hide');
                setTimeout(function() {
                    $('#modal_login').modal('show');
                }, 300);
            }
        }
    });
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    devLog('DOM cargado, ejecutando checkUserSession...');
    checkUserSession();
});

// También ejecutar inmediatamente por si el DOM ya está listo
checkUserSession();
window.updateBottomMenu = function() { checkUserSession(); };

// Monitoreo periódico para detectar cambios en los datos de sesión y validar con el servidor
let lastSessionCheck = Date.now();
setInterval(function() {
    // Si no hay datos de sesión, intentar restaurar desde el servidor
    if (!window.serverUserData || window.serverUserData === null) {
        var finalUserData = <?php echo $serverUserData; ?>;
        if (!finalUserData && window.serverUserDataBasic) {
            finalUserData = window.serverUserDataBasic;
        }
        setUserDataFromBottomMenu(finalUserData);
    } else {
        // Si hay datos de sesión, verificar periódicamente con el servidor (cada 30 segundos)
        const now = Date.now();
        if (now - lastSessionCheck > 30000) { // 30 segundos
            lastSessionCheck = now;
            
            // Verificar con el servidor si la sesión sigue activa
            $.ajax({
                type: "POST",
                url: "/myphp/ajax_actions.php",
                data: {
                    metodo: "check_session"
                },
                cache: false,
                timeout: 3000,
                success: function(data) {
                    try {
                        var response;
                        if (typeof data === 'string') {
                            response = JSON.parse(data.trim());
                        } else {
                            response = data;
                        }

                        if (!response.success || !response.logged_in) {
                            // Sesión expirada - limpiar datos del frontend
                            setUserDataFromBottomMenu(null);
                        } else if (response.user) {
                            // Actualizar datos del usuario si han cambiado
                            setUserDataFromBottomMenu(response.user);
                        }
                    } catch (e) {
                        // Error al procesar respuesta - ignorar
                    }
                },
                error: function() {
                    // Error en la petición - ignorar
                }
            });
        }
    }
}, 5000); // Verificar cada 5 segundos

// Funcionalidad del botón Publicar código
document.addEventListener('DOMContentLoaded', function() {
    const publishCodeBtn = document.getElementById('publish-code-btn');
    if (publishCodeBtn) {
        devLog('Botón Publicar Código encontrado en DOM');
        publishCodeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            devLog('Botón Publicar Código clickeado (móvil)');
            devLog('Estado del usuario:', window.serverUserData);

            // Verificar primero si el usuario está logueado en el frontend
            if (!window.serverUserData || window.serverUserData === null || !window.serverUserData.id) {
                devLog('Usuario no logueado - mostrando modal de login via openLoginModalWithRedirect');
                
                if (typeof window.openLoginModalWithRedirect === 'function') {
                    window.openLoginModalWithRedirect('/nuevo_codigo');
                } else {
                    localStorage.setItem('redirectAfterLogin', '/nuevo_codigo');
                    $('.modal').modal('hide');
                    if (typeof closeMobileMenus === 'function') {
                        closeMobileMenus();
                    }
                    setTimeout(function() {
                        $('#modal_login').modal('show');
                    }, 300);
                }
                return;
            }

            // Usuario parece estar logueado en frontend - verificar sesión en servidor antes de redirigir
            devLog('Verificando sesión en servidor antes de redirigir...');
            $.ajax({
                type: "POST",
                url: "/myphp/ajax_actions.php",
                data: {
                    metodo: "check_session"
                },
                cache: false,
                timeout: 5000,
                success: function(data) {
                    devLog('Respuesta del servidor:', data);
                    try {
                        var response;
                        if (typeof data === 'string') {
                            response = JSON.parse(data.trim());
                        } else {
                            response = data;
                        }

                        devLog('Respuesta parseada:', response);

                        if (response.success === true && response.logged_in === true) {
                            // Sesión válida - redirigir al formulario
                            devLog('Sesion valida - redirigiendo a /nuevo_codigo');
                            window.location.href = '/nuevo_codigo';
                        } else {
                            // Sesión expirada o inválida - limpiar datos del frontend y mostrar modal de login
                            devLog('Sesion expirada o invalida:', response.message || 'Sin mensaje');
                            setUserDataFromBottomMenu(null);

                            if (typeof window.openLoginModalWithRedirect === 'function') {
                                window.openLoginModalWithRedirect('/nuevo_codigo');
                            } else {
                                localStorage.setItem('redirectAfterLogin', '/nuevo_codigo');
                                $('.modal').modal('hide');
                                if (typeof closeMobileMenus === 'function') {
                                    closeMobileMenus();
                                }
                                
                                setTimeout(function() {
                                    $('#modal_login').modal('show');
                                }, 300);
                            }
                        }
                    } catch (e) {
                        devError('Error procesando respuesta de verificacion de sesion:', e, data);
                        // Si hay error parseando, asumir que la sesión no es válida y mostrar login
                        setUserDataFromBottomMenu(null);
                        
                        if (typeof window.openLoginModalWithRedirect === 'function') {
                            window.openLoginModalWithRedirect('/nuevo_codigo');
                        } else {
                            localStorage.setItem('redirectAfterLogin', '/nuevo_codigo');
                            $('.modal').modal('hide');
                            if (typeof closeMobileMenus === 'function') {
                                closeMobileMenus();
                            }
                            
                            setTimeout(function() {
                                $('#modal_login').modal('show');
                            }, 300);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    devError('Error verificando sesion:', error, xhr.responseText);
                    // Si hay error en la petición, asumir que la sesión no es válida y mostrar login
                    setUserDataFromBottomMenu(null);
                    
                    if (typeof window.openLoginModalWithRedirect === 'function') {
                        window.openLoginModalWithRedirect('/nuevo_codigo');
                    } else {
                        localStorage.setItem('redirectAfterLogin', '/nuevo_codigo');
                        $('.modal').modal('hide');
                        if (typeof closeMobileMenus === 'function') {
                            closeMobileMenus();
                        }
                        
                        setTimeout(function() {
                            $('#modal_login').modal('show');
                        }, 300);
                    }
                }
            });
        });
    } else {
        devError('Botón Publicar Código NO encontrado en DOM con ID: publish-code-btn');
    }
});

// Menú hamburguesa eliminado - la navegación se hace desde el bottom nav

// Funcionalidad del menú de perfil
const profileMenu = document.getElementById('mobile-profile-menu');
const closeProfileMenu = document.getElementById('close-profile-menu');

if (profileMenu && closeProfileMenu) {
    // Cerrar menú con el botón X
    closeProfileMenu.addEventListener('click', function() {
        profileMenu.classList.remove('show');
        document.body.style.overflow = '';
    });

    // Cerrar menú al hacer clic fuera
    profileMenu.addEventListener('click', function(e) {
        if (e.target === profileMenu) {
            profileMenu.classList.remove('show');
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
    // Cerrar menú de perfil
    const profileMenu = document.getElementById('mobile-profile-menu');
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



// Cerrar menús cuando se hace clic en enlaces del menú de perfil
function setupProfileMenuLogoutHandler() {
    const profileMenuItems = document.querySelectorAll('.profile-menu-item');
    profileMenuItems.forEach(function(item) {
        item.addEventListener('click', function(e) {
            // Si es el botón de logout, manejar especialmente
            if (item.classList.contains('logout')) {
                e.preventDefault();
                devLog('Botón logout clickeado desde menú móvil');

                // Limpiar datos de sesión
                localStorage.removeItem('userData');
                sessionStorage.removeItem('userData');
                localStorage.removeItem('user_session_changed');

                // Limpiar datos del servidor
                setUserDataFromBottomMenu(null);

                // Redirigir a la página de logout
                window.location.href = '/logout';
                return;
            }

            // Para otros elementos del menú, solo cerrar menús
            closeMobileMenus();
        });
    });
}

// Configurar el handler cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    setupProfileMenuLogoutHandler();
});

// También intentar configurar inmediatamente por si el DOM ya está listo
setupProfileMenuLogoutHandler();

// Cerrar menús al presionar tecla Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeMobileMenus();
    }
});
