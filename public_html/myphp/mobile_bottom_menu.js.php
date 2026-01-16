<?php
// Generar JavaScript para el menú inferior móvil
header('Content-Type: application/javascript');

// Obtener datos del usuario si está logueado
$isLoggedIn = isset($_SESSION["user_id"]) && !empty($_SESSION["user_id"]);
$userDataScript = 'null';

if ($isLoggedIn) {
    try {
        $user_info = get_user_info($_SESSION["user_id"]);
        $userData = [
            'username' => $user_info['username'] ?? 'Usuario',
            'img' => $user_info['img'] ?? 'https://www.codigoamigo.com/img/utilidades/usuario_sin_foto.jpg',
            'mail' => $user_info['mail'] ?? '',
            'id' => $user_info['id'] ?? $_SESSION["user_id"]
        ];
        $userDataScript = json_encode($userData);
    } catch (Exception $e) {
        $userDataScript = 'null';
    }
}

$isLoggedInScript = $isLoggedIn ? 'true' : 'false';
?>

// Función para verificar si el usuario está logueado
function checkUserSession() {
    const isLoggedIn = <?php echo $isLoggedInScript; ?>;
    const userData = <?php echo $userDataScript; ?>;

    const profileIcon = document.getElementById('profile-icon');
    const profileImage = document.getElementById('profile-image');
    const profileText = document.getElementById('profile-text');
    const profileMenuImage = document.getElementById('profile-menu-image');
    const profileMenuName = document.getElementById('profile-menu-name');
    const profileMenuEmail = document.getElementById('profile-menu-email');

    if (isLoggedIn && userData) {
        // Usuario logueado - mostrar foto y datos
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
        // Usuario no logueado - mostrar icono de usuario
        if (profileIcon) profileIcon.style.display = 'block';
        if (profileImage) profileImage.style.display = 'none';
        if (profileText) profileText.textContent = 'Inicia sesión';
        if (profileMenuImage) profileMenuImage.style.display = 'none';
        if (profileMenuName) profileMenuName.textContent = 'Invitado';
        if (profileMenuEmail) profileMenuEmail.textContent = 'Inicia sesión para ver tu perfil';
    }
}

// Verificar sesión al cargar la página
checkUserSession();

// Función para actualizar el menú inferior cuando cambie la sesión
window.updateBottomMenu = function() {
    checkUserSession();
};

// Escuchar cambios en el almacenamiento local para detectar cambios de sesión
window.addEventListener('storage', function(e) {
    if (e.key === 'user_session_changed') {
        checkUserSession();
    }
});
