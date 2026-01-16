<?php
/**
 * Panel de Viewers - VIP Only
 * Muestra usuarios que han visto los códigos del usuario actual
 */

require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';

// Verificar sesión de usuario
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$user_id = $_SESSION['user_id'];
$is_vip = es_usuario_vip($user_id);

// Obtener datos de viewers
$viewers_data = obtener_viewers_usuario($user_id);
$viewers = $viewers_data['viewers'] ?? [];
$total_viewers = $viewers_data['total_viewers'] ?? 0;
$total_potencial = $viewers_data['total_potencial'] ?? 0;

// Obtener datos del usuario
$collection_usuarios = getCollectionUsuarios();
$usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($user_id)]);

// Header
$title = "Mis Viewers | Código Amigo";
$description = "Usuarios que han visto tus códigos de referido";
get_header_modern($title, $description, '', '', '', true);
?>

<style>
.viewers-page {
    max-width: 1000px;
    margin: 0 auto;
    padding: 30px 20px;
}

.viewers-header {
    margin-bottom: 30px;
}

.viewers-title {
    font-size: 2rem;
    font-weight: 800;
    color: #1a1a2e;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.viewers-subtitle {
    color: #666;
    font-size: 1.1rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    text-align: center;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
}

.stat-card.highlight {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.stat-card.vip-highlight {
    background: linear-gradient(135deg, #ffd700 0%, #ff8c00 100%);
    color: white;
}

.stat-card-value {
    font-size: 2.5rem;
    font-weight: 800;
}

.stat-card-label {
    font-size: 0.95rem;
    opacity: 0.9;
}

.viewers-list {
    margin-top: 30px;
}

.viewers-list-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.viewer-card {
    background: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: all 0.3s ease;
}

.viewer-card:hover {
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.viewer-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    font-weight: 700;
    flex-shrink: 0;
}

.viewer-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.viewer-info {
    flex: 1;
}

.viewer-name {
    font-weight: 700;
    color: #1a1a2e;
    font-size: 1.1rem;
    margin-bottom: 5px;
}

.viewer-meta {
    font-size: 0.85rem;
    color: #666;
}

.viewer-meta i {
    margin-right: 5px;
}

.viewer-potential {
    text-align: right;
}

.viewer-potential-amount {
    font-size: 1.5rem;
    font-weight: 800;
    color: #28a745;
}

.viewer-potential-label {
    font-size: 0.8rem;
    color: #666;
}

.viewer-actions {
    display: flex;
    gap: 10px;
}

.btn-contact {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 25px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn-contact:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
    color: white;
    text-decoration: none;
}

.btn-contact.contacted {
    background: #e9ecef;
    color: #666;
}

/* VIP Upsell */
.vip-upsell {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    border-radius: 20px;
    padding: 40px;
    text-align: center;
    color: white;
    margin-bottom: 30px;
}

.vip-upsell h3 {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 15px;
}

.vip-upsell p {
    font-size: 1.1rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto 25px;
}

.btn-upgrade-vip {
    background: linear-gradient(135deg, #ffd700 0%, #ff8c00 100%);
    color: white;
    border: none;
    padding: 15px 40px;
    border-radius: 50px;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
}

.btn-upgrade-vip:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(255, 140, 0, 0.4);
    color: white;
    text-decoration: none;
}

/* Empty state */
.no-viewers {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.no-viewers i {
    font-size: 60px;
    color: #ddd;
    margin-bottom: 20px;
}

.no-viewers h3 {
    color: #333;
    font-weight: 700;
    margin-bottom: 10px;
}

.no-viewers p {
    max-width: 400px;
    margin: 0 auto;
    line-height: 1.6;
}

/* Mass message button */
.btn-mass-message {
    background: linear-gradient(135deg, #ff8a3d 0%, #ff4f0f 100%);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 25px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-mass-message:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(255, 79, 15, 0.3);
}

.btn-mass-message:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .viewer-card {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .viewer-potential {
        text-align: center;
    }
    
    .viewer-actions {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="viewers-page">
    <div class="viewers-header">
        <h1 class="viewers-title">
            <i class="fas fa-users" style="color: #667eea;"></i>
            Mis Viewers
            <?php if ($is_vip): ?>
                <span class="vip-badge-gold"><i class="fas fa-crown"></i> VIP</span>
            <?php endif; ?>
        </h1>
        <p class="viewers-subtitle">
            Usuarios registrados que han visto tus códigos de referido
        </p>
    </div>
    
    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card-value"><?php echo $total_viewers; ?></div>
            <div class="stat-card-label">Viewers registrados</div>
        </div>
        <div class="stat-card highlight">
            <div class="stat-card-value"><?php echo number_format($total_potencial, 0); ?>€</div>
            <div class="stat-card-label">Potencial de ganancias</div>
        </div>
        <?php if ($is_vip): ?>
        <div class="stat-card vip-highlight">
            <div class="stat-card-value"><?php echo number_format($usuario['saldo'] ?? 0, 2); ?>€</div>
            <div class="stat-card-label">Tu saldo VIP</div>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (!$is_vip && $total_viewers > 0): ?>
    <!-- VIP Upsell -->
    <div class="vip-upsell">
        <h3><i class="fas fa-crown"></i> ¡Tienes <?php echo $total_viewers; ?> potenciales clientes!</h3>
        <p>
            Hazte VIP para contactar directamente con todos los usuarios que han visto tus códigos. 
            Ayúdales a completar el proceso y ambos ganáis. Es una situación win-win.
        </p>
        <a href="/public/suscripcion_vip.php" class="btn-upgrade-vip">
            <i class="fas fa-bolt"></i>
            Desbloquear por 9,99€/mes
        </a>
    </div>
    <?php endif; ?>
    
    <!-- Viewers List -->
    <div class="viewers-list">
        <div class="viewers-list-title">
            <div style="display: flex; align-items: center; gap: 10px;">
                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" style="width: 18px; height: 18px; cursor: pointer;">
                <span>Usuarios que han visto tus códigos</span>
            </div>
            <?php if ($is_vip && $total_viewers > 1): ?>
            <button class="btn-mass-message" onclick="openMassMessageModal()" id="btnMassMessage" disabled>
                <i class="fas fa-paper-plane"></i>
                Enviar a seleccionados
            </button>
            <?php endif; ?>
        </div>
        
        <?php if (empty($viewers)): ?>
        <div class="no-viewers">
            <i class="fas fa-users-slash"></i>
            <h3>Aún no tienes viewers registrados</h3>
            <p>
                Cuando usuarios registrados vean tus códigos, aparecerán aquí. 
                Comparte tus códigos para empezar a ver potenciales clientes.
            </p>
        </div>
        <?php else: ?>
            <?php foreach ($viewers as $viewer): ?>
            <div class="viewer-card">
                <?php if($is_vip): ?>
                <div class="viewer-select" style="margin-right: 15px;">
                    <input type="checkbox" class="viewer-checkbox" value="<?php echo htmlspecialchars($viewer['viewer_id']); ?>" onchange="updateMassButton()" style="width: 18px; height: 18px; cursor: pointer;">
                </div>
                <?php endif; ?>

                <div class="viewer-avatar">
                    <?php if (!empty($viewer['viewer_img'])): ?>
                        <img src="<?php echo htmlspecialchars($viewer['viewer_img']); ?>" alt="Avatar">
                    <?php else: ?>
                        <?php echo strtoupper(substr($viewer['viewer_username'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                
                <div class="viewer-info">
                    <div class="viewer-name"><?php echo htmlspecialchars($viewer['viewer_username']); ?></div>
                    <div class="viewer-meta">
                        <i class="fas fa-tag"></i> Vio: <?php echo htmlspecialchars($viewer['codigo_marca']); ?>
                        <?php 
                        if (isset($viewer['viewed_at'])) {
                            $viewed_date = $viewer['viewed_at'];
                            if ($viewed_date instanceof MongoDB\BSON\UTCDateTime) {
                                $dt = $viewed_date->toDateTime();
                                echo ' <i class="fas fa-clock"></i> ' . $dt->format('d/m/Y');
                            }
                        }
                        ?>
                    </div>
                </div>
                
                <div class="viewer-potential">
                    <div class="viewer-potential-amount">+<?php echo number_format($viewer['codigo_beneficio'], 0); ?>€</div>
                    <div class="viewer-potential-label">beneficio</div>
                </div>
                
                <div class="viewer-actions">
                    <?php if ($is_vip): ?>
                        <?php if ($viewer['contacted']): ?>
                            <button class="btn-contact contacted" disabled>
                                <i class="fas fa-check"></i> Contactado
                            </button>
                        <?php else: ?>
                            <a href="/public/chat_usuario.php?open_chat=<?php echo urlencode($viewer['viewer_id']); ?>&msg=<?php echo urlencode('¡Hola! Vi que te interesó mi código de ' . $viewer['codigo_marca'] . '. ¿Necesitas ayuda?'); ?>" class="btn-contact">
                                <i class="fas fa-comment"></i> Contactar
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="/public/suscripcion_vip.php" class="btn-contact" style="background: linear-gradient(135deg, #ffd700 0%, #ff8c00 100%);">
                            <i class="fas fa-crown"></i> VIP para contactar
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php if ($is_vip && $total_viewers > 1): ?>
<!-- Mass Message Modal -->
<div id="massMessageModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #ff8a3d 0%, #ff4f0f 100%); color: white; border: none;">
                <h5 class="modal-title">
                    <i class="fas fa-paper-plane"></i> Enviar mensaje masivo
                </h5>
                <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 1;">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 25px;">
                <div class="mass-message-info">
                    <i class="fas fa-info-circle"></i>
                    <p>
                        Se enviará el mensaje a <strong><span id="selectedCount">0</span> usuarios</strong> seleccionados.
                    </p>
                </div>
                
                <div class="form-group">
                    <label style="font-weight: 700; color: #1a1a2e;">Tu mensaje</label>
                    <textarea id="massMessageText" class="form-control" rows="5" placeholder="Hola! Vi que te interesa el código. Te puedo ayudar a completar el proceso para que ambos ganemos el beneficio. ¿Te animas?" style="border-radius: 10px;"></textarea>
                </div>
            </div>
            <div class="modal-footer" style="border: none; padding: 15px 25px 25px;">
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn" onclick="sendMassMessage()" style="background: linear-gradient(135deg, #ff8a3d 0%, #ff4f0f 100%); color: white; font-weight: 700;">
                    <i class="fas fa-paper-plane"></i> Enviar mensajes
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSelectAll() {
    const isChecked = document.getElementById('selectAll').checked;
    const checkboxes = document.querySelectorAll('.viewer-checkbox');
    checkboxes.forEach(cb => cb.checked = isChecked);
    updateMassButton();
}

function updateMassButton() {
    const selected = document.querySelectorAll('.viewer-checkbox:checked').length;
    const btn = document.getElementById('btnMassMessage');
    const countSpan = document.getElementById('selectedCount');
    
    if (btn) {
        btn.disabled = selected === 0;
        btn.innerHTML = `<i class="fas fa-paper-plane"></i> Enviar a seleccionados (${selected})`;
    }
    if (countSpan) {
        countSpan.textContent = selected;
    }
}

function openMassMessageModal() {
    const selected = document.querySelectorAll('.viewer-checkbox:checked').length;
    if (selected === 0) return;
    $('#massMessageModal').modal('show');
}

async function sendMassMessage() {
    const message = document.getElementById('massMessageText').value.trim();
    if (!message) {
        Swal.fire('Error', 'Escribe un mensaje para enviar', 'warning');
        return;
    }
    
    // Collect selected IDs
    const selectedIds = Array.from(document.querySelectorAll('.viewer-checkbox:checked')).map(cb => cb.value);
    
    try {
        Swal.fire({
            title: 'Enviando...',
            text: 'Por favor espera mientras enviamos los mensajes.',
            allowOutsideClick: false,
            onBeforeOpen: () => {
                Swal.showLoading()
            }
        });

        // FormData for regular POST request or JSON body
        // API expects POST parameters
        const formData = new FormData();
        formData.append('action', 'enviar_masivo');
        formData.append('mensaje', message);
        formData.append('destinatarios', JSON.stringify(selectedIds));
        
        const response = await fetch('/api/chat_api.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            $('#massMessageModal').modal('hide');
            Swal.fire('¡Enviado!', `Mensaje enviado a ${data.stats.enviados} usuarios (${data.stats.fallidos} fallidos)`, 'success')
                .then(() => location.reload());
        } else {
            Swal.fire('Error', data.error || 'Error al enviar mensajes', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('Error', 'Error de conexión', 'error');
    }
}
</script>
<?php endif; ?>

<?php
include_once __DIR__ . '/../myphp/_footer.php';
?>
