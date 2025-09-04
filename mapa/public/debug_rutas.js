// Script de debug para probar rutas de imágenes
console.log("🔧 Iniciando debug de rutas de imágenes...");
console.log("📍 URL actual:", window.location.href);
console.log("📁 Origin:", window.location.origin);

// Función de prueba para verificar rutas
async function probarRuta(ruta, descripcion) {
    try {
        console.log(`🔍 Probando: ${descripcion} - ${ruta}`);
        const response = await fetch(ruta);
        if (response.ok) {
            console.log(`✅ ${descripcion}: OK (${response.status})`);
            return true;
        } else {
            console.log(`❌ ${descripcion}: Error ${response.status}`);
            return false;
        }
    } catch (error) {
        console.log(`❌ ${descripcion}: ${error.message}`);
        return false;
    }
}

// Probar diferentes rutas para un estado
async function probarRutasEstado(estado = "Aguascalientes") {
    console.log(`\n🏛️ Probando rutas para: ${estado}`);
    
    const rutasEscudo = [
        `./img/escudos/${estado}.png`,
        `../img/escudos/${estado}.png`,
        `../../img/escudos/${estado}.png`,
        `../../../img/escudos/${estado}.png`,
        `/final/mapa/public/img/escudos/${estado}.png`
    ];
    
    const rutasMapa = [
        `./img/mapa_estados/${estado}.png`,
        `../img/mapa_estados/${estado}.png`,
        `../../img/mapa_estados/${estado}.png`,
        `../../../img/mapa_estados/${estado}.png`,
        `/final/mapa/public/img/mapa_estados/${estado}.png`
    ];
    
    console.log("\n🛡️ Probando escudos:");
    for (const ruta of rutasEscudo) {
        await probarRuta(ruta, `Escudo ${estado}`);
    }
    
    console.log("\n🗺️ Probando mapas:");
    for (const ruta of rutasMapa) {
        await probarRuta(ruta, `Mapa ${estado}`);
    }
}

// Probar plantillas
async function probarPlantillas() {
    console.log("\n📄 Probando plantillas:");
    
    const rutasPlantillas = [
        './img/hojaplantilla.png',
        '../img/hojaplantilla.png',
        '../../img/hojaplantilla.png',
        '../../../img/hojaplantilla.png',
        '/final/mapa/public/img/hojaplantilla.png',
        './img/hojaplantillahorizontal.png',
        '../img/hojaplantillahorizontal.png',
        '../../img/hojaplantillahorizontal.png',
        '../../../img/hojaplantillahorizontal.png',
        '/final/mapa/public/img/hojaplantillahorizontal.png'
    ];
    
    for (const ruta of rutasPlantillas) {
        await probarRuta(ruta, "Plantilla");
    }
}

// Ejecutar pruebas
async function ejecutarPruebas() {
    await probarRutasEstado("Aguascalientes");
    await probarPlantillas();
    console.log("\n✅ Pruebas completadas");
}

// Hacer disponibles las funciones globalmente
window.probarRutasEstado = probarRutasEstado;
window.probarPlantillas = probarPlantillas;
window.ejecutarPruebas = ejecutarPruebas;

console.log("💡 Funciones disponibles:");
console.log("  - ejecutarPruebas()");
console.log("  - probarRutasEstado('NombreEstado')");
console.log("  - probarPlantillas()");
