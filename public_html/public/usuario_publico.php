<?php 
get_header_modern($title, $description); 
?>

<style>
/* Estilos para la página pública de usuario con diseño moderno y fondo oscuro */
.user-page-container {
    background: #222222;
    min-height: 100vh;
    padding: 0;
}

.user-page-header {
    background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 50%, #222222 100%);
    border-radius: 20px;
    margin: 20px auto;
    max-width: 1600px;
    padding: 40px 30px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.4);
    border: 1px solid rgba(255,255,255,0.05);
}

.user-profile-section {
    display: flex;
    align-items: center;
    gap: 25px;
    margin-bottom: 35px;
}

.user-avatar-large {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    background: var(--primary-orange);
    color: var(--text-white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    font-weight: bold;
    box-shadow: 0 8px 24px rgba(227, 6, 19, 0.4);
    border: 4px solid rgba(227, 6, 19, 0.3);
    flex-shrink: 0;
}

.user-avatar-large img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid rgba(227, 6, 19, 0.3);
}

.user-info {
    flex: 1;
}

.user-info h1 {
    color: var(--text-white) !important;
    font-size: 2.8rem;
    margin: 0 0 8px 0;
    font-weight: 700;
    line-height: 1.2;
}

.user-info p {
    color: var(--text-gray);
    font-size: 1.1rem;
    margin: 0 0 8px 0;
    opacity: 0.9;
}

.user-info .user-stats-info {
    color: var(--text-gray);
    font-size: 0.95rem;
    margin: 0;
    opacity: 0.8;
    display: flex;
    align-items: center;
    gap: 8px;
}

.user-info .user-stats-info i {
    color: var(--primary-orange);
    font-size: 0.9rem;
}

.user-chat-button-container {
    margin-top: 15px;
}

.btn-chat-user-profile {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 24px;
    background: linear-gradient(135deg, var(--primary-orange), #FF4D4D);
    color: var(--text-white);
    border: none;
    border-radius: 25px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(227, 6, 19, 0.3);
}

.btn-chat-user-profile:hover {
    background: linear-gradient(135deg, #FF4D4D, var(--primary-orange));
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(227, 6, 19, 0.4);
}

.btn-chat-user-profile:active {
    transform: translateY(0);
    box-shadow: 0 2px 10px rgba(227, 6, 19, 0.3);
}

.btn-chat-user-profile i {
    font-size: 1.1rem;
}

.stats-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.stat-item {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    padding: 18px 20px;
    text-align: center;
    border: 1px solid rgba(255, 255, 255, 0.08);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.stat-item:hover {
    background: rgba(255, 255, 255, 0.08);
    transform: translateY(-2px);
    border-color: rgba(227, 6, 19, 0.3);
}

.stat-item i {
    font-size: 1.5rem;
    color: var(--primary-orange);
    margin-bottom: 5px;
}

.stat-number {
    font-size: 2.2rem;
    font-weight: 800;
    color: var(--text-white);
    margin: 0;
    line-height: 1;
}

.stat-label {
    color: var(--text-gray);
    font-size: 0.85rem;
    margin: 0;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filters-section {
    background: transparent;
    border-radius: 0;
    padding: 0;
    margin-bottom: 0;
    box-shadow: none;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    padding-top: 25px;
}

.filters-title {
    color: var(--text-white);
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 15px;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-size: 0.85rem;
}

.filter-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 10px 18px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    background: rgba(255, 255, 255, 0.05);
    color: var(--text-gray);
    border-radius: 20px;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

.filter-btn:hover {
    border-color: var(--primary-orange);
    color: var(--text-white);
    background: rgba(227, 6, 19, 0.1);
    transform: translateY(-1px);
}

.filter-btn.active {
    background: var(--primary-orange);
    border-color: var(--primary-orange);
    color: var(--text-white);
    box-shadow: 0 4px 12px rgba(227, 6, 19, 0.3);
}

.codes-section {
    background: transparent;
    border-radius: 0;
    padding: 20px 0;
    margin: 0 auto 20px auto;
    max-width: 1600px;
    width: 100%;
}

.codes-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding: 0 20px 15px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    gap: 20px;
    flex-wrap: wrap;
}

.search-container {
    position: relative;
    flex: 1;
    max-width: 400px;
    min-width: 200px;
}

.search-input {
    width: 100%;
    padding: 12px 40px 12px 16px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 25px;
    color: var(--text-white);
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: var(--primary-orange);
    background: rgba(255, 255, 255, 0.08);
    box-shadow: 0 0 0 3px rgba(227, 6, 19, 0.1);
}

.search-input::placeholder {
    color: var(--text-gray);
    opacity: 0.6;
}

.search-icon {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-gray);
    pointer-events: none;
}

.codes-title {
    color: var(--text-white);
    font-size: 1.6rem;
    font-weight: 700;
    margin: 0;
}

.codes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 18px;
    padding: 0 20px;
}

.code-card {
    background: #2a2a2a;
    border-radius: 16px;
    padding: 1.2rem;
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
    border: 1px solid rgba(255,255,255,0.08);
    transition: all 0.3s ease;
    position: relative;
    overflow: visible;
}

.code-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.4);
    border-color: rgba(227, 6, 19, 0.3);
}

.code-card.featured { }

.code-brand-image {
    position: relative;
    margin-bottom: 12px;
}

.code-brand-image .brand-link {
    display: block;
    position: relative;
}

.code-brand-image .brand-image,
.code-brand-image .brand-placeholder {
    width: 100%;
    height: auto;
    border-radius: 12px;
    transition: opacity 0.3s ease;
}

.code-brand-image:hover .brand-image,
.code-brand-image:hover .brand-placeholder {
    opacity: 0.9;
}

/* Tooltip con descripción */
.brand-description-tooltip {
    position: absolute;
    bottom: calc(100% + 12px);
    left: 50%;
    transform: translateX(-50%) translateY(-5px);
    background: rgba(26, 26, 26, 0.98);
    backdrop-filter: blur(10px);
    color: var(--text-white);
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 0.875rem;
    line-height: 1.5;
    max-width: 300px;
    min-width: 200px;
    width: max-content;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.1);
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    pointer-events: none;
    z-index: 1000;
    word-wrap: break-word;
    text-align: left;
}

.brand-description-tooltip::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 6px solid transparent;
    border-top-color: rgba(26, 26, 26, 0.98);
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
}

.code-brand-image:hover .brand-description-tooltip {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(0);
}

.featured-badge {
    position: absolute;
    top: -8px;
    right: 12px;
    background: linear-gradient(135deg, var(--primary-orange), #FF4D4D);
    color: #fff;
    padding: 0.4rem 0.85rem;
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 700;
    box-shadow: 0 6px 16px rgba(255,107,53,0.35);
    z-index: 10;
    white-space: nowrap;
}

.code-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    gap: 10px;
}

.code-reward {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    background: linear-gradient(135deg, #1f7a1f, #2bbf6a);
    border: 1px solid rgba(255,255,255,0.06);
    color: #fff;
    padding: .5rem .9rem;
    border-radius: 10px;
    font-weight: 800;
    font-size: 1.1rem;
    width: 100%;
    justify-content: center;
}

.code-details {
    margin: 12px 0;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.code-details p {
    margin: 0;
    color: var(--text-gray);
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 6px;
    opacity: 0.85;
}

.code-details strong {
    color: var(--text-white);
    font-weight: 600;
    min-width: 60px;
    font-size: 0.85rem;
}

.code-code {
    background: rgba(255,255,255,0.06);
    padding: 10px 14px;
    border-radius: 8px;
    font-family: 'Courier New', monospace;
    font-weight: 600;
    color: var(--text-white);
    border: 1px solid rgba(255,255,255,0.1);
    font-size: 0.9rem;
    display: inline-block;
}


.code-actions {
    display: flex;
    gap: 8px;
    margin-top: 15px;
    flex-wrap: wrap;
}

.btn-action {
    padding: 10px 18px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-view-code {
    background: linear-gradient(135deg, var(--primary-orange), #FF4D4D);
    color: var(--text-white);
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(255,107,53,0.3);
    flex: 1;
    justify-content: center;
}

.btn-view-code:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(255,107,53,0.4);
    color: var(--text-white);
    text-decoration: none;
}

.btn-share {
    background: rgba(111, 66, 193, 0.2);
    color: var(--text-white);
    border: 1px solid rgba(111, 66, 193, 0.4);
    box-shadow: 0 2px 8px rgba(111, 66, 193, 0.2);
}

.btn-share:hover {
    transform: translateY(-2px);
    background: rgba(111, 66, 193, 0.3);
    box-shadow: 0 4px 12px rgba(111, 66, 193, 0.3);
    color: var(--text-white);
    text-decoration: none;
}

.no-codes {
    text-align: center;
    padding: 80px 20px;
    color: var(--text-gray);
}

.no-codes i {
    font-size: 4rem;
    color: var(--text-gray);
    margin-bottom: 20px;
    display: block;
    opacity: 0.5;
}

.no-codes h3 {
    color: var(--text-white);
    margin-bottom: 15px;
    font-size: 1.5rem;
}

.no-codes p {
    font-size: 1rem;
    margin-bottom: 30px;
    opacity: 0.8;
}

/* Modal para mostrar código */
.code-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.code-modal {
    background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
    border-radius: 20px;
    padding: 0;
    max-width: 500px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.1);
    animation: slideUp 0.3s ease;
    overflow: hidden;
}

@keyframes slideUp {
    from {
        transform: translateY(30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.code-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 25px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.code-modal-header h3 {
    color: var(--text-white);
    font-size: 1.3rem;
    margin: 0;
    font-weight: 700;
}

.code-modal-close {
    background: transparent;
    border: none;
    color: var(--text-gray);
    font-size: 1.5rem;
    cursor: pointer;
    padding: 5px;
    transition: all 0.3s ease;
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.code-modal-close:hover {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-white);
}

.code-modal-body {
    padding: 30px 25px;
}

.code-display {
    text-align: center;
    margin-bottom: 25px;
}

.code-label {
    color: var(--text-gray);
    font-size: 0.9rem;
    margin-bottom: 15px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.code-value {
    background: rgba(255, 255, 255, 0.08);
    border: 2px solid var(--primary-orange);
    border-radius: 12px;
    padding: 20px;
    font-family: 'Courier New', monospace;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-white);
    margin-bottom: 20px;
    word-break: break-all;
    box-shadow: 0 4px 20px rgba(227, 6, 19, 0.2);
}

.btn-copy-code {
    background: linear-gradient(135deg, var(--primary-orange), #FF4D4D);
    color: var(--text-white);
    border: none;
    border-radius: 10px;
    padding: 12px 24px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(227, 6, 19, 0.3);
}

.btn-copy-code:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(227, 6, 19, 0.4);
}

.code-modal-actions {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.code-modal-actions .btn-view-code {
    width: 100%;
    justify-content: center;
}

/* Modal de compartir */
.share-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.85);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    animation: fadeIn 0.3s ease;
}

.share-modal {
    background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
    border-radius: 24px;
    padding: 0;
    max-width: 500px;
    width: 90%;
    box-shadow: 0 25px 70px rgba(0, 0, 0, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.1);
    animation: slideUp 0.3s ease;
    overflow: hidden;
}

.share-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 25px 30px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.share-modal-header h3 {
    color: var(--text-white);
    font-size: 1.4rem;
    margin: 0;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}

.share-modal-header h3 i {
    color: var(--primary-orange);
}

.share-modal-close {
    background: transparent;
    border: none;
    color: var(--text-gray);
    font-size: 1.5rem;
    cursor: pointer;
    padding: 8px;
    transition: all 0.3s ease;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.share-modal-close:hover {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-white);
    transform: rotate(90deg);
}

.share-modal-body {
    padding: 30px;
}

.share-code-info {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
    text-align: center;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.share-code-info .brand-name {
    color: var(--text-white);
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 8px;
}

.share-code-info .code-text {
    background: rgba(227, 6, 19, 0.15);
    border: 2px solid var(--primary-orange);
    border-radius: 10px;
    padding: 12px 20px;
    font-family: 'Courier New', monospace;
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--text-white);
    margin: 10px 0;
    word-break: break-all;
}

.share-platforms {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}

.share-platform-btn {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 18px 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    color: var(--text-white);
}

.share-platform-btn:hover {
    transform: translateY(-3px);
    border-color: currentColor;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
}

.share-platform-btn i {
    font-size: 2rem;
    margin-bottom: 4px;
}

.share-platform-btn span {
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.share-platform-btn.whatsapp {
    color: #25D366;
}

.share-platform-btn.whatsapp:hover {
    background: rgba(37, 211, 102, 0.1);
    box-shadow: 0 8px 20px rgba(37, 211, 102, 0.2);
}

.share-platform-btn.facebook {
    color: #1877F2;
}

.share-platform-btn.facebook:hover {
    background: rgba(24, 119, 242, 0.1);
    box-shadow: 0 8px 20px rgba(24, 119, 242, 0.2);
}

.share-platform-btn.instagram {
    color: #E4405F;
}

.share-platform-btn.instagram:hover {
    background: rgba(228, 64, 95, 0.1);
    box-shadow: 0 8px 20px rgba(228, 64, 95, 0.2);
}

.share-platform-btn.tiktok {
    color: #000000;
}

.share-platform-btn.tiktok:hover {
    background: rgba(0, 0, 0, 0.2);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
}

.share-platform-btn.telegram {
    color: #0088cc;
}

.share-platform-btn.telegram:hover {
    background: rgba(0, 136, 204, 0.1);
    box-shadow: 0 8px 20px rgba(0, 136, 204, 0.2);
}

.share-platform-btn.email {
    color: #EA4335;
}

.share-platform-btn.email:hover {
    background: rgba(234, 67, 53, 0.1);
    box-shadow: 0 8px 20px rgba(234, 67, 53, 0.2);
}

.share-copy-link {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.share-link-input {
    width: 100%;
    padding: 12px 16px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 10px;
    color: var(--text-white);
    font-size: 0.9rem;
    margin-bottom: 12px;
}

.share-link-input:focus {
    outline: none;
    border-color: var(--primary-orange);
    background: rgba(255, 255, 255, 0.08);
}

.btn-copy-link {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, var(--primary-orange), #FF4D4D);
    color: var(--text-white);
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-copy-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(227, 6, 19, 0.4);
}

/* Responsive */
@media (max-width: 1400px) {
    .codes-grid {
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    }
}

@media (max-width: 1024px) {
    .codes-grid {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    }
}

@media (max-width: 768px) {
    .user-page-header {
        margin: 10px;
        padding: 25px 20px;
        border-radius: 16px;
        max-width: 100%;
    }
    
    .user-profile-section {
        flex-direction: column;
        text-align: center;
        gap: 20px;
    }
    
    .user-avatar-large {
        width: 120px;
        height: 120px;
        font-size: 2.5rem;
    }
    
    .user-info h1 {
        font-size: 2.2rem;
    }
    
    .stats-section {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .stat-item {
        padding: 16px;
    }
    
    .codes-section {
        margin: 0 10px 10px 10px;
        padding: 15px 0;
        max-width: 100%;
    }
    
    .codes-header {
        padding: 0 10px;
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .codes-title {
        font-size: 1.4rem;
    }
    
    .search-container {
        width: 100%;
        max-width: 100%;
    }
    
    .codes-grid {
        grid-template-columns: 1fr;
        gap: 15px;
        padding: 0 10px;
    }
    
    .code-card {
        padding: 1.25rem;
    }
    
    .brand-description-tooltip {
        max-width: 240px;
        font-size: 0.8rem;
        padding: 10px 14px;
    }
    
    .filter-buttons {
        justify-content: flex-start;
    }
    
    .filter-btn {
        padding: 8px 16px;
        font-size: 0.85rem;
    }
    
    .code-actions {
        flex-direction: column;
    }
    
    .btn-action {
        width: 100%;
        justify-content: center;
    }
    
    .code-modal {
        width: 95%;
        max-width: 100%;
    }
    
    .code-modal-header,
    .code-modal-body {
        padding: 20px;
    }
    
    .code-value {
        font-size: 1.2rem;
        padding: 15px;
    }
    
    .share-modal {
        width: 95%;
        max-width: 100%;
    }
    
    .share-modal-header,
    .share-modal-body {
        padding: 20px;
    }
    
    .share-platforms {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .share-platform-btn {
        padding: 15px 10px;
    }
    
    .share-platform-btn i {
        font-size: 1.5rem;
    }
}
</style>

<div class="user-page-container">
    <div class="user-page-header">
        <div class="user-profile-section">
            <?php
            // Check VIP
            if (!function_exists('es_usuario_vip')) { include_once __DIR__ . '/../myphp/funciones_usuario.php'; }
            $profile_user_id = isset($data_usuario['_id']) ? (string)$data_usuario['_id'] : '';
            $is_profile_vip = !empty($profile_user_id) && es_usuario_vip($profile_user_id);
            ?>
            <div class="user-avatar-large" style="<?php echo $is_profile_vip ? 'border-color: #ffd700; box-shadow: 0 0 25px rgba(255, 215, 0, 0.5);' : ''; ?>">
                <?php if (!empty($data_usuario["img"])): ?>
                    <img src="<?php echo htmlspecialchars($data_usuario["img"]); ?>" alt="Avatar de <?php echo htmlspecialchars($data_usuario["username"] ?? "Usuario"); ?>" style="<?php echo $is_profile_vip ? 'border-color: #ffd700;' : ''; ?>">
                <?php else: ?>
                    <div class="user-avatar-placeholder">
                        <?php echo strtoupper(substr($data_usuario["username"] ?? "U", 0, 2)); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="user-info">
                <h1>
                    <?php echo htmlspecialchars($data_usuario["username"] ?? "Usuario"); ?>
                    <?php if ($is_profile_vip): ?>
                        <span class="vip-badge-gold" style="font-size: 0.5em; vertical-align: middle; margin-left: 10px;"><i class="fas fa-crown"></i> VIP</span>
                    <?php endif; ?>
                </h1>
                <p>
                    <?php 
                    $fecha_registro_formateada = '';
                    $dias_miembro = null;
                    if (isset($data_usuario["fecha_registro"])) {
                        $raw = $data_usuario["fecha_registro"];
                        try {
                            if ($raw instanceof MongoDB\BSON\UTCDateTime) {
                                $dt = $raw->toDateTime();
                            } elseif (is_numeric($raw)) {
                                $ts = (string)$raw;
                                if (strlen($ts) >= 13) { $ts = substr($ts, 0, 10); }
                                $dt = (new DateTime())->setTimestamp((int)$ts);
                            } elseif (is_string($raw) && strtotime($raw)) {
                                $dt = new DateTime($raw);
                            } else {
                                $dt = null;
                            }
                            if ($dt) {
                                $hoy = new DateTime();
                                $dias_miembro = $hoy->diff($dt)->days;
                                $fecha_registro_formateada = $dt->format('d/m/Y');
                            }
                        } catch (Throwable $e) {
                            $dias_miembro = null;
                        }
                    }
                    if ($fecha_registro_formateada) {
                        echo "Se unió el " . $fecha_registro_formateada;
                        if ($dias_miembro !== null) {
                            echo " · " . (int)$dias_miembro . " días en la comunidad";
                        }
                    } else {
                        echo "Miembro de la comunidad";
                    }
                    ?>
                </p>
                <?php if ($fecha_registro_formateada): ?>
                <p class="user-stats-info">
                    <i class="fas fa-upload"></i> <?php echo $num_codigos; ?> códigos compartidos desde entonces
                </p>
                <?php endif; ?>
                
                <?php 
                // Botón de chat - solo mostrar si el usuario está logueado y no es su propio perfil
                $usuario_actual_id = isset($_SESSION['user_id']) ? (string)$_SESSION['user_id'] : '';
                
                // Obtener ID del usuario del perfil de forma segura
                $usuario_perfil_id = '';
                if (isset($data_usuario['_id'])) {
                    if ($data_usuario['_id'] instanceof MongoDB\BSON\ObjectId) {
                        $usuario_perfil_id = (string)$data_usuario['_id'];
                    } else {
                        $usuario_perfil_id = (string)$data_usuario['_id'];
                    }
                }
                
                $mostrar_boton_chat = !empty($usuario_actual_id) && !empty($usuario_perfil_id) && $usuario_actual_id !== $usuario_perfil_id;
                
                if ($mostrar_boton_chat):
                    $user_id_js = json_encode($usuario_perfil_id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                    $username_js = json_encode($data_usuario["username"] ?? "Usuario", JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                    $user_img_js = json_encode($data_usuario["img"] ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                ?>
                <div class="user-chat-button-container" style="margin-top: 15px;">
                    <button type="button" class="btn-chat-user-profile" 
                            data-chat-user-id="<?php echo htmlspecialchars($usuario_perfil_id); ?>"
                            data-chat-username="<?php echo htmlspecialchars($data_usuario["username"] ?? "Usuario"); ?>"
                            data-chat-user-img="<?php echo htmlspecialchars($data_usuario["img"] ?? ''); ?>"
                            title="Enviar mensaje a <?php echo htmlspecialchars($data_usuario["username"] ?? "Usuario"); ?>">
                        <i class="fas fa-comments"></i>
                        <span>Enviar mensaje</span>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="stats-section">
            <div class="stat-item">
                <i class="fas fa-tag"></i>
                <div class="stat-number"><?php echo $num_codigos; ?></div>
                <div class="stat-label">Códigos compartidos</div>
            </div>
            <div class="stat-item">
                <i class="fas fa-folder"></i>
                <div class="stat-number"><?php echo count($categorias_usuario); ?></div>
                <div class="stat-label">Categorías</div>
            </div>
            <div class="stat-item">
                <i class="fas fa-mouse-pointer"></i>
                <div class="stat-number"><?php echo isset($total_clicks_usuario) ? number_format($total_clicks_usuario) : "0"; ?></div>
                <div class="stat-label">Clicks totales</div>
            </div>
            <div class="stat-item">
                <i class="fas fa-eye"></i>
                <div class="stat-number"><?php echo isset($total_impresiones_usuario) ? number_format($total_impresiones_usuario) : "0"; ?></div>
                <div class="stat-label">Impresiones totales</div>
            </div>
        </div>
        
        <?php if(count($categorias_usuario) > 0): ?>
        <div class="filters-section">
            <div class="filters-title">Filtrar por categoría</div>
            <div class="filter-buttons">
                <button class="filter-btn active" data-category="all" onclick="filterByCategory('all')">
                    Todas las categorías
                </button>
                <?php foreach($categorias_usuario as $categoria): ?>
                    <button class="filter-btn" data-category="<?php echo htmlspecialchars($categoria); ?>" onclick="filterByCategory('<?php echo htmlspecialchars($categoria); ?>')">
                        <?php echo ucwords(str_replace('-', ' ', $categoria)); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="codes-section">
        <div class="codes-header">
            <h3 class="codes-title" id="codesTitle">Códigos compartidos (<?php echo $num_codigos; ?>)</h3>
            <div class="search-container">
                <input type="text" id="searchCodes" class="search-input" placeholder="Buscar por marca..." autocomplete="off">
                <i class="fas fa-search search-icon"></i>
            </div>
        </div>
        
        <?php if($listado_codigos && count($listado_codigos) > 0): ?>
            <div class="codes-grid" id="codesGrid">
                <?php foreach($listado_codigos as $codigo): ?>
                    <?php
                    // Verificar que $codigo['marca'] existe y no es null
                    if (!isset($codigo['marca']) || $codigo['marca'] === null) {
                        continue;
                    }
                    
                    $marca = getObjectMarca('nombre_clave', $codigo['marca']);
                    if (!$marca) {
                        continue;
                    }
                    
                    $is_destacado = isset($codigo['destacado']) && $codigo['destacado'] > 0;
                    
                    // Registrar impresión cuando se muestra el código
                    if (isset($codigo['_id'])) {
                        añadir_impresion_codigo($codigo['_id']);
                    }
                    
                    // Obtener categoría del código
                    $categoria_codigo = isset($codigo['clave_categoria']) ? $codigo['clave_categoria'] : 'sin-categoria';
                    ?>
                    
                    <div class="code-card <?php echo $is_destacado ? 'featured' : ''; ?>" data-category="<?php echo htmlspecialchars($categoria_codigo); ?>" data-marca="<?php echo htmlspecialchars(strtolower($marca['nombre'] ?? $codigo['marca'])); ?>">
                        <?php if($is_destacado): ?>
                            <div class="featured-badge">
                                <i class="fas fa-star"></i> Destacado
                            </div>
                        <?php endif; ?>
                        
                        <?php
                        // Obtener información de la marca para la imagen
                        $marca_info = get_brand_info($codigo['marca']);
                        $marca_imagen = $marca_info['imagen'] ?? '';
                        $brand_slug = $marca_info['nombre_clave'] ?? strtolower($codigo['marca']);
                        $marca_url = '/de-' . $brand_slug;
                        
                        // Formatear fecha si existe
                        $fecha_formateada = '';
                        if(isset($codigo['fecha_publicacion'])) {
                            $fecha_formateada = formatDateAgoLarge($codigo['fecha_publicacion']);
                        }
                        ?>
                        
                        <!-- Imagen de la marca (formato estándar) -->
                        <div class="code-brand-image">
                            <a href="<?php echo htmlspecialchars($marca_url); ?>" class="brand-link">
                                <?php if($marca_imagen): ?>
                                    <img loading="lazy" src="<?php echo htmlspecialchars($marca_imagen); ?>" alt="<?php echo htmlspecialchars($marca['nombre'] ?? $codigo['marca']); ?>" class="brand-image">
                                <?php else: ?>
                                    <div class="brand-placeholder">
                                        <i class="fas fa-tag"></i>
                                    </div>
                                <?php endif; ?>
                            </a>
                            <!-- Tooltip con descripción al hover -->
                            <?php if(!empty($codigo['descripcion'])): ?>
                                <div class="brand-description-tooltip">
                                    <?php echo htmlspecialchars($codigo['descripcion']); ?>
                                </div>
                            <?php endif; ?>
                            <!-- Badge flotante con nombre de marca -->
                            <div class="brand-name-badge">
                                <?php echo htmlspecialchars($marca['nombre'] ?? $codigo['marca']); ?>
                            </div>
                            <!-- Badge flotante con fecha (si existe) -->
                            <?php if($fecha_formateada): ?>
                                <div class="brand-date-badge">
                                    <i class="far fa-clock"></i>
                                    <span><?php echo htmlspecialchars($fecha_formateada); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="code-header">
                            <div class="code-reward">
                                <?php echo $codigo['num_beneficio'] ?? '10'; ?>€
                            </div>
                        </div>
                        
                        <div class="code-details">
                            <p><strong>Clicks:</strong> <?php echo $codigo['totalclicks'] ?? '0'; ?></p>
                            <p><strong>Impresiones:</strong> <?php echo isset($codigo['total_impressions']) ? number_format($codigo['total_impressions']) : '0'; ?></p>
                        </div>
                        
                        <div class="code-actions">
                            <a href="/de-<?php echo htmlspecialchars($brand_slug); ?>?codigo=<?php echo $codigo['_id']; ?>" class="btn-action btn-view-code">
                                <i class="fas fa-eye"></i> Ver Código
                            </a>
                            <button class="btn-action btn-share" onclick="openShareModal(<?php echo json_encode((string)$codigo['_id']); ?>, <?php echo json_encode($marca['nombre'] ?? $codigo['marca']); ?>, <?php echo json_encode($codigo['codigo'] ?? ''); ?>, <?php echo json_encode($codigo['descripcion'] ?? ''); ?>, <?php echo json_encode($marca_url); ?>)">
                                <i class="fas fa-share"></i> Compartir
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-codes">
                <i class="fas fa-code"></i>
                <h3>No hay códigos compartidos</h3>
                <p>Este usuario aún no ha compartido ningún código de descuento.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Variables globales para filtros
let currentCategory = 'all';
let currentSearch = '';

// Función para filtrar códigos (categoría + búsqueda)
function filterCodes() {
    const codeCards = document.querySelectorAll('.code-card');
    let visibleCount = 0;
    
    codeCards.forEach(card => {
        const cardCategory = card.getAttribute('data-category');
        const cardMarca = card.getAttribute('data-marca') || '';
        const searchLower = currentSearch.toLowerCase().trim();
        
        // Verificar filtro de categoría
        const categoryMatch = currentCategory === 'all' || cardCategory === currentCategory;
        
        // Verificar filtro de búsqueda
        const searchMatch = searchLower === '' || cardMarca.includes(searchLower);
        
        // Mostrar u ocultar según ambos filtros
        if (categoryMatch && searchMatch) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Actualizar contador
    const titleElement = document.getElementById('codesTitle');
    if (titleElement) {
        titleElement.textContent = `Códigos compartidos (${visibleCount})`;
    }
}

// Función para filtrar por categoría
function filterByCategory(category) {
    // Remover clase active de todos los botones
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Agregar clase active al botón clickeado
    event.target.classList.add('active');
    
    // Actualizar categoría actual
    currentCategory = category;
    
    // Aplicar filtros
    filterCodes();
}

// Buscador AJAX en tiempo real
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchCodes');
    
    if (searchInput) {
        // Filtrar mientras se escribe (con debounce)
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            
            searchTimeout = setTimeout(function() {
                currentSearch = searchInput.value;
                filterCodes();
            }, 150); // Esperar 150ms después de que el usuario deje de escribir
        });
        
        // Limpiar búsqueda con Escape
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                searchInput.value = '';
                currentSearch = '';
                filterCodes();
            }
        });
    }
});

// Función para ver código (redirige directamente a la página de detalle)
function viewCode(codigoId, marca) {
    window.location.href = `/de-${marca}?codigo=${codigoId}`;
}

// Función para abrir modal de compartir
function openShareModal(codigoId, marcaNombre, codigoTexto, descripcion, marcaUrl) {
    console.log('openShareModal llamado con:', {codigoId, marcaNombre, codigoTexto, descripcion, marcaUrl});
    
    try {
        // Validar parámetros
        if (!codigoId) {
            console.error('Error: codigoId no proporcionado');
            return;
        }
        
        // Escapar caracteres especiales para evitar problemas en el template string
        const safeMarcaNombre = String(marcaNombre || 'Marca').replace(/`/g, '\\`').replace(/\$/g, '\\$');
        const safeCodigoTexto = String(codigoTexto || '').replace(/`/g, '\\`').replace(/\$/g, '\\$');
        const safeDescripcion = String(descripcion || '').replace(/`/g, '\\`').replace(/\$/g, '\\$');
        const safeMarcaUrl = String(marcaUrl || '');
        
        const shareUrl = window.location.origin + safeMarcaUrl + '?codigo=' + codigoId;
        const shareText = `¡Mira este código de descuento de ${safeMarcaNombre}! Código: ${safeCodigoTexto}${safeDescripcion ? ' - ' + safeDescripcion : ''}`;
        
        // Crear modal
        const modal = document.createElement('div');
        modal.className = 'share-modal-overlay';
        modal.innerHTML = `
            <div class="share-modal">
                <div class="share-modal-header">
                    <h3><i class="fas fa-share-alt"></i> Compartir código</h3>
                    <button class="share-modal-close" onclick="closeShareModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="share-modal-body">
                    <div class="share-code-info">
                        <div class="brand-name">${safeMarcaNombre}</div>
                        <div class="code-text">${safeCodigoTexto}</div>
                    </div>
                    
                    <div class="share-platforms">
                        <a href="https://wa.me/?text=${encodeURIComponent(shareText + ' ' + shareUrl)}" target="_blank" class="share-platform-btn whatsapp">
                            <i class="fab fa-whatsapp"></i>
                            <span>WhatsApp</span>
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}" target="_blank" class="share-platform-btn facebook">
                            <i class="fab fa-facebook"></i>
                            <span>Facebook</span>
                        </a>
                        <a href="https://www.instagram.com/" target="_blank" class="share-platform-btn instagram" onclick="copyToClipboardForInstagram('${safeCodigoTexto.replace(/'/g, "\\'").replace(/"/g, '&quot;')}', event)">
                            <i class="fab fa-instagram"></i>
                            <span>Instagram</span>
                        </a>
                        <a href="https://www.tiktok.com/" target="_blank" class="share-platform-btn tiktok" onclick="copyToClipboardForTikTok('${safeCodigoTexto.replace(/'/g, "\\'").replace(/"/g, '&quot;')}', event)">
                            <i class="fab fa-tiktok"></i>
                            <span>TikTok</span>
                        </a>
                        <a href="https://t.me/share/url?url=${encodeURIComponent(shareUrl)}&text=${encodeURIComponent(shareText)}" target="_blank" class="share-platform-btn telegram">
                            <i class="fa-brands fa-telegram"></i>
                            <span>Telegram</span>
                        </a>
                        <a href="mailto:?subject=${encodeURIComponent('Código de descuento ' + safeMarcaNombre)}&body=${encodeURIComponent(shareText + '\\n\\n' + shareUrl)}" class="share-platform-btn email">
                            <i class="fas fa-envelope"></i>
                            <span>Email</span>
                        </a>
                    </div>
                    
                    <div class="share-copy-link">
                        <input type="text" class="share-link-input" id="shareLinkInput" value="${shareUrl}" readonly>
                        <button class="btn-copy-link" onclick="copyShareLink()">
                            <i class="fas fa-copy"></i> Copiar enlace
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        // Cerrar al hacer click fuera del modal
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeShareModal();
            }
        });
        
        // Cerrar con Escape
        const escapeHandler = function(e) {
            if (e.key === 'Escape') {
                closeShareModal();
                document.removeEventListener('keydown', escapeHandler);
            }
        };
        document.addEventListener('keydown', escapeHandler);
    } catch (error) {
        console.error('Error al abrir modal de compartir:', error);
        alert('Error al abrir el modal de compartir. Por favor, inténtalo de nuevo.');
    }
}

// Función para cerrar modal de compartir
function closeShareModal() {
    const modal = document.querySelector('.share-modal-overlay');
    if (modal) {
        modal.remove();
    }
}

// Función para copiar enlace
function copyShareLink() {
    const input = document.getElementById('shareLinkInput');
    if (input) {
        input.select();
        input.setSelectionRange(0, 99999); // Para móviles
        navigator.clipboard.writeText(input.value).then(() => {
            const btn = document.querySelector('.btn-copy-link');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i> ¡Copiado!';
            btn.style.background = 'linear-gradient(135deg, #28a745, #20c997)';
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.style.background = '';
            }, 2000);
        }).catch(() => {
            alert('No se pudo copiar el enlace');
        });
    }
}

// Funciones para copiar código para Instagram y TikTok (ya que no tienen API de compartir directa)
function copyToClipboardForInstagram(codigo, event) {
    event.preventDefault();
    navigator.clipboard.writeText(codigo).then(() => {
        alert('Código copiado: ' + codigo + '\\n\\nPega el código en tu publicación de Instagram');
    });
}

function copyToClipboardForTikTok(codigo, event) {
    event.preventDefault();
    navigator.clipboard.writeText(codigo).then(() => {
        alert('Código copiado: ' + codigo + '\\n\\nPega el código en tu video de TikTok');
    });
}

// Inicializar botón de chat cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    const chatButton = document.querySelector('.btn-chat-user-profile');
    if (chatButton) {
        chatButton.addEventListener('click', function() {
            const userId = this.getAttribute('data-chat-user-id');
            const userName = this.getAttribute('data-chat-username');
            const userImg = this.getAttribute('data-chat-user-img');
            
            if (typeof openChatModal === 'function') {
                const defaultMsg = "hola buenas, me ayudas con el proceso y lo hacemos juntos?";
                openChatModal(userId, userName, userImg, defaultMsg);
            } else {
                // Si la función no está disponible, esperar un poco y reintentar
                setTimeout(function() {
                    if (typeof openChatModal === 'function') {
                        const defaultMsg = "hola buenas, me ayudas con el proceso y lo hacemos juntos?";
                        openChatModal(userId, userName, userImg, defaultMsg);
                    } else {
                        console.error('openChatModal no está disponible');
                        alert('El sistema de chat no está disponible. Por favor, recarga la página.');
                    }
                }, 500);
            }
        });
    }
});
</script>

<?php get_footer(); ?>
