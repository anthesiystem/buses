async function cargarCatalogos() {
  console.log("Catálogos cargando...");
  try {
    const res = await fetch("../../server/acciones/cargar_catalogos.php");
    const data = await res.json();
    console.log("Respuesta recibida:", data);

    const selects = {
      dependencias: "Fk_dependencia",
      entidades: "Fk_entidad",
      buses: "Fk_bus",
      engines: "Fk_engine",
      versiones: "Fk_version",
      categorias: "Fk_categoria",
      estatuses: "Fk_estado_bus"
    };

    for (const [clave, name] of Object.entries(selects)) {
      const select = document.querySelector(`[name="${name}"]`);
      if (!select) {
        console.warn("No se encontró el campo:", clave);
        continue;
      }

      // Limpiar opciones previas
      select.innerHTML = '<option value="">Seleccione</option>';

      data[clave]?.forEach(opcion => {
        const opt = document.createElement("option");
        opt.value = opcion.ID;
        opt.textContent = opcion.descripcion;
        select.appendChild(opt);
      });

      console.log(`✅ Campo ${clave} cargado con ${data[clave]?.length} opciones`);
    }
  } catch (error) {
    console.error("Error al cargar catálogos:", error);
  }
}





document.addEventListener('DOMContentLoaded', () => {
  const formFiltro = document.getElementById('filtrosForm');
  const tablaBody = document.querySelector('#tablaRegistros tbody');
  const loader = document.getElementById('cargando');
  const paginacion = document.getElementById('paginacion');

  let registrosCompletos = [];
  let paginaActual = 1;
  const registrosPorPagina = 10;

  function mostrarLoader(mostrar) {
    if (loader) loader.style.display = mostrar ? 'block' : 'none';
  }

  function renderizarTabla(data) {
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

    renderizarPaginacion(data.length);
  }

  function renderizarPaginacion(total) {
  const totalPaginas = Math.ceil(total / registrosPorPagina);
  paginacion.innerHTML = '';
  if (totalPaginas <= 1) return;

  const maxBotonesVisibles = 7; // Puedes ajustar a 5, 7, etc.
  let inicio = Math.max(1, paginaActual - Math.floor(maxBotonesVisibles / 2));
  let fin = inicio + maxBotonesVisibles - 1;
  if (fin > totalPaginas) {
    fin = totalPaginas;
    inicio = Math.max(1, fin - maxBotonesVisibles + 1);
  }

  const nav = document.createElement('nav');
  const ul = document.createElement('ul');
  ul.className = 'pagination justify-content-center';

  // Anterior
  const liPrev = document.createElement('li');
  liPrev.className = 'page-item ' + (paginaActual === 1 ? 'disabled' : '');
  const btnPrev = document.createElement('button');
  btnPrev.className = 'page-link';
  btnPrev.innerText = 'Anterior';
  btnPrev.onclick = () => {
    if (paginaActual > 1) {
      paginaActual--;
      renderizarTabla(registrosCompletos);
    }
  };
  liPrev.appendChild(btnPrev);
  ul.appendChild(liPrev);

  // Rango limitado de páginas
  for (let i = inicio; i <= fin; i++) {
    const li = document.createElement('li');
    li.className = 'page-item ' + (i === paginaActual ? 'active' : '');
    const btn = document.createElement('button');
    btn.className = 'page-link';
    btn.textContent = i;
    btn.onclick = () => {
      paginaActual = i;
      renderizarTabla(registrosCompletos);
    };
    li.appendChild(btn);
    ul.appendChild(li);
  }

  // Siguiente
  const liNext = document.createElement('li');
  liNext.className = 'page-item ' + (paginaActual === totalPaginas ? 'disabled' : '');
  const btnNext = document.createElement('button');
  btnNext.className = 'page-link';
  btnNext.innerText = 'Siguiente';
  btnNext.onclick = () => {
    if (paginaActual < totalPaginas) {
      paginaActual++;
      renderizarTabla(registrosCompletos);
    }
  };
  liNext.appendChild(btnNext);
  ul.appendChild(liNext);

  nav.appendChild(ul);
  paginacion.appendChild(nav);
}


  function cargarRegistros(filtros = {}) {
    mostrarLoader(true);

    fetch('../../server/acciones/registros_datos.php', {
      method: 'POST',
      body: new URLSearchParams(filtros)
    })
    .then(res => res.json())
    .then(resp => {
      const data = resp.data || [];
      registrosCompletos = data;
      paginaActual = 1;
      renderizarTabla(registrosCompletos);
      mostrarLoader(false);
    })
    .catch(err => {
      console.error("Error al cargar registros:", err);
      mostrarLoader(false);
    });
  }

  formFiltro.addEventListener('submit', e => {
    e.preventDefault();
    const datos = Object.fromEntries(new FormData(formFiltro));
    cargarRegistros(datos);
  });

  cargarRegistros();

window.abrirModal = async function () {
  const form = document.getElementById("formRegistro");
  form.reset();
  form.ID.value = "";

  await cargarCatalogos();
  new bootstrap.Modal(document.getElementById("modalRegistro")).show();
};

window.editar = async function (datos) {
  await cargarCatalogos();
  const form = document.getElementById("formRegistro");

  Object.entries(datos).forEach(([k, v]) => {
    const campo = form.querySelector(`[name="${k}"]`);
    if (campo) campo.value = v ?? "";
  });

  new bootstrap.Modal(document.getElementById("modalRegistro")).show();
};

});

document.getElementById("formRegistro").addEventListener("submit", function (e) {
  e.preventDefault();
  const form = e.target;
  const datos = new FormData(form);

  fetch("../../server/acciones/guardar_registro.php", {
    method: "POST",
    body: datos
  })
    .then(res => res.json())
.then(resp => {
  console.log("Respuesta:", resp); // ← Agregado para depuración
  if (resp.success === true || resp.success === "true") {
    bootstrap.Modal.getInstance(document.getElementById("modalRegistro")).hide();

 // Detectar si fue nuevo o edición
  const fueNuevo = !form.querySelector('[name="ID"]').value;

      const mensaje = fueNuevo
    ? "✅ Registro creado exitosamente"
    : "✅ Registro actualizado exitosamente";
  document.getElementById("mensajeToast").textContent = mensaje;
  const toast = new bootstrap.Toast(document.getElementById("toastExito"));
  toast.show();

    form.reset();
    cargarRegistrosDesdeJSON(); // recargar tabla

  } else {
    alert("Error al guardar: " + resp.error);
  }
})

    .catch(err => {
      console.error("Error al guardar:", err);
      alert("Error al guardar");
    });
});


function cargarRegistros() {
  fetch('/mapa/public/sections/registros/tabla.php')
    .then(res => res.text())
    .then(html => {
      document.getElementById("contenedorTabla").innerHTML = html;
    })
    .catch(err => console.error("Error al cargar tabla:", err));
}

function cargarRegistros() {
  const contenedor = document.getElementById("contenedorTabla");
  const spinner = document.getElementById("spinnerTabla");

  // Mostrar spinner
  if (spinner) spinner.style.display = 'block';

  fetch('/mapa/public/sections/registros/tabla.php')
    .then(res => res.text())
    .then(html => {
      contenedor.innerHTML = html;
    })
    .catch(err => {
      console.error("Error al cargar registros:", err);
    })
    .finally(() => {
      if (spinner) spinner.style.display = 'none';
    });
}

