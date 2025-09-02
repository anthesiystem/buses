// modal.js - Funcionalidad del modal
function abrirModal() {
  const form = document.getElementById('formRegistro');
  form.reset();
  form.ID.value = '';
  const helper = document.getElementById('helperEtapa');
  if (helper) helper.textContent = 'Seleccione una etapa para ver su porcentaje.';
  new bootstrap.Modal(document.getElementById('modalRegistro')).show();
}

function editar(datos) {
  const f = document.getElementById('formRegistro');
  f.reset();
  const campos = [
    'ID','Fk_dependencia','Fk_entidad','Fk_bus','Fk_motor_base','Fk_tecnologia',
    'Fk_estado_bus','Fk_categoria','Fk_etapa','fecha_inicio','fecha_migracion'
  ];
  campos.forEach(k => { if (k in datos && f[k]) f[k].value = datos[k] ?? ''; });

  const selEtapa = f.querySelector('#Fk_etapa');
  const helperEtapa = document.getElementById('helperEtapa');
  const opt = selEtapa?.selectedOptions?.[0];
  if (opt && opt.dataset.avance && helperEtapa) {
    helperEtapa.textContent = 'Porcentaje de etapa: ' + opt.dataset.avance + '%';
  } else if (helperEtapa) {
    helperEtapa.textContent = 'Seleccione una etapa para ver su porcentaje.';
  }

  new bootstrap.Modal(document.getElementById('modalRegistro')).show();
}

// Eventos del modal
document.addEventListener('DOMContentLoaded', () => {
  const hoy = new Date().toISOString().split('T')[0];
  const fi  = document.querySelector('[name="fecha_inicio"]');
  const fm  = document.querySelector('[name="fecha_migracion"]');
  if (fi) fi.max = hoy;
  if (fm) fm.max = hoy;

  const form         = document.getElementById('formRegistro');
  const selEstado    = form.querySelector('[name="Fk_estado_bus"]');
  const selCategoria = form.querySelector('[name="Fk_categoria"]');
  const selEtapa     = form.querySelector('#Fk_etapa');
  const helperEtapa  = document.getElementById('helperEtapa');

  selEtapa?.addEventListener('change', () => {
    const opt = selEtapa.selectedOptions[0];
    const pct = opt?.dataset?.avance ? parseInt(opt.dataset.avance, 10) : null;
    if (Number.isInteger(pct) && helperEtapa) {
      helperEtapa.textContent = 'Porcentaje de etapa: ' + pct + '%';
    } else if (helperEtapa) {
      helperEtapa.textContent = 'Seleccione una etapa para ver su porcentaje.';
    }
  });

  // Forzar Implementado/Productivos si etapa es 100%
  form.addEventListener('submit', (e) => {
    if (!form.checkValidity()) { form.reportValidity(); e.preventDefault(); return; }

    const optEtapa = selEtapa?.selectedOptions?.[0] || null;
    const etapaPct = optEtapa && optEtapa.dataset.avance ? parseInt(optEtapa.dataset.avance, 10) : 0;

    const textoEstado    = selEstado?.options[selEstado.selectedIndex]?.text?.trim() || '';
    const textoCategoria = selCategoria?.options[selCategoria.selectedIndex]?.text?.trim() || '';

    const findValueByText = (selectEl, txt) => {
      if (!selectEl) return null;
      const opt = Array.from(selectEl.options).find(o => (o.text || '').trim() === txt);
      return opt ? opt.value : null;
    };

    if (etapaPct === 100) {
      if (!/Implementado/i.test(textoEstado)) {
        const v = findValueByText(selEstado, 'Implementado');
        if (v) selEstado.value = v;
      }
      if (/^(Migraciones|Pruebas)$/i.test(textoCategoria)) {
        const v = findValueByText(selCategoria, 'Productivos');
        if (v) selCategoria.value = v;
      }
    }
  });
});

// Reglas dinámicas del modal
document.addEventListener('DOMContentLoaded', () => {
  const form         = document.getElementById('formRegistro');
  const selEstado    = form.querySelector('#Fk_estado_bus');
  const selCategoria = form.querySelector('#Fk_categoria');
  const selEtapa     = form.querySelector('#Fk_etapa');
  const fi           = form.querySelector('[name="fecha_inicio"]');
  const fm           = form.querySelector('[name="fecha_migracion"]');
  const helperEtapa  = document.getElementById('helperEtapa');
  const hintFmig     = document.getElementById('hintFmig');

  const getText = (selectEl) => selectEl?.options[selectEl.selectedIndex]?.text?.trim() || '';

  const setDisabled = (el, disabled) => {
    if (!el) return;
    el.disabled = !!disabled;
    const wrap = el.closest('.col-md-6, .col-md-4') || el.parentElement;
    if (wrap) wrap.classList.toggle('is-disabled', !!disabled);
  };

  selEtapa?.addEventListener('change', () => {
    const opt = selEtapa.selectedOptions[0];
    const pct = opt?.dataset?.avance ? parseInt(opt.dataset.avance, 10) : null;
    if (Number.isInteger(pct) && helperEtapa) {
      helperEtapa.textContent = 'Porcentaje de etapa: ' + pct + '%';
    } else if (helperEtapa) {
      helperEtapa.textContent = 'Seleccione una etapa para ver su porcentaje.';
    }
  });

  const pickEtapa100 = () => {
    const opt100 = Array.from(selEtapa.options).find(o => (o.dataset?.avance|0) === 100);
    return opt100 || null;
  };

  const applyEstadoRules = () => {
    const txt = getText(selEstado).toLowerCase();

    setDisabled(selEtapa, false);
    setDisabled(fm, false);
    if (hintFmig) hintFmig.textContent = 'Opcional según estatus';

    if (/sin implementar/.test(txt)) {
      selEtapa.value = '';
      fm.value = '';
      setDisabled(selEtapa, true);
      setDisabled(fm, true);
      if (helperEtapa) helperEtapa.textContent = 'Indisponible en "Sin implementar".';
      if (hintFmig)    hintFmig.textContent    = 'Indisponible en "Sin implementar".';
      return;
    }

    if (/prueba/.test(txt)) {
      setDisabled(selEtapa, false);
      setDisabled(fm, false);
      const opt = selEtapa.selectedOptions[0];
      const pct = opt?.dataset?.avance ? parseInt(opt.dataset.avance,10) : null;
      if (pct === 100) {
        selEtapa.value = '';
        if (helperEtapa) helperEtapa.textContent = 'En "En pruebas" la etapa no puede ser 100%.';
      }
      return;
    }

    if (/implementado/.test(txt)) {
      const opt100 = pickEtapa100();
      if (opt100) selEtapa.value = opt100.value;
      setDisabled(selEtapa, true);
      if (helperEtapa) helperEtapa.textContent = 'Etapa fijada a Implementado (100%).';
      return;
    }
  };

  selEstado?.addEventListener('change', applyEstadoRules);
  applyEstadoRules();
});

// Manejo de overlays y AJAX
(function() {
  const form = document.getElementById('formRegistro');
  const overlayCargando = document.getElementById('cargando');
  const overlayExito = document.getElementById('guardadoExitoAnimado');

  if (form && overlayCargando) {
    form.addEventListener('submit', async function(e) {
      e.preventDefault();
      
      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      const btns = form.querySelectorAll('button, input[type="submit"]');
      btns.forEach(b => b.disabled = true);
      overlayCargando.style.display = 'block';

      try {
        const fd = new FormData(form);
        fd.set('ajax', '1');

        const esCargaDinamica = window.parent !== window || document.getElementById('main-content');
        const submitUrl = esCargaDinamica ? 'sections/registros/actions.php' : 'actions.php';

        const resp = await fetch(submitUrl, {
          method: 'POST',
          body: fd,
          headers: { 
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        if (!resp.ok) {
          throw new Error('Error del servidor: ' + resp.status);
        }

        const data = await resp.json();

        if (data.ok) {
          const modal = bootstrap.Modal.getInstance(document.getElementById('modalRegistro'));
          modal && modal.hide();

          if (overlayExito) {
            if (data.status === 'updated') {
              overlayExito.querySelector('div:last-child').textContent = 'Actualizado exitosamente';
            }
            overlayExito.style.display = 'block';
            setTimeout(() => {
              overlayExito.style.display = 'none';
            }, 1200);
          }

          cargarDatos();
        } else {
          alert(data.msg || 'No se pudo guardar.');
        }
      } catch (err) {
        console.error('Error:', err);
        alert('Error de conexión: ' + err.message);
      } finally {
        overlayCargando.style.display = 'none';
        btns.forEach(b => b.disabled = false);
      }
    });
  }

  const params = new URLSearchParams(window.location.search);
  const ok = params.get('ok');
  if (ok && overlayExito) {
    if (ok === 'updated') {
      overlayExito.querySelector('div:last-child').textContent = 'Actualizado exitosamente';
    }
    overlayExito.style.display = 'block';
    setTimeout(() => {
      overlayExito.style.display = 'none';
      const url = new URL(window.location.href);
      url.searchParams.delete('ok');
      window.history.replaceState({}, document.title, url.toString());
    }, 1200);
  }
})();

// Exponer funciones globalmente
window.abrirModal = abrirModal;
window.editar = editar;
