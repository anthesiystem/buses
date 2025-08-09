// catalogos.js
export function cargarCatalogos() {
  const form = document.getElementById('formRegistro');

// catalogos.js (o donde llenas los selects)
const nameMap = {
  dependencia: 'Fk_dependencia',
  entidad: 'Fk_entidad',
  bus: 'Fk_bus',
  motor_base: 'Fk_motor_base',
  version: 'Fk_version',
  estado: 'Fk_estado_bus',
  categoria: 'Fk_categoria',
};


  const fill = (sel, opts) => {
    if (!sel || !Array.isArray(opts)) return;
    sel.innerHTML = '<option value="">Seleccione...</option>';
    for (const o of opts) {
      const opt = document.createElement('option');
      opt.value = o.ID;
      opt.text = o.descripcion;
      sel.appendChild(opt);
    }
  };

  const data = window.catalogos || {};
  for (const [clave, lista] of Object.entries(data)) {
    const name = nameMap[clave];
    if (!name) continue;
    const select = (form?.querySelector(`[name="${name}"]`)) || document.querySelector(`[name="${name}"]`);
    fill(select, lista);
  }

  // fechas max = hoy
  const maxDate = new Date().toISOString().split('T')[0];
  const fechaInicio = form?.querySelector('[name="fecha_inicio"]') || document.querySelector('[name="fecha_inicio"]');
  const fechaMigracion = form?.querySelector('[name="fecha_migracion"]') || document.querySelector('[name="fecha_migracion"]');
  if (fechaInicio) fechaInicio.max = maxDate;
  if (fechaMigracion) fechaMigracion.max = maxDate;
}
