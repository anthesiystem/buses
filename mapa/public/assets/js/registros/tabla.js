// /js/registros/tabla.js

export function renderizarTabla(data, paginaActual = 1, registrosPorPagina = 10) {
  const tablaBody = document.querySelector('#tablaRegistros tbody');
  const paginacion = document.getElementById('paginacion');

  if (!tablaBody || !paginacion) return;

  tablaBody.innerHTML = '';

  if (!Array.isArray(data) || data.length === 0) {
    tablaBody.innerHTML = '<tr><td colspan="12" class="text-center">Sin resultados</td></tr>';
    paginacion.innerHTML = '';
    return;
  }

  const inicio = (paginaActual - 1) * registrosPorPagina;
  const fin = inicio + registrosPorPagina;
  const paginaDatos = data.slice(inicio, fin);

  paginaDatos.forEach(r => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${r.ID}</td>
      <td>${r.Dependencia}</td>
      <td>${r.Entidad}</td>
      <td>${r.Bus}</td>
      <td>${r.Engine}</td>
      <td>${r.Version}</td>
      <td>${r.Estado}</td>
      <td>${r.Categoria}</td>
      <td>${r.Inicio}</td>
      <td>${r.Migracion}</td>
      <td>
        <div class="progress">
          <div class="progress-bar ${r.avance == 100 ? 'bg-success' : r.avance >= 50 ? 'bg-warning' : 'bg-danger'}"
               style="width: ${r.avance}%; min-width: 40px;">
            ${r.avance}%
          </div>
        </div>
      </td>
      <td>
        <button class="btn btn-sm btn-primary" onclick='editar(${JSON.stringify(r)})'>Editar</button>
      </td>
    `;
    tablaBody.appendChild(tr);
  });
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

  // Botón "Primera"
  const liPrimera = document.createElement('li');
  liPrimera.className = 'page-item ' + (paginaActual === 1 ? 'disabled' : '');
  const btnPrimera = document.createElement('button');
  btnPrimera.className = 'page-link';
  btnPrimera.textContent = '«';
  btnPrimera.onclick = () => onPageChange(1);
  liPrimera.appendChild(btnPrimera);
  ul.appendChild(liPrimera);

  // Botón "Anterior"
  const liAnterior = document.createElement('li');
  liAnterior.className = 'page-item ' + (paginaActual === 1 ? 'disabled' : '');
  const btnAnterior = document.createElement('button');
  btnAnterior.className = 'page-link';
  btnAnterior.textContent = '‹';
  btnAnterior.onclick = () => {
    if (paginaActual > 1) onPageChange(paginaActual - 1);
  };
  liAnterior.appendChild(btnAnterior);
  ul.appendChild(liAnterior);

  // Mostrar 5 páginas alrededor de la actual
  const rango = 2;
  let inicio = Math.max(1, paginaActual - rango);
  let fin = Math.min(totalPaginas, paginaActual + rango);

  for (let i = inicio; i <= fin; i++) {
    const li = document.createElement('li');
    li.className = 'page-item ' + (i === paginaActual ? 'active' : '');
    const btn = document.createElement('button');
    btn.className = 'page-link';
    btn.textContent = i;
    btn.onclick = () => onPageChange(i);
    li.appendChild(btn);
    ul.appendChild(li);
  }

  // Botón "Siguiente"
  const liSiguiente = document.createElement('li');
  liSiguiente.className = 'page-item ' + (paginaActual === totalPaginas ? 'disabled' : '');
  const btnSiguiente = document.createElement('button');
  btnSiguiente.className = 'page-link';
  btnSiguiente.textContent = '›';
  btnSiguiente.onclick = () => {
    if (paginaActual < totalPaginas) onPageChange(paginaActual + 1);
  };
  liSiguiente.appendChild(btnSiguiente);
  ul.appendChild(liSiguiente);

  // Botón "Última"
  const liUltima = document.createElement('li');
  liUltima.className = 'page-item ' + (paginaActual === totalPaginas ? 'disabled' : '');
  const btnUltima = document.createElement('button');
  btnUltima.className = 'page-link';
  btnUltima.textContent = '»';
  btnUltima.onclick = () => onPageChange(totalPaginas);
  liUltima.appendChild(btnUltima);
  ul.appendChild(liUltima);

  nav.appendChild(ul);
  paginacion.appendChild(nav);
}
