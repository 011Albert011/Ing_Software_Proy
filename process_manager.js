// Process Manager - Simulador de Planificación CPU + Gestión de Memoria
class ProcessManager {
    constructor() {
        console.log('Initializing ProcessManager...');
        this.currentProcess = null;
        this.processQueue = [];
        this.currentAlgorithm = 'FCFS';

        // Registro local de datos de pago: { processId -> { items, total } }
        // Esto es independiente del worker y no se pierde con cambios de algoritmo
        this._paymentRegistry = {};
        this._colorMap   = {};
        this._colorIndex = 0;
        this._palette    = [
            '#ff6b6b','#4ecdc4','#45b7d1','#96ceb4',
            '#ffeaa7','#dda0dd','#98d8c8','#f7dc6f',
            '#82e0aa','#f1948a'
        ];

        // ── Worker de CPU (ya existente) ──
        try {
            this.worker = new Worker('./cpu_scheduler.js');
            console.log('CPU Worker created successfully');
        } catch (error) {
            console.error('Error creating CPU worker:', error);
        }

        // ── Worker de Memoria (nuevo) ──
        try {
            this.memWorker = new Worker('./memory_manager.js');
            console.log('Memory Worker created successfully');
        } catch (error) {
            console.error('Error creating Memory worker:', error);
        }

        this.setupWorkerListeners();
        this.setupMemoryWorkerListeners();
        this.setupAlgorithmToggle();
        this.loadSavedState();
        this.checkPendingProcesses();
        this.updateUI();

        // Restaura el estado de memoria desde localStorage y lo manda al worker
        const savedMemory = localStorage.getItem('memoryManagerState');
        if (savedMemory) {
            // Reconstruye el colorMap desde el historial guardado ANTES de renderizar
            try {
                const parsed = JSON.parse(savedMemory);
                if (parsed.allocationHistory) {
                    parsed.allocationHistory.forEach(r => {
                        if (!this._colorMap[r.processId]) {
                            this._colorMap[r.processId] =
                                this._palette[this._colorIndex % this._palette.length];
                            this._colorIndex++;
                        }
                    });
                }
            } catch(e) {}
            this.memWorker.postMessage({ action: 'restoreMemoryState', data: savedMemory });
        } else {
            this.memWorker.postMessage({ action: 'getState' });
        }
    }

    static getPendingProcessesKey() { return 'pendingProcesses'; }
    static getSavedStateKey()       { return 'processQueueState'; }

    loadSavedState() {
        // Restaura el registro de pagos primero
        try {
            const savedRegistry = localStorage.getItem('paymentRegistry');
            if (savedRegistry) {
                this._paymentRegistry = JSON.parse(savedRegistry);
            }
        } catch(e) {}

        const savedState = JSON.parse(localStorage.getItem(ProcessManager.getSavedStateKey()) || 'null');
        if (!savedState) return false;
        const { currentProcess, processQueue, algorithm } = savedState;
        if (algorithm) this.currentAlgorithm = algorithm;
        if ((currentProcess && currentProcess.id) ||
            (Array.isArray(processQueue) && processQueue.length > 0)) {
            this.worker.postMessage({ action: 'restoreState', data: savedState });
            return true;
        }
        return false;
    }

    saveState() {
        const state = {
            algorithm: this.currentAlgorithm,
            currentProcess: this.currentProcess ? { ...this.currentProcess } : null,
            processQueue: this.processQueue.map(p => ({ ...p }))
        };
        localStorage.setItem(ProcessManager.getSavedStateKey(), JSON.stringify(state));
        // Persiste el registro de pagos por separado para no mezclar con el estado del CPU
        localStorage.setItem('paymentRegistry', JSON.stringify(this._paymentRegistry));
    }

    clearSavedState() {
        localStorage.removeItem(ProcessManager.getSavedStateKey());
        localStorage.removeItem('paymentRegistry');
        this._paymentRegistry = {};
    }
    clearPendingProcesses(){ localStorage.removeItem(ProcessManager.getPendingProcessesKey()); }

    checkPendingProcesses() {
        const pendingProcesses = JSON.parse(localStorage.getItem(ProcessManager.getPendingProcessesKey()) || '[]');
        if (pendingProcesses.length > 0) {
            pendingProcesses.forEach(pd => this.addProcess(pd));
            this.clearPendingProcesses();
        }
    }

    setupWorkerListeners() {
        this.worker.onmessage = (e) => {
            const { action, data } = e.data;
            switch (action) {
                case 'processAdded':     this.onProcessAdded(data);     break;
                case 'processUpdated':   this.onProcessUpdated(data);   break;
                case 'processCompleted': this.onProcessCompleted(data); break;
                case 'queueUpdated':     this.onQueueUpdated(data);     break;
                case 'algorithmChanged': this.onAlgorithmChanged(data); break;
                case 'allProcessesCompleted': this.onAllProcessesCompleted(); break;
            }
        };
    }

    setupAlgorithmToggle() {
        const toggle = document.getElementById('algorithmToggle');
        if (toggle) {
            toggle.addEventListener('change', (e) => {
                this.currentAlgorithm = e.target.checked ? 'SJF' : 'FCFS';
                this.worker.postMessage({ action: 'changeAlgorithm', algorithm: this.currentAlgorithm });
            });
        }
    }

    onAlgorithmChanged(data) { this.currentAlgorithm = data.algorithm; }

    addProcess(processData) {
        this.worker.postMessage({ action: 'addProcess', data: processData });
    }

    onProcessAdded(process) {
        this.updateUI();
        this.saveState();
        this.showNotification(`Proceso ${process.id} agregado a la cola`, 'success');

        // Guarda items y total en el registro local usando el id como clave.
        // Así aunque el worker pierda estos datos, el manager siempre los tiene.
        if (process.items && process.total) {
            this._paymentRegistry[process.id] = {
                items: process.items,
                total: process.total
            };
        }

        // ── asignar memoria al proceso ──
        const memSize = this._calcMemorySize(process.total || 0);
        this.memWorker.postMessage({
            action: 'allocate',
            processId: process.id,
            memorySize: memSize
        });
    }

    onProcessUpdated(process) {
        this.currentProcess = process;
        this.saveState();
        this.updateUI();
    }

    onProcessCompleted(process) {
        this.currentProcess = null;
        this.updateUI();
        this.showNotification(`Proceso ${process.id} completado`, 'success');

        // Liberar memoria
        this.memWorker.postMessage({ action: 'free', processId: process.id });

        // Recupera items y total del registro local (fuente de verdad independiente del worker)
        const paymentData = this._paymentRegistry[process.id];
        if (paymentData) {
            // Enriquece el proceso con los datos de pago antes de procesarlo
            process.items = paymentData.items;
            process.total = paymentData.total;
            // Limpia el registro una vez usado
            delete this._paymentRegistry[process.id];
        }

        this.handleProcessCompletion(process);
    }

    onQueueUpdated(queue) {
        this.processQueue = queue;
        this.saveState();
        this.updateUI();
    }

    onAllProcessesCompleted() {
        this.currentProcess = null;
        this.processQueue   = [];
        this.clearSavedState();
        this.updateUI();
        this.showNotification('Todos los procesos completados', 'success');
    }

    // ── Listeners del Worker de Memoria (nuevo) ─────────────────

    setupMemoryWorkerListeners() {
        this.memWorker.onmessage = (e) => {
            const { action, data } = e.data;
            switch (action) {
                case 'allocated':
                    // Asigna el color exactamente una vez cuando el proceso entra a memoria
                    if (!this._colorMap[data.record.processId]) {
                        this._colorMap[data.record.processId] =
                            this._palette[this._colorIndex % this._palette.length];
                        this._colorIndex++;
                    }
                    this.updateMemoryUI(data);
                    break;
                case 'freed':
                case 'state':
                    this.updateMemoryUI(data);
                    break;
                // el worker no puede escribir en localStorage; lo hace el hilo principal
                case 'saveMemoryState':
                    localStorage.setItem('memoryManagerState', data);
                    break;
            }
        };
    }

    //calculo de memoria segun total de compra
    // Cada $10000 = 1 MB 
    _calcMemorySize(total) {
        const mb = Math.ceil(total / 10000);
        return Math.min(Math.max(mb, 1), 32);
    }


    updateMemoryUI(data) {
        this._renderMemoryMap('mapFirstFit',  data.memoryFirstFit,  'first');
        this._renderMemoryMap('mapBestFit',   data.memoryBestFit,   'best');
        this._renderMemoryMap('mapWorstFit',  data.memoryWorstFit,  'worst');
        this._renderFragmentation(data.fragmentation);
        if (data.history) this._renderHistory(data.history);
    }

    _renderMemoryMap(containerId, memoryArray, type) {
        const container = document.getElementById(containerId);
        if (!container) return;

        // El color ya fue asignado en setupMemoryWorkerListeners al recibir 'allocated'.
        // Aquí solo lo consultamos — nunca se incrementa el índice en el render.
        const getColor = (pid) => this._colorMap[pid] || '#cccccc';

        container.innerHTML = memoryArray.map((block, i) => {
            if (block === null) {
                return `<div class="mem-block free" title="Bloque ${i}: Libre"></div>`;
            }
            const color   = getColor(block);
            const shortId = block.length > 14 ? block.slice(-8) : block;
            return `<div class="mem-block used"
                        style="background:${color};"
                        title="Bloque ${i}: ${block}">
                        <span class="mem-label">${shortId}</span>
                    </div>`;
        }).join('');

        const used  = memoryArray.filter(b => b !== null).length;
        const total = memoryArray.length;
        const pct   = Math.round((used / total) * 100);

        const statsEl = document.getElementById(`stats-${type}`);
        if (statsEl) statsEl.textContent = `Usado: ${used} MB / ${total} MB (${pct}%)`;
    }

    _renderFragmentation(fragData) {
        const el = document.getElementById('fragmentationTable');
        if (!el || !fragData) return;
        el.innerHTML = `
            <tr>
                <td>First-Fit</td>
                <td>${fragData.firstFit} MB</td>
                <td>${fragData.firstFit === 0 ? 'Sin fragmentación' : 'Fragmentado'}</td>
            </tr>
            <tr>
                <td>Best-Fit</td>
                <td>${fragData.bestFit} MB</td>
                <td>${fragData.bestFit === 0 ? 'Sin fragmentación' : 'Fragmentado'}</td>
            </tr>
            <tr>
                <td>Worst-Fit</td>
                <td>${fragData.worstFit} MB</td>
                <td>${fragData.worstFit === 0 ? 'Sin fragmentación' : 'Fragmentado'}</td>
            </tr>
        `;
    }

    _renderHistory(history) {
        const el = document.getElementById('allocationHistory');
        if (!el) return;
        if (history.length === 0) {
            el.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#6c757d;">Sin asignaciones aún</td></tr>';
            return;
        }
        el.innerHTML = history.map(r => `
            <tr>
                <td>${r.processId.slice(-10)}</td>
                <td>${r.memorySize} MB</td>
                <td>${r.firstFit  ? `Bloque ${r.firstFit.start}`  : '<span style="color:#ff6b6b">Sin espacio</span>'}</td>
                <td>${r.bestFit   ? `Bloque ${r.bestFit.start}`   : '<span style="color:#ff6b6b">Sin espacio</span>'}</td>
                <td>${r.worstFit  ? `Bloque ${r.worstFit.start}`  : '<span style="color:#ff6b6b">Sin espacio</span>'}</td>
            </tr>
        `).join('');
    }

    // ── UI CPU (sin cambios respecto al original) ────────────────

    updateUI() {
        this.updateProcessTable();
    }

    updateProcessTable() {
        const tbody = document.getElementById('processTableBody');
        if (!tbody) return;

        if (!this.currentProcess && this.processQueue.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;color:#6c757d;">
                No hay procesos en ejecución. Realiza una compra para ver la simulación.</td></tr>`;
            return;
        }

        let html = '';
        if (this.currentProcess) {
            html += `<tr>
                <td>${this.currentProcess.id}</td>
                <td>${this.currentProcess.remainingTime}s</td>
                <td class="status-${this.currentProcess.status.toLowerCase()}">${this.currentProcess.status}</td>
                <td>${this.currentAlgorithm}</td>
            </tr>`;
        }
        this.processQueue.forEach(p => {
            html += `<tr>
                <td>${p.id}</td>
                <td>${p.burstTime}s</td>
                <td class="status-listo">LISTO</td>
                <td>${this.currentAlgorithm}</td>
            </tr>`;
        });
        tbody.innerHTML = html;
    }

    handleProcessCompletion(process) {
        const xhr  = new XMLHttpRequest();
        const url  = window.location.origin +
        window.location.pathname.replace('process_manager.php', 'Procesar_pago.php');
        xhr.open('POST', url, true);
        xhr.withCredentials = true;
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = () => {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        if (data.success) {
                            if (data.ruta) {
                                window.open('Imprimir.php?archivo=' + encodeURIComponent(data.ruta), '_blank');
                            }
                        } else {
                            this.showNotification('Error al procesar el pago: ' + data.message, 'error');
                        }
                    } catch (e) {
                        this.showNotification('Error al procesar la respuesta del servidor', 'error');
                    }
                } else {
                    this.showNotification('Error de conexión (HTTP ' + xhr.status + ')', 'error');
                }
            }
        };
        xhr.send(JSON.stringify({ items: process.items, total: process.total, processId: process.id }));
    }

    showNotification(message, type = 'info') {
        const n = document.createElement('div');
        n.style.cssText = `
            position:fixed; top:20px; right:20px;
            background:${type === 'success' ? '#28a745' : '#dc3545'};
            color:white; padding:1rem 2rem; border-radius:8px;
            box-shadow:0 5px 20px rgba(0,0,0,.2); z-index:9999;
        `;
        n.textContent = message;
        document.body.appendChild(n);
        setTimeout(() => n.remove(), 3000);
    }
}

const processManager = new ProcessManager();
window.processManager = processManager;