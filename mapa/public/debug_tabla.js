// Función de debugging para verificar la estructura de datos
function debugTablaModal() {
    console.log("🔍 DEBUGGING TABLA MODAL");
    console.log("=====================================");
    
    // Verificar si el modal existe
    const modal = document.getElementById("modalDetalles");
    if (!modal) {
        console.error("❌ No se encontró el modal #modalDetalles");
        return;
    }
    
    // Verificar la tabla
    const tabla = modal.querySelector("table tbody");
    if (!tabla) {
        console.error("❌ No se encontró la tabla en el modal");
        return;
    }
    
    const filas = tabla.querySelectorAll("tr");
    console.log(`📊 Filas encontradas: ${filas.length}`);
    
    if (filas.length === 0) {
        console.warn("⚠️ La tabla está vacía. ¿Está abierto el modal con datos?");
        return;
    }
    
    // Mostrar las primeras 3 filas como ejemplo
    console.log("\n🔍 Estructura de las primeras filas:");
    filas.forEach((fila, indexFila) => {
        if (indexFila < 3) {
            console.log(`\nFila ${indexFila + 1}:`);
            const celdas = fila.querySelectorAll("td");
            celdas.forEach((celda, indexCelda) => {
                console.log(`  [${indexCelda}] ${celda.innerText.trim()}`);
            });
        }
    });
    
    // Verificar buses únicos
    const busesSet = new Set();
    filas.forEach(fila => {
        const bus = fila.children[1]?.innerText.trim(); // Columna 1 para bus
        if (bus) busesSet.add(bus);
    });
    
    console.log(`\n🚌 Buses únicos encontrados (${busesSet.size}):`);
    Array.from(busesSet).forEach(bus => console.log(`  - ${bus}`));
    
    // Verificar estatus únicos
    const estatusSet = new Set();
    filas.forEach(fila => {
        const estatus = fila.children[8]?.innerText.trim(); // Columna 8 para estatus
        if (estatus) estatusSet.add(estatus);
    });
    
    console.log(`\n📋 Estatus únicos encontrados (${estatusSet.size}):`);
    Array.from(estatusSet).forEach(estatus => console.log(`  - ${estatus}`));
    
    // Verificar el catálogo de buses
    const script = document.getElementById("mapaScript");
    if (script && script.dataset.catalogoBuses) {
        try {
            const catalogo = JSON.parse(script.dataset.catalogoBuses);
            console.log(`\n📚 Catálogo completo (${catalogo.length} buses):`, catalogo);
        } catch (error) {
            console.error("❌ Error al parsear catálogo:", error);
        }
    } else {
        console.warn("\n⚠️ No se encontró el catálogo de buses en #mapaScript");
    }
    
    console.log("\n✅ Debug completado");
}

// Hacer disponible globalmente
window.debugTablaModal = debugTablaModal;
console.log("💡 Función disponible: debugTablaModal()");
