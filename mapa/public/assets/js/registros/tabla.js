// /js/registros/tabla.js

// Mapa para acceder al registro completo por ID (evita poner JSON en el HTML)
const registrosPorId = new Map();

function fmtFecha(d) {
  if (!d) return '';
  // Intenta YYYY-MM-DD o ISO
  const date = /^\d{4}-\d{2}-\d{2}/.test(d) ? new Date(d + 'T00:00:00') : new Date(d);
  if (isNaN(date)) return d; // deja tal cual si no parsea
  const yyyy = date.getFullYear();
  const mm = String(date.getMonth() + 1).padStart(2, '0');
  const dd = String(date.getDate()).padStart(2, '0');
  return `${yyyy}-${mm}-${dd}`;
}

function esc(s) {
  return String(s ?? '').replace(/[&<>"']/g, m => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[m]));
}

export function renderizarTabla(data, paginaActual = 1, registrosPorPagina = 10) {
  const tablaBody = document.getElementById('cuerpoTabla');
  const paginacion = document.getElementById('paginacion');

  if (!tablaBody || !paginacion) return;

  // Limpia estructuras
  tablaBody.innerHTML = '';
  registrosPorId.clear();

  if (!Array.isArray(data) || data.length === 0) {
    tablaBody.innerHTML = '<tr><td colspan="12" class="text-center">Sin resultados</td></tr>';
    paginacion.innerHTML = '';
    return;
  }

  const inicio = (paginaActual - 1) * registrosPorPagina;
  const fin = inicio + registrosPorPagina;
  const paginaDatos = data.slice(inicio, fin);

  paginaDatos.forEach(r => {
    const id = r.ID;
    registrosPorId.set(String(id), r); // guarda referencia

    const dep = esc(r.Dependencia);
    const ent = esc(r.Entidad);
    const bus = esc(r.Bus);
    const eng = esc(r.Engine);
    const ver = esc(r.Version);
    const est = esc(r.Estado);
    const cat = esc(r.Categoria);
    const ini = esc(fmtFecha(r.Inicio));
    const mig = esc(fmtFecha(r.Migracion));

    const av = Math.max(0, Math.min(100, parseInt(r.avance ?? 0, 10) || 0));
    const clase = av === 100 ? 'bg-success' : av >= 50 ? 'bg-warning' : 'bg-danger';

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${id}</td>
      <td>${dep}</td>
      <td>${ent}</td>
      <td>${bus}</td>
      <td>${eng}</td>
      <td>${ver}</td>
      <td>${est}</td>
      <td>${cat}</td>
      <td>${ini}</td>
      <td>${mig}</td>
      <td>
        <div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="${av}">
          <div class="progress-bar ${clase}" style="width: ${av}%; min-width: 40px;">${av}%</div>
        </div>
      </td>
      <td>
        <button class="btn btn-sm btn-primary btn-editar" data-id="${id}" type="button">Editar</button>
      </td>
    `;
    tablaBody.appendChild(tr);
  });

  // Delegación de eventos para “Editar”
  // (solo se agrega una vez)
  if (!tablaBody.dataset.editarBind) {
    tablaBody.addEventListener('click', e => {
      const btn = e.target.closest('.btn-editar');
      if (!btn) return;
      const id = String(btn.dataset.id || '');
      const reg = registrosPorId.get(id);
      if (reg && typeof window.editar === 'function') {
        window.editar(reg);
      }
    });
    tablaBody.dataset.editarBind = '1';
  }
}

export function renderizarPaginacion(totalRegistros, paginaActual, registrosPorPagina, onPageChange) {
  const paginacion = document.getElementById('paginacion');
  if (!paginacion) return;

  const totalPaginas = Math.ceil(totalRegistros / registrosPorPagina);
  paginacion.innerHTML = '';

  if (totalPaginas <= 1) return;

  const nav = document.createElement('nav');
  const ul = document.createElement('ul');
  ul.className = 'pagination justify-content-center';

  const addBtn = (label, disabled, handler) => {
    const li = document.createElement('li');
    li.className = 'page-item ' + (disabled ? 'disabled' : '');
    const btn = document.createElement('button');
    btn.className = 'page-link';
    btn.textContent = label;
    btn.type = 'button';
    if (!disabled) btn.onclick = handler;
    li.appendChild(btn);
    return li;
  };

  // « Primera
  ul.appendChild(addBtn('«', paginaActual === 1, () => onPageChange(1)));
  // ‹ Anterior
  ul.appendChild(addBtn('‹', paginaActual === 1, () => onPageChange(paginaActual - 1)));

  const rango = 2;
  let inicio = Math.max(1, paginaActual - rango);
  let fin = Math.min(totalPaginas, paginaActual + rango);

  for (let i = inicio; i <= fin; i++) {
    const li = document.createElement('li');
    li.className = 'page-item ' + (i === paginaActual ? 'active' : '');
    const btn = document.createElement('button');
    btn.className = 'page-link';
    btn.type = 'button';
    btn.textContent = i;
    btn.onclick = () => onPageChange(i);
    li.appendChild(btn);
    ul.appendChild(li);
  }

  // › Siguiente
  ul.appendChild(addBtn('›', paginaActual === totalPaginas, () => onPageChange(paginaActual + 1)));
  // » Última
  ul.appendChild(addBtn('»', paginaActual === totalPaginas, () => onPageChange(totalPaginas)));

  nav.appendChild(ul);
  paginacion.appendChild(nav);
}
