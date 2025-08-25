<?php
/************************************************************
 * Dashboard de Comparativas
 * - Vista con filtros (Entidad, Bus, Rango de fechas)
 * - KPIs, Barras (por Entidad), Pie global, Tabla comparativa
 * Requiere: server/config.php con $pdo (PDO MySQL)
 ************************************************************/
require_once '../../../server/config.php';

// --- Cargar catálogos para los filtros ---
$entidades = $pdo->query("SELECT ID, descripcion FROM entidad WHERE activo = 1 ORDER BY descripcion")->fetchAll(PDO::FETCH_ASSOC);
$buses     = $pdo->query("SELECT ID, descripcion FROM bus     WHERE activo = 1 ORDER BY descripcion")->fetchAll(PDO::FETCH_ASSOC);

// Helper XSS
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }




?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Dashboard de Comparativas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 (CDN) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Chart.js (CDN) -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

  <style>
    :root{
      --card-radius: 1rem;
      --shadow-soft: 0 6px 20px rgba(0,0,0,.08);
    }
    .page-wrap{ padding: 1.25rem; }
    .kpi-card{
      border: 0; border-radius: var(--card-radius);
      box-shadow: var(--shadow-soft);
    }
    .kpi-title{ font-size: .9rem; color: #6c757d; margin-bottom: .25rem; }
    .kpi-value{ font-weight: 700; font-size: 1.4rem; }
    .toolbar{
      border-radius: var(--card-radius);
      box-shadow: var(--shadow-soft);
      padding: .75rem;
      background: #fff;
    }
    .chart-card, .table-card{
      border: 0; border-radius: var(--card-radius);
      box-shadow: var(--shadow-soft);
    }
    .chip-color{
      display:inline-block;width:24px;height:16px;border-radius:4px;vertical-align:middle;margin-right:.35rem;border:1px solid #e5e5e5;
    }
    .table thead th{ position: sticky; top: 0; background: #111827; color:#fff; z-index:1; }
    .table-responsive{ max-height: 58vh; }
    .form-select[multiple]{ height: 140px; }

    #chartBarras { width: 100%; height: 360px; display: block; }
  </style>
</head>
<body class="bg-light">
<div class="page-wrap container-fluid">

  <div class="mb-3">
    <h1 class="h4 mb-1">📊 Dashboard de Comparativas</h1>
    <div class="text-muted">Avance por Entidad / Estatus y resumen global</div>
  </div>

  <!-- Filtros -->
  <div class="toolbar mb-3">
    <form id="formFiltros" class="row g-3 align-items-end">
      <div class="col-12 col-md-5">
        <label class="form-label">Entidad(es)</label>
        <select id="entidades" name="entidades[]" multiple class="form-select">
          <?php foreach($entidades as $e): ?>
            <option value="<?= (int)$e['ID'] ?>"><?= h($e['descripcion']) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="form-text">Selecciona una o varias. Vacío = todas.</div>
      </div>

      <div class="col-12 col-md-3">
        <label class="form-label">Bus</label>
        <select id="bus_id" name="bus_id" class="form-select">
          <option value="">Todos</option>
          <?php foreach($buses as $b): ?>
            <option value="<?= (int)$b['ID'] ?>"><?= h($b['descripcion']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-6 col-md-2">
        <label class="form-label">Desde</label>
        <input type="date" id="desde" name="desde" class="form-control">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label">Hasta</label>
        <input type="date" id="hasta" name="hasta" class="form-control">
      </div>

      <div class="col-12 d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <span class="me-1">🔎</span>Aplicar
        </button>
        <button type="button" id="btnLimpiar" class="btn btn-outline-secondary">Limpiar</button>
        <button type="button" id="btnCSV" class="btn btn-outline-success">Exportar CSV</button>
        <button type="button" id="btnImprimir" class="btn btn-outline-dark">Imprimir</button>
      </div>
    </form>
  </div>

  <!-- KPIs -->
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
      <div class="card kpi-card p-3">
        <div class="kpi-title">Concluidos</div>
        <div class="kpi-value" id="kpiConcluidos">—</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card kpi-card p-3">
        <div class="kpi-title">En pruebas</div>
        <div class="kpi-value" id="kpiPruebas">—</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card kpi-card p-3">
        <div class="kpi-title">Sin ejecutar</div>
        <div class="kpi-value" id="kpiSinEjecutar">—</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card kpi-card p-3">
        <div class="kpi-title">Avance promedio</div>
        <div class="kpi-value" id="kpiAvanceProm">—</div>
      </div>
    </div>
  </div>

  <!-- Gráficas -->
 <div class="card chart-card p-3">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <h2 class="h6 m-0">Avance por entidad (barras apiladas)</h2>
    <div class="small text-muted">
      <span class="chip-color" style="background:#22c55e"></span>Concluido
      <span class="chip-color ms-2" style="background:#f59e0b"></span>Pruebas
      <span class="chip-color ms-2" style="background:#9ca3af"></span>Sin ejecutar
    </div>
  </div>
  <!-- Contenedor con altura fija: evita el “crecimiento infinito” por reflow -->
  <div id="chartBarrasWrap" style="position:relative; height:380px; width:100%;">
    <canvas id="chartBarras"></canvas>
  </div>
</div>


  <!-- Tabla -->
  <div class="card table-card">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h2 class="h6 m-0">Comparativa por entidad</h2>
      <span class="text-muted small">Click en encabezados para ordenar (lado cliente)</span>
    </div>
    <div class="table-responsive">
      <table id="tablaComp" class="table table-hover table-sm align-middle text-center mb-0">
        <thead>
          <tr>
            <th data-sort="text">Entidad</th>
            <th data-sort="num">#Registros</th>
            <th data-sort="num">Concluido</th>
            <th data-sort="num">Pruebas</th>
            <th data-sort="num">Sin ejecutar</th>
            <th data-sort="num">% Avance</th>
          </tr>
        </thead>
        <tbody id="tbodyComp"></tbody>
      </table>
    </div>
  </div>

  <!-- Loader ligero -->
  <div id="loader" class="text-center py-4 d-none">
    <div class="spinner-border text-primary" role="status"></div>
    <div class="mt-2 text-muted small">Cargando datos...</div>
  </div>
</div>

<script>

    // Evita registrar eventos/intervalos más de una vez
if (window.__DASH_INIT__) {
  console.warn('Dashboard ya inicializado; no se vuelve a registrar.');
} else {
  window.__DASH_INIT__ = true;
  // --- Estado de página ---
  let chartBarras, chartPie;
  const $loader = document.getElementById('loader');
  const $form   = document.getElementById('formFiltros');

  // --- Helpers ---
  function toggleLoader(show){ $loader.classList.toggle('d-none', !show); }

  function paramsFromForm(){
    const fd = new FormData($form);
    const entidades = Array.from(document.getElementById('entidades').selectedOptions).map(o => o.value);
    const bus_id    = fd.get('bus_id') || '';
    const desde     = fd.get('desde')  || '';
    const hasta     = fd.get('hasta')  || '';

    const p = new URLSearchParams();
    if (entidades.length) p.set('entidades', entidades.join(','));
    if (bus_id)           p.set('bus_id', bus_id);
    if (desde)            p.set('desde', desde);
    if (hasta)            p.set('hasta', hasta);
    return p.toString();
  }

  function fmtPct(n){
    if (n === null || n === undefined || isNaN(n)) return '—';
    return (Math.round(n * 10) / 10).toFixed(1) + '%';
  }

  function descargarCSV(nombre, filas){
    const csv = filas.map(r => r.map(v => `"${String(v).replace(/"/g,'""')}"`).join(',')).join('\n');
    const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
    const url  = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = nombre;
    document.body.appendChild(a); a.click();
    document.body.removeChild(a); URL.revokeObjectURL(url);
  }

  // --- Render KPIs ---
  function renderKPIs(kpi){
    document.getElementById('kpiConcluidos').textContent   = kpi.concluidos ?? '—';
    document.getElementById('kpiPruebas').textContent      = kpi.pruebas ?? '—';
    document.getElementById('kpiSinEjecutar').textContent  = kpi.sin_ejecutar ?? '—';
    const avg = kpi.avance_promedio_global ?? null;
    document.getElementById('kpiAvanceProm').textContent   = (avg==null)?'—':fmtPct(avg);
  }

  // --- Render Tabla ---
  function renderTabla(items){
    const tbody = document.getElementById('tbodyComp');
    tbody.innerHTML = '';
    items.forEach(it => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="text-start">${it.entidad}</td>
        <td>${it.total}</td>
        <td>${it.concluidos}</td>
        <td>${it.pruebas}</td>
        <td>${it.sin_ejecutar}</td>
        <td>${fmtPct(it.avance_promedio)}</td>
      `;
      tbody.appendChild(tr);
    });

    // Ordenamiento básico (cliente)
    document.querySelectorAll('#tablaComp thead th').forEach((th, idx) => {
      th.style.cursor = 'pointer';
      th.onclick = () => {
        const type = th.dataset.sort || 'text';
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const asc  = !th.classList.contains('sorted-asc');
        document.querySelectorAll('#tablaComp thead th').forEach(x => x.classList.remove('sorted-asc','sorted-desc'));
        th.classList.add(asc ? 'sorted-asc':'sorted-desc');

        rows.sort((a,b) => {
          const A = a.children[idx].innerText.trim();
          const B = b.children[idx].innerText.trim();
          if (type === 'num'){
            return asc ? (Number(A)-Number(B)) : (Number(B)-Number(A));
          }
          return asc ? A.localeCompare(B) : B.localeCompare(A);
        });
        rows.forEach(r => tbody.appendChild(r));
      };
    });
  }

  // --- Render Gráficas ---
 function resetCanvas(id) {
  const old = document.getElementById(id);
  const parent = old.parentNode;
  const clone = old.cloneNode(false); // sin hijos, sin estado
  parent.replaceChild(clone, old);
  return clone.getContext('2d');
}

function renderBarras(payload){
  const labels = payload.entities.map(e => String(e.entidad));
  const dsCon  = payload.entities.map(e => Number(e.concluidos)    || 0);
  const dsPr   = payload.entities.map(e => Number(e.pruebas)       || 0);
  const dsSin  = payload.entities.map(e => Number(e.sin_ejecutar)  || 0);

  const data = {
    labels,
    datasets: [
      { label: 'Concluido',    data: dsCon, backgroundColor: '#22c55e', stack: 's1' },
      { label: 'Pruebas',      data: dsPr,  backgroundColor: '#f59e0b', stack: 's1' },
      { label: 'Sin ejecutar', data: dsSin, backgroundColor: '#9ca3af', stack: 's1' },
    ]
  };

  const cfg = {
    type: 'bar',
    data,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: false, // evita "crecimiento" visual si hay re-renders rápidos
      scales: {
        x: { stacked: true },
        y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } }
      },
      plugins: {
        legend: { position: 'bottom' },
        tooltip: {
          callbacks: {
            footer: (items) => {
              const i = items[0].dataIndex;
              const tot = (payload.entities[i]?.total) ?? 0;
              return 'Total: ' + tot;
            }
          }
        }
      }
    }
  };

  const ctx = resetCanvas('chartBarras'); // ← lienzo limpio siempre
  // Si usabas chartBarras.destroy(), ya no es necesario con resetCanvas
  new Chart(ctx, cfg);
}


  function renderPie(kpi){
    const data = {
      labels: ['Concluido','Pruebas','Sin ejecutar'],
      datasets: [{
        data: [kpi.concluidos, kpi.pruebas, kpi.sin_ejecutar],
        backgroundColor: ['#22c55e','#f59e0b','#9ca3af']
      }]
    };
    const cfg = {
      type: 'pie',
      data,
      options: {
        responsive:true,
        maintainAspectRatio:false,
        plugins:{ legend:{ position:'bottom' } }
      }
    };
    const ctx = document.getElementById('chartPie').getContext('2d');
    if (chartPie) chartPie.destroy();
    chartPie = new Chart(ctx, cfg);
  }

  // --- Fetch datos ---
  let __inFlight = false;

async function cargarDatos(){
  if (__inFlight) return;         // evita 2do fetch mientras el 1ro no termina
  __inFlight = true;
  toggleLoader(true);
  try {
    const q = paramsFromForm();
    const url = 'api_dashboard_datos.php' + (q ? ('?'+q) : '');
    const res = await fetch(url, { cache:'no-store' });
    const txt = await res.text();
    let json;
    try { json = JSON.parse(txt); } catch (e) { throw new Error('Respuesta no válida del servidor.'); }
    if (json.error) throw new Error(json.error);

    renderKPIs(json.kpi);
    renderBarras(json);         // ← con resetCanvas
    renderPie(json.kpi);
    renderTabla(json.entities);

    // persistencia de filtros...
  } catch (err) {
    console.error(err);
    alert(err.message || 'Error al cargar datos');
  } finally {
    toggleLoader(false);
    __inFlight = false;
  }
}


  // --- Eventos ---
  $form.addEventListener('submit', (e)=>{ e.preventDefault(); cargarDatos(); });
  document.getElementById('btnLimpiar').onclick = ()=>{
    document.getElementById('entidades').selectedIndex = -1;
    document.getElementById('bus_id').value = '';
    document.getElementById('desde').value  = '';
    document.getElementById('hasta').value  = '';
    cargarDatos();
  };
  document.getElementById('btnCSV').onclick = ()=>{
    // Exportar la tabla a CSV
    const rows = [['Entidad','#Registros','Concluido','Pruebas','Sin ejecutar','% Avance']];
    document.querySelectorAll('#tbodyComp tr').forEach(tr=>{
      rows.push(Array.from(tr.children).map(td => td.innerText.trim()));
    });
    descargarCSV('dashboard_comparativas.csv', rows);
  };
  document.getElementById('btnImprimir').onclick = ()=>{ window.print(); };

  // --- Restaurar filtros guardados ---
  (function restore(){
    try{
      const raw = localStorage.getItem('dash_filtros');
      if(!raw) return;
      const f = JSON.parse(raw);
      const selEnt = document.getElementById('entidades');
      if (Array.isArray(f.entidades)){
        Array.from(selEnt.options).forEach(o => { o.selected = f.entidades.includes(o.value); });
      }
      document.getElementById('bus_id').value = f.bus_id || '';
      document.getElementById('desde').value  = f.desde  || '';
      document.getElementById('hasta').value  = f.hasta  || '';
    }catch{}
  })();

  // Carga inicial
  cargarDatos();
  }
</script>
</body>
</html>
