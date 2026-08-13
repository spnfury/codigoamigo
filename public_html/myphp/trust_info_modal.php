<?php
/**
 * Modal informativo del sistema de Trust Score
 * Incluir este archivo en las páginas donde se muestran trust badges.
 * Al hacer clic en un .trust-badge, se abre este modal explicativo.
 */
?>

<!-- Modal Trust Score Info -->
<div class="trust-modal-overlay" id="trustInfoModal" style="display:none;">
    <div class="trust-modal">
        <div class="trust-modal-header">
            <h3><i class="fas fa-shield-alt"></i> Niveles de Confianza</h3>
            <button class="trust-modal-close" onclick="closeTrustModal()">&times;</button>
        </div>
        <div class="trust-modal-body">
            <p class="trust-modal-intro">Cada código tiene un <strong>nivel de confianza</strong> basado en la calidad del código y la actividad del usuario que lo publica.</p>

            <!-- Niveles -->
            <div class="trust-levels-grid">
                <div class="trust-level-row trust-level-premium">
                    <div class="trust-level-stars">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <div class="trust-level-info">
                        <span class="trust-level-label" style="background:#fef3c7;color:#92400e;">Premium</span>
                        <span class="trust-level-desc">Código destacado (promocionado)</span>
                    </div>
                </div>
                <div class="trust-level-row trust-level-recommended">
                    <div class="trust-level-stars" style="color:#10b981;">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star" style="color:#d1d5db;"></i>
                    </div>
                    <div class="trust-level-info">
                        <span class="trust-level-label" style="background:#d1fae5;color:#065f46;">Recomendado</span>
                        <span class="trust-level-desc">Usuario muy activo y bien valorado</span>
                    </div>
                </div>
                <div class="trust-level-row trust-level-trusted">
                    <div class="trust-level-stars" style="color:#3b82f6;">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star" style="color:#d1d5db;"></i><i class="far fa-star" style="color:#d1d5db;"></i>
                    </div>
                    <div class="trust-level-info">
                        <span class="trust-level-label" style="background:#dbeafe;color:#1e40af;">De confianza</span>
                        <span class="trust-level-desc">Usuario con buen historial</span>
                    </div>
                </div>
                <div class="trust-level-row trust-level-verified">
                    <div class="trust-level-stars" style="color:#6b7280;">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star" style="color:#d1d5db;"></i><i class="far fa-star" style="color:#d1d5db;"></i><i class="far fa-star" style="color:#d1d5db;"></i>
                    </div>
                    <div class="trust-level-info">
                        <span class="trust-level-label" style="background:#f3f4f6;color:#374151;">Verificado</span>
                        <span class="trust-level-desc">Usuario con perfil básico completo</span>
                    </div>
                </div>
                <div class="trust-level-row trust-level-new">
                    <div class="trust-level-stars" style="color:#9ca3af;">
                        <i class="fas fa-star"></i><i class="far fa-star" style="color:#d1d5db;"></i><i class="far fa-star" style="color:#d1d5db;"></i><i class="far fa-star" style="color:#d1d5db;"></i><i class="far fa-star" style="color:#d1d5db;"></i>
                    </div>
                    <div class="trust-level-info">
                        <span class="trust-level-label" style="background:#f9fafb;color:#6b7280;">Nuevo</span>
                        <span class="trust-level-desc">Usuario recién registrado</span>
                    </div>
                </div>
            </div>

            <div class="trust-how-to-improve">
                <h4><i class="fas fa-arrow-up"></i> ¿Cómo subir de nivel?</h4>
                <ul class="trust-tips-list">
                    <li><i class="fas fa-camera"></i> Sube una foto de perfil</li>
                    <li><i class="fas fa-envelope-open"></i> Verifica tu email</li>
                    <li><i class="fas fa-clock"></i> Antigüedad de la cuenta (hasta 5 años)</li>
                    <li><i class="fas fa-tags"></i> Publica códigos en varias marcas</li>
                    <li><i class="fas fa-thumbs-up"></i> Recibe votos positivos en tus códigos</li>
                    <li><i class="fas fa-pen"></i> Añade descripción a tus códigos</li>
                </ul>
            </div>

            <?php if (isset($_SESSION["user_id"]) && !empty($_SESSION["user_id"])): ?>
            <div class="trust-modal-cta">
                <a href="/perfil" class="trust-modal-btn"><i class="fas fa-user-edit"></i> Ver mi nivel en Mi Perfil</a>
            </div>
            <?php else: ?>
            <div class="trust-modal-cta">
                <a href="/login" class="trust-modal-btn"><i class="fas fa-sign-in-alt"></i> Inicia sesión para ver tu nivel</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Trust Info Modal Styles */
.trust-modal-overlay {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.7);
    backdrop-filter: blur(4px);
    z-index: 10001;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: trustFadeIn 0.25s ease;
}

@keyframes trustFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes trustSlideUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.trust-modal {
    background: #fff;
    border-radius: 20px;
    max-width: 480px;
    width: 92%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 25px 60px rgba(0,0,0,0.4);
    animation: trustSlideUp 0.3s ease;
}

.trust-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #eee;
    position: sticky;
    top: 0;
    background: #fff;
    border-radius: 20px 20px 0 0;
    z-index: 2;
}

.trust-modal-header h3 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 700;
    color: #1e3a5f;
    display: flex;
    align-items: center;
    gap: 8px;
}

.trust-modal-header h3 i {
    color: #3b82f6;
}

.trust-modal-close {
    background: #f3f4f6;
    border: none;
    width: 34px; height: 34px;
    border-radius: 50%;
    font-size: 1.3rem;
    color: #6b7280;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.trust-modal-close:hover {
    background: #e5e7eb;
    color: #1f2937;
}

.trust-modal-body {
    padding: 20px 24px 24px;
}

.trust-modal-intro {
    color: #4b5563;
    font-size: 0.9rem;
    line-height: 1.5;
    margin: 0 0 18px;
}

.trust-levels-grid {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 20px;
}

.trust-level-row {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 10px 14px;
    border-radius: 12px;
    background: #f9fafb;
    border: 1px solid #f3f4f6;
    transition: all 0.2s;
}

.trust-level-row:hover {
    background: #f3f4f6;
    border-color: #e5e7eb;
}

.trust-level-stars {
    font-size: 0.8rem;
    min-width: 80px;
    color: #f59e0b;
}

.trust-level-stars i {
    margin: 0 1px;
}

.trust-level-info {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
    flex-wrap: wrap;
}

.trust-level-label {
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    white-space: nowrap;
}

.trust-level-desc {
    color: #6b7280;
    font-size: 0.8rem;
}

.trust-how-to-improve {
    background: linear-gradient(135deg, #eff6ff, #f0fdf4);
    border-radius: 14px;
    padding: 16px 18px;
    margin-bottom: 18px;
    border: 1px solid #dbeafe;
}

.trust-how-to-improve h4 {
    margin: 0 0 12px;
    font-size: 0.95rem;
    font-weight: 700;
    color: #1e3a5f;
    display: flex;
    align-items: center;
    gap: 6px;
}

.trust-how-to-improve h4 i {
    color: #10b981;
}

.trust-tips-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.trust-tips-list li {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.85rem;
    color: #374151;
}

.trust-tips-list li i {
    color: #3b82f6;
    width: 18px;
    text-align: center;
    font-size: 0.85rem;
}

.trust-modal-cta {
    text-align: center;
}

.trust-modal-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 0.9rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba(59,130,246,0.3);
}

.trust-modal-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(59,130,246,0.4);
    color: #fff;
    text-decoration: none;
}

/* Mobile responsive */
@media (max-width: 480px) {
    .trust-modal {
        width: 96%;
        border-radius: 16px;
    }
    .trust-modal-header {
        padding: 16px 18px;
        border-radius: 16px 16px 0 0;
    }
    .trust-modal-body {
        padding: 16px 18px 20px;
    }
    .trust-level-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
}
</style>

<script>
// Trust Info Modal
function openTrustModal() {
    var modal = document.getElementById('trustInfoModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeTrustModal() {
    var modal = document.getElementById('trustInfoModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// Close on overlay click
document.addEventListener('click', function(e) {
    if (e.target && e.target.id === 'trustInfoModal') {
        closeTrustModal();
    }
});

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTrustModal();
    }
});

// Make all trust badges clickable
document.addEventListener('DOMContentLoaded', function() {
    // Delegate click on trust-badge elements
    document.addEventListener('click', function(e) {
        var badge = e.target.closest('.trust-badge');
        if (badge) {
            e.preventDefault();
            e.stopPropagation();
            openTrustModal();
        }
    });
    
    // Add cursor pointer to all trust badges
    var style = document.createElement('style');
    style.textContent = '.trust-badge { cursor: pointer !important; } .trust-badge:hover { opacity: 0.85; transition: opacity 0.2s; }';
    document.head.appendChild(style);
});
</script>
