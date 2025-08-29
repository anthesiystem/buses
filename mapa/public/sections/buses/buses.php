<?php require_once '../../../server/config.php'; ?>
<!-- Bootstrap JS Bundle (incluye Popper) -->


<style>
  /* ===== Row-Cards para tabla de Buses ===== */
  .tbl-wrap{ background:#fff; border:1px solid #eef0f4; border-radius:14px; padding:.35rem; }
  .table-rowcards{ border-collapse:separate; border-spacing:0 .65rem; margin:0; }
  .table-rowcards thead th{
    font-size:.78rem; letter-spacing:.02em; text-transform:uppercase;
    color:#64748b; background:#f7f8fa; border:0!important; padding:.85rem .9rem;
  }
  .table-rowcards tbody tr{ box-shadow:0 1px 2px rgba(16,24,40,.06); }
  .table-rowcards tbody td{
    background:#fff; border:1px solid #edf0f6; border-left:0; border-right:0;
    padding:.9rem .9rem; vertical-align:middle;
  }
  .table-rowcards tbody td:first-child{
    border-left:1px solid #edf0f6; border-top-left-radius:12px; border-bottom-left-radius:12px;
  }
  .table-rowcards tbody td:last-child{
    border-right:1px solid #edf0f6; border-top-right-radius:12px; border-bottom-right-radius:12px;
  }

  .col-id{ width:70px; }
  .id-chip{ background:#f6f7fb; border:1px solid #edf0f6; padding:.35rem .6rem; border-radius:.6rem; font-weight:700; }
  .bus-name{ font-weight:600; }

  /* Chips de color (Implementado / Pruebas / Sin implementar) */
  .color-chip{ width:28px; height:18px; border-radius:6px; border:2px solid #fff; box-shadow:0 0 0 1px rgba(0,0,0,.08) inset; margin:auto; }

  /* Icono */
  .bus-icon{ width:40px; height:40px; border-radius:9px; background:#fff; display:grid; place-items:center;
             box-shadow:0 1px 2px rgba(16,24,40,.06); overflow:hidden; margin:auto; }
  .bus-icon img{ width:40px; height:40px; object-fit:contain; }

  /* Estado */
  .badge-estado{ font-weight:700; letter-spacing:.02em; padding:.45rem .7rem; border-radius:999px; font-size:.74rem; border:1px solid transparent; }
  .estado-activo{ background:#ecfdf3; color:#067647; border-color:#abefc6; }
  .estado-inactivo{ background:#f1f5f9; color:#475569; border-color:#e2e8f0; }

  /* Acciones */
  .acciones{ display:flex; gap:.5rem; justify-content:flex-end; }
  .btn-soft{ border:1px solid #e6e8ef; background:#fff; color:#1f2937; padding:.45rem .75rem; border-radius:.7rem; font-weight:600; }
  .btn-soft:hover{ box-shadow:0 1px 2px rgba(16,24,40,.12); transform:translateY(-1px); }
  .btn-edit{ color:#1d4ed8; border-color:#e3e8ff; background:#f5f7ff; }
  .btn-danger-soft{ color:#dc2626; border-color:#fde2e2; background:#fff7f7; }

  /* reemplaza tu .color-chip actual por este */
.color-chip{
  width: 30px;
  height: 20px;
  border-radius: 6px;
  border: 1px solid #edf0f6;
  background: var(--chip, #e9edf5ff); /* <- toma el color inline */
  margin: auto;
}
/* Pill oscuro con dot cuadrado */
.color-pill{
  display: inline-flex;
  align-items: center;
  gap: .55rem;
  padding: .35rem .6rem;
  border-radius: 12px;
  background: #f1f2f5ff;                 /* fondo oscuro del pill */
  color: #2b2b2bff;
  font-weight: 600;
  font-size: .82rem;
  box-shadow: inset 0 0 0 1px rgba(255,255,255,.06);
}

/* Cuadrito de color (usa variable --chip) */
.color-pill .dot-sq{
  width: 18px; height: 18px;
  border-radius: 6px;
  background: var(--chip, #e5e7eb);
  box-shadow:
    0 0 0 2px #ffffff inset,          /* borde blanco interior */
    0 0 0 1px rgba(0,0,0,.25);        /* línea externa suave */
}

/* Opcional: centrar el pill en la celda */
.td-color { text-align: center; }


  /* Responsive: oculta columnas secundarias si hace falta */
  @media (max-width: 1000px){ .col-sinimpl{ display:none; } }
  @media (max-width: 820px){ .col-icono{ display:none; } }

  .btn-brand {
    --bs-btn-bg: var(--brand);
    --bs-btn-border-color: var(--brand);
    --bs-btn-hover-bg: var(--brand-600);
    --bs-btn-hover-border-color: var(--brand-600);
    --bs-btn-active-bg: var(--brand-700);
    --bs-btn-active-border-color: var(--brand-700);
    --bs-btn-color: #fff;
    background-color: #941414ff;
}



/* Activar modo compacto en el wrapper */
.tbl-wrap.compact { border-radius: 10px; padding: .25rem; background-color: #ffffff44; }

/* Menos espacio entre cards */
.tbl-wrap.compact .table-rowcards{ border-spacing: 0 .35rem; }

/* Encabezados más pequeños */
.tbl-wrap.compact .table-rowcards thead th{
  font-size: .72rem; padding: .55rem .6rem;
}

/* Celdas más densas */
.tbl-wrap.compact .table-rowcards tbody td{
  padding: .55rem .6rem;
}

/* Texto general un poco menor */
.tbl-wrap.compact td, 
.tbl-wrap.compact th { font-size: .9rem; }

/* ID chip más chico */
.tbl-wrap.compact .id-chip{
  padding: .25rem .45rem; border-radius: .5rem; font-size: .8rem;
}
.tbl-wrap.compact .col-id{ width: 56px; }

/* Nombre bus un poco más chico */
.tbl-wrap.compact .bus-name{ font-size: .92rem; }

/* Chips simples (rectangulitos de color) */
.tbl-wrap.compact .color-chip{ width: 22px; height: 14px; border-radius: 5px; }

/* Pills oscuras con hex */
.tbl-wrap.compact .color-pill{
  padding: .25rem .45rem; font-size: .74rem; border-radius: 10px; gap: .4rem;
}
.tbl-wrap.compact .color-pill .dot-sq{ width: 14px; height: 14px; border-radius: 5px; }

/* Icono más compacto */
.tbl-wrap.compact .bus-icon{ width: 40px; height: 40px; border-radius: 7px; }
.tbl-wrap.compact .bus-icon img{ width: 35px; height: 35px; }

/* Botones más pequeños */
.tbl-wrap.compact .btn-soft{
  padding: .3rem .5rem; border-radius: .55rem; font-size: .82rem;
}
.tbl-wrap.compact .acciones{ gap: .35rem; }

    #main-content {
    max-width: 90%;
    padding-left: 12%;
    padding-top: 1%;}

</style>

<script>
  // Asegura “#” y valor por defecto
  const chip = v => v ? (String(v).trim().startsWith('#') ? v : `#${v}`) : '#E5E7EB';
  const hexText = v => {
    const s = chip(v);
    return s.startsWith('#') ? s.toUpperCase() : s; // si viene rgb(...) lo deja igual
  };
</script>


<div class="container mt-4" style="margin-top: 4.5rem !important;">
  <h4 class="mb-3">Administración de Buses</h4>
  <button class="btn btn-brand" onclick="abrirModalBus()">➕ Agregar Bus</button>
<div class="tbl-wrap compact">
  <table class="table table-rowcards align-middle text-center">
    <thead>
      <tr>
        <th class="col-id">ID</th>
        <th class="text-start">Nombre</th>
        <th>Implementado</th>
        <th>Pruebas</th>
        <th class="col-sinimpl">Sin Implementar</th>
        <th class="col-icono">Icono</th>
        <th>Estado</th>
        <th class="text-end">Acciones</th>
      </tr>
    </thead>
    <tbody id="tablaBuses"></tbody>
  </table>
</div>
</div>
<?php include 'modal_bus.php'; ?>

<script>
  window.BUSES_PATH = 'sections/buses/'; // para fetch()
</script>
<script src="buses.js?v=<?=time()?>"></script>

<!-- Sistema de registro de vistas en bitácora -->
<script src="../../assets/js/bitacora_tracker.js"></script>

<script> if (window.initBuses) window.initBuses(); </script>

