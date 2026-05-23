<?php
    session_start();
    if(!isset($_SESSION["k_username"])) {
        header("Location: Index.php");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoHub - Gestor de Procesos del Sistema</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* ── Sección general ─────────────────────────────────── */
        .process-manager {
            min-height: 100vh;
            padding: 3rem 0 4rem;
            background: radial-gradient(circle at top, rgba(255,107,0,.1), transparent 28%),
                        linear-gradient(180deg, rgba(11,13,23,.95), #0b0c10 85%);
        }

        .process-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            background: rgba(31,40,51,.96);
            border-radius: 24px;
            border: 1px solid rgba(255,255,255,.08);
            box-shadow: 0 30px 70px rgba(0,0,0,.35);
        }

        .process-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .process-header h2 {
            color: white;
            font-size: 2.2rem;
            margin-bottom: .4rem;
        }
        .process-header p { color: var(--silver); font-size: 1rem; }

        /* ── Tabla de procesos CPU ───────────────────────────── */
        .algorithm-toggle {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin: 1.5rem auto;
            padding: .8rem 1.2rem;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 16px;
            width: fit-content;
        }
        .toggle-switch { position:relative; display:inline-block; width:60px; height:30px; }
        .toggle-switch input { opacity:0; width:0; height:0; }
        .toggle-slider {
            position:absolute; cursor:pointer;
            top:0; left:0; right:0; bottom:0;
            background:#4f5f74; transition:.4s;
            border-radius:30px;
        }
        .toggle-slider:before {
            position:absolute; content:"";
            height:22px; width:22px; left:4px; bottom:4px;
            background:white; transition:.4s; border-radius:50%;
        }
        input:checked + .toggle-slider { background: var(--primary-color); }
        input:checked + .toggle-slider:before { transform: translateX(30px); }
        .algorithm-label { font-weight:700; font-size:1rem; color:var(--accent-color); }

        .process-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
            border-radius: 18px;
            overflow: hidden;
            background: rgba(255,255,255,.04);
        }
        .process-table th,
        .process-table td {
            padding: .9rem 1.2rem;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,.07);
            color: var(--silver);
        }
        .process-table th { background:rgba(255,255,255,.06); color:var(--text-color); font-weight:700; }
        .process-table tr:hover { background:rgba(255,255,255,.05); }

        .status-listo      { color:#f4d35e; font-weight:700; }
        .status-ejecución,
        .status-running    { color:#4aa96c; font-weight:700; }
        .status-terminado  { color:#37b24d; font-weight:700; }

        /* ── Separador de secciones ──────────────────────────── */
        .section-divider {
            border: none;
            border-top: 1px solid rgba(255,255,255,.08);
            margin: 2.5rem 0;
        }
        .section-title-mem {
            color: white;
            font-size: 1.6rem;
            margin-bottom: .4rem;
            text-align: center;
        }
        .section-subtitle-mem {
            color: var(--silver);
            font-size: .9rem;
            text-align: center;
            margin-bottom: 2rem;
        }

        /* ── Grid de mapas de memoria ────────────────────────── */
        .memory-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        @media (max-width: 900px) { .memory-grid { grid-template-columns: 1fr; } }

        .memory-card {
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.07);
            border-radius: 16px;
            padding: 1.2rem;
        }
        .memory-card h4 {
            color: var(--accent-color);
            font-size: 1rem;
            margin-bottom: .3rem;
        }
        .memory-card .algo-desc {
            color: rgba(230,240,255,.6);
            font-size: .78rem;
            margin-bottom: .8rem;
        }
        .memory-stats {
            font-size: .8rem;
            color: rgba(230,240,255,.7);
            margin-bottom: .6rem;
        }

        /* ── Mapa visual de bloques ──────────────────────────── */
        .memory-map {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 3px;
        }
        .mem-block {
            aspect-ratio: 1;
            border-radius: 4px;
            border: 1px solid rgba(255,255,255,.06);
            position: relative;
            overflow: hidden;
            cursor: default;
            transition: transform .15s;
        }
        .mem-block:hover { transform: scale(1.15); z-index: 2; }
        .mem-block.free  { background: rgba(255,255,255,.06); }
        .mem-block.used  { }
        .mem-label {
            position: absolute;
            bottom: 1px; left: 1px; right: 1px;
            font-size: 5px;
            color: rgba(0,0,0,.7);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: none;
        }
        .mem-block:hover .mem-label { display: block; }

        /* ── Leyenda ─────────────────────────────────────────── */
        .memory-legend {
            display: flex;
            gap: 1.2rem;
            margin-bottom: 1.5rem;
            justify-content: center;
            font-size: .85rem;
            color: var(--silver);
        }
        .legend-item { display:flex; align-items:center; gap:.4rem; }
        .legend-box  { width:14px; height:14px; border-radius:3px; }

        /* ── Tabla comparativa de fragmentación ──────────────── */
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }
        .comparison-table th,
        .comparison-table td {
            padding: .8rem 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,.07);
            color: var(--silver);
            font-size: .9rem;
        }
        .comparison-table th { color: var(--text-color); font-weight: 700; }
        .comparison-table tr:hover { background: rgba(255,255,255,.03); }

        /* ── Historial de asignaciones ───────────────────────── */
        .history-table {
            width: 100%;
            border-collapse: collapse;
        }
        .history-table th,
        .history-table td {
            padding: .7rem 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,.06);
            color: var(--silver);
            font-size: .85rem;
        }
        .history-table th { color: var(--text-color); font-weight: 700; }

        /* ── Botón reset memoria ─────────────────────────────── */
        .btn-reset-mem {
            display: block;
            margin: 0 auto 1.5rem;
            padding: .6rem 1.8rem;
            background: rgba(255,255,255,.05);
            color: var(--silver);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 999px;
            cursor: pointer;
            font-size: .9rem;
            transition: background .2s;
        }
        .btn-reset-mem:hover { background: rgba(255,255,255,.1); color: white; }

        /* ── Back button ─────────────────────────────────────── */
        .back-btn {
            position: fixed; top:20px; left:20px;
            background: rgba(255,255,255,.06);
            color: var(--text-color);
            border: 1px solid rgba(255,255,255,.08);
            padding: 10px 18px; border-radius: 999px;
            text-decoration: none; z-index: 1000;
            transition: transform .2s, background .2s;
        }
        .back-btn:hover { transform: translateY(-2px); background: rgba(255,255,255,.1); }

    </style>
</head>
<body>

    <a href="IndexPrincipal.php" class="back-btn">← Volver al Inicio</a>

    <section class="process-manager">
        <div class="container">
            <div class="process-container">

                <!-- ══ SECCIÓN 1: PLANIFICADOR CPU ══════════════ -->
                <div class="process-header">
                    <h2>Gestor de Procesos del Sistema</h2>
                    <p>Simulación de Planificación de CPU + Gestión de Memoria</p>
                </div>

                <div class="algorithm-toggle">
                    <span class="algorithm-label">FCFS</span>
                    <label class="toggle-switch">
                        <input type="checkbox" id="algorithmToggle">
                        <span class="toggle-slider"></span>
                    </label>
                    <span class="algorithm-label">SJF</span>
                </div>

                <table class="process-table">
                    <thead>
                        <tr>
                            <th>ID Proceso</th>
                            <th>Tiempo de Ráfaga (s)</th>
                            <th>Estado</th>
                            <th>Algoritmo Activo</th>
                        </tr>
                    </thead>
                    <tbody id="processTableBody">
                        <tr>
                            <td colspan="4" style="text-align:center;color:#6c757d;">
                                No hay procesos en ejecución. Realiza una compra para ver la simulación.
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- ══ SECCIÓN 2: GESTOR DE MEMORIA ════════════ -->
                <hr class="section-divider">

                <h3 class="section-title-mem">Simulador de Gestión de Memoria</h3>
                <p class="section-subtitle-mem">
                    RAM simulada: 32 MB · Cada $10,000 de compra = 1 MB requerido · Los tres algoritmos en paralelo
                </p>

                <!-- Leyenda -->
                <div class="memory-legend">
                    <div class="legend-item">
                        <div class="legend-box" style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);"></div>
                        Libre
                    </div>
                    <div class="legend-item">
                        <div class="legend-box" style="background:#ff6b6b;"></div>
                        Proceso 1
                    </div>
                    <div class="legend-item">
                        <div class="legend-box" style="background:#4ecdc4;"></div>
                        Proceso 2
                    </div>
                    <div class="legend-item">
                        <div class="legend-box" style="background:#45b7d1;"></div>
                        Proceso 3…
                    </div>
                </div>

                <!-- Mapas de memoria: los 3 algoritmos lado a lado -->
                <div class="memory-grid">

                    <div class="memory-card">
                        <h4>First-Fit</h4>
                        <div class="algo-desc">Asigna el primer hueco donde cabe el proceso.</div>
                        <div class="memory-stats" id="stats-first">Usado: 0 MB / 32 MB (0%)</div>
                        <div class="memory-map" id="mapFirstFit"></div>
                    </div>

                    <div class="memory-card">
                        <h4>Best-Fit</h4>
                        <div class="algo-desc">Asigna el hueco más pequeño donde cabe el proceso.</div>
                        <div class="memory-stats" id="stats-best">Usado: 0 MB / 32 MB (0%)</div>
                        <div class="memory-map" id="mapBestFit"></div>
                    </div>

                    <div class="memory-card">
                        <h4>Worst-Fit</h4>
                        <div class="algo-desc">Asigna el hueco más grande disponible.</div>
                        <div class="memory-stats" id="stats-worst">Usado: 0 MB / 32 MB (0%)</div>
                        <div class="memory-map" id="mapWorstFit"></div>
                    </div>

                </div>

                <!-- Tabla comparativa de fragmentación -->
                <h4 style="color:white;margin-bottom:.8rem;">Fragmentación Externa por Algoritmo</h4>
                <table class="comparison-table">
                    <thead>
                        <tr>
                            <th>Algoritmo</th>
                            <th>Fragmentación Externa</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="fragmentationTable">
                        <tr><td colspan="3" style="text-align:center;color:#6c757d;">Sin datos aún</td></tr>
                    </tbody>
                </table>

                <!-- Historial de asignaciones -->
                <h4 style="color:white;margin-bottom:.8rem;">Historial de Asignaciones</h4>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Proceso</th>
                            <th>Memoria requerida</th>
                            <th>First-Fit (bloque)</th>
                            <th>Best-Fit (bloque)</th>
                            <th>Worst-Fit (bloque)</th>
                        </tr>
                    </thead>
                    <tbody id="allocationHistory">
                        <tr><td colspan="5" style="text-align:center;color:#6c757d;">Sin asignaciones aún</td></tr>
                    </tbody>
                </table>

                <button class="btn-reset-mem" onclick="processManager.memWorker.postMessage({action:'reset'})">
                    Limpiar memoria simulada
                </button>

            </div><!-- /process-container -->
        </div><!-- /container -->
    </section>

    <script src="process_manager.js"></script>
</body>
</html>