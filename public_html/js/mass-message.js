/**
 * Mass Messaging Functionality - Local Implementation
 * Replaces the previous modal view with a mass message interface overlay.
 * Runs completely within the iframe context to avoid cross-origin/lifecycle issues.
 */

// Global variables
let massMessageRecipients = [];
let massMessagePotentialEarnings = 0;
let massMessageEarningPerUser = 0;

/**
 * Formats a number in European style (1.234,56)
 * @param {Number|String} number 
 * @param {Number} decimals 
 * @returns {String}
 */
function formatNumberEuropean(number, decimals = 2) {
    let n = parseFloat(number);
    if (isNaN(n)) return "0,00";

    return n.toLocaleString('de-DE', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

/**
 * Initialize and show the Mass Message Modal Overlay
 * @param {Array} recipients - List of user IDs
 * @param {Number} earningPerUser - Potential earning per user (used if totalPotential not provided)
 * @param {Number} totalPotential - Pre-calculated total potential (optional)
 */
window.initMassMessageModal = function (recipients, earningPerUser, totalPotential) {
    massMessageRecipients = recipients || [];
    massMessageEarningPerUser = earningPerUser || 0;

    if (totalPotential !== undefined && totalPotential !== null) {
        massMessagePotentialEarnings = parseFloat(totalPotential);
    } else {
        // Calculate potential earnings
        massMessagePotentialEarnings = parseFloat((massMessageRecipients.length * massMessageEarningPerUser).toFixed(2));
    }

    // Create (if needed) and Show the Overlay
    createAndShowOverlay();
};

/**
 * Creates the overlay HTML and appends it to body (if not exists), then shows it.
 */
function createAndShowOverlay() {
    // Check if exists
    let overlay = document.getElementById('massMessageOverlay');

    // If it doesn't exist, inject CSS and HTML
    if (!overlay) {
        injectMassMessageStyles();
        injectMassMessageHTML();
        overlay = document.getElementById('massMessageOverlay');
    }

    // Update dynamic content (counts, earnings)
    updateOverlayContent();

    // Show it
    overlay.style.display = 'flex';

    // Animation reset
    const card = overlay.querySelector('.mm-card');
    if (card) {
        card.style.animation = 'none';
        card.offsetHeight; /* trigger reflow */
        card.style.animation = 'mmFadeIn 0.3s ease forwards';
    }
}

function injectMassMessageStyles() {
    const styleId = 'mass-message-styles';
    if (document.getElementById(styleId)) return;

    const styles = `
        <style id="${styleId}">
            /* Full Screen Overlay */
            #massMessageOverlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.6);
                z-index: 2147483647; /* Max Z-Index */
                display: none;
                align-items: center;
                justify-content: center;
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                backdrop-filter: blur(4px);
            }
            
            /* Card Container */
            .mm-card {
                background: #ffffff;
                width: 90%;
                max-width: 500px;
                border-radius: 20px;
                overflow: hidden;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                display: flex;
                flex-direction: column;
                max-height: 90vh;
                position: relative;
            }
            
            @keyframes mmFadeIn {
                from { opacity: 0; transform: translateY(30px) scale(0.95); }
                to { opacity: 1; transform: translateY(0) scale(1); }
            }
            
            /* Header */
            .mm-header {
                background: linear-gradient(135deg, #E30613 0%, #ff4d4d 100%);
                color: white;
                padding: 18px 25px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                box-shadow: 0 4px 15px rgba(227, 6, 19, 0.2);
                z-index: 2;
            }
            
            .mm-title {
                margin: 0;
                font-weight: 800;
                font-size: 1.2rem;
                letter-spacing: -0.5px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .mm-close {
                background: rgba(255,255,255,0.2);
                border: none;
                color: white;
                width: 32px;
                height: 32px;
                border-radius: 50%;
                font-size: 1.2rem;
                cursor: pointer;
                transition: all 0.2s;
                display: flex;
                align-items: center;
                justify-content: center;
                line-height: 1;
            }
            
            .mm-close:hover {
                background: rgba(255,255,255,0.4);
                transform: rotate(90deg);
            }
            
            /* Body */
            .mm-body {
                padding: 25px;
                overflow-y: auto;
                background: #f8f9fa;
            }
            
            /* VIP Content */
            .mm-alert {
                background: white;
                border-radius: 12px;
                padding: 15px 20px;
                margin-bottom: 20px;
                border-left: 5px solid #28a745;
                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            }
            
            .mm-alert-title {
                color: #28a745;
                font-weight: 800;
                margin-bottom: 4px;
                font-size: 1.1rem;
            }
            
            .mm-textarea-group {
                background: white;
                padding: 5px;
                border-radius: 12px;
                border: 2px solid #e9ecef;
                transition: all 0.3s;
            }
            
            .mm-textarea-group:focus-within {
                border-color: #E30613;
                box-shadow: 0 0 0 4px rgba(227, 6, 19, 0.1);
            }
            
            .mm-textarea {
                width: 100%;
                border: none;
                padding: 15px;
                font-size: 1rem;
                resize: none;
                outline: none;
                min-height: 120px;
                color: #333;
                background: transparent;
                font-family: inherit;
            }
            
            /* Footer */
            .mm-footer {
                padding: 15px 25px;
                background: white;
                border-top: 1px solid #eee;
                display: flex;
                justify-content: flex-end;
                gap: 12px;
            }
            
            .mm-btn {
                padding: 10px 24px;
                border-radius: 50px;
                font-weight: 700;
                font-size: 0.95rem;
                border: none;
                cursor: pointer;
                transition: all 0.2s;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .mm-btn-secondary {
                background: #f1f3f5;
                color: #495057;
            }
            
            .mm-btn-secondary:hover {
                background: #e9ecef;
                color: #212529;
            }
            
            .mm-btn-primary {
                background: linear-gradient(135deg, #1cb5e0 0%, #000851 100%); /* Default blueish for send */
                background: #E30613; /* Brand Red */
                color: white;
                box-shadow: 0 4px 10px rgba(227, 6, 19, 0.3);
            }
            
            .mm-btn-primary:hover:not(:disabled) {
                transform: translateY(-2px);
                box-shadow: 0 6px 15px rgba(227, 6, 19, 0.4);
            }
            
            .mm-btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }
            
            /* Non-VIP Styles */
            .mm-non-vip-header {
                background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
                color: #212529;
            }
            
            .mm-vip-promo {
                text-align: center;
                padding: 10px 0;
            }
            
            .mm-vip-icon-wrapper {
                width: 80px;
                height: 80px;
                background: #fff8e1;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 20px;
            }
            
            .mm-vip-icon {
                font-size: 40px;
                color: #ffc107;
            }
            
            .mm-vip-title {
                font-weight: 800;
                font-size: 1.5rem;
                margin-bottom: 10px;
                color: #333;
            }
            
            .mm-vip-benefits {
                text-align: left;
                background: white;
                padding: 20px;
                border-radius: 12px;
                margin: 20px 0;
                box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            }
            
            .mm-vip-benefits li {
                margin-bottom: 12px;
                display: flex;
                align-items: center;
                font-weight: 500;
                color: #555;
            }
            
            .mm-vip-benefits i {
                color: #28a745;
                margin-right: 10px;
                font-size: 1.1rem;
            }
            
            .mm-btn-vip-action {
                background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
                color: #212529;
                text-decoration: none;
                display: block;
                width: 100%;
                padding: 16px;
                border-radius: 50px;
                font-weight: 800;
                font-size: 1.1rem;
                margin-top: 20px;
                box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
                transition: transform 0.2s;
                border: none;
                cursor: pointer;
            }
            
            .mm-btn-vip-action:hover {
                transform: scale(1.02);
                color: #212529;
                text-decoration: none;
            }
            
            /* Utilities */
            .d-none { display: none !important; }
            .text-success { color: #28a745; }
        </style>
    `;
    document.body.insertAdjacentHTML('beforeend', styles);
}

function injectMassMessageHTML() {
    const html = `
    <div id="massMessageOverlay">
        <div class="mm-card">
            
            <!-- VIP Header -->
            <div class="mm-header" id="mmHeaderVip">
                <h3 class="mm-title"><i class="fas fa-paper-plane"></i> Mensaje Masivo</h3>
                <button class="mm-close" onclick="closeMassMessageOverlay()">&times;</button>
            </div>
            
            <!-- Non-VIP Header -->
            <div class="mm-header mm-non-vip-header d-none" id="mmHeaderNonVip">
                <h3 class="mm-title" style="color:#212529"><i class="fas fa-crown"></i> Funcionalidad VIP</h3>
                <button class="mm-close" style="color:#212529; background:rgba(0,0,0,0.1);" onclick="closeMassMessageOverlay()">&times;</button>
            </div>

            <div class="mm-body">
                <!-- VIP User Content (Form) -->
                <div id="mmVipContent" class="d-none">
                    <div class="mm-alert">
                       <div class="mm-alert-title"><i class="fas fa-coins mr-2"></i> Potencial: <span class="mm-potential-earnings">0</span>€</div>
                       <small>Contactando a <strong><span class="mm-recipient-count">0</span> usuarios</strong> interesados.</small>
                    </div>
                    
                    <label style="font-weight:700; margin-bottom: 8px; display:block; color:#333;">Escribe tu mensaje:</label>
                    <div class="mm-textarea-group">
                        <textarea class="mm-textarea" id="massMessageText" placeholder="Escribe tu mensaje aquí…">Hola, he visto que te interesa usar mi código. Si tienes cualquier duda sobre cómo registrarte o activar el bonus, escríbeme por aquí y te ayudo encantado!</textarea>
                    </div>
                    <div style="margin-top:10px; font-size: 0.85rem; color:#888; display:flex; gap:6px;">
                        <i class="fas fa-info-circle" style="margin-top:2px;"></i> 
                        <span>Consejo: Los mensajes personalizados y amables aumentan tu conversión un 40%.</span>
                    </div>
                </div>

                <!-- Non-VIP Content (Promo) -->
                <div id="mmNonVipContent" class="d-none mm-vip-promo">
                    <div class="mm-vip-icon-wrapper">
                        <i class="fas fa-crown mm-vip-icon"></i>
                    </div>
                    
                    <h3 class="mm-vip-title">¡Maximiza tus ganancias!</h3>
                    <p style="color:#666; margin-bottom:20px;">El envío masivo es exclusivo para usuarios VIP.</p>
                    
                    <div class="mm-vip-benefits">
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <li><i class="fas fa-check-circle"></i> Contacta a <strong><span class="mm-recipient-count">0</span> usuarios</strong> ahora mismo</li>
                            <li><i class="fas fa-check-circle"></i> Gana hasta <strong><span class="mm-potential-earnings">0</span>€</strong> potenciales</li>
                            <li><i class="fas fa-check-circle"></i> Ahorra tiempo enviando 1 solo mensaje</li>
                        </ul>
                    </div>
                    
                    <a href="/public/suscripcion_vip.php" target="_parent" class="mm-btn-vip-action">
                        <i class="fas fa-star mr-2"></i> HAZTE VIP AHORA
                    </a>
                    <p style="color:#999; font-size:0.8rem; margin-top:12px;">Cancela cuando quieras.</p>
                </div>
            </div>
            
            <!-- Footer (Only for VIP) -->
            <div class="mm-footer" id="mmFooter">
                <button class="mm-btn mm-btn-secondary" onclick="closeMassMessageOverlay()">Cancelar</button>
                <button class="mm-btn mm-btn-primary" onclick="window.sendMassMessageAction()" id="btnSendMass">
                    <i class="fas fa-paper-plane"></i> Enviar a Todos
                </button>
            </div>
        </div>
    </div>
    `;

    document.body.insertAdjacentHTML('beforeend', html);
}

function updateOverlayContent() {
    const isVip = window.userIsVip === true;

    // Update numbers
    document.querySelectorAll('.mm-recipient-count').forEach(el => el.textContent = formatNumberEuropean(massMessageRecipients.length, 0));
    document.querySelectorAll('.mm-potential-earnings').forEach(el => el.textContent = formatNumberEuropean(massMessagePotentialEarnings, 2));

    // Toggle Views
    const vipContent = document.getElementById('mmVipContent');
    const nonVipContent = document.getElementById('mmNonVipContent');
    const footer = document.getElementById('mmFooter');
    const headerVip = document.getElementById('mmHeaderVip');
    const headerNonVip = document.getElementById('mmHeaderNonVip');

    if (isVip) {
        vipContent.classList.remove('d-none');
        nonVipContent.classList.add('d-none');
        footer.style.display = 'flex';
        headerVip.classList.remove('d-none');
        headerNonVip.classList.add('d-none');
    } else {
        vipContent.classList.add('d-none');
        nonVipContent.classList.remove('d-none');
        footer.style.display = 'none';
        headerVip.classList.add('d-none');
        headerNonVip.classList.remove('d-none');
    }
}

/**
 * Close the overlay
 */
window.closeMassMessageOverlay = function () {
    const overlay = document.getElementById('massMessageOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
};

/**
 * Perform the send action via AJAX
 */
window.sendMassMessageAction = function () {
    const txt = document.getElementById('massMessageText');
    const msg = txt.value.trim();

    if (!msg) {
        if (typeof Swal !== 'undefined') {
            Swal.fire('Error', 'Por favor escribe un mensaje.', 'warning');
        } else {
            alert('Por favor escribe un mensaje.');
        }
        return;
    }

    const btn = document.getElementById('btnSendMass');
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

    // Prepare Data
    const formData = new FormData();
    formData.append('action', 'enviar_masivo');
    formData.append('destinatarios', JSON.stringify(massMessageRecipients));
    formData.append('mensaje', msg);

    // Send Request
    fetch('/api/chat_api.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalContent;

            if (data.success) {
                closeMassMessageOverlay();

                let msgHtml = `Has contactado con éxito a ${data.stats.enviados} usuarios.`;
                let msgText = `¡Mensajes enviados con éxito a ${data.stats.enviados} usuarios!`;
                if (data.stats.omitidos > 0) {
                    msgHtml += `<br><br><small style="color:#666;"><i>(Se han omitido ${data.stats.omitidos} usuarios a los que ya habías contactado antes para evitar spam)</i></small>`;
                    msgText += `\n(Se han omitido ${data.stats.omitidos} usuarios a los que ya habías contactado antes para evitar spam)`;
                }

                // Try explicit parent Swal, or local Swal if exists, or alert
                if (window.parent && window.parent.Swal) {
                    window.parent.Swal.fire({
                        icon: 'success',
                        title: '¡Mensajes enviados!',
                        html: msgHtml,
                        confirmButtonColor: '#E30613'
                    });
                } else if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Mensajes enviados!',
                        html: msgHtml,
                        confirmButtonColor: '#E30613'
                    });
                } else {
                    alert(msgText);
                }

                // Clear input
                txt.value = '';
            } else {
                const errorMsg = data.error || 'Ocurrió un error al enviar los mensajes.';
                if (window.parent && window.parent.Swal) {
                    window.parent.Swal.fire('Error', errorMsg, 'error');
                } else {
                    alert('Error: ' + errorMsg);
                }
            }
        })
        .catch(err => {
            console.error('Mass Message Error:', err);
            btn.disabled = false;
            btn.innerHTML = originalContent;
            alert('Error de conexión al servidor. Inténtalo de nuevo.');
        });
};
