<?php
    session_start();
    session_destroy();

    // Incluir configuración para obtener la URL correcta
    include_once __DIR__ . '/../config/app.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Hasta pronto! - CodigoAmigo</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: center;
            color: white;
        }

        .logout-container {
            max-width: 400px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logout-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.8;
        }

        .logout-title {
            font-size: 2rem;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .logout-message {
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.6;
            opacity: 0.9;
        }

        .redirect-text {
            font-size: 0.9rem;
            opacity: 0.7;
            margin-top: 20px;
        }

        .logo {
            margin-bottom: 20px;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .logo i {
            margin-right: 10px;
        }

        .loading-dots {
            display: inline-block;
            font-size: 1.2rem;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logo">
            <i class="fas fa-fire"></i>
            CodigoAmigo
        </div>

        <div class="logout-icon">
            <i class="fas fa-door-open"></i>
        </div>

        <h1 class="logout-title">¡Te esperamos pronto!</h1>

        <p class="logout-message">
            Gracias por usar CodigoAmigo. Has cerrado sesión correctamente.
        </p>

        <div class="redirect-text">
            Redirigiendo al inicio<span class="loading-dots">...</span>
        </div>
    </div>

    <script>
        // Limpiar almacenamiento local y de sesión para evitar el "usuario zombie"
        try {
            localStorage.removeItem('userData');
            sessionStorage.removeItem('userData');
            localStorage.removeItem('user_session_changed');
            localStorage.removeItem('redirectAfterLogin');
            
            // Si el header moderno usa otras claves, limpiarlas también
            localStorage.clear(); // Limpiar todo para estar seguros en el logout
            sessionStorage.clear();
            
            // Resetear variables globales
            window.currentUserId = '';
            window.currentUserIdVar = '';
            if (window.serverUserData) window.serverUserData = null;
            if (window.serverUserDataBasic) window.serverUserDataBasic = null;
        } catch (e) {
            console.error("Error al limpiar almacenamiento:", e);
        }

        // Redirigir al home después de 2 segundos (antes 3)
        setTimeout(function() {
            window.location.href = '<?php echo $GLOBALS["website"]; ?>';
        }, 2000);

        // Animación de los puntos
        let dots = 0;
        setInterval(function() {
            const dotsElement = document.querySelector('.loading-dots');
            if (dotsElement) {
                dots = (dots + 1) % 4;
                dotsElement.textContent = '.'.repeat(dots) + ' '.repeat(3 - dots);
            }
        }, 500);
    </script>
</body>
</html>