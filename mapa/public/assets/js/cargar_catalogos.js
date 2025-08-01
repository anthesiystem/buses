async function cargarCatalogos() {
  console.log("Catálogos cargando...");
  try {
    const res = await fetch("../server/acciones/cargar_catalogos.php");
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
