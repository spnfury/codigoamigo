/**
 * Code Viewer Modal
 * Modal para mostrar código con registro de viewers
 */

(function () {
    'use strict';

    // Estilos del modal
    const modalStyles = `
        .code-reveal-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .code-reveal-modal-overlay.active {
            opacity: 1;
        }
        
        .code-reveal-modal {
            background: white;
            border-radius: 20px;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            transform: scale(0.9);
            transition: transform 0.3s ease;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.3);
        }
        
        .code-reveal-modal-overlay.active .code-reveal-modal {
            transform: scale(1);
        }
        
        .code-reveal-header {
            background: linear-gradient(135deg, #ff8a3d 0%, #ff4f0f 100%);
            padding: 30px;
            text-align: center;
            border-radius: 20px 20px 0 0;
        }
        
        .code-reveal-header h3 {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 10px 0;
        }
        
        .code-reveal-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.95rem;
            margin: 0;
        }
        
        .code-reveal-body {
            padding: 30px;
        }
        
        .code-reveal-benefits {
            margin-bottom: 25px;
        }
        
        .code-reveal-benefit {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .code-reveal-benefit:last-child {
            border-bottom: none;
        }
        
        .code-reveal-benefit-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .code-reveal-benefit-icon i {
            color: white;
            font-size: 18px;
        }
        
        .code-reveal-benefit-text h5 {
            font-size: 1rem;
            font-weight: 700;
            color: #1a1a2e;
            margin: 0 0 4px 0;
        }
        
        .code-reveal-benefit-text p {
            font-size: 0.9rem;
            color: #666;
            margin: 0;
            line-height: 1.4;
        }
        
        .code-reveal-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .btn-reveal-register {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #ff8a3d 0%, #ff4f0f 100%);
            border: none;
            border-radius: 50px;
            color: white;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-reveal-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255, 79, 15, 0.3);
        }
        
        .btn-reveal-continue {
            width: 100%;
            padding: 14px;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 50px;
            color: #666;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-reveal-continue:hover {
            background: #e9ecef;
        }
        
        .code-reveal-disclaimer {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 0 0 20px 20px;
            font-size: 0.85rem;
            color: #666;
        }
        
        .code-reveal-disclaimer i {
            color: #28a745;
            margin-right: 5px;
        }
        
        /* Código revelado */
        .revealed-code-container {
            background: linear-gradient(135deg, #2b1f36, #3b2740);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .revealed-code-text {
            font-family: 'Courier New', monospace;
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            letter-spacing: 3px;
            word-break: break-all;
        }
        
        .btn-copy-revealed {
            margin-top: 15px;
            padding: 12px 25px;
            background: linear-gradient(135deg, #ff8a3d 0%, #ff4f0f 100%);
            border: none;
            border-radius: 25px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-copy-revealed:hover {
            transform: scale(1.05);
        }
        
        .close-modal-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 35px;
            height: 35px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .close-modal-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }
    `;

    // Inyectar estilos
    const styleSheet = document.createElement('style');
    styleSheet.textContent = modalStyles;
    document.head.appendChild(styleSheet);

    // Función para mostrar el modal de reveal
    window.showCodeRevealModal = function (codigoId, marca, beneficio) {
        const isLoggedIn = typeof currentUserId !== 'undefined' && currentUserId && currentUserId !== '';

        const overlay = document.createElement('div');
        overlay.className = 'code-reveal-modal-overlay';
        overlay.innerHTML = `
            <div class="code-reveal-modal" style="position: relative;">
                <button class="close-modal-btn" onclick="this.closest('.code-reveal-modal-overlay').remove()">
                    <i class="fas fa-times"></i>
                </button>
                <div class="code-reveal-header">
                    <h3><i class="fas fa-gift"></i> ¡Código disponible!</h3>
                    <p>Gana ${beneficio || 'dinero'}€ con este código de ${marca || 'referido'}</p>
                </div>
                <div class="code-reveal-body">
                    <div class="code-reveal-benefits">
                        <div class="code-reveal-benefit">
                            <div class="code-reveal-benefit-icon">
                                <i class="fas fa-hands-helping"></i>
                            </div>
                            <div class="code-reveal-benefit-text">
                                <h5>Ayuda personalizada</h5>
                                <p>El autor del código puede contactarte por chat y ayudarte paso a paso para que ambos ganéis el beneficio. ¡Situación win-win!</p>
                            </div>
                        </div>
                        <div class="code-reveal-benefit">
                            <div class="code-reveal-benefit-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="code-reveal-benefit-text">
                                <h5>Proceso verificado</h5>
                                <p>El autor tiene experiencia con este código y sabe exactamente qué pasos seguir para que el beneficio se aplique correctamente.</p>
                            </div>
                        </div>
                        <div class="code-reveal-benefit">
                            <div class="code-reveal-benefit-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <div class="code-reveal-benefit-text">
                                <h5>Chat directo</h5>
                                <p>Si tienes dudas durante el proceso, podrás preguntar directamente al autor. Gratis y sin compromiso.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="code-reveal-actions">
                        ${!isLoggedIn ? `
                            <button class="btn-reveal-register" onclick="showLoginModal('Regístrate para ver el código y recibir ayuda', window.location.href); this.closest('.code-reveal-modal-overlay').remove();">
                                <i class="fas fa-user-plus"></i> Registrarme y ver código
                            </button>
                            <button class="btn-reveal-continue" onclick="revealCodeWithoutLogin('${codigoId}', this)">
                                <i class="fas fa-eye"></i> Ver código sin registrarme
                            </button>
                        ` : `
                            <button class="btn-reveal-register" onclick="revealCodeAsUser('${codigoId}', this)">
                                <i class="fas fa-unlock"></i> Ver código
                            </button>
                        `}
                    </div>
                </div>
                <div class="code-reveal-disclaimer">
                    <i class="fas fa-lock"></i> Tus datos están protegidos. Solo el autor podrá contactarte si te registras.
                </div>
            </div>
        `;

        document.body.appendChild(overlay);

        // Animar entrada
        requestAnimationFrame(() => {
            overlay.classList.add('active');
        });

        // Cerrar al hacer clic fuera
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.remove();
            }
        });
    };

    // Revelar código para usuario logueado
    window.revealCodeAsUser = async function (codigoId, btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';

        try {
            const response = await fetch('/ajax/registrar_vista_codigo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ codigo_id: codigoId })
            });

            const data = await response.json();

            if (data.success && data.codigo) {
                showRevealedCode(data.codigo.codigo, btn);
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-unlock"></i> Ver código';
                alert(data.error || 'Error al cargar el código');
            }
        } catch (error) {
            console.error('Error:', error);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-unlock"></i> Ver código';
        }
    };

    // Revelar código sin login (pero registrar sesión anónima)
    window.revealCodeWithoutLogin = async function (codigoId, btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';

        try {
            const response = await fetch('/ajax/registrar_vista_codigo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ codigo_id: codigoId })
            });

            const data = await response.json();

            if (data.success && data.codigo) {
                showRevealedCode(data.codigo.codigo, btn);
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-eye"></i> Ver código sin registrarme';
                alert(data.error || 'Error al cargar el código');
            }
        } catch (error) {
            console.error('Error:', error);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-eye"></i> Ver código sin registrarme';
        }
    };

    // Mostrar código revelado
    function showRevealedCode(codigo, btn) {
        const modal = btn.closest('.code-reveal-modal');
        const body = modal.querySelector('.code-reveal-body');
        const header = modal.querySelector('.code-reveal-header');

        header.innerHTML = `
            <h3><i class="fas fa-check-circle"></i> ¡Aquí tienes tu código!</h3>
            <p>Copia el código y úsalo para obtener tu beneficio</p>
        `;

        body.innerHTML = `
            <div class="revealed-code-container">
                <div class="revealed-code-text" id="revealedCodeText">${codigo}</div>
                <button class="btn-copy-revealed" onclick="copyRevealedCode()">
                    <i class="fas fa-copy"></i> Copiar código
                </button>
            </div>
            <div class="code-reveal-benefits">
                <div class="code-reveal-benefit">
                    <div class="code-reveal-benefit-icon" style="background: linear-gradient(135deg, #ff8a3d 0%, #ff4f0f 100%);">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <div class="code-reveal-benefit-text">
                        <h5>¿Necesitas ayuda?</h5>
                        <p>Regístrate y el autor del código podrá ayudarte por chat si tienes problemas.</p>
                    </div>
                </div>
            </div>
            <button class="btn-reveal-continue" onclick="this.closest('.code-reveal-modal-overlay').remove()">
                Cerrar
            </button>
        `;
    }

    // Copiar código revelado
    window.copyRevealedCode = function () {
        const codeText = document.getElementById('revealedCodeText');
        if (codeText) {
            navigator.clipboard.writeText(codeText.textContent).then(() => {
                const btn = document.querySelector('.btn-copy-revealed');
                if (btn) {
                    btn.innerHTML = '<i class="fas fa-check"></i> ¡Copiado!';
                    setTimeout(() => {
                        btn.innerHTML = '<i class="fas fa-copy"></i> Copiar código';
                    }, 2000);
                }
            });
        }
    };
})();
