/**
 * Sistema de mapas para tracking de envíos - VERSIÓN COMPLETA FUNCIONAL
 * Solo usa Leaflet básico + OpenStreetMap + Nominatim (100% gratuito)
 * Basado en test exitoso - Garantía de funcionamiento
 */

// Variables globales para instancias de mapas
let map;
let mapHistorial;
let isMapVisible = false;
let isMapHistorialVisible = false;

// Variables para marcadores y polylines
let markers = [];
let routePolyline = null;
let markersHistorial = [];
let routePolylineHistorial = null;

/**
 * Colores corporativos para visualización
 */
const COLORES = {
    VERDE: '#198754',    // Entregado (estado '3')
    AZUL: '#33549b',     // Puntos intermedios
    NARANJA: '#fd7e14',  // En Proceso (estados '1', '2', '5')
    ROJO: '#dc3545'      // Devuelto (estado '4')
};

/**
 * Limpia marcadores y polylines del mapa de forma segura
 * @param {Object} mapaInstance - Instancia del mapa de Leaflet
 * @param {Array} marcadores - Array de marcadores a remover
 * @param {Object} polyline - Polyline a remover
 */
function limpiarMapa(mapaInstance, marcadores, polyline) {
    console.log(' Limpiando mapa');
    
    try {
        // Limpiar marcadores
        if (marcadores && marcadores.length > 0) {
            marcadores.forEach((marker, index) => {
                try {
                    if (marker && mapaInstance && mapaInstance.hasLayer(marker)) {
                        mapaInstance.removeLayer(marker);
                        console.log(` Marcador ${index + 1} removido`);
                    }
                } catch (e) {
                    console.warn(` Error removiendo marcador ${index + 1}:`, e);
                }
            });
            marcadores.length = 0;
        }
        
        // Limpiar polyline
        if (polyline && mapaInstance) {
            try {
                if (mapaInstance.hasLayer(polyline)) {
                    mapaInstance.removeLayer(polyline);
                    console.log(' Polyline removida');
                }
            } catch (e) {
                console.warn(' Error removiendo polyline:', e);
            }
        }
        
        console.log(' Mapa limpiado correctamente');
    } catch (error) {
        console.error(' Error en limpiarMapa:', error);
    }
}

/**
 * Espera a que un elemento DOM sea visible
 * @param {string} elementId - ID del elemento
 * @param {Function} callback - Función a ejecutar
 * @param {number} maxIntentos - Máximo intentos
 */
function esperarElementoVisible(elementId, callback, maxIntentos = 30) {
    let intentos = 0;
    
    const verificar = () => {
        intentos++;
        const elemento = document.getElementById(elementId);
        
        if (elemento && elemento.offsetHeight > 0 && elemento.offsetWidth > 0) {
            console.log(` Elemento ${elementId} visible en intento ${intentos}`);
            setTimeout(() => callback(elemento), 50);
            return;
        }
        
        if (intentos < maxIntentos) {
            setTimeout(verificar, 100);
        } else {
            console.error(` Elemento ${elementId} no visible después de ${maxIntentos} intentos`);
        }
    };
    
    verificar();
}

/**
 * Sincroniza los botones de estado visual
 */
function sincronizarBotonesEstado() {
    try {
        if (!window.envioData) {
            console.warn(' No hay datos de envío disponibles para sincronizar');
            return;
        }
        
        const estadoActual = window.envioData.estadoActual;
        console.log(' Sincronizando botones para estado:', estadoActual);
        
        const botonesEstado = document.querySelectorAll('.status-indicator');
        
        botonesEstado.forEach((boton, index) => {
            try {
                // Remover clases activas previas
                boton.classList.remove('status-entregado', 'status-devuelto', 'status-proceso');
                boton.classList.add('status-inactive');
                
                // Activar botón correspondiente
                if (estadoActual === '3' && boton.textContent.includes('Entregado')) {
                    boton.classList.remove('status-inactive');
                    boton.classList.add('status-entregado');
                } else if (estadoActual === '4' && boton.textContent.includes('Devuelto')) {
                    boton.classList.remove('status-inactive');
                    boton.classList.add('status-devuelto');
                } else if (['1', '2', '5'].includes(estadoActual) && boton.textContent.includes('En Proceso')) {
                    boton.classList.remove('status-inactive');
                    boton.classList.add('status-proceso');
                }
            } catch (e) {
                console.warn(` Error sincronizando botón ${index + 1}:`, e);
            }
        });
        
        console.log(' Botones de estado sincronizados');
    } catch (error) {
        console.error(' Error en sincronizarBotonesEstado:', error);
    }
}

/**
 * Obtiene el color según el estado actual del envío
 * @returns {string} - Color hexadecimal
 */
function obtenerColorSegunEstado() {
    try {
        if (!window.envioData || !window.envioData.estadoActual) {
            console.log(' Sin estado específico, usando color por defecto');
            return COLORES.AZUL;
        }
        
        const estado = window.envioData.estadoActual;
        
        switch(estado) {
            case '3': // Entregado
                console.log(' Estado: Entregado');
                return COLORES.VERDE;
            case '4': // Devuelto
                console.log(' Estado: Devuelto');
                return COLORES.ROJO;
            case '1': // Recibido del cliente
            case '2': // En procesamiento
            case '5': // Siniestrado
            default: // En proceso
                console.log(' Estado: En proceso');
                return COLORES.NARANJA;
        }
    } catch (error) {
        console.error(' Error obteniendo color de estado:', error);
        return COLORES.AZUL;
    }
}

/**
 * Extrae el nombre de ciudad limpio de diferentes formatos
 * @param {string|Array} valorCiudad - Valor que contiene la ciudad
 * @returns {string|null} - Nombre de ciudad limpio
 */
function extraerCiudad(valorCiudad) {
    try {
        if (!valorCiudad) {
            console.log(' Valor de ciudad vacío');
            return null;
        }
        
        let ciudad = Array.isArray(valorCiudad) ? valorCiudad[0] : valorCiudad;
        
        if (typeof ciudad !== 'string') {
            console.log(' Valor de ciudad no es string:', typeof ciudad);
            return null;
        }
        
        // Extraer solo el nombre de la ciudad (antes del paréntesis)
        const nombreLimpio = ciudad.split('(')[0].trim().toUpperCase();
        console.log(` Ciudad extraída: "${ciudad}" → "${nombreLimpio}"`);
        
        return nombreLimpio;
    } catch (error) {
        console.error(' Error extrayendo ciudad:', error);
        return null;
    }
}

/**
 * Busca coordenadas geográficas usando Nominatim de OpenStreetMap
 * @param {string} direccion - Dirección o ciudad a geocodificar
 * @returns {Promise<Object|null>} - Promesa con coordenadas o null
 */
async function buscarCoordenadas(direccion) {
    try {
        console.log(` Buscando coordenadas para: "${direccion}"`);
        
        // Rate limiting para evitar restricciones
        await new Promise(resolve => setTimeout(resolve, 200));
        
        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(direccion)}&limit=1&countrycodes=CO`;
        console.log(` URL de geocodificación: ${url}`);
        
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log(` Respuesta de geocodificación:`, data);
        
        if (data && data.length > 0) {
            const coordenadas = {
                lat: parseFloat(data[0].lat),
                lng: parseFloat(data[0].lon)
            };
            console.log(` Coordenadas encontradas para "${direccion}":`, coordenadas);
            return coordenadas;
        } else {
            console.log(` No se encontraron coordenadas para: "${direccion}"`);
            return null;
        }
    } catch (error) {
        console.error(` Error en geocodificación para "${direccion}":`, error);
        return null;
    }
}

/**
 * Crea un marcador personalizado
 * @param {Object} coordenadas - Objeto con lat y lng
 * @param {string} titulo - Título del marcador
 * @param {string} tipo - Tipo: 'origen', 'destino', 'intermedio'
 * @param {string} popupContent - Contenido personalizado del popup
 * @returns {Object|null} - Marcador de Leaflet o null
 */
function crearMarcador(coordenadas, titulo, tipo, popupContent = null) {
    try {
        console.log(` Creando marcador: "${titulo}" (${tipo})`);
        
        let iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png';
        
        // Determinar color del icono según tipo
        switch(tipo) {
            case 'origen':
                iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png';
                console.log(' Marcador de origen');
                break;
            case 'destino':
                const colorEstado = obtenerColorSegunEstado();
                if (colorEstado === COLORES.VERDE) {
                    iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png';
                    console.log(' Marcador de destino - Entregado');
                } else if (colorEstado === COLORES.ROJO) {
                    iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png';
                    console.log(' Marcador de destino - Devuelto');
                } else {
                    iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-orange.png';
                    console.log(' Marcador de destino - En proceso');
                }
                break;
            case 'intermedio':
                iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png';
                console.log(' Marcador intermedio');
                break;
            default:
                console.log(' Marcador por defecto');
        }
        
        // Crear icono personalizado
        const customIcon = L.icon({
            iconUrl: iconUrl,
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });
        
        // Crear marcador
        const marker = L.marker([coordenadas.lat, coordenadas.lng], { icon: customIcon });
        
        // Contenido del popup
        const content = popupContent || `
            <div style="text-align: center; padding: 8px; min-width: 150px;">
                <h6 style="margin-bottom: 8px; color: ${obtenerColorSegunEstado()};">
                    <strong>${titulo}</strong>
                </h6>
                <p style="margin: 4px 0; color: #666; font-size: 12px;">
                    ${tipo === 'origen' ? ' Punto de origen' : 
                      tipo === 'destino' ? ' Punto de destino' : ' Punto intermedio'}
                </p>
                <small style="color: #999;">
                    Lat: ${coordenadas.lat.toFixed(4)}, Lng: ${coordenadas.lng.toFixed(4)}
                </small>
            </div>
        `;
        
        marker.bindPopup(content);
        
        console.log(` Marcador creado exitosamente: "${titulo}"`);
        return marker;
        
    } catch (error) {
        console.error(` Error creando marcador para "${titulo}":`, error);
        return null;
    }
}

/**
 * Crea un marcador detallado para el historial
 * @param {Object} ubicacion - Datos de la ubicación
 * @param {number} numero - Número de parada
 * @param {boolean} esUltimo - Si es el último punto
 * @returns {Object|null} - Marcador de Leaflet
 */
function crearMarcadorHistorial(ubicacion, numero, esUltimo) {
    try {
        console.log(` Creando marcador historial ${numero}: ${ubicacion.nombre}`);
        
        // Determinar tipo basado en posición
        let tipo = ubicacion.tipo;
        if (esUltimo && tipo !== 'origen') {
            tipo = 'destino';
        }
        
        // Crear contenido detallado del popup
        const estadoTexto = tipo === 'origen' ? 'Punto de origen' :
                           tipo === 'destino' ? getEstadoFinalTexto() : 'Punto intermedio';
        
        const popupContent = `
            <div style="min-width: 250px; max-width: 300px;">
                <h6 style="margin-bottom: 8px; color: ${obtenerColorSegunEstado()}; font-weight: bold;">
                     Parada ${numero}: ${ubicacion.nombre}
                </h6>
                <p style="margin: 4px 0; color: ${obtenerColorSegunEstado()}; font-size: 12px; font-weight: bold;">
                    ${estadoTexto}
                </p>
                ${ubicacion.movimiento ? `
                    <hr style="margin: 8px 0; border-color: #ddd;">
                    <div style="font-size: 11px; color: #555; line-height: 1.4;">
                        <div style="margin-bottom: 4px;">
                            <strong> Actividad:</strong> ${ubicacion.nombreMovimiento || ubicacion.movimiento.NomMov || 'Sin descripción'}
                        </div>
                        <div style="margin-bottom: 2px;">
                            <strong> Fecha:</strong> ${ubicacion.fecha || ubicacion.movimiento.FecMov || 'Sin fecha'}
                        </div>
                        ${ubicacion.idProceso || ubicacion.movimiento.IdProc ? `
                            <div style="margin-bottom: 2px;">
                                <strong> ID Proceso:</strong> ${ubicacion.idProceso || ubicacion.movimiento.IdProc}
                            </div>
                        ` : ''}
                    </div>
                ` : ''}
                <hr style="margin: 6px 0; border-color: #eee;">
                <small style="color: #999; font-size: 10px;">
                    Coordenadas: ${ubicacion.coordenadas.lat.toFixed(4)}, ${ubicacion.coordenadas.lng.toFixed(4)}
                </small>
            </div>
        `;
        
        return crearMarcador(ubicacion.coordenadas, ubicacion.nombre, tipo, popupContent);
        
    } catch (error) {
        console.error(' Error creando marcador historial:', error);
        return null;
    }
}

/**
 * Obtiene el texto del estado final según el estado actual
 * @returns {string} - Texto del estado
 */
function getEstadoFinalTexto() {
    try {
        if (!window.envioData || !window.envioData.estadoActual) {
            return 'EN PROCESO';
        }
        
        switch(window.envioData.estadoActual) {
            case '3': return ' ENTREGADO';
            case '4': return ' DEVUELTO';
            case '1':
            case '2':
            case '5':
            default: return ' EN PROCESO';
        }
    } catch (error) {
        console.error(' Error obteniendo texto de estado:', error);
        return 'EN PROCESO';
    }
}

/**
 * Muestra un mensaje cuando no hay datos de ubicación
 * @param {Object} mapaInstance - Instancia del mapa
 */
function mostrarMensajeNoData(mapaInstance) {
    try {
        console.log('ℹ Mostrando mensaje de sin datos');
        
        const popup = L.popup()
            .setLatLng(mapaInstance.getCenter())
            .setContent(`
                <div style="text-align: center; padding: 15px;">
                    <h6 style="margin-bottom: 8px; color: #666;">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Sin datos de ubicación</strong>
                    </h6>
                    <p style="margin: 0; color: #888; font-size: 13px;">
                        No se encontraron ciudades para mostrar en el mapa.
                    </p>
                    <small style="color: #aaa; font-size: 11px; margin-top: 8px; display: block;">
                        Verifica que el envío tenga información de ubicaciones.
                    </small>
                </div>
            `)
            .openOn(mapaInstance);
            
        console.log(' Mensaje de sin datos mostrado');
    } catch (error) {
        console.error(' Error mostrando mensaje de sin datos:', error);
    }
}

/**
 * Muestra error en el contenedor del mapa
 * @param {string} containerId - ID del contenedor
 */
function mostrarErrorMapa(containerId) {
    try {
        console.log(` Mostrando error en contenedor: ${containerId}`);
        
        const container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = `
                <div style="height: 400px; background: #f8f9fa; border: 2px dashed #ddd; border-radius: 8px; display: flex; align-items: center; justify-content: center; text-align: center; color: #666;">
                    <div>
                        <i class="fas fa-exclamation-triangle fa-3x mb-3" style="color: #ffc107;"></i>
                        <h5 style="margin-bottom: 8px;">Error al cargar el mapa</h5>
                        <p style="margin-bottom: 15px;">No se pudo inicializar el mapa correctamente.</p>
                        <button onclick="location.reload()" class="btn btn-primary btn-sm">
                            <i class="fas fa-refresh me-1"></i>Recargar página
                        </button>
                    </div>
                </div>
            `;
            console.log(' Mensaje de error mostrado');
        } else {
            console.error(` Contenedor ${containerId} no encontrado`);
        }
    } catch (error) {
        console.error(' Error mostrando mensaje de error:', error);
    }
}

/**
 * Procesa y muestra la ruta del envío en el mapa
 * @param {Object} mapaInstance - Instancia del mapa
 * @param {string} tipo - Tipo de mapa ('principal' o 'historial')
 */
async function procesarRutaEnvio(mapaInstance, tipo) {
    try {
        console.log(` Procesando ruta para mapa ${tipo}`);
        
        // Verificar datos disponibles
        if (!window.envioData) {
            console.error(' No hay datos de envío disponibles');
            mostrarMensajeNoData(mapaInstance);
            return;
        }
        
        console.log(' Datos de envío disponibles:', window.envioData);
        
        let ubicaciones = [];
        
        // Priorizar movimientos del historial
        if (window.envioData.movimientos && window.envioData.movimientos.length > 0) {
            console.log(` Procesando ${window.envioData.movimientos.length} movimientos del historial`);
            
            const ciudadesVistas = new Set();
            
            window.envioData.movimientos.forEach((movimiento, index) => {
                console.log(` Procesando movimiento ${index + 1}:`, movimiento);
                
                const ciudadOrigen = extraerCiudad(movimiento.OriMov);
                const ciudadDestino = extraerCiudad(movimiento.DesMov);
                
                // Agregar ciudad origen si es nueva
                if (ciudadOrigen && !ciudadesVistas.has(ciudadOrigen.toLowerCase())) {
                    ubicaciones.push({
                        nombre: ciudadOrigen,
                        tipo: index === 0 ? 'origen' : 'intermedio',
                        movimiento: movimiento,
                        fecha: movimiento.FecMov,
                        nombreMovimiento: movimiento.NomMov,
                        idProceso: movimiento.IdProc
                    });
                    ciudadesVistas.add(ciudadOrigen.toLowerCase());
                    console.log(` Ciudad origen agregada: ${ciudadOrigen}`);
                }
                
                // Agregar ciudad destino si es nueva y diferente
                if (ciudadDestino && 
                    ciudadDestino !== ciudadOrigen && 
                    !ciudadesVistas.has(ciudadDestino.toLowerCase())) {
                    
                    ubicaciones.push({
                        nombre: ciudadDestino,
                        tipo: 'intermedio',
                        movimiento: movimiento,
                        fecha: movimiento.FecMov,
                        nombreMovimiento: movimiento.NomMov,
                        idProceso: movimiento.IdProc
                    });
                    ciudadesVistas.add(ciudadDestino.toLowerCase());
                    console.log(` Ciudad destino agregada: ${ciudadDestino}`);
                }
            });
            
            // Marcar último como destino final
            if (ubicaciones.length > 0) {
                ubicaciones[ubicaciones.length - 1].tipo = 'destino';
                console.log(` Marcando última ubicación como destino: ${ubicaciones[ubicaciones.length - 1].nombre}`);
            }
            
        } else if (window.envioData.ciudadOrigen && window.envioData.ciudadDestino) {
            console.log(' Usando ruta simple origen-destino');
            
            ubicaciones = [
                { 
                    nombre: window.envioData.ciudadOrigen, 
                    tipo: 'origen',
                    movimiento: null
                },
                { 
                    nombre: window.envioData.ciudadDestino, 
                    tipo: 'destino',
                    movimiento: null
                }
            ];
        }
        
        console.log(` Total de ubicaciones a procesar: ${ubicaciones.length}`);
        
        if (ubicaciones.length === 0) {
            console.log(' No hay ubicaciones para mostrar');
            mostrarMensajeNoData(mapaInstance);
            return;
        }
        
        // Geocodificar todas las ubicaciones
        console.log(' Iniciando proceso de geocodificación...');
        const ubicacionesConCoords = [];
        
        for (let i = 0; i < ubicaciones.length; i++) {
            const ubicacion = ubicaciones[i];
            console.log(` Geocodificando ${i + 1}/${ubicaciones.length}: ${ubicacion.nombre}`);
            
            const coords = await buscarCoordenadas(ubicacion.nombre + ', Colombia');
            
            if (coords) {
                ubicacionesConCoords.push({ ...ubicacion, coordenadas: coords });
                console.log(` Geocodificación exitosa para: ${ubicacion.nombre}`);
            } else {
                console.log(` Geocodificación fallida para: ${ubicacion.nombre}`);
            }
        }
        
        console.log(` Ubicaciones geocodificadas exitosamente: ${ubicacionesConCoords.length}/${ubicaciones.length}`);
        
        if (ubicacionesConCoords.length === 0) {
            console.log(' No se pudieron geocodificar ninguna ubicación');
            mostrarMensajeNoData(mapaInstance);
            return;
        }
        
        // Crear marcadores en el mapa
        console.log(' Creando marcadores en el mapa...');
        const marcadoresCreados = [];
        const puntosRuta = [];
        
        ubicacionesConCoords.forEach((ubicacion, index) => {
            console.log(` Creando marcador ${index + 1}/${ubicacionesConCoords.length}: ${ubicacion.nombre}`);
            
            const esUltimo = index === ubicacionesConCoords.length - 1;
            let marcador;
            
            if (ubicacion.movimiento) {
                // Marcador detallado para historial
                marcador = crearMarcadorHistorial(ubicacion, index + 1, esUltimo);
            } else {
                // Marcador simple
                marcador = crearMarcador(ubicacion.coordenadas, ubicacion.nombre, ubicacion.tipo);
            }
            
            if (marcador) {
                marcador.addTo(mapaInstance);
                marcadoresCreados.push(marcador);
                puntosRuta.push([ubicacion.coordenadas.lat, ubicacion.coordenadas.lng]);
                
                // Guardar según tipo de mapa
                if (tipo === 'principal') {
                    markers.push(marcador);
                } else {
                    markersHistorial.push(marcador);
                }
                
                console.log(` Marcador ${index + 1} agregado al mapa`);
            } else {
                console.warn(` No se pudo crear marcador para: ${ubicacion.nombre}`);
            }
        });
        
        console.log(` Total de marcadores creados: ${marcadoresCreados.length}`);
        
        // Crear polyline si hay múltiples puntos
        if (puntosRuta.length > 1) {
            console.log(` Creando polyline con ${puntosRuta.length} puntos`);
            
            const colorRuta = obtenerColorSegunEstado();
            
            const polyline = L.polyline(puntosRuta, {
                color: colorRuta,
                weight: 4,
                opacity: 0.8,
                dashArray: '10, 5', // Línea punteada para indicar ruta estimada
                lineCap: 'round',
                lineJoin: 'round'
            }).addTo(mapaInstance);
            
            // Popup para la polyline
            polyline.bindPopup(`
                <div style="text-align: center; padding: 8px;">
                    <h6 style="margin-bottom: 6px; color: ${colorRuta};">
                        <i class="fas fa-route me-2"></i>
                        <strong>Ruta estimada del envío</strong>
                    </h6>
                    <p style="margin: 4px 0; color: #666; font-size: 12px;">
                         Conexión directa entre ${puntosRuta.length} puntos
                    </p>
                    <small style="color: #999; font-size: 11px;">
                        Estado actual: ${getEstadoFinalTexto()}
                    </small>
                </div>
            `);
            
            // Guardar polyline según tipo de mapa
            if (tipo === 'principal') {
                routePolyline = polyline;
            } else {
                routePolylineHistorial = polyline;
            }
            
            console.log(` Polyline creada con color ${colorRuta}`);
        } else {
            console.log(' Solo hay un punto, no se creará polyline');
        }
        
        // Ajustar vista del mapa para mostrar todos los marcadores
        if (marcadoresCreados.length > 0) {
            console.log(' Ajustando vista del mapa...');
            
            try {
                const group = new L.featureGroup(marcadoresCreados);
                const bounds = group.getBounds();
                
                if (bounds.isValid()) {
                    mapaInstance.fitBounds(bounds.pad(0.15));
                    console.log(' Vista del mapa ajustada correctamente');
                } else {
                    console.warn(' Bounds inválidos, usando vista por defecto');
                    mapaInstance.setView([4.5709, -74.2973], 6);
                }
            } catch (e) {
                console.warn(' Error ajustando vista, usando vista por defecto:', e);
                mapaInstance.setView([4.5709, -74.2973], 6);
            }
        }
        
        console.log(` Ruta procesada exitosamente para mapa ${tipo}`);
        console.log(` Resumen: ${ubicacionesConCoords.length} ubicaciones, ${marcadoresCreados.length} marcadores, ${puntosRuta.length > 1 ? '1 polyline' : 'sin polyline'}`);
        
    } catch (error) {
        console.error(` Error procesando ruta para mapa ${tipo}:`, error);
        mostrarMensajeNoData(mapaInstance);
    }
}

/**
 * Inicializa el mapa principal
 */
function inicializarMapa() {
    console.log(' Inicializando mapa principal');
    
    try {
        // Sincronizar botones de estado
        sincronizarBotonesEstado();
        
        esperarElementoVisible('map', function(elemento) {
            try {
                console.log(' Elemento map encontrado, inicializando...');
                
                // Limpiar mapa anterior si existe
                if (map) {
                    console.log('🧹 Limpiando mapa principal anterior');
                    limpiarMapa(map, markers, routePolyline);
                    map.remove();
                    map = null;
                    routePolyline = null;
                }
                
                // Crear nuevo mapa centrado en Colombia
                console.log(' Creando nueva instancia de mapa principal');
                map = L.map('map').setView([4.5709, -74.2973], 6);
                
                // Agregar capa de OpenStreetMap
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 19
                }).addTo(map);
                
                console.log(' Mapa principal creado exitosamente');
                
                // Procesar ruta del envío
                setTimeout(() => {
                    console.log(' Iniciando procesamiento de ruta principal...');
                    procesarRutaEnvio(map, 'principal');
                }, 100);
                
            } catch (error) {
                console.error(' Error creando mapa principal:', error);
                mostrarErrorMapa('map');
            }
        });
        
    } catch (error) {
        console.error(' Error en inicializarMapa:', error);
        mostrarErrorMapa('map');
    }
}

/**
 * Inicializa el mapa del historial
 */
function inicializarMapaHistorial() {
    console.log(' Inicializando mapa historial');
    
    try {
        // Sincronizar botones de estado
        sincronizarBotonesEstado();
        
        esperarElementoVisible('mapHistorial', function(elemento) {
            try {
                console.log(' Elemento mapHistorial encontrado, inicializando...');
                
                // Limpiar mapa anterior si existe
                if (mapHistorial) {
                    console.log('🧹 Limpiando mapa historial anterior');
                    limpiarMapa(mapHistorial, markersHistorial, routePolylineHistorial);
                    mapHistorial.remove();
                    mapHistorial = null;
                    routePolylineHistorial = null;
                }
                
                // Crear nuevo mapa centrado en Colombia
                console.log(' Creando nueva instancia de mapa historial');
                mapHistorial = L.map('mapHistorial').setView([4.5709, -74.2973], 6);
                
                // Agregar capa de OpenStreetMap
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 19
                }).addTo(mapHistorial);
                
                console.log(' Mapa historial creado exitosamente');
                
                // Procesar ruta del envío
                setTimeout(() => {
                    console.log(' Iniciando procesamiento de ruta historial...');
                    procesarRutaEnvio(mapHistorial, 'historial');
                }, 100);
                
            } catch (error) {
                console.error(' Error creando mapa historial:', error);
                mostrarErrorMapa('mapHistorial');
            }
        });
        
    } catch (error) {
        console.error(' Error en inicializarMapaHistorial:', error);
        mostrarErrorMapa('mapHistorial');
    }
}

/**
 * Configura los event listeners del DOM
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log(' DOM cargado - Configurando sistema de mapas completo');
    console.log(' Fecha y hora:', new Date().toISOString());
    console.log(' Usuario:', 'Will-AGW');
    console.log(' Versión:', '2.3 - COMPLETE WORKING');
    
    try {
        // Mostrar datos de envío disponibles
        if (window.envioData) {
            console.log(' Datos de envío cargados:', window.envioData);
            
            // Sincronizar botones de estado inicial
            sincronizarBotonesEstado();
        } else {
            console.warn(' window.envioData no está disponible al cargar DOM');
            console.log(' Esto es normal si los datos se cargan después del DOM');
        }
        
        // Configurar botón del mapa principal
        const toggleBtn = document.getElementById('toggleMapBtn');
        if (toggleBtn) {
            console.log(' Configurando botón mapa principal');
            
            toggleBtn.addEventListener('click', function() {
                console.log(' Click en botón mapa principal - Estado actual:', isMapVisible);
                
                try {
                    const mapContainer = document.getElementById('mapContainer');
                    if (!mapContainer) {
                        console.error(' Contenedor mapContainer no encontrado');
                        return;
                    }
                    
                    const btn = this;
                    
                    if (!isMapVisible) {
                        // Mostrar mapa
                        console.log(' Mostrando mapa principal');
                        mapContainer.style.display = 'block';
                        btn.innerHTML = '<i class="fas fa-eye-slash me-2"></i>OCULTAR MAPA';
                        btn.classList.add('btn-active');
                        
                        setTimeout(() => {
                            inicializarMapa();
                        }, 100);
                        
                        isMapVisible = true;
                        console.log(' Mapa principal mostrado');
                    } else {
                        // Ocultar mapa
                        console.log(' Ocultando mapa principal');
                        mapContainer.style.display = 'none';
                        btn.innerHTML = '<i class="fas fa-map-marked-alt me-2"></i>VER MAPA';
                        btn.classList.remove('btn-active');
                        
                        if (map) {
                            limpiarMapa(map, markers, routePolyline);
                        }
                        
                        isMapVisible = false;
                        console.log(' Mapa principal ocultado');
                    }
                } catch (error) {
                    console.error(' Error en botón mapa principal:', error);
                }
            });
            
            console.log(' Event listener mapa principal configurado');
        } else {
            console.warn(' Botón toggleMapBtn no encontrado en el DOM');
        }

        // Configurar botón del mapa historial
        const toggleBtnHistorial = document.getElementById('toggleMapBtnHistorial');
        if (toggleBtnHistorial) {
            console.log(' Configurando botón mapa historial');
            
            toggleBtnHistorial.addEventListener('click', function() {
                console.log(' Click en botón mapa historial - Estado actual:', isMapHistorialVisible);
                
                try {
                    const mapContainer = document.getElementById('mapContainerHistorial');
                    if (!mapContainer) {
                        console.error(' Contenedor mapContainerHistorial no encontrado');
                        return;
                    }
                    
                    const btn = this;
                    
                    if (!isMapHistorialVisible) {
                        // Mostrar mapa historial
                        console.log(' Mostrando mapa historial');
                        mapContainer.style.display = 'block';
                        btn.innerHTML = '<i class="fas fa-eye-slash me-2"></i>OCULTAR MAPA';
                        btn.classList.add('btn-active');
                        
                        setTimeout(() => {
                            inicializarMapaHistorial();
                        }, 100);
                        
                        isMapHistorialVisible = true;
                        console.log(' Mapa historial mostrado');
                    } else {
                        // Ocultar mapa historial
                        console.log(' Ocultando mapa historial');
                        mapContainer.style.display = 'none';
                        btn.innerHTML = '<i class="fas fa-map-marked-alt me-2"></i>VER MAPA';
                        btn.classList.remove('btn-active');
                        
                        if (mapHistorial) {
                            limpiarMapa(mapHistorial, markersHistorial, routePolylineHistorial);
                        }
                        
                        isMapHistorialVisible = false;
                        console.log(' Mapa historial ocultado');
                    }
                } catch (error) {
                    console.error(' Error en botón mapa historial:', error);
                }
            });
            
            console.log(' Event listener mapa historial configurado');
        } else {
            console.warn(' Botón toggleMapBtnHistorial no encontrado en el DOM');
        }
        
        console.log(' Sistema de mapas completamente configurado y listo');
        console.log(' Funciones disponibles: inicializarMapa(), inicializarMapaHistorial()');
        
    } catch (error) {
        console.error(' Error configurando event listeners:', error);
    }
});