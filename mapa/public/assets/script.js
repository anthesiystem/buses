// --- loader central de secciones ---
const __loadedScripts = new Set();

function scriptYaCargado(srcAbsoluto) {
  if (__loadedScripts.has(srcAbsoluto)) return true;
  // También verifica si ya existe en el DOM
  return !!document.querySelector(`script[src="${srcAbsoluto}"]`);
}

function cargarScriptSecuencial(src) {
  return new Promise((ok, fail) => {
    if (scriptYaCargado(src)) return ok(); // evitar duplicados
    const s = document.createElement('script');
    s.src = src;
    s.onload = () => { __loadedScripts.add(src); ok(); };
    s.onerror = (e) => fail(new Error(`No se pudo cargar script: ${src}`));
    document.body.appendChild(s);
  });
}

async function cargarScriptsExternosEnOrden(temp, rutaBase) {
  const externos = Array.from(temp.querySelectorAll('script[src]'));
  for (const sc of externos) {
    const src = sc.getAttribute('src');
    if (!src) continue;

    const esAbsoluta = src.startsWith('http') || src.startsWith('/');
    const absoluto = esAbsoluta
      ? src
      : rutaBase + src.replace(/^(\.\/|\/)/, '');

    console.log('🟡 Cargando script externo:', absoluto);
    await cargarScriptSecuencial(absoluto);
  }
}

function ejecutarScriptsInline(temp) {
  const internos = temp.querySelectorAll('script:not([src])');
  internos.forEach(sc => {
    const contenido = sc.textContent?.trim();
    if (!contenido) return;
    const tag = document.createElement('script');
    tag.text = contenido;
    try {
      document.body.appendChild(tag);
    } catch (err) {
      console.warn('⚠️ Error en script embebido:', err);
    }
  });
}

function detectarInitSegunRuta(ruta) {
  // Ajusta estas reglas según tus secciones
  if (ruta.includes('/buses/')) return 'initBuses';
  if (ruta.includes('/usuarios/')) return 'initUsuarios';
  if (ruta.includes('/registros/')) return 'initRegistros';
  if (ruta.includes('general_v2')) return 'initMapaGeneralV2';
  // por defecto ninguno
  return null;
}

async function cargarSeccion(ruta) {
  const contenedor = document.getElementById('main-content');
  contenedor.innerHTML = '<div class="text-center p-4">Cargando...</div>';

  // Persistir para restaurar tras F5
  localStorage.setItem('seccionActual', ruta);

  // Base de la sección (todo lo relativo cuelga de aquí)
  const rutaBase = ruta.substring(0, ruta.lastIndexOf('/') + 1);

  try {
    const resp = await fetch(ruta);
    if (!resp.ok) throw new Error(`Error al cargar ${ruta}`);
    const html = await resp.text();

    // Crear contenedor temporal para procesar scripts
    const temp = document.createElement('div');
    temp.innerHTML = html;

    // Exponer BASE para los fetch del módulo
    window.SECTION_BASE = rutaBase;
    // Si es la vista de buses, deja también BUSES_PATH
    if (ruta.includes('/buses/')) {
      window.BUSES_PATH = rutaBase; // usado por buses.js
    }

    // Primero pinta el HTML visible
    contenedor.innerHTML = temp.innerHTML;

    // Luego carga scripts externos en orden (y sin duplicados)
    await cargarScriptsExternosEnOrden(temp, rutaBase);

    // Ejecuta scripts inline de la sección
    ejecutarScriptsInline(temp);

    // Por último, intenta correr el init de la sección
    const initName = detectarInitSegunRuta(ruta);
    if (initName && typeof window[initName] === 'function') {
      window[initName]();
    } else {
      console.log('ℹ️ No se encontró init para la sección o no aplica:', initName);
    }

    window.scrollTo(0, 0);
  } catch (error) {
    contenedor.innerHTML = `<div class="text-danger p-4">❌ No se pudo cargar la vista: ${error.message}</div>`;
    console.error(error);
  }
}

// Restaurar última sección al iniciar
window.addEventListener('DOMContentLoaded', () => {
  const seccion = localStorage.getItem('seccionActual') || 'sections/tablero.php';
  console.log('Cargando sección inicial:', seccion);
  cargarSeccion(seccion);
});
