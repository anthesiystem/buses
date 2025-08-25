function cargarSeccion(ruta) {
  const contenedor = document.getElementById('main-content');
  contenedor.innerHTML = '<div class="text-center p-4">Cargando...</div>';

  // Guarda la ruta actual en localStorage
  localStorage.setItem('seccionActual', ruta);

  fetch(ruta)
    .then(response => {
      if (!response.ok) throw new Error(`Error al cargar ${ruta}`);
      return response.text();
    })
    .then(html => {
      const temp = document.createElement('div');
      temp.innerHTML = html;

      // Cargar contenido visible
      contenedor.innerHTML = temp.innerHTML;

      // 🔄 Ejecutar scripts EXTERNOS (con src)
temp.querySelectorAll("script[src]").forEach(script => {
  const src = script.getAttribute('src');
  if (!src) return;

  const newScript = document.createElement("script");

  // ⚠️ Si src ya es absoluto (/mapa/server/mapa.js), lo dejamos igual.
  // Si es relativo (ej: ./algo.js), lo ajustamos relativo a la sección cargada.
  const esRutaAbsoluta = src.startsWith("http") || src.startsWith("/");
  const rutaBase = ruta.substring(0, ruta.lastIndexOf('/') + 1);
  newScript.src = esRutaAbsoluta ? src : rutaBase + src.replace(/^(\.\/|\/)/, '');

  Array.from(script.attributes).forEach(attr => {
    if (attr.name.startsWith("data-")) {
      newScript.setAttribute(attr.name, attr.value);
    }
  });

  console.log("🟡 Insertando script externo:", newScript.src);
  document.body.appendChild(newScript);
});

      // 🔄 Ejecutar scripts INTERNOS embebidos
      temp.querySelectorAll("script:not([src])").forEach(script => {
        const contenido = script.textContent?.trim();
        if (contenido) {
          const inlineScript = document.createElement("script");
          inlineScript.text = contenido;
          try {
            document.body.appendChild(inlineScript);
          } catch (err) {
            console.warn("⚠️ Error en script embebido:", err);
          }
        }
      });

      window.scrollTo(0, 0);
    })
    .catch(error => {
      contenedor.innerHTML = `<div class="text-danger p-4">❌ No se pudo cargar la vista: ${error.message}</div>`;
      console.error(error);
    });
}

  setTimeout(() => {
    const seccion = localStorage.getItem('seccionActual') || 'sections/inicio.php';
    console.log("Cargando sección:", seccion);
    cargarSeccion(seccion);
  }, 100); // espera breve para asegurar que todo esté cargado

