let map;
let mapHistorial;
let isMapVisible = false;
let isMapHistorialVisible = false;

// Variables para marcadores y rutas
let markers = [];
let routeControl = null;
let markersHistorial = [];
let routeControlHistorial = null;

// Función para limpiar marcadores y rutas
function limpiarMapa(mapaInstance, marcadores, control) {
    if (marcadores && marcadores.length > 0) {
        marcadores.forEach(marker => {
            if (marker && mapaInstance.hasLayer(marker)) {
                mapaInstance.removeLayer(marker);
            }
        });
        marcadores.length = 0;
    }
    
    if (control && mapaInstance.hasLayer(control)) {
        mapaInstance.removeControl(control);
    }
}

// Función para esperar que el elemento sea visible
function esperarElementoVisible(elementId, callback, maxIntentos = 30) {
    let intentos = 0;
    
    const verificar = () => {
        intentos++;
        const elemento = document.getElementById(elementId);
        
        if (elemento && elemento.offsetHeight > 0 && elemento.offsetWidth > 0) {
            console.log(`Elemento ${elementId} visible en intento ${intentos}`);
            setTimeout(() => callback(elemento), 50);
            return;
        }
        
        if (intentos < maxIntentos) {
            setTimeout(verificar, 100);
        } else {
            console.error(`Elemento ${elementId} no visible después de ${maxIntentos} intentos`);
        }
    };
    
    verificar();
}

// Inicializar el mapa principal (Tab Detalles)
function inicializarMapa() {
    console.log('Inicializando mapa principal con OpenStreetMap...');
    
    if (!window.envioData) {
        console.error('No hay datos disponibles para el mapa');
        return;
    }
    
    esperarElementoVisible('map', function(mapElement) {
        try {
            // Limpiar mapa anterior si existe
            if (map) {
                limpiarMapa(map, markers, routeControl);
                map.remove();
            }
            
            // Crear nuevo mapa centrado en Colombia
            map = L.map('map').setView([4.5709, -74.2973], 6);
            
            // Agregar capa de OpenStreetMap
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(map);
            
            console.log('Mapa principal inicializado correctamente');
            
            // Cargar datos en el mapa
            setTimeout(() => {
                cargarDatosEnMapa(map, 'principal');
            }, 100);
            
        } catch (error) {
            console.error('Error inicializando mapa:', error);
            mostrarErrorMapa('map');
        }
    });
}

// Inicializar el mapa del historial (Tab Historial)
function inicializarMapaHistorial() {
    console.log('Inicializando mapa historial con OpenStreetMap...');
    
    if (!window.envioData) {
        console.error('No hay datos disponibles para el mapa');
        return;
    }
    
    esperarElementoVisible('mapHistorial', function(mapElement) {
        try {
            // Limpiar mapa anterior si existe
            if (mapHistorial) {
                limpiarMapa(mapHistorial, markersHistorial, routeControlHistorial);
                mapHistorial.remove();
            }
            
            // Crear nuevo mapa centrado en Colombia
            mapHistorial = L.map('mapHistorial').setView([4.5709, -74.2973], 6);
            
            // Agregar capa de OpenStreetMap
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(mapHistorial);
            
            console.log('Mapa historial inicializado correctamente');
            
            // Cargar datos en el mapa
            setTimeout(() => {
                cargarDatosEnMapa(mapHistorial, 'historial');
            }, 100);
            
        } catch (error) {
            console.error('Error inicializando mapa historial:', error);
            mostrarErrorMapa('mapHistorial');
        }
    });
}

// Cargar datos en el mapa
function cargarDatosEnMapa(mapaInstance, tipo) {
    console.log('Cargando datos en mapa:', window.envioData);
    
    if (window.envioData.ciudadOrigen && window.envioData.ciudadDestino) {
        console.log('Calculando ruta entre:', window.envioData.ciudadOrigen, '→', window.envioData.ciudadDestino);
        calcularRuta(mapaInstance, tipo);
    } else if (window.envioData.movimientos && window.envioData.movimientos.length > 0) {
        console.log('Mostrando movimientos en mapa');
        mostrarMovimientos(mapaInstance, tipo);
    } else {
        console.log('No hay datos suficientes para mostrar en el mapa');
        mostrarMensajeNoData(mapaInstance);
    }
}

// Calcular ruta entre origen y destino
function calcularRuta(mapaInstance, tipo) {
    const origen = window.envioData.ciudadOrigen;
    const destino = window.envioData.ciudadDestino;
    
    console.log('Buscando coordenadas para:', origen, 'y', destino);
    
    // Usar Nominatim para geocodificación (servicio gratuito basado en OpenStreetMap)
    Promise.all([
        buscarCoordenadas(origen + ', Colombia'),
        buscarCoordenadas(destino + ', Colombia')
    ]).then(([coordOrigen, coordDestino]) => {
        if (coordOrigen && coordDestino) {
            // Crear marcadores
            const marcadorOrigen = crearMarcador(coordOrigen, origen, 'origen');
            const marcadorDestino = crearMarcador(coordDestino, destino, 'destino');
            
            marcadorOrigen.addTo(mapaInstance);
            marcadorDestino.addTo(mapaInstance);
            
            // Guardar marcadores según el tipo de mapa
            if (tipo === 'principal') {
                markers.push(marcadorOrigen, marcadorDestino);
            } else {
                markersHistorial.push(marcadorOrigen, marcadorDestino);
            }
            
            // Crear ruta
            const control = L.Routing.control({
                waypoints: [
                    L.latLng(coordOrigen.lat, coordOrigen.lng),
                    L.latLng(coordDestino.lat, coordDestino.lng)
                ],
                routeWhileDragging: false,
                addWaypoints: false,
                createMarker: function() { return null; }, // No crear marcadores automáticos
                lineOptions: {
                    styles: [{ color: '#3388ff', weight: 4, opacity: 0.7 }]
                }
            }).addTo(mapaInstance);
            
            // Guardar control de ruta
            if (tipo === 'principal') {
                routeControl = control;
            } else {
                routeControlHistorial = control;
            }
            
            // Ajustar vista para mostrar toda la ruta
            const group = new L.featureGroup([marcadorOrigen, marcadorDestino]);
            mapaInstance.fitBounds(group.getBounds().pad(0.1));
            
            console.log('Ruta creada exitosamente');
        } else {
            console.log('No se pudieron obtener coordenadas, mostrando marcadores individuales');
            mostrarCiudadesSeparadas(mapaInstance, tipo);
        }
    }).catch(error => {
        console.error('Error al calcular ruta:', error);
        mostrarCiudadesSeparadas(mapaInstance, tipo);
    });
}

// Buscar coordenadas usando Nominatim (gratis)
async function buscarCoordenadas(direccion) {
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(direccion)}&limit=1`);
        const data = await response.json();
        
        if (data && data.length > 0) {
            return {
                lat: parseFloat(data[0].lat),
                lng: parseFloat(data[0].lon)
            };
        }
        return null;
    } catch (error) {
        console.error('Error en geocodificación:', error);
        return null;
    }
}

// Crear marcador personalizado
function crearMarcador(coordenadas, titulo, tipo) {
    let iconUrl, color;
    
    switch(tipo) {
        case 'origen':
            iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png';
            color = 'green';
            break;
        case 'destino':
            // Color según estado del envío
            switch(window.envioData.estadoActual) {
                case '3': // Entregado
                    iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png';
                    color = 'green';
                    break;
                case '4': // Devuelto
                    iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-orange.png';
                    color = 'orange';
                    break;
                case '5': // Siniestrado
                    iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png';
                    color = 'red';
                    break;
                default: // En proceso
                    iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-yellow.png';
                    color = 'yellow';
            }
            break;
        default:
            iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png';
            color = 'blue';
    }
    
    const customIcon = L.icon({
        iconUrl: iconUrl,
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });
    
    let emoji = '';
    let estado = '';
    
    if (tipo === 'destino') {
        switch(window.envioData.estadoActual) {
            case '3':
                emoji = '✅';
                estado = ' - Entregado';
                break;
            case '4':
                emoji = '↩️';
                estado = ' - Devuelto';
                break;
            case '5':
                emoji = '❌';
                estado = ' - Siniestrado';
                break;
            default:
                emoji = '🚛';
                estado = ' - En proceso';
        }
    } else {
        emoji = '📍';
    }
    
    return L.marker([coordenadas.lat, coordenadas.lng], { icon: customIcon })
        .bindPopup(`<b>${emoji} ${titulo}${estado}</b>`);
}

// Mostrar ciudades por separado si no se puede calcular ruta
function mostrarCiudadesSeparadas(mapaInstance, tipo) {
    console.log('Mostrando ciudades por separado');
    
    const promesas = [];
    
    if (window.envioData.ciudadOrigen) {
        promesas.push(
            buscarCoordenadas(window.envioData.ciudadOrigen + ', Colombia')
                .then(coord => coord ? { tipo: 'origen', coord, nombre: window.envioData.ciudadOrigen } : null)
        );
    }
    
    if (window.envioData.ciudadDestino) {
        promesas.push(
            buscarCoordenadas(window.envioData.ciudadDestino + ', Colombia')
                .then(coord => coord ? { tipo: 'destino', coord, nombre: window.envioData.ciudadDestino } : null)
        );
    }
    
    Promise.all(promesas).then(resultados => {
        const marcadoresCreados = [];
        
        resultados.forEach(resultado => {
            if (resultado) {
                const marcador = crearMarcador(resultado.coord, resultado.nombre, resultado.tipo);
                marcador.addTo(mapaInstance);
                marcadoresCreados.push(marcador);
                
                // Guardar marcadores según el tipo de mapa
                if (tipo === 'principal') {
                    markers.push(marcador);
                } else {
                    markersHistorial.push(marcador);
                }
            }
        });
        
        // Ajustar vista si hay marcadores
        if (marcadoresCreados.length > 0) {
            const group = new L.featureGroup(marcadoresCreados);
            mapaInstance.fitBounds(group.getBounds().pad(0.1));
        }
    });
}

// Mostrar movimientos en el mapa
function mostrarMovimientos(mapaInstance, tipo) {
    if (!window.envioData.movimientos || window.envioData.movimientos.length === 0) {
        console.log('No hay movimientos para mostrar');
        return;
    }
    
    console.log('Procesando', window.envioData.movimientos.length, 'movimientos');
    
    const promesasMovimientos = window.envioData.movimientos.map((movimiento, index) => {
        const ciudad = movimiento.DesMov || movimiento.OriMov;
        if (ciudad && typeof ciudad === 'string') {
            return buscarCoordenadas(ciudad + ', Colombia')
                .then(coord => coord ? { coord, movimiento, ciudad, index } : null);
        }
        return Promise.resolve(null);
    });
    
    Promise.all(promesasMovimientos).then(resultados => {
        const marcadoresCreados = [];
        
        resultados.forEach(resultado => {
            if (resultado) {
                const marcador = L.marker([resultado.coord.lat, resultado.coord.lng])
                    .bindPopup(`
                        <b> ${resultado.movimiento.NomMov || 'Movimiento'}</b><br>
                         ${resultado.ciudad}<br>
                         ${resultado.movimiento.FecMov || 'Sin fecha'}
                    `);
                
                marcador.addTo(mapaInstance);
                marcadoresCreados.push(marcador);
                
                // Guardar marcadores según el tipo de mapa
                if (tipo === 'principal') {
                    markers.push(marcador);
                } else {
                    markersHistorial.push(marcador);
                }
            }
        });
        
        // Ajustar vista si hay marcadores
        if (marcadoresCreados.length > 0) {
            const group = new L.featureGroup(marcadoresCreados);
            mapaInstance.fitBounds(group.getBounds().pad(0.1));
        }
    });
}

// Mostrar mensaje cuando no hay datos
function mostrarMensajeNoData(mapaInstance) {
    const popup = L.popup()
        .setLatLng(mapaInstance.getCenter())
        .setContent(`
            <div style="text-align: center; padding: 15px;">
                <i class="fas fa-map-marker-alt" style="font-size: 2rem; color: #6c757d; margin-bottom: 10px;"></i>
                <h6><strong>No hay datos de ubicación</strong></h6>
                <p style="margin: 0; color: #6c757d;">No se encontraron ciudades para mostrar en el mapa.</p>
            </div>
        `)
        .openOn(mapaInstance);
}

// Mostrar error en el mapa
function mostrarErrorMapa(containerId) {
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = `
            <div style="height: 400px; background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 8px; display: flex; align-items: center; justify-content: center; text-align: center; color: #6c757d;">
                <div>
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 15px;"></i>
                    <h5>Error al cargar el mapa</h5>
                    <p>No se pudo inicializar el mapa. Inténtalo de nuevo.</p>
                </div>
            </div>
        `;
    }
}

// Event listeners cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, configurando event listeners para OpenStreetMap');
    
    try {
        // Botón del mapa principal
        const toggleBtn = document.getElementById('toggleMapBtn');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                try {
                    const mapContainer = document.getElementById('mapContainer');
                    const btn = this;
                    
                    console.log('Click en botón mapa principal. Estado actual:', isMapVisible);
                    
                    if (!isMapVisible) {
                        mapContainer.style.display = 'block';
                        btn.innerHTML = '<i class="fas fa-eye-slash me-2"></i>OCULTAR MAPA';
                        btn.classList.add('btn-active');
                        
                        // Inicializar mapa
                        setTimeout(() => {
                            inicializarMapa();
                        }, 100);
                        
                        isMapVisible = true;
                    } else {
                        mapContainer.style.display = 'none';
                        btn.innerHTML = '<i class="fas fa-map-marked-alt me-2"></i>VER MAPA';
                        btn.classList.remove('btn-active');
                        
                        // Limpiar mapa
                        if (map) {
                            limpiarMapa(map, markers, routeControl);
                        }
                        
                        isMapVisible = false;
                    }
                } catch (error) {
                    console.error('Error en click mapa principal:', error);
                }
            });
            console.log('Event listener para mapa principal configurado');
        }

        // Botón del mapa historial
        const toggleBtnHistorial = document.getElementById('toggleMapBtnHistorial');
        if (toggleBtnHistorial) {
            toggleBtnHistorial.addEventListener('click', function() {
                try {
                    const mapContainer = document.getElementById('mapContainerHistorial');
                    const btn = this;
                    
                    console.log('Click en botón mapa historial. Estado actual:', isMapHistorialVisible);
                    
                    if (!isMapHistorialVisible) {
                        mapContainer.style.display = 'block';
                        btn.innerHTML = '<i class="fas fa-eye-slash me-2"></i>OCULTAR MAPA';
                        btn.classList.add('btn-active');
                        
                        // Inicializar mapa historial
                        setTimeout(() => {
                            inicializarMapaHistorial();
                        }, 100);
                        
                        isMapHistorialVisible = true;
                    } else {
                        mapContainer.style.display = 'none';
                        btn.innerHTML = '<i class="fas fa-map-marked-alt me-2"></i>VER MAPA';
                        btn.classList.remove('btn-active');
                        
                        // Limpiar mapa historial
                        if (mapHistorial) {
                            limpiarMapa(mapHistorial, markersHistorial, routeControlHistorial);
                        }
                        
                        isMapHistorialVisible = false;
                    }
                } catch (error) {
                    console.error('Error en click mapa historial:', error);
                }
            });
            console.log('Event listener para mapa historial configurado');
        }
        
    } catch (error) {
        console.error('Error configurando event listeners:', error);
    }
});