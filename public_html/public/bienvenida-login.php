<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION["user_id"])) {
    header('Location: /');
    exit;
}

$redirect = filter_input(INPUT_GET, 'redirect', FILTER_UNSAFE_RAW) ?: '/';
if (!is_string($redirect) || strpos($redirect, '/') !== 0) {
    $redirect = '/';
}

$title = $title ?? '¡Bienvenido a Código Amigo!';
$description = $description ?? 'Comparte códigos amigo y descubre nuevas oportunidades para ahorrar cada día.';

$username = $_SESSION['username'] ?? '';
$mail = $_SESSION['mail'] ?? '';
$displayName = trim($username) !== '' ? $username : ($mail !== '' ? strtok($mail, '@') : 'Código Amigo');

$avatar = $_SESSION['img'] ?? '';
if (!filter_var($avatar, FILTER_VALIDATE_URL)) {
    $avatar = 'https://www.codigoamigo.com/img/logo_social_codigoamigo_final.jpg';
}

get_header_modern($title, $description);
?>

<div class="welcome-wrapper">
    <div class="welcome-hero">
        <div class="welcome-content">
            <div class="welcome-badge">¡Sesión iniciada!</div>
            <h1>Hola <?php echo htmlspecialchars($displayName); ?> 👋</h1>
            <p>Nos alegra verte de nuevo. Tu comunidad de códigos descuento sigue creciendo y hay novedades listas para ti.</p>
            <div class="welcome-actions">
                <a class="primary" href="/mis-anuncios">Ver mis códigos</a>
                <a class="secondary" href="/publicar">Publicar nuevo código</a>
            </div>
            <div class="welcome-info">
                <span>Te redirigiremos en <strong id="welcome-countdown">5</strong> segundos</span>
                <span>o <a href="<?php echo htmlspecialchars($redirect); ?>">continúa ahora mismo</a></span>
            </div>
        </div>
        <div class="welcome-figure">
            <div class="welcome-avatar">
                <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar de <?php echo htmlspecialchars($displayName); ?>">
            </div>
            <div class="welcome-card">
                <h3>Tu resumen rápido</h3>
                <ul>
                    <li>✨ Comparte, ahorra y gana recompensas</li>
                    <li>📣 Destaca tus mejores códigos en segundos</li>
                    <li>🤝 Únete a una comunidad que recomienda lo mejor</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="welcome-grid">
        <div class="grid-item">
            <h4>Explora novedades</h4>
            <p>Descubre los códigos destacados de la semana y las marcas que más están premiando a nuestra comunidad.</p>
            <a href="/destacados">Ir a destacados</a>
        </div>
        <div class="grid-item">
            <h4>Comparte fácil</h4>
            <p>Sube tus códigos en menos de un minuto y deja que otros usuarios se beneficien de tus recomendaciones.</p>
            <a href="/publicar">Compartir código</a>
        </div>
        <div class="grid-item">
            <h4>Gana recompensas</h4>
            <p>Suma puntos cada vez que un amigo usa tus códigos y consulta tu progreso desde el panel personal.</p>
            <a href="/mis-anuncios">Ver mi panel</a>
        </div>
    </div>
</div>

<style>
.welcome-wrapper {
    min-height: calc(100vh - 120px);
    background: radial-gradient(circle at top left, #ff9248, #1b1b1b 45%);
    padding: 120px 0 80px;
    color: #fff;
}

.welcome-hero {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 40px;
    max-width: 1100px;
    margin: 0 auto 60px;
    padding: 40px;
    background: rgba(0, 0, 0, 0.35);
    border-radius: 28px;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35);
    backdrop-filter: blur(10px);
}

.welcome-content {
    flex: 1 1 460px;
}

.welcome-badge {
    display: inline-block;
    padding: 8px 14px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.15);
    font-weight: 600;
    letter-spacing: 0.5px;
    margin-bottom: 18px;
}

.welcome-content h1 {
    font-size: 48px;
    margin-bottom: 18px;
    letter-spacing: -1px;
}

.welcome-content p {
    font-size: 20px;
    line-height: 1.5;
    max-width: 420px;
    margin-bottom: 30px;
    color: rgba(255, 255, 255, 0.85);
}

.welcome-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
}

.welcome-actions a {
    padding: 14px 26px;
    border-radius: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.welcome-actions a.primary {
    background: linear-gradient(135deg, #E30613, #f7931e);
    color: #fff;
    box-shadow: 0 12px 30px rgba(227, 6, 19, 0.35);
}

.welcome-actions a.secondary {
    background: rgba(255, 255, 255, 0.12);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.welcome-actions a:hover {
    transform: translateY(-3px);
    box-shadow: 0 14px 40px rgba(227, 6, 19, 0.45);
}

.welcome-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 15px;
    color: rgba(255, 255, 255, 0.75);
}

.welcome-info a {
    color: #fff;
    text-decoration: underline;
}

.welcome-figure {
    flex: 0 0 340px;
    display: flex;
    flex-direction: column;
    gap: 24px;
    align-items: center;
}

.welcome-avatar {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    overflow: hidden;
    border: 4px solid rgba(255, 255, 255, 0.4);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.4);
}

.welcome-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.welcome-card {
    width: 100%;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 24px;
    padding: 26px;
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.08);
}

.welcome-card h3 {
    margin-bottom: 16px;
    font-size: 20px;
}

.welcome-card ul {
    margin: 0;
    padding-left: 20px;
    color: rgba(255, 255, 255, 0.85);
    line-height: 1.6;
}

.welcome-grid {
    max-width: 1100px;
    margin: 0 auto;
    display: grid;
    gap: 24px;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.grid-item {
    background: rgba(0, 0, 0, 0.35);
    padding: 28px;
    border-radius: 24px;
    color: rgba(255, 255, 255, 0.85);
    box-shadow: 0 18px 45px rgba(0, 0, 0, 0.3);
    backdrop-filter: blur(6px);
}

.grid-item h4 {
    color: #fff;
    font-size: 20px;
    margin-bottom: 14px;
}

.grid-item a {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 14px;
    color: #ffb46a;
    text-decoration: none;
    font-weight: 600;
}

.grid-item a:hover {
    color: #ffd3a4;
}

@media (max-width: 992px) {
    .welcome-wrapper {
        padding: 100px 20px 60px;
    }

    .welcome-hero {
        padding: 32px;
    }

    .welcome-content h1 {
        font-size: 40px;
    }
}

@media (max-width: 768px) {
    .welcome-wrapper {
        padding-top: 80px;
    }

    .welcome-hero {
        padding: 26px 22px;
    }

    .welcome-content h1 {
        font-size: 34px;
    }

    .welcome-content p {
        font-size: 18px;
    }
}

@media (max-width: 576px) {
    .welcome-hero {
        padding: 24px 18px;
    }

    .welcome-content h1 {
        font-size: 32px;
    }

    .welcome-actions {
        flex-direction: column;
    }

    .welcome-actions a {
        width: 100%;
        text-align: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var targetUrl = <?php echo json_encode($redirect, JSON_UNESCAPED_SLASHES); ?>;
    var countdownEl = document.getElementById('welcome-countdown');
    var seconds = 5;

    var tick = function () {
        seconds -= 1;
        if (seconds <= 0) {
            window.location.href = targetUrl;
            return;
        }
        if (countdownEl) {
            countdownEl.textContent = seconds.toString();
        }
    };

    setTimeout(function () {
        window.location.href = targetUrl;
    }, seconds * 1000);

    setInterval(tick, 1000);
});
</script>

<?php get_footer(); ?>

