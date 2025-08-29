// TEST SIMPLE - Sin referencias a routing
console.log('🧪 TEST: Iniciando mapa simple');

let testMap;
let testVisible = false;

function testInicializarMapa() {
    console.log('🧪 TEST: Creando mapa de prueba');
    
    try {
        const elemento = document.getElementById('map');
        if (!elemento) {
            console.error('❌ TEST: Elemento map no encontrado');
            return;
        }
        
        // Limpiar mapa anterior
        if (testMap) {
            testMap.remove();
            testMap = null;
        }
        
        // Crear mapa simple
        testMap = L.map('map').setView([4.5709, -74.2973], 6);
        
        // Agregar capa
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(testMap);
        
        // Agregar un marcador simple
        L.marker([4.5709, -74.2973])
            .addTo(testMap)
            .bindPopup('TEST: Mapa funcionando correctamente')
            .openPopup();
        
        console.log('✅ TEST: Mapa creado exitosamente');
        
    } catch (error) {
        console.error('❌ TEST: Error creando mapa:', error);
    }
}

// Event listener de prueba
document.addEventListener('DOMContentLoaded', function() {
    console.log('🧪 TEST: DOM cargado');
    
    const btn = document.getElementById('toggleMapBtn');
    if (btn) {
        btn.addEventListener('click', function() {
            console.log('🧪 TEST: Click en botón');
            
            const container = document.getElementById('mapContainer');
            
            if (!testVisible) {
                container.style.display = 'block';
                this.innerHTML = '<i class="fas fa-eye-slash me-2"></i>OCULTAR MAPA TEST';
                setTimeout(() => testInicializarMapa(), 100);
                testVisible = true;
            } else {
                container.style.display = 'none';
                this.innerHTML = '<i class="fas fa-map-marked-alt me-2"></i>VER MAPA TEST';
                if (testMap) {
                    testMap.remove();
                    testMap = null;
                }
                testVisible = false;
            }
        });
        console.log('✅ TEST: Event listener configurado');
    } else {
        console.error('❌ TEST: Botón no encontrado');
    }
});

console.log('📁 TEST: Archivo de prueba cargado');