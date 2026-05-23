// Memory Manager Worker - Simula gestión de memoria con First-Fit, Best-Fit, Worst-Fit
class MemoryManager {
    constructor() {
        this.TOTAL_BLOCKS = 32;
        this.BLOCK_SIZE_MB = 1;
        // Estado inicial vacío; se restaura desde el hilo principal vía 'restoreMemoryState'
        this.memoryFirstFit    = new Array(this.TOTAL_BLOCKS).fill(null);
        this.memoryBestFit     = new Array(this.TOTAL_BLOCKS).fill(null);
        this.memoryWorstFit    = new Array(this.TOTAL_BLOCKS).fill(null);
        this.allocationHistory = [];
    }

    onMessage(data) {
        switch (data.action) {
            case 'allocate':
                this.allocateMemory(data.processId, data.memorySize);
                break;
            case 'free':
                this.freeMemory(data.processId);
                break;
            case 'getState':
                this.sendState();
                break;
            case 'reset':
                this.resetMemory();
                break;
            // Restaura el estado guardado en localStorage (enviado desde el hilo principal)
            case 'restoreMemoryState':
                this.restoreState(data.data);
                break;
        }
    }

    // El worker no puede acceder a localStorage directamente.
    // Cuando necesita guardar, manda 'saveMemoryState' al hilo principal
    // y él lo escribe en localStorage.
    _persistState() {
        self.postMessage({
            action: 'saveMemoryState',
            data: JSON.stringify({
                memoryFirstFit:    this.memoryFirstFit,
                memoryBestFit:     this.memoryBestFit,
                memoryWorstFit:    this.memoryWorstFit,
                allocationHistory: this.allocationHistory
            })
        });
    }

    restoreState(savedData) {
        if (!savedData) { this.sendState(); return; }
        try {
            const p = typeof savedData === 'string' ? JSON.parse(savedData) : savedData;
            this.memoryFirstFit    = p.memoryFirstFit    || new Array(this.TOTAL_BLOCKS).fill(null);
            this.memoryBestFit     = p.memoryBestFit     || new Array(this.TOTAL_BLOCKS).fill(null);
            this.memoryWorstFit    = p.memoryWorstFit    || new Array(this.TOTAL_BLOCKS).fill(null);
            this.allocationHistory = p.allocationHistory || [];
        } catch(e) {
            this.memoryFirstFit    = new Array(this.TOTAL_BLOCKS).fill(null);
            this.memoryBestFit     = new Array(this.TOTAL_BLOCKS).fill(null);
            this.memoryWorstFit    = new Array(this.TOTAL_BLOCKS).fill(null);
            this.allocationHistory = [];
        }
        this.sendState();
    }

    allocateMemory(processId, memorySize) {
        const firstFitStart  = this._firstFit(this.memoryFirstFit, memorySize);
        const bestFitStart   = this._bestFit(this.memoryBestFit, memorySize);
        const worstFitStart  = this._worstFit(this.memoryWorstFit, memorySize);

        // Aplicar asignaciones
        if (firstFitStart !== -1) {
            this._assign(this.memoryFirstFit, firstFitStart, memorySize, processId);
        }
        if (bestFitStart !== -1) {
            this._assign(this.memoryBestFit, bestFitStart, memorySize, processId);
        }
        if (worstFitStart !== -1) {
            this._assign(this.memoryWorstFit, worstFitStart, memorySize, processId);
        }

        const record = {
            processId,
            memorySize,
            firstFit:  firstFitStart  !== -1 ? { start: firstFitStart,  size: memorySize } : null,
            bestFit:   bestFitStart   !== -1 ? { start: bestFitStart,   size: memorySize } : null,
            worstFit:  worstFitStart  !== -1 ? { start: worstFitStart,  size: memorySize } : null,
            timestamp: Date.now()
        };

        this.allocationHistory.push(record);

        self.postMessage({
            action: 'allocated',
            data: {
                record,
                memoryFirstFit:  [...this.memoryFirstFit],
                memoryBestFit:   [...this.memoryBestFit],
                memoryWorstFit:  [...this.memoryWorstFit],
                fragmentation:   this._calcFragmentation(),
                history:         this.allocationHistory
            }
        });
        this._persistState(); // guarda en localStorage via hilo principal
    }

    freeMemory(processId) {
        this._free(this.memoryFirstFit, processId);
        this._free(this.memoryBestFit,  processId);
        this._free(this.memoryWorstFit, processId);

        self.postMessage({
            action: 'freed',
            data: {
                processId,
                memoryFirstFit:  [...this.memoryFirstFit],
                memoryBestFit:   [...this.memoryBestFit],
                memoryWorstFit:  [...this.memoryWorstFit],
                fragmentation:   this._calcFragmentation()
            }
        });
        this._persistState(); // guarda en localStorage via hilo principal
    }

    // ── Algoritmos ──────────────────────────────────────────────

    // First-Fit: primer hueco donde cabe el proceso
    _firstFit(memory, size) {
        let count = 0, start = -1;
        for (let i = 0; i < this.TOTAL_BLOCKS; i++) {
            if (memory[i] === null) {
                if (count === 0) start = i;
                count++;
                if (count === size) return start;
            } else {
                count = 0; start = -1;
            }
        }
        return -1;
    }

    // Best-Fit: hueco libre más pequeño donde cabe el proceso
    _bestFit(memory, size) {
        const holes = this._getHoles(memory);
        const valid = holes.filter(h => h.size >= size);
        if (valid.length === 0) return -1;
        valid.sort((a, b) => a.size - b.size);
        return valid[0].start;
    }

    // Worst-Fit: hueco libre más grande
    _worstFit(memory, size) {
        const holes = this._getHoles(memory);
        const valid = holes.filter(h => h.size >= size);
        if (valid.length === 0) return -1;
        valid.sort((a, b) => b.size - a.size);
        return valid[0].start;
    }

    // Devuelve lista de huecos libres contiguos
    _getHoles(memory) {
        const holes = [];
        let count = 0, start = -1;
        for (let i = 0; i <= this.TOTAL_BLOCKS; i++) {
            if (i < this.TOTAL_BLOCKS && memory[i] === null) {
                if (count === 0) start = i;
                count++;
            } else {
                if (count > 0) holes.push({ start, size: count });
                count = 0; start = -1;
            }
        }
        return holes;
    }

    _assign(memory, start, size, processId) {
        for (let i = start; i < start + size; i++) {
            memory[i] = processId;
        }
    }

    _free(memory, processId) {
        for (let i = 0; i < this.TOTAL_BLOCKS; i++) {
            if (memory[i] === processId) memory[i] = null;
        }
    }

    // Calcula fragmentación externa: huecos libres no contiguos
    _calcFragmentation() {
        const calc = (memory) => {
            const holes = this._getHoles(memory);
            const freeBlocks = memory.filter(b => b === null).length;
            const largestHole = holes.length > 0 ? Math.max(...holes.map(h => h.size)) : 0;
            // Fragmentación = bloques libres que NO están en el hueco más grande
            return freeBlocks - largestHole;
        };
        return {
            firstFit:  calc(this.memoryFirstFit),
            bestFit:   calc(this.memoryBestFit),
            worstFit:  calc(this.memoryWorstFit)
        };
    }

    sendState() {
        self.postMessage({
            action: 'state',
            data: {
                memoryFirstFit:  [...this.memoryFirstFit],
                memoryBestFit:   [...this.memoryBestFit],
                memoryWorstFit:  [...this.memoryWorstFit],
                fragmentation:   this._calcFragmentation(),
                history:         this.allocationHistory
            }
        });
    }

    resetMemory() {
        this.memoryFirstFit    = new Array(this.TOTAL_BLOCKS).fill(null);
        this.memoryBestFit     = new Array(this.TOTAL_BLOCKS).fill(null);
        this.memoryWorstFit    = new Array(this.TOTAL_BLOCKS).fill(null);
        this.allocationHistory = [];
        this._persistState(); // borra el estado guardado
        this.sendState();
    }
}

const memManager = new MemoryManager();
self.onmessage = (e) => memManager.onMessage(e.data);