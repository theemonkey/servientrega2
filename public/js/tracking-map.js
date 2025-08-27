let map;
let mapHistorial;
let directionsService;
let directionsRenderer;
let directionsRendererHistorial;
let isMapVisible = false;
let isMapHistorialVisible = false;

// Variables para simulación
let isSimulationMode = false;

// Función llamada por Google Maps API
function initMap() {
    console.log('Google Maps API cargada correctamente');
    
    // Verificar que todas las APIs necesarias estén disponibles
    if (!google.maps.DirectionsService) {
        console.error('Directions API no está habilitada - Activando modo simulación');
        isSimulationMode = true;
        return;
    }
    
    if (!google.maps.Geocoder) {
        console.error('Geocoding API no está habilitada - Activando modo simulación');
        isSimulationMode = true;
        return;
    }
    
    console.log('Todas las APIs necesarias están disponibles');
}

// Mostrar simulación de mapa cuando hay errores de API
function mostrarSimulacionMapa(containerId) {
    const container = document.getElementById(containerId);
    if (container) {
        const ciudadOrigen = window.envioData.ciudadOrigen || 'Ciudad Origen';
        const ciudadDestino = window.envioData.ciudadDestino || 'Ciudad Destino';
        const estado = window.envioData.estadoActual || '2';
        
        // Determinar color y emoji según estado
        let estadoInfo = {
            color: '#ffc107',
            emoji: '🚛',
            texto: 'En Proceso'
        };
        
        switch(estado) {
            case '3':
                estadoInfo = { color: '#28a745', emoji: '✅', texto: 'Entregado' };
                break;
            case '4':
                estadoInfo = { color: '#fd7e14', emoji: '↩️', texto: 'Devuelto' };
                break;
            case '5':
                estadoInfo = { color: '#dc3545', emoji: '❌', texto: 'Siniestrado' };
                break;
        }
        
        container.innerHTML = `
            <div style="height: 400px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; position: relative; overflow: hidden;">
                <!-- Fondo de mapa simulado -->
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="%23ffffff" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg></div>
                
                <!-- Contenido del mapa -->
                <div style="position: relative; z-index: 2; height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; color: white; padding: 20px;">
                    
                    <!-- Título -->
                    <div style="background: rgba(0,0,0,0.6); padding: 15px 25px; border-radius: 10px; margin-bottom: 30px;">
                        <h5 style="margin: 0; color: white;">
                            <i class="fas fa-map-marked-alt me-2"></i>
                            Vista de Ruta - Modo Demostración
                        </h5>
                    </div>
                    
                    <!-- Ruta visual -->
                    <div style="display: flex; align-items: center; justify-content: center; gap: 20px; flex-wrap: wrap; margin-bottom: 20px;">
                        
                        <!-- Origen -->
                        <div style="text-align: center;">
                            <div style="width: 40px; height: 40px; background: #28a745; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; box-shadow: 0 4px 15px rgba(40,167,69,0.4);">
                                <i class="fas fa-play" style="color: white; font-size: 14px; margin-left: 2px;"></i>
                            </div>
                            <div style="font-weight: bold; font-size: 14px;">${ciudadOrigen}</div>
                            <div style="font-size: 12px; opacity: 0.8;">Origen</div>
                        </div>
                        
                        <!-- Línea de conexión -->
                        <div style="flex: 1; min-width: 100px; max-width: 200px; height: 3px; background: linear-gradient(to right, #28a745, ${estadoInfo.color}); border-radius: 3px; position: relative;">
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-truck" style="color: #666; font-size: 10px;"></i>
                            </div>
                        </div>
                        
                        <!-- Destino -->
                        <div style="text-align: center;">
                            <div style="width: 40px; height: 40px; background: ${estadoInfo.color}; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                                <span style="font-size: 18px;">${estadoInfo.emoji}</span>
                            </div>
                            <div style="font-weight: bold; font-size: 14px;">${ciudadDestino}</div>
                            <div style="font-size: 12px; opacity: 0.8;">Destino - ${estadoInfo.texto}</div>
                        </div>
                    </div>
                    
                    <!-- Información adicional -->
                    <div style="background: rgba(0,0,0,0.4); padding: 15px; border-radius: 8px; font-size: 13px; max-width: 400px;">
                        <div style="margin-bottom: 8px;">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Estado actual:</strong> 
                            <span style="color: ${estadoInfo.color}; font-weight: bold;">${estadoInfo.texto}</span>
                        </div>
                        <div style="opacity: 0.8;">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Vista simulada - Se activará el mapa real cuando se configure la API Key de Google Maps
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        console.log('Simulación de mapa cargada para:', containerId);
    }
}

// Mostrar error de API en los contenedores
function showApiError(message) {
    const mapContainers = ['map', 'mapHistorial'];
    mapContainers.forEach(containerId => {
        mostrarSimulacionMapa(containerId);
    });
}

// Función SIMPLIFICADA para esperar elemento visible
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
    console.log('Inicializando mapa principal...');
    
    if (!window.envioData) {
        console.error('No hay datos disponibles para el mapa');
        return;
    }
    
    // Si hay errores de API, mostrar simulación
    if (isSimulationMode) {
        console.log('Modo simulación activado para mapa principal');
        mostrarSimulacionMapa('map');
        return;
    }
    
    esperarElementoVisible('map', function(mapElement) {
        try {
            const center = { lat: 4.5709, lng: -74.2973 };
            
            map = new google.maps.Map(mapElement, {
                zoom: 6,
                center: center,
                mapTypeControl: true,
                streetViewControl: false,
                fullscreenControl: true,
            });

            if (google.maps.DirectionsService && google.maps.DirectionsRenderer) {
                if (!directionsService) {
                    directionsService = new google.maps.DirectionsService();
                }
                directionsRenderer = new google.maps.DirectionsRenderer({
                    draggable: false,
                    panel: null
                });
                directionsRenderer.setMap(map);
            }

            console.log('Mapa principal inicializado correctamente');
            
            setTimeout(() => {
                cargarDatosEnMapa(map, directionsRenderer);
            }, 100);
            
        } catch (error) {
            console.error('Error inicializando mapa:', error);
            // Si hay error, activar modo simulación
            isSimulationMode = true;
            mostrarSimulacionMapa('map');
        }
    });
}

// Inicializar el mapa del historial (Tab Historial)
function inicializarMapaHistorial() {
    console.log('Inicializando mapa historial...');
    
    if (!window.envioData) {
        console.error('No hay datos disponibles para el mapa');
        return;
    }
    
    // Si hay errores de API, mostrar simulación
    if (isSimulationMode) {
        console.log('Modo simulación activado para mapa historial');
        mostrarSimulacionMapa('mapHistorial');
        return;
    }
    
    esperarElementoVisible('mapHistorial', function(mapElement) {
        try {
            const center = { lat: 4.5709, lng: -74.2973 };
            
            mapHistorial = new google.maps.Map(mapElement, {
                zoom: 6,
                center: center,
                mapTypeControl: true,
                streetViewControl: false,
                fullscreenControl: true,
            });

            if (!directionsService && google.maps.DirectionsService) {
                directionsService = new google.maps.DirectionsService();
            }
            
            if (google.maps.DirectionsRenderer) {
                directionsRendererHistorial = new google.maps.DirectionsRenderer({
                    draggable: false,
                    panel: null
                });
                directionsRendererHistorial.setMap(mapHistorial);
            }

            console.log('Mapa historial inicializado correctamente');
            
            setTimeout(() => {
                cargarDatosEnMapa(mapHistorial, directionsRendererHistorial);
            }, 100);
            
        } catch (error) {
            console.error('Error inicializando mapa historial:', error);
            // Si hay error, activar modo simulación
            isSimulationMode = true;
            mostrarSimulacionMapa('mapHistorial');
        }
    });
}

// Resto de funciones... (cargarDatosEnMapa, calcularRuta, etc.)
function cargarDatosEnMapa(mapaInstance, renderer) {
    console.log('Cargando datos en mapa:', window.envioData);
    
    if (window.envioData.ciudadOrigen && window.envioData.ciudadDestino) {
        console.log('Calculando ruta entre:', window.envioData.ciudadOrigen, '→', window.envioData.ciudadDestino);
        
        if (directionsService && renderer) {
            calcularRuta(mapaInstance, renderer);
        } else {
            console.log('Directions API no disponible, mostrando ciudades separadas');
            mostrarCiudadesSeparadas(mapaInstance);
        }
    } else if (window.envioData.movimientos && window.envioData.movimientos.length > 0) {
        console.log('Mostrando movimientos en mapa');
        mostrarMovimientos(mapaInstance);
    } else {
        console.log('No hay datos suficientes para mostrar en el mapa');
        mostrarMensajeNoData(mapaInstance);
    }
}

function mostrarMensajeNoData(mapaInstance) {
    const infoWindow = new google.maps.InfoWindow({
        content: `
            <div style="padding: 15px; text-align: center;">
                <i class="fas fa-map-marker-alt" style="font-size: 2rem; color: #6c757d; margin-bottom: 10px;"></i>
                <h6><strong>No hay datos de ubicación</strong></h6>
                <p style="margin: 0; color: #6c757d;">No se encontraron ciudades para mostrar en el mapa.</p>
            </div>
        `,
        position: mapaInstance.getCenter()
    });
    infoWindow.open(mapaInstance);
}

function calcularRuta(mapaInstance, renderer) {
    const origen = window.envioData.ciudadOrigen + ', Colombia';
    const destino = window.envioData.ciudadDestino + ', Colombia';
    
    console.log('Solicitando ruta desde:', origen, 'hasta:', destino);
    
    directionsService.route({
        origin: origen,
        destination: destino,
        travelMode: google.maps.TravelMode.DRIVING,
    }, function(response, status) {
        if (status === 'OK') {
            console.log('Ruta calculada exitosamente');
            renderer.setDirections(response);
            agregarMarcadoresEstado(response, mapaInstance);
        } else {
            console.log('Error al calcular ruta:', status, '- Mostrando ciudades por separado');
            mostrarCiudadesSeparadas(mapaInstance);
        }
    });
}

function agregarMarcadoresEstado(directionsResult, mapaInstance) {
    const route = directionsResult.routes[0];
    const leg = route.legs[0];
    
    new google.maps.Marker({
        position: leg.start_location,
        map: mapaInstance,
        title: 'Origen: ' + window.envioData.ciudadOrigen,
        icon: { url: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png' }
    });
    
    let iconColor = 'yellow-dot.png';
    let estadoTexto = 'En proceso';
    let emoji = '📦';
    
    switch(window.envioData.estadoActual) {
        case '3':
            iconColor = 'green-dot.png';
            estadoTexto = 'Entregado';
            emoji = '✅';
            break;
        case '4':
            iconColor = 'orange-dot.png';
            estadoTexto = 'Devuelto';
            emoji = '↩️';
            break;
        case '5':
            iconColor = 'red-dot.png';
            estadoTexto = 'Siniestrado';
            emoji = '❌';
            break;
        default:
            iconColor = 'yellow-dot.png';
            estadoTexto = 'En proceso';
            emoji = '🚛';
    }
    
    new google.maps.Marker({
        position: leg.end_location,
        map: mapaInstance,
        title: `${emoji} Destino: ${window.envioData.ciudadDestino} (${estadoTexto})`,
        icon: { url: 'https://maps.google.com/mapfiles/ms/icons/' + iconColor }
    });
    
    console.log('Marcadores agregados - Estado:', estadoTexto);
}

function mostrarCiudadesSeparadas(mapaInstance) {
    if (!google.maps.Geocoder) {
        console.error('Geocoding API no disponible');
        mostrarMensajeNoData(mapaInstance);
        return;
    }
    
    const geocoder = new google.maps.Geocoder();
    
    if (window.envioData.ciudadOrigen) {
        geocoder.geocode({ address: window.envioData.ciudadOrigen + ', Colombia' }, function(results, status) {
            if (status === 'OK') {
                new google.maps.Marker({
                    position: results[0].geometry.location,
                    map: mapaInstance,
                    title: 'Origen: ' + window.envioData.ciudadOrigen,
                    icon: { url: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png' }
                });
                mapaInstance.setCenter(results[0].geometry.location);
                console.log('Marcador de origen agregado:', window.envioData.ciudadOrigen);
            } else {
                console.log('Error geocodificando origen:', status);
            }
        });
    }
    
    if (window.envioData.ciudadDestino) {
        geocoder.geocode({ address: window.envioData.ciudadDestino + ', Colombia' }, function(results, status) {
            if (status === 'OK') {
                let iconColor = 'yellow-dot.png';
                let emoji = '📦';
                
                switch(window.envioData.estadoActual) {
                    case '3':
                        iconColor = 'green-dot.png';
                        emoji = '✅';
                        break;
                    case '4':
                        iconColor = 'orange-dot.png';
                        emoji = '↩️';
                        break;
                    case '5':
                        iconColor = 'red-dot.png';
                        emoji = '❌';
                        break;
                    default:
                        iconColor = 'yellow-dot.png';
                        emoji = '🚛';
                }
                
                new google.maps.Marker({
                    position: results[0].geometry.location,
                    map: mapaInstance,
                    title: `${emoji} Destino: ${window.envioData.ciudadDestino}`,
                    icon: { url: 'https://maps.google.com/mapfiles/ms/icons/' + iconColor }
                });
                console.log('Marcador de destino agregado:', window.envioData.ciudadDestino);
            } else {
                console.log('Error geocodificando destino:', status);
            }
        });
    }
}

function mostrarMovimientos(mapaInstance) {
    if (!window.envioData.movimientos || window.envioData.movimientos.length === 0) {
        console.log('No hay movimientos para mostrar');
        return;
    }
    
    if (!google.maps.Geocoder) {
        console.error('Geocoding API no disponible para movimientos');
        return;
    }
    
    const geocoder = new google.maps.Geocoder();
    console.log('Procesando', window.envioData.movimientos.length, 'movimientos');
    
    window.envioData.movimientos.forEach((movimiento, index) => {
        const ciudad = movimiento.DesMov || movimiento.OriMov;
        if (ciudad && typeof ciudad === 'string') {
            geocoder.geocode({ address: ciudad + ', Colombia' }, function(results, status) {
                if (status === 'OK') {
                    new google.maps.Marker({
                        position: results[0].geometry.location,
                        map: mapaInstance,
                        title: `${movimiento.NomMov || 'Movimiento'} - ${ciudad}`,
                        icon: { url: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png' }
                    });
                    
                    if (index === 0) {
                        mapaInstance.setCenter(results[0].geometry.location);
                    }
                    console.log('Marcador de movimiento agregado:', ciudad);
                } else {
                    console.log('Error geocodificando movimiento:', ciudad, status);
                }
            });
        }
    });
}

// Event listeners cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, configurando event listeners');
    
    try {
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
                        
                        if (!map && typeof google !== 'undefined') {
                            inicializarMapa();
                        } else if (map) {
                            setTimeout(() => {
                                google.maps.event.trigger(map, 'resize');
                                console.log('Redimensionando mapa principal');
                            }, 100);
                        }
                        
                        isMapVisible = true;
                    } else {
                        mapContainer.style.display = 'none';
                        btn.innerHTML = '<i class="fas fa-map-marked-alt me-2"></i>VER MAPA';
                        btn.classList.remove('btn-active');
                        isMapVisible = false;
                    }
                } catch (error) {
                    console.error('Error en click mapa principal:', error);
                }
            });
            console.log('Event listener para mapa principal configurado');
        }

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
                        
                        if (!mapHistorial && typeof google !== 'undefined') {
                            inicializarMapaHistorial();
                        } else if (mapHistorial) {
                            setTimeout(() => {
                                google.maps.event.trigger(mapHistorial, 'resize');
                                console.log('Redimensionando mapa historial');
                            }, 100);
                        }
                        
                        isMapHistorialVisible = true;
                    } else {
                        mapContainer.style.display = 'none';
                        btn.innerHTML = '<i class="fas fa-map-marked-alt me-2"></i>VER MAPA';
                        btn.classList.remove('btn-active');
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

// Manejo global de errores de Google Maps
window.gm_authFailure = function() {
    console.error('Error de autenticación con Google Maps API');
    isSimulationMode = true;
    showApiError('Error de autenticación - Verifica la API Key');
};

// Prevenir errores de IntersectionObserver
window.addEventListener('error', function(event) {
    if (event.message && event.message.includes('IntersectionObserver')) {
        console.warn('Error de IntersectionObserver capturado y ignorado');
        event.preventDefault();
        return false;
    }
});

// Detectar errores de API automáticamente
window.addEventListener('load', function() {
    setTimeout(() => {
        // Si después de 3 segundos seguimos viendo errores de API, activar simulación
        if (document.querySelectorAll('[class*="gm-err"]').length > 0) {
            console.log('Errores de Google Maps detectados, activando modo simulación');
            isSimulationMode = true;
        }
    }, 3000);
});

/*Nota: Pendiente validar que la simulacion no se active ahora que solucionamos lo del mapa
no aparece mapa cuando se consulta guia directamente */