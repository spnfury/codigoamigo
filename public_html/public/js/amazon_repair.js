/**
 * Amazon Link Auto-Repair Component
 * Se encarga de buscar enlaces de ganga.ad/chollo.biz y expandirlos usando el navegador del admin
 */

const AmazonRepair = {
    batchSize: 5,
    isRunning: false,
    isSilent: true, // Modo silencioso por defecto
    stats: { total: 0, fixed: 0, failed: 0 },

    init: function () {
        console.log("Amazon Repair Inicializado (Silent Mode)");
        this.renderUI();
        // Auto-iniciar después de 5 segundos
        setTimeout(() => this.start(true), 5000);
    },

    renderUI: function () {
        const container = document.getElementById('amazon-repair-container');
        if (!container) return;

        container.innerHTML = `
            <div id="amazon-repair-card" class="card mt-4" style="border-left: 5px solid #ff9900; opacity: 0.8; transition: opacity 0.3s; margin-bottom: 50px;">
                <div class="card-body">
                    <h6 class="card-title text-warning"><i class="fab fa-amazon"></i> Mantenimiento Automático de Amazon <span id="repair-active-tag" class="badge bg-success" style="display:none; font-size: 10px;">Activo</span></h6>
                    <p class="card-text text-muted" style="font-size: 11px;">El sistema está resolviendo enlaces sistemáticamente mientras navegas.</p>
                    
                    <div id="repair-stats" class="mb-2">
                        <span class="badge bg-light text-dark" style="border:1px solid #ddd;">Pendientes: <span id="stat-count">...</span></span>
                        <span class="badge bg-light text-success" style="border:1px solid #ddd;">Reparados: <span id="stat-fixed">0</span></span>
                    </div>

                    <div id="repair-log" class="small text-muted" style="max-height: 80px; overflow-y: auto; font-size: 10px; border: 1px solid #f9f9f9; padding: 2px; background: #fafafa; display:none;"></div>

                    <div class="mt-2 text-end">
                        <button id="btn-toggle-log" class="btn btn-link btn-sm p-0" style="font-size: 10px;">Ver registro</button>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('btn-toggle-log').onclick = (e) => {
            e.preventDefault();
            const log = document.getElementById('repair-log');
            log.style.display = log.style.display === 'none' ? 'block' : 'none';
        };
    },

    start: async function (silent = false) {
        if (this.isRunning) return;
        this.isRunning = true;
        this.isSilent = silent;

        const activeTag = document.getElementById('repair-active-tag');
        if (activeTag) activeTag.style.display = 'inline-block';

        this.log("Iniciando escaneo de mantenimiento...");
        this.processBatch();
    },

    log: function (msg) {
        if (!this.isSilent) console.log(`[AmazonRepair] ${msg}`);
        const log = document.getElementById('repair-log');
        if (log) {
            const entry = document.createElement('div');
            entry.textContent = `[${new Date().toLocaleTimeString()}] ${msg}`;
            log.prepend(entry);
            if (log.childNodes.length > 20) log.removeChild(log.lastChild);
        }
    },

    updateStats: function () {
        const fixed = document.getElementById('stat-fixed');
        if (fixed) fixed.textContent = this.stats.fixed;
    },

    processBatch: async function () {
        if (!this.isRunning) return;

        try {
            const resp = await fetch(`/public/api_amazon_maintenance.php?action=get_broken&limit=${this.batchSize}`);
            const data = await resp.json();

            const statCount = document.getElementById('stat-count');
            if (statCount) statCount.textContent = data.count || 0;

            if (!data.success || data.count === 0) {
                const activeTag = document.getElementById('repair-active-tag');
                if (activeTag) activeTag.style.display = 'none';
                this.isRunning = false;
                // Volver a comprobar en 2 minutos en lugar de 5
                setTimeout(() => this.start(true), 120000);
                return;
            }

            for (const link of data.links) {
                if (!this.isRunning) break;
                await this.repairLink(link);
            }

            // Lote cada 10 segundos en modo silencioso
            setTimeout(() => this.processBatch(), 10000);

        } catch (e) {
            this.log("Error: " + e.message);
            this.isRunning = false;
        }
    },

    repairLink: async function (link) {
        try {
            let expandedUrl = await this.resolveUrl(link.enlace);

            const updateResp = await fetch('/public/api_amazon_maintenance.php?action=update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: link.id,
                    enlace_expandido: expandedUrl || ''
                })
            });

            const updateResult = await updateResp.json();
            if (updateResult.success) {
                this.stats.fixed++;
                this.log(`✓ Reparado: ${link.titulo.substring(0, 20)}...`);
            }
        } catch (e) {
            this.log(`! Error en link: ${link.id}`);
        }
        this.updateStats();
    },

    resolveUrl: async function (url) {
        try {
            const resp = await fetch(`https://url-expander.vercel.app/api?url=${encodeURIComponent(url)}`);
            const data = await resp.json();
            if (data.resolvedUrl && data.resolvedUrl.includes('amazon.')) return data.resolvedUrl;
        } catch (e) { }

        return null;
    }
};
window.addEventListener('load', () => AmazonRepair.init());
