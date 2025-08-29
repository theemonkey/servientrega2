/**
 * Sistema de mapas para tracking de envíos - VERSIÓN CON ROUTING
 * Usa Leaflet + OpenStreetMap + Leaflet Routing Machine
 * Esta versión mantiene el routing externo para comparación
 * Se utiliza el servidor demo (no apto para producción)
 */

// Variables globales para instancias de mapas
let map;
let mapHistorial;
let isMapVisible = false;
let isMapHistorialVisible = false;

// Variables para marcadores y rutas (versión con routing)
let markers = [];
let routeControl = null; // Control de routing externo
let markersHistorial = [];
let routeControlHistorial = null; // Control de routing externo para historial

/**
 * Colores corporativos para visualización de estados
 * Cada color representa un estado específico del envío
 */
const COLORES = {
    VERDE: '#198754',    // Entregado (estado '3')
    AZUL: '#33549b',     // Puntos intermedios
    NARANJA: '#fd7e14',  // En Proceso (estados '1', '2', '5')
    ROJO: '#dc3545'      // Devuelto (estado '4')
};

/**
 * Función para limpiar marcadores y controles de routing del mapa
 * Remueve todos los elementos gráficos antes de crear nuevos
 * @param {Object} mapaInstance - Instancia del mapa de Leaflet
 * @param {Array} marcadores - Array de marcadores a remover
 * @param {Object} control - Control de routing a remover
 */
function limpiarMapa(mapaInstance, marcadores, control) {
    // Limpiar marcadores existentes
    if (marcadores && marcadores.length > 0) {
        marcadores.forEach(marker => {
            if (marker && mapaInstance.hasLayer(marker)) {
                mapaInstance.removeLayer(marker);
            }
        });
        marcadores.length = 0; // Vaciar el array
    }
    
    // Limpiar control de routing existente
    if (control && mapaInstance.hasLayer(control)) {
        mapaInstance.removeControl(control);
    }
}

/**
 * Función para esperar que un elemento DOM sea visible antes de proceder
 * Implementa polling para verificar la disponibilidad del elemento
 * @param {string} elementId - ID del elemento DOM a verificar
 * @param {Function} callback - Función a ejecutar cuando el elemento sea visible
 * @param {number} maxIntentos - Número máximo de intentos antes de abandonar
 */
function esperarElementoVisible(elementId, callback, maxIntentos = 30) {
    let intentos = 0;
    
    const verificar = () => {
        intentos++;
        const elemento = document.getElementById(elementId);
        
        // Verificar si el elemento existe y es visible
        if (elemento && elemento.offsetHeight > 0 && elemento.offsetWidth > 0) {
            console.log(`Elemento ${elementId} visible en intento ${intentos}`);
            setTimeout(() => callback(elemento), 50);
            return;
        }
        
        // Continuar intentando si no se ha alcanzado el límite
        if (intentos < maxIntentos) {
            setTimeout(verificar, 100);
        } else {
            console.error(`Elemento ${elementId} no visible después de ${maxIntentos} intentos`);
        }
    };
    
    verificar();
}

/**
 * Función para sincronizar botones de estado visual según el estado actual del envío
 * Actualiza las clases CSS para mostrar el estado correcto en la interfaz
 */
function sincronizarBotonesEstado() {
    const estadoActual = window.envioData.estadoActual;
    
    console.log('Sincronizando botones para estado:', estadoActual);
    
    // Buscar todos los botones de estado en el DOM
    const botonesEstado = document.querySelectorAll('.status-indicator');
    
    botonesEstado.forEach(boton => {
        // Remover clases activas previas para resetear el estado
        boton.classList.remove('status-entregado', 'status-devuelto', 'status-proceso');
        boton.classList.add('status-inactive');
        
        // Activar el botón correspondiente según la lógica del estado actual
        if (estadoActual === '3' && boton.textContent.includes('Entregado')) {
            // ENTREGADO - Aplicar estilo verde
            boton.classList.remove('status-inactive');
            boton.classList.add('status-entregado');
        } else if (estadoActual === '4' && boton.textContent.includes('Devuelto')) {
            // DEVUELTO - Aplicar estilo rojo
            boton.classList.remove('status-inactive');
            boton.classList.add('status-devuelto');
        } else if ((estadoActual === '1' || estadoActual === '2' || estadoActual === '5') && boton.textContent.includes('En Proceso')) {
            // EN PROCESO - Aplicar estilo naranja (estados 1, 2, 5)
            boton.classList.remove('status-inactive');
            boton.classList.add('status-proceso');
        }
    });
    
    console.log('Botones de estado sincronizados correctamente');
}

/**
 * Inicializar el mapa principal (Tabla Detalles)
 * Crea una nueva instancia de mapa con OpenStreetMap como base
 */
function inicializarMapa() {
    console.log('Inicializando mapa principal con OpenStreetMap...');
    
    // Verificar que existan datos de envío antes de proceder
    if (!window.envioData) {
        console.error('No hay datos disponibles para el mapa');
        return;
    }
    
    // Sincronizar estado visual de los botones
    sincronizarBotonesEstado();
    
    // Esperar a que el contenedor del mapa sea visible
    esperarElementoVisible('map', function(mapElement) {
        try {
            // Limpiar instancia anterior si existe
            if (map) {
                limpiarMapa(map, markers, routeControl);
                map.remove();
            }
            
        // Crear nuevo mapa centrado en Colombia
            map = L.map('map', {
                attributionControl: false // Mantener false
            }).setView([4.5709, -74.2973], 6);


            // Agregar capa base de OpenStreetMap
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(map);
            
            console.log('Mapa principal inicializado correctamente');
            
            // Cargar datos del envío en el mapa con delay para asegurar inicialización
            setTimeout(() => {
                cargarDatosEnMapa(map, 'principal');
            }, 100);
            
        } catch (error) {
            console.error('Error inicializando mapa:', error);
            mostrarErrorMapa('map');
        }
    });
}

/**
 * Inicializar el mapa del historial (Tabla Historial)
 * Crea una segunda instancia de mapa para mostrar el historial completo
 */
function inicializarMapaHistorial() {
    console.log('Inicializando mapa historial con OpenStreetMap...');
    
    // Verificar disponibilidad de datos
    if (!window.envioData) {
        console.error('No hay datos disponibles para el mapa');
        return;
    }
    
    // Sincronizar estado visual
    sincronizarBotonesEstado();
    
    // Esperar a que el contenedor del mapa historial sea visible
    esperarElementoVisible('mapHistorial', function(mapElement) {
        try {
            // Limpiar instancia anterior del mapa historial
            if (mapHistorial) {
                limpiarMapa(mapHistorial, markersHistorial, routeControlHistorial);
                mapHistorial.remove();
            }
            
            // Crear nuevo mapa historial centrado en Colombia
            mapHistorial = L.map('mapHistorial').setView([4.5709, -74.2973], 6);
            
            // Agregar capa base de OpenStreetMap
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(mapHistorial);
            
            console.log('Mapa historial inicializado correctamente');
            
            // Cargar datos del envío en el mapa historial
            setTimeout(() => {
                cargarDatosEnMapa(mapHistorial, 'historial');
            }, 100);
            
        } catch (error) {
            console.error('Error inicializando mapa historial:', error);
            mostrarErrorMapa('mapHistorial');
        }
    });
}

/**
 * Cargar y procesar datos del envío en el mapa especificado
 * Determina qué tipo de ruta mostrar según los datos disponibles
 * @param {Object} mapaInstance - Instancia del mapa donde cargar los datos
 * @param {string} tipo - Tipo de mapa ('principal' o 'historial')
 */
function cargarDatosEnMapa(mapaInstance, tipo) {
    console.log('Cargando datos en mapa:', window.envioData);
    
    // Priorizar mostrar movimientos del historial si están disponibles
    if (window.envioData.movimientos && window.envioData.movimientos.length > 0) {
        console.log('Mostrando ruta completa del historial de movimientos');
        mostrarRutaCompleta(mapaInstance, tipo);
    } else if (window.envioData.ciudadOrigen && window.envioData.ciudadDestino) {
        // Fallback a ruta simple origen-destino
        console.log('Calculando ruta simple entre origen y destino');
        calcularRutaSimple(mapaInstance, tipo);
    } else {
        // No hay datos suficientes para mostrar
        console.log('No hay datos suficientes para mostrar en el mapa');
        mostrarMensajeNoData(mapaInstance);
    }
}

/**
 * Mostrar ruta completa basada en el historial de movimientos con filtrado inteligente
 * Procesa todos los movimientos y filtra para mostrar solo puntos relevantes únicos
 * @param {Object} mapaInstance - Instancia del mapa
 * @param {string} tipo - Tipo de mapa ('principal' o 'historial')
 */
function mostrarRutaCompleta(mapaInstance, tipo) {
    console.log('Procesando', window.envioData.movimientos.length, 'movimientos del historial');
    console.log('Movimientos completos:', window.envioData.movimientos);
    
    // Crear secuencia de ubicaciones FILTRADA - Solo puntos relevantes únicos
    const ubicacionesRelevantes = [];
    const ciudadesYaVistas = new Set(); // Control de duplicados
    
    // Procesar cada movimiento del historial
    window.envioData.movimientos.forEach((movimiento, index) => {
        console.log(`Procesando movimiento ${index + 1}:`, movimiento);
        
        // Extraer ciudades del movimiento actual
        const ciudadOrigen = extraerCiudad(movimiento.OriMov);
        const ciudadDestino = extraerCiudad(movimiento.DesMov);
        
        // FILTRO 1: Agregar ciudad de origen solo si es nueva
        if (ciudadOrigen && !ciudadesYaVistas.has(ciudadOrigen.toLowerCase())) {
            ubicacionesRelevantes.push({
                nombre: ciudadOrigen,
                tipo: index === 0 ? 'origen' : 'intermedio', // Primer movimiento = origen
                orden: ubicacionesRelevantes.length + 1,
                movimiento: movimiento,
                fecha: movimiento.FecMov,
                nombreMovimiento: movimiento.NomMov,
                idProceso: movimiento.IdProc,
                razon: index === 0 ? 'Punto de origen' : 'Nueva ciudad en ruta'
            });
            ciudadesYaVistas.add(ciudadOrigen.toLowerCase());
            console.log(`Ciudad relevante agregada: ${ciudadOrigen} (${index === 0 ? 'origen' : 'nueva ciudad'})`);
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
            console.log(`Ciudad relevante agregada: ${ciudadDestino} (nueva ciudad)`);
        }
    });
    
    // Marcar el último elemento como destino final
    if (ubicacionesRelevantes.length > 0) {
        ubicacionesRelevantes[ubicacionesRelevantes.length - 1].tipo = 'destino';
        ubicacionesRelevantes[ubicacionesRelevantes.length - 1].razon = 'Destino final';
    }
    
    console.log(`Filtrado aplicado: ${window.envioData.movimientos.length} movimientos → ${ubicacionesRelevantes.length} puntos relevantes`);
    console.log('Ubicaciones relevantes seleccionadas:', ubicacionesRelevantes);
    
    // Verificar que hay ubicaciones para procesar
    if (ubicacionesRelevantes.length === 0) {
        console.log('No se encontraron ubicaciones relevantes');
        mostrarMensajeNoData(mapaInstance);
        return;
    }
    
    // Buscar coordenadas geográficas para todas las ubicaciones relevantes
    const promesasGeocodificacion = ubicacionesRelevantes.map(async (ubicacion, index) => {
        try {
            console.log(`Geocodificando punto relevante: ${ubicacion.nombre}`);
            const coordenadas = await buscarCoordenadas(ubicacion.nombre + ', Colombia');
            if (coordenadas) {
                console.log(`Coordenadas encontradas para ${ubicacion.nombre}:`, coordenadas);
                return { ...ubicacion, coordenadas, indiceOriginal: index };
            } else {
                console.log(`No se encontraron coordenadas para: ${ubicacion.nombre}`);
                return null;
            }
        } catch (error) {
            console.error('Error geocodificando:', ubicacion.nombre, error);
            return null;
        }
    });
    
    // Procesar resultados de geocodificación
    Promise.all(promesasGeocodificacion).then(resultados => {
        // Filtrar solo resultados válidos (con coordenadas)
        const ubicacionesConCoordenadas = resultados.filter(resultado => resultado !== null);
        
        console.log('Ubicaciones relevantes con coordenadas válidas:', ubicacionesConCoordenadas);
        
        // Verificar que hay ubicaciones geocodificadas
        if (ubicacionesConCoordenadas.length === 0) {
            console.log('No se pudieron geocodificar las ubicaciones relevantes');
            mostrarMensajeNoData(mapaInstance);
            return;
        }
        
        // Crear marcadores para cada ubicación relevante
        const marcadoresCreados = [];
        const puntosRuta = []; // Array para waypoints del routing
        
        ubicacionesConCoordenadas.forEach((ubicacion, index) => {
            const esUltimo = index === ubicacionesConCoordenadas.length - 1;
            const marcador = crearMarcadorHistorial(ubicacion, index + 1, esUltimo);
            
            // Agregar marcador al mapa
            marcador.addTo(mapaInstance);
            marcadoresCreados.push(marcador);
            
            // Crear waypoint para routing (formato L.latLng)
            puntosRuta.push(L.latLng(ubicacion.coordenadas.lat, ubicacion.coordenadas.lng));
            
            // Guardar marcadores según el tipo de mapa
            if (tipo === 'principal') {
                markers.push(marcador);
            } else {
                markersHistorial.push(marcador);
            }
            
            console.log(`Marcador relevante ${index + 1} creado: ${ubicacion.nombre} (${ubicacion.razon})`);
        });
        
        // Crear ruta secuencial con Leaflet Routing Machine y colores corporativos
        if (puntosRuta.length > 1) {
            let colorRuta;
            
            // Determinar color de la ruta según el estado del envío
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
            
            // Crear control de routing con configuración personalizada
            const control = L.Routing.control({
                waypoints: puntosRuta, // Puntos de la ruta
                routeWhileDragging: false, // Deshabilitar drag
                addWaypoints: false, // No permitir agregar waypoints
                createMarker: function() { return null; }, // No crear marcadores automáticos
                lineOptions: {
                    styles: [{ 
                        color: colorRuta, 
                        weight: 4, 
                        opacity: 0.8 
                    }]
                },
                show: false // Ocultar panel de instrucciones
            }).addTo(mapaInstance);
            
            // Guardar control de ruta según el tipo de mapa
            if (tipo === 'principal') {
                routeControl = control;
            } else {
                routeControlHistorial = control;
            }
            
            console.log(`Ruta relevante creada con ${puntosRuta.length} puntos, color: ${colorRuta}`);
        }
        
        // Ajustar vista del mapa para mostrar toda la ruta
        if (marcadoresCreados.length > 0) {
            const group = new L.featureGroup(marcadoresCreados);
            mapaInstance.fitBounds(group.getBounds().pad(0.1)); // Padding del 10%
        }
        
        console.log(`Ruta relevante completada: ${ubicacionesConCoordenadas.length} puntos mostrados de ${window.envioData.movimientos.length} movimientos totales`);
        
    }).catch(error => {
        console.error('Error creando ruta relevante:', error);
        mostrarMensajeNoData(mapaInstance);
    });
}

/**
 * Función auxiliar para extraer nombre de ciudad limpio de diferentes formatos
 * Maneja tanto strings como arrays y extrae solo el nombre principal
 * @param {string|Array} valorCiudad - Valor que puede contener el nombre de ciudad
 * @returns {string|null} - Nombre de ciudad limpio o null si no es válido
 */
function extraerCiudad(valorCiudad) {
    if (!valorCiudad) return null;
    
    // Manejar arrays (tomar primer elemento)
    let ciudad = Array.isArray(valorCiudad) ? valorCiudad[0] : valorCiudad;
    
    if (typeof ciudad !== 'string') return null;
    
    // Extraer solo el nombre de la ciudad (antes del paréntesis si existe)
    const nombreCiudad = ciudad.split('(')[0].trim();
    return limpiarNombreCiudad(nombreCiudad);
}

/**
 * Función auxiliar para limpiar y normalizar nombres de ciudades
 * Remueve caracteres especiales y normaliza formato para comparaciones
 * @param {string} ciudad - Nombre de ciudad a limpiar
 * @returns {string|null} - Nombre limpio y normalizado
 */
function limpiarNombreCiudad(ciudad) {
    if (!ciudad || typeof ciudad !== 'string') return null;
    
    return ciudad
        .trim()  // Remover espacios inicio/fin
        .replace(/\s+/g, ' ')  // Normalizar espacios múltiples
        .toUpperCase()  // Convertir a mayúsculas para comparaciones consistentes
        .replace(/[^\w\sñáéíóúü]/gi, '')  // Remover caracteres especiales (conservar español)
        .trim();  // Trim final
}

/**
 * Crear marcador específico para el historial sin emojis con iconos estándar de Leaflet
 * Genera marcadores personalizados con colores según tipo y estado del envío
 * @param {Object} ubicacion - Datos completos de la ubicación
 * @param {number} numero - Número secuencial de la parada
 * @param {boolean} esUltimo - Indica si es el último punto de la ruta
 * @returns {Object} - Marcador de Leaflet configurado
 */
function crearMarcadorHistorial(ubicacion, numero, esUltimo) {
    let iconUrl, colorTexto;
    
    // Determinar color del marcador e icono estándar según tipo y estado
    if (ubicacion.tipo === 'origen') {
        // Punto de origen siempre verde
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
    
    // Crear icono personalizado con URL y sombra
    const customIcon = L.icon({
        iconUrl: iconUrl,
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });
    
    // Determinar texto del estado sin emojis
    let estadoTexto = '';
    
    if (ubicacion.tipo === 'origen') {
        estadoTexto = 'Punto de origen';
    } else if (ubicacion.tipo === 'destino' || esUltimo) {
        // Texto según estado final del envío
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
    
    // Crear contenido del popup sin emojis, sin badges, solo colores de fuente
    let popupContent = `
        <div style="min-width: 250px; max-width: 300px;">
            <h6 style="margin-bottom: 8px; color: ${colorTexto}; font-weight: bold;">
                Parada ${numero}: ${ubicacion.nombre}
            </h6>
            <p style="margin: 4px 0; color: ${colorTexto}; font-size: 12px; font-weight: bold;">${estadoTexto}</p>
    `;
    
    // Agregar información del movimiento si está disponible
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
    
    // Crear y retornar marcador con popup configurado
    return L.marker([ubicacion.coordenadas.lat, ubicacion.coordenadas.lng], { icon: customIcon })
        .bindPopup(popupContent);
}

/**
 * Calcular ruta simple entre origen y destino (fallback cuando no hay historial)
 * Crea una ruta básica de dos puntos cuando no hay movimientos detallados
 * @param {Object} mapaInstance - Instancia del mapa
 * @param {string} tipo - Tipo de mapa ('principal' o 'historial')
 */
function calcularRutaSimple(mapaInstance, tipo) {
    const origen = window.envioData.ciudadOrigen;
    const destino = window.envioData.ciudadDestino;
    
    console.log('Calculando ruta simple entre:', origen, 'y', destino);
    
    // Geocodificar ambas ciudades en paralelo
    Promise.all([
        buscarCoordenadas(origen + ', Colombia'),
        buscarCoordenadas(destino + ', Colombia')
    ]).then(([coordOrigen, coordDestino]) => {
        if (coordOrigen && coordDestino) {
            // Crear marcadores simples con iconos estándar
            const marcadorOrigen = crearMarcadorSimple(coordOrigen, origen, 'origen');
            const marcadorDestino = crearMarcadorSimple(coordDestino, destino, 'destino');
            
            // Agregar marcadores al mapa
            marcadorOrigen.addTo(mapaInstance);
            marcadorDestino.addTo(mapaInstance);
            
            // Guardar marcadores según el tipo de mapa
            if (tipo === 'principal') {
                markers.push(marcadorOrigen, marcadorDestino);
            } else {
                markersHistorial.push(marcadorOrigen, marcadorDestino);
            }
            
            // Crear ruta con colores corporativos según estado
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
            
            // Crear control de routing simple entre dos puntos
            const control = L.Routing.control({
                waypoints: [
                    L.latLng(coordOrigen.lat, coordOrigen.lng),
                    L.latLng(coordDestino.lat, coordDestino.lng)
                ],
                routeWhileDragging: false,
                addWaypoints: false,
                createMarker: function() { return null; }, // No crear marcadores automáticos
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
            
            // Ajustar vista para mostrar ambos puntos
            const group = new L.featureGroup([marcadorOrigen, marcadorDestino]);
            mapaInstance.fitBounds(group.getBounds().pad(0.1));
            
            console.log('Ruta simple creada exitosamente');
        } else {
            // No se pudieron geocodificar las ciudades
            mostrarMensajeNoData(mapaInstance);
        }
    }).catch(error => {
        console.error('Error al calcular ruta simple:', error);
        mostrarMensajeNoData(mapaInstance);
    });
}

/**
 * Crear marcador simple con iconos estándar para rutas básicas
 * Utilizado en rutas simples origen-destino sin historial detallado
 * @param {Object} coordenadas - Objeto con lat y lng
 * @param {string} titulo - Título a mostrar en el popup
 * @param {string} tipo - Tipo de marcador ('origen' o 'destino')
 * @returns {Object} - Marcador de Leaflet configurado
 */
function crearMarcadorSimple(coordenadas, titulo, tipo) {
    let iconUrl;
    
    // Determinar icono según tipo de marcador
    switch(tipo) {
        case 'origen':
            iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png';
            break;
        case 'destino':
            // Color del destino según estado del envío
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
    
    // Crear icono personalizado
    const customIcon = L.icon({
        iconUrl: iconUrl,
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });
    
    // Determinar texto del estado para marcadores de destino
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
    
    // Crear y retornar marcador con popup simple
    return L.marker([coordenadas.lat, coordenadas.lng], { icon: customIcon })
        .bindPopup(`<b>${titulo}${estado}</b>`);
}

/**
 * Buscar coordenadas geográficas usando el servicio Nominatim (gratuito)
 * Utiliza la API de OpenStreetMap para geocodificación
 * @param {string} direccion - Dirección o ciudad a geocodificar
 * @returns {Promise<Object|null>} - Promesa que resuelve a coordenadas o null
 */
async function buscarCoordenadas(direccion) {
    try {
        // Agregar delay para evitar rate limiting del servicio gratuito
        await new Promise(resolve => setTimeout(resolve, 100));
        
        // Hacer petición a la API de Nominatim
        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(direccion)}&limit=1&countrycodes=CO`);
        const data = await response.json();
        
        // Verificar si se encontraron resultados
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

/**
 * Mostrar mensaje cuando no hay datos de ubicación disponibles
 * Muestra un popup centrado informando la ausencia de datos
 * @param {Object} mapaInstance - Instancia del mapa donde mostrar el mensaje
 */
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

/**
 * Mostrar mensaje de error cuando el mapa no se puede cargar
 * Reemplaza el contenedor del mapa con un mensaje de error visual
 * @param {string} containerId - ID del contenedor del mapa
 */
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

/**
 * Configurar event listeners cuando el DOM esté completamente cargado
 * Maneja la interacción del usuario con los botones de mostrar/ocultar mapas
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, configurando event listeners para OpenStreetMap');
    
    try {
        // Sincronizar botones de estado al cargar la página
        if (window.envioData) {
            sincronizarBotonesEstado();
        }
        
        // Configurar botón del mapa principal
        const toggleBtn = document.getElementById('toggleMapBtn');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                try {
                    const mapContainer = document.getElementById('mapContainer');
                    const btn = this;
                    
                    console.log('Click en botón mapa principal. Estado actual:', isMapVisible);
                    
                    if (!isMapVisible) {
                        // Mostrar mapa principal
                        mapContainer.style.display = 'block';
                        btn.innerHTML = '<i class="fas fa-eye-slash me-2"></i>OCULTAR MAPA';
                        btn.classList.add('btn-active');
                        
                        // Inicializar mapa con delay para asegurar visibilidad
                        setTimeout(() => {
                            inicializarMapa();
                        }, 100);
                        
                        isMapVisible = true;
                    } else {
                        // Ocultar mapa principal
                        mapContainer.style.display = 'none';
                        btn.innerHTML = '<i class="fas fa-map-marked-alt me-2"></i>VER MAPA';
                        btn.classList.remove('btn-active');
                        
                        // Limpiar mapa si existe
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

        // Configurar botón del mapa historial
        const toggleBtnHistorial = document.getElementById('toggleMapBtnHistorial');
        if (toggleBtnHistorial) {
            toggleBtnHistorial.addEventListener('click', function() {
                try {
                    const mapContainer = document.getElementById('mapContainerHistorial');
                    const btn = this;
                    
                    console.log('Click en botón mapa historial. Estado actual:', isMapHistorialVisible);
                    
                    if (!isMapHistorialVisible) {
                        // Mostrar mapa historial
                        mapContainer.style.display = 'block';
                        btn.innerHTML = '<i class="fas fa-eye-slash me-2"></i>OCULTAR MAPA';
                        btn.classList.add('btn-active');
                        
                        // Inicializar mapa historial con delay
                        setTimeout(() => {
                            inicializarMapaHistorial();
                        }, 100);
                        
                        isMapHistorialVisible = true;
                    } else {
                        // Ocultar mapa historial
                        mapContainer.style.display = 'none';
                        btn.innerHTML = '<i class="fas fa-map-marked-alt me-2"></i>VER MAPA';
                        btn.classList.remove('btn-active');
                        
                        // Limpiar mapa historial si existe
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