document.addEventListener("DOMContentLoaded", function () {
  const avanceInput = document.getElementById("avance");
  const estatusSelect = document.getElementById("Fk_estado_bus");
  const categoriaSelect = document.getElementById("Fk_categoria");
  const fechaInicio = document.getElementById("fecha_inicio");
  const fechaMigracion = document.getElementById("fecha_migracion");

  const IMPLEMENTADO_ID = "3";
  const PRODUCTIVOS_ID = "3";
  const CATEGORIAS_CAMBIO = ["1", "2"]; // Migraciones y Pruebas

  function actualizarCamposSegunEstado() {
    const esImplementado = estatusSelect.value === IMPLEMENTADO_ID;
    const esProductivo = categoriaSelect.value === PRODUCTIVOS_ID;
    const avance = parseInt(avanceInput.value);

    const deshabilitar = esImplementado || esProductivo || avance >= 100;

    avanceInput.disabled = deshabilitar;
    estatusSelect.disabled = deshabilitar;
    categoriaSelect.disabled = deshabilitar;
    if (fechaInicio) fechaInicio.disabled = deshabilitar;
    if (fechaMigracion) fechaMigracion.disabled = deshabilitar;
  }

  function marcarImplementado() {
    if (estatusSelect.value !== IMPLEMENTADO_ID) {
      estatusSelect.value = IMPLEMENTADO_ID;
    }
    if (categoriaSelect.value !== PRODUCTIVOS_ID) {
      categoriaSelect.value = PRODUCTIVOS_ID;
    }
    if (avanceInput.value !== "100") {
      avanceInput.value = 100;
    }
    alert("⚠️ El bus se pasó a IMPLEMENTADO automáticamente.");
  }

  avanceInput.addEventListener("input", () => {
    const avance = parseInt(avanceInput.value);
    if (avance >= 100) {
      estatusSelect.value = IMPLEMENTADO_ID;
      if (CATEGORIAS_CAMBIO.includes(categoriaSelect.value)) {
        categoriaSelect.value = PRODUCTIVOS_ID;
      }
      alert("⚠️ El bus se pasó a IMPLEMENTADO automáticamente.");
    }
    actualizarCamposSegunEstado();
  });

  estatusSelect.addEventListener("change", () => {
    if (estatusSelect.value === IMPLEMENTADO_ID) {
      avanceInput.value = 100;
      if (CATEGORIAS_CAMBIO.includes(categoriaSelect.value)) {
        categoriaSelect.value = PRODUCTIVOS_ID;
      }
      alert("⚠️ El bus se pasó a IMPLEMENTADO automáticamente.");
    }
    actualizarCamposSegunEstado();
  });

  categoriaSelect.addEventListener("change", () => {
    if (categoriaSelect.value === PRODUCTIVOS_ID) {
      estatusSelect.value = IMPLEMENTADO_ID;
      avanceInput.value = 100;
      alert("⚠️ El bus se pasó a IMPLEMENTADO automáticamente.");
    }
    actualizarCamposSegunEstado();
  });

  // Limitar fechas al día actual
  const hoy = new Date().toISOString().split("T")[0];
  if (fechaInicio) {
    fechaInicio.setAttribute("max", hoy);
    fechaInicio.addEventListener("change", () => {
      if (fechaInicio.value > hoy) {
        alert("⚠️ La fecha de inicio no puede ser mayor a hoy.");
        fechaInicio.value = hoy;
      }
    });
  }

  if (fechaMigracion) {
    fechaMigracion.setAttribute("max", hoy);
    fechaMigracion.addEventListener("change", () => {
      if (fechaMigracion.value > hoy) {
        alert("⚠️ La fecha de migración no puede ser mayor a hoy.");
        fechaMigracion.value = hoy;
      }
    });
  }

  // Ejecutar al iniciar para proteger si ya viene cargado como implementado
  actualizarCamposSegunEstado();
});
