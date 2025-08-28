let map;
let mapHistorial;
let isMapVisible = false;
let isMapHistorialVisible = false;

// Variables para marcadores y rutas
let markers = [];
let routeControl = null;
let markersHistorial = [];
let routeControlHistorial = null;

// Colores corporativos para texto
const COLORES = {
    VERDE: '#198754',    // Entregado (estado '3')
    AZUL: '#33549b',     // Puntos intermedios
    NARANJA: '#fd7e14',  // En Proceso (estados '1', '2', '5')
    ROJO: '#dc3545'      // Devuelto (estado '4')
};

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

// Función para sincronizar botones de estado - CORREGIDA según tu blade
function sincronizarBotonesEstado() {
    const estadoActual = window.envioData.estadoActual;
    
    console.log('Sincronizando botones para estado:', estadoActual);
    
    // Buscar todos los botones de estado
    const botonesEstado = document.querySelectorAll('.status-indicator');
    
    botonesEstado.forEach(boton => {
        // Remover clases activas previas
        boton.classList.remove('status-entregado', 'status-devuelto', 'status-proceso');
        boton.classList.add('status-inactive');
        
        // Activar el botón correspondiente según tu lógica del blade
        if (estadoActual === '3' && boton.textContent.includes('Entregado')) {
            // ENTREGADO - Verde
            boton.classList.remove('status-inactive');
            boton.classList.add('status-entregado');
        } else if (estadoActual === '4' && boton.textContent.includes('Devuelto')) {
            // DEVUELTO - Rojo
            boton.classList.remove('status-inactive');
            boton.classList.add('status-devuelto');
        } else if ((estadoActual === '1' || estadoActual === '2' || estadoActual === '5') && boton.textContent.includes('En Proceso')) {
            // EN PROCESO - Naranja (estados 1, 2, 5)
            boton.classList.remove('status-inactive');
            boton.classList.add('status-proceso');
        }
    });
    
    console.log('Botones de estado sincronizados correctamente');
}

// Inicializar el mapa principal (Tab Detalles)
function inicializarMapa() {
    console.log('Inicializando mapa principal con OpenStreetMap...');
    
    if (!window.envioData) {
        console.error('No hay datos disponibles para el mapa');
        return;
    }
    
    // Sincronizar botones de estado
    sincronizarBotonesEstado();
    
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
    
    // Sincronizar botones de estado
    sincronizarBotonesEstado();
    
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
    
    // Priorizar mostrar movimientos del historial si existen
    if (window.envioData.movimientos && window.envioData.movimientos.length > 0) {
        console.log('Mostrando ruta completa del historial de movimientos');
        mostrarRutaCompleta(mapaInstance, tipo);
    } else if (window.envioData.ciudadOrigen && window.envioData.ciudadDestino) {
        console.log('Calculando ruta simple entre origen y destino');
        calcularRutaSimple(mapaInstance, tipo);
    } else {
        console.log('No hay datos suficientes para mostrar en el mapa');
        mostrarMensajeNoData(mapaInstance);
    }
}

// Mostrar ruta completa basada en el historial de movimientos - CON FILTRADO INTELIGENTE
function mostrarRutaCompleta(mapaInstance, tipo) {
    console.log('Procesando', window.envioData.movimientos.length, 'movimientos del historial');
    console.log('Movimientos completos:', window.envioData.movimientos);
    
    // Crear secuencia de ubicaciones FILTRADA - Solo puntos relevantes
    const ubicacionesRelevantes = [];
    const ciudadesYaVistas = new Set();
    
    window.envioData.movimientos.forEach((movimiento, index) => {
        console.log(`Procesando movimiento ${index + 1}:`, movimiento);
        
        // Extraer ciudades del movimiento
        const ciudadOrigen = extraerCiudad(movimiento.OriMov);
        const ciudadDestino = extraerCiudad(movimiento.DesMov);
        
        // FILTRO 1: Agregar ciudad de origen solo si es nueva
        if (ciudadOrigen && !ciudadesYaVistas.has(ciudadOrigen.toLowerCase())) {
            ubicacionesRelevantes.push({
                nombre: ciudadOrigen,
                tipo: index === 0 ? 'origen' : 'intermedio',
                orden: ubicacionesRelevantes.length + 1,
                movimiento: movimiento,
                fecha: movimiento.FecMov,
                nombreMovimiento: movimiento.NomMov,
                idProceso: movimiento.IdProc,
                razon: index === 0 ? 'Punto de origen' : 'Nueva ciudad en ruta'
            });
            ciudadesYaVistas.add(ciudadOrigen.toLowerCase());
            console.log(`✅ Ciudad relevante agregada: ${ciudadOrigen} (${index === 0 ? 'origen' : 'nueva ciudad'})`);
        }
        
        // FILTRO 2: Agregar ciudad de destino solo si es nueva Y diferente al origen
        if (ciudadDestino && 
            ciudadDestino !== ciudadOrigen && 
            !ciudadesYaVistas.has(ciudadDestino.toLowerCase())) {
            
            ubicacionesRelevantes.push({
                nombre: ciudadDestino,
                tipo: 'intermedio',
                orden: ubicacionesRelevantes.length + 1,
                movimiento: movimiento,
                fecha: movimiento.FecMov,
                nombreMovimiento: movimiento.NomMov,
                idProceso: movimiento.IdProc,
                razon: 'Nueva ciudad en ruta'
            });
            ciudadesYaVistas.add(ciudadDestino.toLowerCase());
            console.log(`✅ Ciudad relevante agregada: ${ciudadDestino} (nueva ciudad)`);
        }
    });
    
    // Marcar el último elemento como destino final
    if (ubicacionesRelevantes.length > 0) {
        ubicacionesRelevantes[ubicacionesRelevantes.length - 1].tipo = 'destino';
        ubicacionesRelevantes[ubicacionesRelevantes.length - 1].razon = 'Destino final';
    }
    
    console.log(`🎯 Filtrado aplicado: ${window.envioData.movimientos.length} movimientos → ${ubicacionesRelevantes.length} puntos relevantes`);
    console.log('Ubicaciones relevantes seleccionadas:', ubicacionesRelevantes);
    
    if (ubicacionesRelevantes.length === 0) {
        console.log('No se encontraron ubicaciones relevantes');
        mostrarMensajeNoData(mapaInstance);
        return;
    }
    
    // Buscar coordenadas para todas las ubicaciones relevantes
    const promesasGeocodificacion = ubicacionesRelevantes.map(async (ubicacion, index) => {
        try {
            console.log(`Geocodificando punto relevante: ${ubicacion.nombre}`);
            const coordenadas = await buscarCoordenadas(ubicacion.nombre + ', Colombia');
            if (coordenadas) {
                console.log(`✅ Coordenadas encontradas para ${ubicacion.nombre}:`, coordenadas);
                return { ...ubicacion, coordenadas, indiceOriginal: index };
            } else {
                console.log(`❌ No se encontraron coordenadas para: ${ubicacion.nombre}`);
                return null;
            }
        } catch (error) {
            console.error('Error geocodificando:', ubicacion.nombre, error);
            return null;
        }
    });
    
    Promise.all(promesasGeocodificacion).then(resultados => {
        // Filtrar resultados válidos
        const ubicacionesConCoordenadas = resultados.filter(resultado => resultado !== null);
        
        console.log('Ubicaciones relevantes con coordenadas válidas:', ubicacionesConCoordenadas);
        
        if (ubicacionesConCoordenadas.length === 0) {
            console.log('No se pudieron geocodificar las ubicaciones relevantes');
            mostrarMensajeNoData(mapaInstance);
            return;
        }
        
        // Crear marcadores para cada ubicación relevante
        const marcadoresCreados = [];
        const puntosRuta = [];
        
        ubicacionesConCoordenadas.forEach((ubicacion, index) => {
            const esUltimo = index === ubicacionesConCoordenadas.length - 1;
            const marcador = crearMarcadorHistorial(ubicacion, index + 1, esUltimo);
            
            marcador.addTo(mapaInstance);
            marcadoresCreados.push(marcador);
            puntosRuta.push(L.latLng(ubicacion.coordenadas.lat, ubicacion.coordenadas.lng));
            
            // Guardar marcadores según el tipo de mapa
            if (tipo === 'principal') {
                markers.push(marcador);
            } else {
                markersHistorial.push(marcador);
            }
            
            console.log(`✅ Marcador relevante ${index + 1} creado: ${ubicacion.nombre} (${ubicacion.razon})`);
        });
        
        // Crear ruta secuencial con colores corporativos
        if (puntosRuta.length > 1) {
            let colorRuta;
            
            switch(window.envioData.estadoActual) {
                case '3': // Entregado
                    colorRuta = COLORES.VERDE;
                    break;
                case '4': // Devuelto
                    colorRuta = COLORES.ROJO;
                    break;
                case '1': // Recibido del cliente
                case '2': // En procesamiento
                case '5': // Siniestrado
                default: // En proceso
                    colorRuta = COLORES.NARANJA;
            }
            
            const control = L.Routing.control({
                waypoints: puntosRuta,
                routeWhileDragging: false,
                addWaypoints: false,
                createMarker: function() { return null; },
                lineOptions: {
                    styles: [{ 
                        color: colorRuta, 
                        weight: 4, 
                        opacity: 0.8 
                    }]
                },
                show: false
            }).addTo(mapaInstance);
            
            // Guardar control de ruta
            if (tipo === 'principal') {
                routeControl = control;
            } else {
                routeControlHistorial = control;
            }
            
            console.log(`✅ Ruta relevante creada con ${puntosRuta.length} puntos, color: ${colorRuta}`);
        }
        
        // Ajustar vista para mostrar toda la ruta
        if (marcadoresCreados.length > 0) {
            const group = new L.featureGroup(marcadoresCreados);
            mapaInstance.fitBounds(group.getBounds().pad(0.1));
        }
        
        console.log(`🎉 Ruta relevante completada: ${ubicacionesConCoordenadas.length} puntos mostrados de ${window.envioData.movimientos.length} movimientos totales`);
        
    }).catch(error => {
        console.error('Error creando ruta relevante:', error);
        mostrarMensajeNoData(mapaInstance);
    });
}

// Función auxiliar para extraer nombre de ciudad limpio
function extraerCiudad(valorCiudad) {
    if (!valorCiudad) return null;
    
    let ciudad = Array.isArray(valorCiudad) ? valorCiudad[0] : valorCiudad;
    
    if (typeof ciudad !== 'string') return null;
    
    // Extraer solo el nombre de la ciudad (antes del paréntesis)
    const nombreCiudad = ciudad.split('(')[0].trim();
    return limpiarNombreCiudad(nombreCiudad);
}

// Función auxiliar para limpiar nombres de ciudades
function limpiarNombreCiudad(ciudad) {
    if (!ciudad || typeof ciudad !== 'string') return null;
    
    return ciudad
        .trim()
        .replace(/\s+/g, ' ')
        .toUpperCase() // Normalizar a mayúsculas para comparaciones
        .replace(/[^\w\sñáéíóúü]/gi, '') // Conservar caracteres especiales del español
        .trim();
}

// Crear marcador específico para el historial SIN EMOJIS con iconos estándar de Leaflet
function crearMarcadorHistorial(ubicacion, numero, esUltimo) {
    let iconUrl, colorTexto;
    
    // Determinar color del marcador e icono estándar
    if (ubicacion.tipo === 'origen') {
        iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png';
        colorTexto = COLORES.VERDE;
    } else if (ubicacion.tipo === 'destino' || esUltimo) {
        // El último punto se colorea según el estado del envío
        switch(window.envioData.estadoActual) {
            case '3': // Entregado
                iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png';
                colorTexto = COLORES.VERDE;
                break;
            case '4': // Devuelto
                iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png';
                colorTexto = COLORES.ROJO;
                break;
            case '1': // Recibido del cliente
            case '2': // En procesamiento
            case '5': // Siniestrado
            default: // En proceso
                iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-orange.png';
                colorTexto = COLORES.NARANJA;
        }
    } else {
        // Puntos intermedios en azul
        iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png';
        colorTexto = COLORES.AZUL;
    }
    
    const customIcon = L.icon({
        iconUrl: iconUrl,
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });
    
    // Determinar texto del estado SIN emojis
    let estadoTexto = '';
    
    if (ubicacion.tipo === 'origen') {
        estadoTexto = 'Punto de origen';
    } else if (ubicacion.tipo === 'destino' || esUltimo) {
        switch(window.envioData.estadoActual) {
            case '3':
                estadoTexto = 'ENTREGADO';
                break;
            case '4':
                estadoTexto = 'DEVUELTO';
                break;
            case '1':
            case '2':
            case '5':
            default:
                estadoTexto = 'EN PROCESO';
        }
    } else {
        estadoTexto = 'Punto intermedio';
    }
    
    // Crear contenido del popup SIN emojis, SIN badges, solo colores de fuente
    let popupContent = `
        <div style="min-width: 250px; max-width: 300px;">
            <h6 style="margin-bottom: 8px; color: ${colorTexto}; font-weight: bold;">
                Parada ${numero}: ${ubicacion.nombre}
            </h6>
            <p style="margin: 4px 0; color: ${colorTexto}; font-size: 12px; font-weight: bold;">${estadoTexto}</p>
    `;
    
    if (ubicacion.movimiento) {
        popupContent += `
            <hr style="margin: 8px 0; border-color: #ddd;">
            <div style="font-size: 11px; color: #555; line-height: 1.4;">
                <div style="margin-bottom: 4px;">
                    <strong>${ubicacion.nombreMovimiento || 'Sin descripción'}</strong>
                </div>
                <div style="margin-bottom: 2px;">
                    <strong>Fecha:</strong> ${ubicacion.fecha || 'Sin fecha'}
                </div>
                <div style="margin-bottom: 2px;">
                    <strong>ID Proceso:</strong> ${ubicacion.idProceso || 'N/A'}
                </div>
            </div>
        `;
    }
    
    popupContent += '</div>';
    
    return L.marker([ubicacion.coordenadas.lat, ubicacion.coordenadas.lng], { icon: customIcon })
        .bindPopup(popupContent);
}

// Calcular ruta simple entre origen y destino (fallback)
function calcularRutaSimple(mapaInstance, tipo) {
    const origen = window.envioData.ciudadOrigen;
    const destino = window.envioData.ciudadDestino;
    
    console.log('Calculando ruta simple entre:', origen, 'y', destino);
    
    Promise.all([
        buscarCoordenadas(origen + ', Colombia'),
        buscarCoordenadas(destino + ', Colombia')
    ]).then(([coordOrigen, coordDestino]) => {
        if (coordOrigen && coordDestino) {
            // Crear marcadores con iconos estándar
            const marcadorOrigen = crearMarcadorSimple(coordOrigen, origen, 'origen');
            const marcadorDestino = crearMarcadorSimple(coordDestino, destino, 'destino');
            
            marcadorOrigen.addTo(mapaInstance);
            marcadorDestino.addTo(mapaInstance);
            
            // Guardar marcadores según el tipo de mapa
            if (tipo === 'principal') {
                markers.push(marcadorOrigen, marcadorDestino);
            } else {
                markersHistorial.push(marcadorOrigen, marcadorDestino);
            }
            
            // Crear ruta con colores corporativos corregidos
            let colorRuta;
            switch(window.envioData.estadoActual) {
                case '3': // Entregado
                    colorRuta = COLORES.VERDE;
                    break;
                case '4': // Devuelto
                    colorRuta = COLORES.ROJO;
                    break;
                case '1':
                case '2':
                case '5':
                default: // En proceso
                    colorRuta = COLORES.NARANJA;
            }
            
            const control = L.Routing.control({
                waypoints: [
                    L.latLng(coordOrigen.lat, coordOrigen.lng),
                    L.latLng(coordDestino.lat, coordDestino.lng)
                ],
                routeWhileDragging: false,
                addWaypoints: false,
                createMarker: function() { return null; },
                lineOptions: {
                    styles: [{ color: colorRuta, weight: 4, opacity: 0.8 }]
                }
            }).addTo(mapaInstance);
            
            // Guardar control de ruta
            if (tipo === 'principal') {
                routeControl = control;
            } else {
                routeControlHistorial = control;
            }
            
            // Ajustar vista
            const group = new L.featureGroup([marcadorOrigen, marcadorDestino]);
            mapaInstance.fitBounds(group.getBounds().pad(0.1));
            
            console.log('Ruta simple creada exitosamente');
        } else {
            mostrarMensajeNoData(mapaInstance);
        }
    }).catch(error => {
        console.error('Error al calcular ruta simple:', error);
        mostrarMensajeNoData(mapaInstance);
    });
}

// Crear marcador simple con iconos estándar
function crearMarcadorSimple(coordenadas, titulo, tipo) {
    let iconUrl;
    
    switch(tipo) {
        case 'origen':
            iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png';
            break;
        case 'destino':
            switch(window.envioData.estadoActual) {
                case '3':
                    iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png';
                    break;
                case '4':
                    iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png';
                    break;
                case '1':
                case '2':
                case '5':
                default:
                    iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-orange.png';
            }
            break;
        default:
            iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png';
    }
    
    const customIcon = L.icon({
        iconUrl: iconUrl,
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });
    
    let estado = '';
    
    if (tipo === 'destino') {
        switch(window.envioData.estadoActual) {
            case '3':
                estado = ' - ENTREGADO';
                break;
            case '4':
                estado = ' - DEVUELTO';
                break;
            case '1':
            case '2':
            case '5':
            default:
                estado = ' - EN PROCESO';
        }
    }
    
    return L.marker([coordenadas.lat, coordenadas.lng], { icon: customIcon })
        .bindPopup(`<b>${titulo}${estado}</b>`);
}

// Buscar coordenadas usando Nominatim (gratuito)
async function buscarCoordenadas(direccion) {
    try {
        // Agregar delay para evitar rate limiting
        await new Promise(resolve => setTimeout(resolve, 100));
        
        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(direccion)}&limit=1&countrycodes=CO`);
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

// Mostrar mensaje cuando no hay datos
function mostrarMensajeNoData(mapaInstance) {
    const popup = L.popup()
        .setLatLng(mapaInstance.getCenter())
        .setContent(`
            <div style="text-align: center; padding: 15px;">
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
        // Sincronizar botones de estado al cargar la página
        if (window.envioData) {
            sincronizarBotonesEstado();
        }
        
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
                        
                        setTimeout(() => {
                            inicializarMapa();
                        }, 100);
                        
                        isMapVisible = true;
                    } else {
                        mapContainer.style.display = 'none';
                        btn.innerHTML = '<i class="fas fa-map-marked-alt me-2"></i>VER MAPA';
                        btn.classList.remove('btn-active');
                        
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
                        
                        setTimeout(() => {
                            inicializarMapaHistorial();
                        }, 100);
                        
                        isMapHistorialVisible = true;
                    } else {
                        mapContainer.style.display = 'none';
                        btn.innerHTML = '<i class="fas fa-map-marked-alt me-2"></i>VER MAPA';
                        btn.classList.remove('btn-active');
                        
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