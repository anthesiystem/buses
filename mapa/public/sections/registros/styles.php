<!-- styles.php - Estilos CSS -->
<style>
  :root{
    --brand:#7b1e2b; --brand-600:#8e2433; --brand-700:#661822; --brand-rgb:123,30,43;
    --ink:#1f2937; --muted:#6b7280; --row-hover:rgba(var(--brand-rgb),.04); --row-selected:rgba(var(--brand-rgb),.08);
    --header-bg:#ffffff; --header-border:#e5e7eb; --table-border:#e5e7eb; --badge-bg:#f3f4f6;
  }
  body{ color:var(--ink); background:#fafafa; }
  .page-title{ font-weight:700; letter-spacing:.2px; }
  .btn-brand{
    --bs-btn-bg:var(--brand); --bs-btn-border-color:var(--brand);
    --bs-btn-hover-bg:var(--brand-600); --bs-btn-hover-border-color:var(--brand-600);
    --bs-btn-active-bg:var(--brand-700); --bs-btn-active-border-color:var(--brand-700);
    --bs-btn-color:#fff;
  }
  .btn-outline-brand{
    --bs-btn-color:var(--brand); --bs-btn-border-color:var(--brand);
    --bs-btn-hover-bg:var(--brand); --bs-btn-hover-border-color:var(--brand);
    --bs-btn-hover-color:#fff;
  }
  .table-card{
    background:#fff; border:1px solid var(--table-border);
    border-radius:14px; overflow:hidden; box-shadow:0 6px 24px rgba(0,0,0,.04);
  }
  .table-responsive{ max-height:70vh; }
  .table-brand thead th{
    background:var(--header-bg);
    border-bottom:1px solid var(--header-border); color:var(--muted);
    font-weight:700; text-transform:uppercase; font-size:.78rem; letter-spacing:.5px; cursor:pointer;
  }
  .table-brand tbody td{ vertical-align:middle; border-color:var(--table-border); }
  .table-brand tbody tr:hover{ background:var(--row-hover); }
  .table-brand tbody tr.selected{ background:var(--row-selected); box-shadow:inset 4px 0 0 var(--brand); }
  .table-brand .progress{ height:8px; background:#efe7e9; }
  .progress-bar.brand{ background:var(--brand); }
  .badge-soft{ background:var(--badge-bg); color:var(--ink); border:1px solid #e5e7eb; font-weight:600; }
  .badge-implementado{ border-color:#d1fae5; background:#f0fdf4; color:#065f46; }
  .badge-pruebas{ border-color:#fde68a; background:#fffbeb; color:#92400e; }
  .actions .btn{ padding:.25rem .5rem; }
  @media (max-width:768px){
    .col-sm-hide{ display:none; }
    .actions .btn .text{ display:none; }
  }

  .modal-modern .modal-header{
    background: linear-gradient(135deg, rgba(123,30,43,.95), rgba(102,24,34,.95));
    color:#fff; border-bottom:0;
  }
  .modal-modern .modal-content{
    border:0; border-radius:20px; box-shadow:0 10px 30px rgba(0,0,0,.15);
  }
  .modal-modern .modal-body{
    background:#fafafa;
  }
  .fieldset-card{
    background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:14px;
    box-shadow:0 2px 10px rgba(0,0,0,.03);
  }
  .fieldset-card legend{
    font-size:.85rem; font-weight:700; color:#6b7280; padding:0 6px;
  }
  .help-inline{ font-size:.85rem; color:#6b7280; }
  .is-disabled{ opacity:.6; pointer-events:none; }

  /* Chips para la celda de Bus */
  .chip{
    display:inline-flex; align-items:center; gap:.35rem;
    padding:.2rem .6rem; border-radius:9999px; font-weight:600;
    background:var(--badge-bg); color:var(--ink); border:1px solid #e5e7eb;
    white-space:nowrap; max-width:100%; overflow:hidden; text-overflow:ellipsis;
  }
  .chip i{ font-size:1rem; line-height:1; }

  .chip-impl{ background:rgba(var(--brand-rgb), .08); color:var(--brand); border-color:rgba(var(--brand-rgb), .35); }
  .chip-pru { background:#fffbeb; color:#92400e; border-color:#fde68a; }
  .chip-sin { background:#f3f4f6; color:#374151; border-color:#e5e7eb; }

  /* Acento de fila según estado (opcional) */
  .row-impl{ box-shadow: inset 4px 0 0 var(--brand); }
  .row-pru { box-shadow: inset 4px 0 0 #f59e0b; }   /* ámbar */
  .row-sin { box-shadow: inset 4px 0 0 #9ca3af; }   /* gris */

  #main-content {
      max-width: 90%;
      padding-left: 12%;
      padding-top: 5%;
  }

  /* Sin scroll interno en la tarjeta/tabla */
  .table-card { max-height: none !important; overflow: visible !important; }
  .table-responsive { overflow: visible !important; }
</style>
