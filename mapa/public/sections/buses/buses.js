document.addEventListener('DOMContentLoaded', cargarBuses);
console.log("✅ buses.js cargado correctamente");


function cargarBuses() {
  fetch('buses_datos.php')
    .then(res => res.json())
    .then(data => {
      console.log(data);
      const cuerpo = document.getElementById('tablaBuses');
      cuerpo.innerHTML = '';
      data.forEach(bus => {
        cuerpo.innerHTML += `
          <tr>
            <td>${bus.ID}</td>
            <td>${bus.descripcion}</td>
            <td><div style="width: 30px; height: 20px; background:${bus.color_implementado}; margin:auto;"></div></td>
            <td><div style="width: 30px; height: 20px; background:${bus.pruebas}; margin:auto;"></div></td>
            <td><div style="width: 30px; height: 20px; background:${bus.color_sin_implementar}; margin:auto;"></div></td>
            <td><img src="../../icons/${bus.imagen?.replace('/icons/', '')}" height="30"></td>
            <td><span class="badge bg-${bus.activo == 1 ? 'success' : 'danger'}">${bus.activo == 1 ? 'Activo' : 'Inactivo'}</span></td>
            <td>
              <button class="btn btn-sm btn-primary" onclick='editarBus(${JSON.stringify(bus)})'>✏️</button>
              <button class="btn btn-sm btn-${bus.activo == 1 ? 'danger' : 'success'}" onclick="cambiarEstado(${bus.ID}, ${bus.activo})">
                ${bus.activo == 1 ? 'Desactivar' : 'Activar'}
              </button>
            </td>
          </tr>`;
      });
    }).catch(error => {
      console.error("Error cargando buses:", error);
    });
}

function abrirModalBus() {
  document.getElementById('formBus').reset();
  document.getElementById('ID').value = '';
  new bootstrap.Modal(document.getElementById('modalBus')).show();
}

function editarBus(bus) {
  document.getElementById('ID').value = bus.ID;
  document.getElementById('descripcion').value = bus.descripcion;
  document.getElementById('color_implementado').value = bus.color_implementado;
  document.getElementById('color_sin_implementar').value = bus.color_sin_implementar;
  document.getElementById('pruebas').value = bus.pruebas;
  new bootstrap.Modal(document.getElementById('modalBus')).show();
}

document.getElementById('formBus').addEventListener('submit', function (e) {
  e.preventDefault();
  const formData = new FormData(this);
  fetch('./guardar_bus.php', {
    method: 'POST',
    body: formData
  }).then(res => res.json())
    .then(resp => {
      if (resp.success) {
        bootstrap.Modal.getInstance(document.getElementById('modalBus')).hide();
        cargarBuses();
      } else {
        alert('❌ Error: ' + resp.message);
      }
    }).catch(err => console.error(err));
});

function cambiarEstado(id, estado) {
  fetch(`./cambiar_estado_bus.php?id=${id}&estado=${estado}`)
    .then(res => res.json())
    .then(resp => {
      if (resp.success) cargarBuses();
      else alert('Error al cambiar estado');
    });
}
