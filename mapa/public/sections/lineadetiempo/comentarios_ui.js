// /mapa/public/sections/lineadetiempo/comentarios_ui.js
(() => {
  function q(root, sel)     { return root.querySelector(sel); }
  function qa(root, sel)    { return Array.from(root.querySelectorAll(sel)); }
  function text(el, s)      { if (el) el.textContent = s; }
  function cls(el, name)    { if (el) el.className = name; }

  // ——— Vista previa de la etiqueta seleccionada ———
  function initEtiquetaPreview(root) {
    const pill   = q(root, '#pillEtiqueta');
    const radios = qa(root, 'input[name="color"].btn-check');
    if (!pill || radios.length === 0) return;

    const sync = () => {
      const r = radios.find(x => x.checked);
      if (!r) return;
      const label = r.dataset.label || '';
      const klass = r.dataset.class || 'bg-success';
      cls(pill, 'badge ' + klass + ' ms-1');
      text(pill, label);
    };
    radios.forEach(r => r.addEventListener('change', sync));
    sync();
  }

  // ——— Contadores y habilitar botón ———
  function initCounters(root) {
    const inpTitle = q(root, 'input[name="encabezado"]');
    const inpBody  = q(root, 'textarea[name="comentario"]');
    const c1       = q(root, '.counter:nth-of-type(1)') || q(root, '#cntTitle');
    const c2       = q(root, '.counter:nth-of-type(2)') || q(root, '#cntBody');
    const btn      = q(root, 'form#formComentario button[type="submit"]');

    if (!inpTitle || !inpBody || !btn) return;

    const update = () => {
      if (c1) text(c1, `${(inpTitle.value || '').length}/45`);
      if (c2) text(c2, `${(inpBody.value  || '').length}/500`);
      btn.disabled = !(inpTitle.value.trim() && inpBody.value.trim());
    };
    inpTitle.addEventListener('input', update);
    inpBody .addEventListener('input', update);
    update();
  }

  // ——— Guardar comentario con fetch y recargar modal ———
  async function guardarComentario(form) {
    try {
      const btn = form.querySelector('button[type="submit"]');
      const original = btn ? btn.innerHTML : '';
      if (btn) { btn.disabled = true; btn.innerHTML = 'Guardando…'; }

      const res = await fetch(form.action, { method: 'POST', body: new FormData(form) });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json().catch(() => { throw new Error('JSON inválido'); });

      if (!data.success) {
        alert(data.message || 'No se pudo guardar');
        if (btn) { btn.disabled = false; btn.innerHTML = original; }
        return false; // evita navegación
      }

      const id   = form.querySelector('[name="Fk_registro"]').value;
      const base = window.LT_BASE || ''; // lo inyecta comentarios_modal.php
      const url  = `${base}/comentarios_modal.php?id=${encodeURIComponent(id)}&_=${Date.now()}`;

      // Reemplaza el contenido del modal (la .modal-dialog)
      const dlg = form.closest('.modal-dialog');
      if (dlg) {
        dlg.innerHTML = '<div class="modal-content"><div class="modal-body p-4 text-center">Actualizando…</div></div>';
        const html = await (await fetch(url, { cache: 'no-store' })).text();
        dlg.innerHTML = html; // Debe comenzar con <div class="modal-content"> (ya lo hace)
      }

      return false; // evita navegación
    } catch (err) {
      console.error('guardarComentario error:', err);
      alert('Error de red al guardar');
      return false; // evita navegación al JSON
    }
  }

  // ——— Filtro por etapa al hacer clic en el STEPPER ———
  function initStepperFilter(root) {
    const bar  = q(root, '#barEtapas');
    const btnAll = q(root, '#btnAll');
    const list = q(root, '#listaComentarios');
    if (!bar || !list) return;

    // Todos
    btnAll && btnAll.addEventListener('click', () => {
      qa(list, '.tl-item').forEach(it => { it.style.display = ''; });
      qa(bar, 'li.step').forEach(li => li.classList.remove('current'));
    });

    // Delegación: clic en <li class="step" data-id="...">
    bar.addEventListener('click', (e) => {
      const li = e.target.closest('li.step[data-id]');
      if (!li) return;
      const target = String(li.dataset.id || '');
      // marca visual
      qa(bar, 'li.step').forEach(s => s.classList.remove('current'));
      li.classList.add('current');
      // filtra timeline
      qa(list, '.tl-item').forEach(it => {
        const id = String(it.dataset.etapaId || '');
        it.style.display = (id === target) ? '' : 'none';
      });
    });
  }

  // ——— init principal (llámalo cada vez que se inserte el modal) ———
  function initComentariosModal(rootNode) {
    const root = rootNode && rootNode.querySelector ? rootNode : document;
    initEtiquetaPreview(root);
    initCounters(root);
    initStepperFilter(root);

    // Enlaza onsubmit si no lo hizo inline
    const form = q(root, 'form#formComentario');
    if (form) {
      form.addEventListener('submit', (ev) => {
        ev.preventDefault();
        guardarComentario(form);
      });
    }

    // Expone función global por si se usa en onsubmit=""
    window.guardarComentario = guardarComentario;
  }

  // expone init
  window.initComentariosModal = initComentariosModal;
})();
