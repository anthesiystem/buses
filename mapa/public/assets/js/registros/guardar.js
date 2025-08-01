// /js/registros/guardar.js

import { cargarRegistrosDesdeJSON } from './filtros.js';

export function inicializarGuardado() {



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
        console.log("Respuesta:", resp);
        if (resp.success === true || resp.success === "true") {
          bootstrap.Modal.getInstance(document.getElementById("modalRegistro")).hide();

          const fueNuevo = !form.querySelector('[name="ID"]').value;
          const mensaje = fueNuevo
            ? "✅ Registro creado exitosamente"
            : "✅ Registro actualizado exitosamente";

          document.getElementById("mensajeToast").textContent = mensaje;
          const toast = new bootstrap.Toast(document.getElementById("toastExito"));
          toast.show();

          form.reset();
          cargarRegistrosDesdeJSON(); // recargar tabla con los filtros actuales
        
        
        
                    // Mostrar animación central
            const animacion = document.getElementById("guardadoExitoAnimado");
            if (animacion) {
              animacion.classList.remove("oculto");
              animacion.style.display = "block";
                setTimeout(() => {
                    animacion.classList.add("oculto");
                    setTimeout(() => animacion.style.display = "none", 500); // espera que se desvanezca
                  }, 2500);
                }

        
        
        
        
        
        } else {
          alert("Error al guardar: " + resp.error);
        }
      })
      .catch(err => {
        console.error("Error al guardar:", err);
        alert("Error al guardar");
      });
  });
}
