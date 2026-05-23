// CPU Scheduler Worker - Simula planificación de procesos
class CPUScheduler {
    constructor() {
        this.processQueue = [];
        this.currentProcess = null;
        this.algorithm = 'FCFS'; // FCFS or SJF
        this.intervalId = null;
        this.totalBurstTime = 0;
        this.elapsedTime = 0;
    }

    onMessage(data) {
        const { action } = data;
        switch (action) {
            case 'addProcess':
                this.addProcess(data.data);
                break;
            case 'restoreState':
                this.restoreState(data.data);
                break;
            case 'changeAlgorithm':
                this.changeAlgorithm(data.algorithm);
                break;
            case 'getStatus':
                this.sendStatus();
                break;
        }
    }

    addProcess(processData) {
        const burstTime = processData.originalBurstTime || processData.burstTime || Math.floor(Math.random() * 11) + 5;
        const remainingTime = processData.remainingTime != null ? processData.remainingTime : burstTime;

        const process = {
            id: processData.id || `P${Date.now()}`,
            burstTime: burstTime,
            remainingTime: remainingTime,
            originalBurstTime: burstTime,
            status: processData.status || 'LISTO',
            items: processData.items,
            total: processData.total,
            arrivalTime: processData.arrivalTime || Date.now()
        };

        this.processQueue.push(process);
        this.sortQueue();

        self.postMessage({
            action: 'processAdded',
            data: process
        });

        this.sendQueueUpdate();

        if (!this.currentProcess) {
            this.startNextProcess();
        }
    }

    changeAlgorithm(newAlgorithm) {
        this.algorithm = newAlgorithm;
        this.sortQueue();
        self.postMessage({
            action: 'algorithmChanged',
            data: { algorithm: this.algorithm }
        });
    }

    sortQueue() {
        if (this.algorithm === 'SJF') {
            this.processQueue.sort((a, b) => a.originalBurstTime - b.originalBurstTime);
        } else {
            this.processQueue.sort((a, b) => a.arrivalTime - b.arrivalTime);
        }
    }

    startNextProcess() {
        if (this.processQueue.length === 0) {
            this.currentProcess = null;
            // Gantt removed, no progress update
            self.postMessage({ action: 'allProcessesCompleted' });
            return;
        }

        this.currentProcess = this.processQueue.shift();
        this.currentProcess.status = 'EJECUCIÓN';

        self.postMessage({
            action: 'processUpdated',
            data: this.currentProcess
        });

        this.sendQueueUpdate();
        this.totalBurstTime = this.currentProcess.originalBurstTime;
        this.elapsedTime = this.totalBurstTime - this.currentProcess.remainingTime;
        this.startTimer();
    }

    startTimer() {
        if (this.intervalId) clearInterval(this.intervalId);
        
        this.intervalId = setInterval(() => {
            if (!this.currentProcess) {
                this.stopTimer();
                return;
            }

            this.currentProcess.remainingTime--;
            this.elapsedTime++;
            
            const progress = (this.elapsedTime / this.totalBurstTime) * 100;
            // Gantt removed, no update needed

            self.postMessage({
                action: 'processUpdated',
                data: { ...this.currentProcess }
            });

            if (this.currentProcess.remainingTime <= 0) {
                this.completeCurrentProcess();
            }
        }, 1000);
    }

    completeCurrentProcess() {
        this.stopTimer();
        this.currentProcess.status = 'TERMINADO';
        self.postMessage({
            action: 'processCompleted',
            data: this.currentProcess
        });
        this.currentProcess = null;
        this.startNextProcess();
    }

    stopTimer() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }

    sendQueueUpdate() {
        self.postMessage({
            action: 'queueUpdated',
            data: this.processQueue.map(p => ({
                id: p.id,
                burstTime: p.originalBurstTime,
                remainingTime: p.remainingTime,
                status: p.status,
                items: p.items,       // necesario para procesar el pago al terminar
                total: p.total,       // necesario para calcular memoria y pago
                arrivalTime: p.arrivalTime
            }))
        });
    }

    restoreState(savedState) {
        const { currentProcess, processQueue, algorithm } = savedState;
        if (algorithm) {
            this.algorithm = algorithm;
        }

        if (Array.isArray(processQueue)) {
            this.processQueue = processQueue.map(p => ({
                id: p.id || `P${Date.now()}`,
                burstTime: p.originalBurstTime || p.burstTime || Math.floor(Math.random() * 11) + 5,
                remainingTime: p.remainingTime != null ? p.remainingTime : (p.originalBurstTime || p.burstTime || Math.floor(Math.random() * 11) + 5),
                originalBurstTime: p.originalBurstTime || p.burstTime || Math.floor(Math.random() * 11) + 5,
                status: p.status || 'LISTO',
                items: p.items,
                total: p.total,
                arrivalTime: p.arrivalTime || Date.now()
            }));
            this.sortQueue();
        }

        if (currentProcess && currentProcess.id) {
            this.currentProcess = {
                id: currentProcess.id,
                burstTime: currentProcess.originalBurstTime || currentProcess.burstTime || Math.floor(Math.random() * 11) + 5,
                remainingTime: currentProcess.remainingTime != null ? currentProcess.remainingTime : (currentProcess.originalBurstTime || currentProcess.burstTime || Math.floor(Math.random() * 11) + 5),
                originalBurstTime: currentProcess.originalBurstTime || currentProcess.burstTime || Math.floor(Math.random() * 11) + 5,
                status: 'EJECUCIÓN',
                items: currentProcess.items,
                total: currentProcess.total,
                arrivalTime: currentProcess.arrivalTime || Date.now()
            };

            self.postMessage({ action: 'processUpdated', data: this.currentProcess });
            this.sendQueueUpdate();
            this.totalBurstTime = this.currentProcess.originalBurstTime;
            this.elapsedTime = this.totalBurstTime - this.currentProcess.remainingTime;
            this.startTimer();
            return;
        }

        if (this.processQueue.length > 0) {
            this.sortQueue();
            this.startNextProcess();
        } else {
            self.postMessage({ action: 'allProcessesCompleted' });
        }
    }

    sendStatus() {
        self.postMessage({
            action: 'status',
            data: {
                currentProcess: this.currentProcess,
                queue: this.processQueue,
                algorithm: this.algorithm
            }
        });
    }
}

// Inicialización correcta del Worker
const scheduler = new CPUScheduler();

self.onmessage = function(e) {
    scheduler.onMessage(e.data);
};