<?php
/**
 * Botón flotante para promocionar Telegram en secciones de chollos
 */
?>
<div id="floating-telegram-btn" class="floating-telegram" style="display: none;">
    <a href="https://t.me/cholloscodigoamigo" target="_blank" rel="noopener noreferrer" class="tel-btn">
        <i class="fa-brands fa-telegram"></i>
        <span class="btn-text">Chollos en Telegram</span>
    </a>
</div>

<style>
.floating-telegram {
    position: fixed;
    bottom: 30px;
    left: 30px;
    z-index: 1000;
    transition: all 0.3s ease;
}

.floating-telegram.visible {
    display: block !important;
    animation: slideInLeft 0.5s ease forwards;
}

.tel-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #0088cc;
    color: white !important;
    padding: 12px 20px;
    border-radius: 50px;
    text-decoration: none !important;
    font-weight: 700;
    box-shadow: 0 4px 15px rgba(0, 136, 204, 0.4);
    border: 2px solid white;
}

.tel-btn:hover {
    background: #0077b5;
    transform: translateY(-5px);
    box-shadow: 0 6px 20px rgba(0, 136, 204, 0.6);
}

.btn-text {
    font-size: 0.95em;
}

@keyframes slideInLeft {
    from { transform: translateX(-100px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@media (max-width: 768px) {
    .floating-telegram {
        bottom: 80px; /* Evitar solapamiento con menú inferior móvil */
        left: 20px;
    }
    .btn-text {
        display: none;
    }
    .tel-btn {
        padding: 15px;
        border-radius: 50%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('floating-telegram-btn');
    if (btn) {
        // Mostrar en todas las páginas, no solo chollos
        setTimeout(() => {
            btn.classList.add('visible');
        }, 1000);
    }
});
</script>
