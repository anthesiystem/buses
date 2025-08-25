// --- loader central de secciones (robusto) ---
const __loadedScripts = new Set();

function marcarCargado(src) { if (src) __loadedScripts.add(src); }
function yaCargado(src)     { return __loadedScripts.has(src); }

function absolutizar(src, base) {
  const abs = src.startsWith('http') || src.startsWith('/');
  return abs ? src : base + src.replace(/^(\.\/|\/)/, '');
}

// Copia SOLO atributos seguros (id, type, data-*) para evitar InvalidCharacterError
function copiarAttrsSeguros(origen, destino) {
  const id = origen.getAttribute('id');
  if (id) destino.id = id;
  const type = origen.getAttribute('type');
  if (type) destino.type = type;
  for (const attr of Array.from(origen.attributes)) {
    const name = attr.name || '';
    if (name === 'src') continue;
    if (name.toLowerCase().startsWith('data-')) {
      try { destino.setAttribute(name, attr.value); } catch {}
    }
  }
}

function cargarScriptExterno(info) {
  return new Promise((ok, fail) => {
    if (!info.srcAbs) return ok();
    if (yaCargado(info.srcAbs)) return ok();

    const s = document.createElement('script');
    copiarAttrsSeguros(info.node, s);

    // Evita duplicar IDs (p.ej. mapaScript)
    if (s.id) {
      const prev = document.getElementById(s.id);
      if (prev) prev.remove();
    }

    s.src = info.srcAbs;
    s.onload  = () => { marcarCargado(info.srcAbs); ok(); };
    s.onerror = () => fail(new Error(`No se pudo cargar script: ${info.srcAbs}`));
    document.body.appendChild(s);
  });
}

function ejecutarInline(info) {
  const code = info.node.textContent?.trim();
  if (!code) return;
  const s = document.createElement('script');
  copiarAttrsSeguros(info.node, s);
  s.text = code;
  try { document.body.appendChild(s); } catch {}
}

async function procesarScripts(temp, rutaBase) {
  // Recolecta y REMUEVE scripts del HTML temporal (evita “scripts inertes”)
  const externos = [];
  const inlines  = [];
  Array.from(temp.querySelectorAll('script')).forEach(sc => {
    const src = sc.getAttribute('src');
    if (src) externos.push({ node: sc, srcAbs: absolutizar(src, rutaBase) });
    else     inlines.push({ node: sc });
    sc.remove();
  });

  // Externos en orden
  for (const info of externos) {
    console.log('🟡 Cargando script externo:', info.srcAbs);
    await cargarScriptExterno(info);
  }

  // Inline al final
  inlines.forEach(ejecutarInline);
}

function detectarInitSegunRuta(ruta) {
  if (ruta.includes('/buses/'))     return 'initBuses';
  if (ruta.includes('/usuarios/'))  return 'initUsuarios';
  if (ruta.includes('/registros/')) return 'initRegistros';
  return null;
}

async function cargarSeccion(ruta) {
  const contenedor = document.getElementById('main-content');
  contenedor.innerHTML = '<div class="text-center p-4">Cargando...</div>';

  localStorage.setItem('seccionActual', ruta);
  const rutaBase = ruta.substring(0, ruta.lastIndexOf('/') + 1);

  try {
    const resp = await fetch(ruta, { cache: 'no-store' });
    if (!resp.ok) throw new Error(`Error al cargar ${ruta}`);
    const html = await resp.text();

    const temp = document.createElement('div');
    temp.innerHTML = html;

    // Helpers si tus módulos los usan
    window.SECTION_BASE = rutaBase;
    if (ruta.includes('/buses/')) window.BUSES_PATH = rutaBase;

    // 1) Inyecta scripts (y remueve los del HTML temporal)
    await procesarScripts(temp, rutaBase);

    // 2) Pinta el HTML visible (sin scripts inertes)
    contenedor.innerHTML = temp.innerHTML;

    // 3) Init opcional
    const initName = detectarInitSegunRuta(ruta);
    if (initName && typeof window[initName] === 'function') window[initName]();

    window.scrollTo(0, 0);
  } catch (error) {
    contenedor.innerHTML = `<div class="text-danger p-4">❌ No se pudo cargar la vista: ${error.message}</div>`;
    console.error(error);
  }
}

// Sección inicial
window.addEventListener('DOMContentLoaded', () => {
  const seccion = localStorage.getItem('seccionActual') || 'sections/tablero.php';
  console.log('Cargando sección inicial:', seccion);
  cargarSeccion(seccion);
});
