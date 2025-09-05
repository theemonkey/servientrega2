<?php
/**
 * ===============================================
 * VISTA DE RESULTADOS DE RASTREO DE GUÍAS DIRECTO(sin formulario)
 * ===============================================
 *
 * Descripción: Vista principal para mostrar información detallada de la guia
 * de rastreo con pestañas para detalles e historial, incluyendo mapas interactivos
 *
 * Funcionalidades:
 * - Navegación con breadcrumb
 * - Pestañas Bootstrap (Detalles/Historial)
 * - Mapas interactivos con Leaflet/OpenStreetMap
 * - Disclaimer externo para información legal
 * - Modal para visualización de comprobantes
 * - Sistema de estados con colores dinámicos
 * - Responsive design
 */
?>

@extends('layout/plantilla')

@section('tituloPagina', 'Resultado de la Guía')

@section('contenido')
{{--
    =======================================
    NAVEGACIÓN BREADCRUMB
    =======================================
    Implementa navegación segura con validación de URLs
    Evita loops infinitos en tracking
--}}
    <nav aria-label="breadcrumb" class="mt-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                @php
                    /**
                     * Generación de URL de retorno segura
                     * Valida que no sea una URL de tracking para evitar loops
                     * Establece fallback a página principal si no hay origen válido
                     */
                    $volverUrl = '/';
                    if (isset($urlOrigen) && $urlOrigen) {
                        // Validar que no sea una URL de tracking para evitar loops
                        if (!str_contains($urlOrigen, 'guia/') && !str_contains($urlOrigen, 'error')) {
                            $volverUrl = $urlOrigen;
                        }
                    }
                @endphp
                {{-- Botón de retorno con icono FontAwesome --}}
                <a href="{{ $volverUrl }}" class="text-decoration-none">
                    <i class="fas fa-arrow-left me-2"></i>Volver
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                {{-- Indicador de página actual con número de guía --}}
                <i class="fas fa-box me-2"></i>Guía #{{ $numeroGuia ?? 'Sin número' }}
            </li>
        </ol>
    </nav>

    {{--
        ======================================
        CONTENEDOR PRINCIPAL CON PESTAÑAS
        ======================================
        Estructura de card Bootstrap con sistema de tabs
    --}}
    <div class="card shadow-sm mt-5">
        {{-- Header de la card con navegación por pestañas --}}
        <div class="card-header bg-light">
            <ul class="nav nav-tabs card-header-tabs" id="myTab" role="tablist">
                {{-- Pestaña Detalles (activa por defecto) --}}
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="detalle-tab" data-bs-toggle="tab" data-bs-target="#detalle"
                            type="button" role="tab" aria-controls="detalle" aria-selected="true">
                        <i class="fas fa-info-circle me-2"></i>Detalles
                    </button>
                </li>
                {{-- Pestaña Historial --}}
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="historial-tab" data-bs-toggle="tab" data-bs-target="#historial"
                            type="button" role="tab" aria-controls="historial" aria-selected="false">
                        <i class="fas fa-history me-2"></i>Historial
                    </button>
                </li>
            </ul>
        </div>

        {{-- Contenido de las pestañas --}}
        <div class="card-body tab-content" id="myTabContent">

            {{--
                ===========================
                PESTAÑA DETALLES
                ===========================
                Muestra información completa del envío con mapa interactivo
            --}}
            <div class="tab-pane fade show active" id="detalle" role="tabpanel" aria-labelledby="detalle-tab">

                {{-- Verificación de existencia de datos de movimientos --}}
                @if(isset($respuesta['Mov']))
                    @php
                        /**
                         * ==============================
                         * PROCESAMIENTO DE DATOS PHP
                         * ==============================
                         * Normaliza y procesa los datos recibidos de la API
                         */

                        /**
                         * Normalización de movimientos
                         * Maneja tanto arrays como valores únicos
                         */
                        $movimientos = is_array($respuesta['Mov']['InformacionMov'])
                            ? $respuesta['Mov']['InformacionMov']
                            : [$respuesta['Mov']['InformacionMov']];
                        $ultimoMovimiento = end($movimientos);

                        /**
                         * Mapeo de estados del envío
                         * Cada ID corresponde a un estado específico del proceso logístico
                         */
                        $estadosMap = [
                            '1' => 'RECIBIDO DEL CLIENTE',    // Estado inicial - paquete recibido
                            '2' => 'EN PROCESAMIENTO',        // En tránsito o procesándose
                            '3' => 'ENTREGADO',              // Entrega exitosa al destinatario
                            '4' => 'ENTREGADO A REMITENTE',  // Devuelto al remitente
                            '5' => 'SINIESTRADO'             // Problema/pérdida del envío
                        ];

                        /**
                         * Obtención del estado actual
                         * Maneja arrays y valores únicos de forma segura
                         */
                        $idEstadoActual = is_array($respuesta['IdEstAct'] ?? null)
                            ? implode(', ', $respuesta['IdEstAct'])
                            : ($respuesta['IdEstAct'] ?? null);

                        /**
                         * Descripción textual del estado
                         * Usa el mapeo definido o fallback a datos originales
                         */
                        $estadoActualTexto = $estadosMap[$idEstadoActual] ?? ($respuesta['EstAct'] ?? 'No disponible');

                        /**
                         * Sistema de colores para badges según estado
                         * Cada estado tiene un color específico para UX
                         */
                        $badgeColorMap = [
                            '1' => 'badge-recibido',      // Azul - estado inicial
                            '2' => 'badge-procesamiento', // Amarillo - en proceso
                            '3' => 'badge-entregado',     // Verde - éxito
                            '4' => 'badge-devolucion',    // Naranja - devolución
                            '5' => 'badge-siniestrado'    // Rojo - problema
                        ];

                        /**
                         * Determinar color del badge
                         * Fallback a 'procesamiento' si no hay estado definido
                         */
                        $badgeColor = $badgeColorMap[$idEstadoActual] ?? 'badge-procesamiento';

                        /**
                         * Procesamiento de PQR (Peticiones, Quejas y Reclamos)
                         * Valida y procesa el número CUN para mostrar observaciones
                         */
                        $numCun = $respuesta['NumCun'] ?? null;
                        $pqrTexto = 'Sin observaciones'; // Valor por defecto

                        if (!empty($numCun)) {
                            if (is_array($numCun)) {
                                $numCunValue = implode(', ', $numCun);
                                if ($numCunValue && $numCunValue !== '0' && trim($numCunValue) !== '') {
                                    $pqrTexto = $numCunValue;
                                }
                            } else {
                                if ($numCun && $numCun !== '0' && $numCun !== 0 && trim($numCun) !== '') {
                                    $pqrTexto = $numCun;
                                }
                            }
                        }
                    @endphp

                    {{--
                        ==================================
                        HEADER CON INFORMACIÓN PRINCIPAL
                        ==================================
                        Muestra número de guía y estado actual de forma prominente
                    --}}
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            {{-- Número de guía prominente --}}
                            <h5 class="card-title mb-0">Número de guía:
                                {{ is_array($respuesta['NumGui'] ?? null) ? implode(', ', $respuesta['NumGui']) : ($respuesta['NumGui'] ?? 'No disponible') }}
                            </h5>
                        </div>
                        <div class="d-flex align-items-center">
                            {{-- Estado actual con badge de color dinámico --}}
                            <span class="text-secondary me-2">
                                <strong><i class="fas fa-clipboard-check me-2"></i>Estado actual:</strong>
                            </span>
                            <span class="badge {{ $badgeColor }}">{{ $estadoActualTexto }}</span>
                        </div>
                    </div>

                    {{--
                        ==============================
                        INFORMACIÓN EN DOS COLUMNAS
                        ==============================
                        Layout responsivo con información del remitente y destinatario
                    --}}
                    <div class="row">

                        {{--
                            COLUMNA IZQUIERDA: INFORMACIÓN DEL REMITENTE
                        --}}
                        <div class="col-md-6">
                            <h5 class="text-secondary mb-3">Información de Origen (Remitente)</h5>

                            {{-- Nombre del remitente --}}
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Nombre</div>
                                    <div class="info-value">{{ is_array($respuesta['NomRem'] ?? null) ? implode(', ', $respuesta['NomRem']) : ($respuesta['NomRem'] ?? 'No disponible') }}</div>
                                </div>
                            </div>

                            {{-- Ciudad de recogida --}}
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Ciudad de recogida</div>
                                    <div class="info-value">{{ is_array($respuesta['CiuRem'] ?? null) ? implode(', ', $respuesta['CiuRem']) : ($respuesta['CiuRem'] ?? 'No disponible') }}</div>
                                </div>
                            </div>

                            {{-- Dirección del remitente --}}
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-home"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Dirección</div>
                                    <div class="info-value">{{ is_array($respuesta['DirRem'] ?? null) ? implode(', ', $respuesta['DirRem']) : ($respuesta['DirRem'] ?? 'No disponible') }}</div>
                                </div>
                            </div>

                            {{-- Fecha de envío --}}
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Fecha de Envío</div>
                                    <div class="info-value">{{ is_array($respuesta['FecEnv'] ?? null) ? implode(', ', $respuesta['FecEnv']) : ($respuesta['FecEnv'] ?? 'No disponible') }}</div>
                                </div>
                            </div>

                            {{-- Número de piezas --}}
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-cubes"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Número de Piezas</div>
                                    <div class="info-value">{{ is_array($respuesta['NumPie'] ?? null) ? implode(', ', $respuesta['NumPie']) : ($respuesta['NumPie'] ?? 'No disponible') }}</div>
                                </div>
                            </div>

                            {{-- Tipo de régimen --}}
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Tipo de Régimen</div>
                                    <div class="info-value">{{ is_array($respuesta['Regime'] ?? null) ? implode(', ', $respuesta['Regime']) : ($respuesta['Regime'] ?? 'No disponible') }}</div>
                                </div>
                            </div>
                        </div>

                        {{--
                            COLUMNA DERECHA: INFORMACIÓN DEL DESTINATARIO
                        --}}
                        <div class="col-md-6">
                            <h5 class="text-secondary mb-3">Información de Destino (Destinatario)</h5>

                            {{-- Nombre del destinatario --}}
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Nombre</div>
                                    <div class="info-value">{{ is_array($respuesta['NomDes'] ?? null) ? implode(', ', $respuesta['NomDes']) : ($respuesta['NomDes'] ?? 'No disponible') }}</div>
                                </div>
                            </div>

                            {{-- Ciudad de destino --}}
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Ciudad de destino</div>
                                    <div class="info-value">{{ is_array($respuesta['CiuDes'] ?? null) ? implode(', ', $respuesta['CiuDes']) : ($respuesta['CiuDes'] ?? 'No disponible') }}</div>
                                </div>
                            </div>

                            {{-- Dirección del destinatario --}}
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-home"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Dirección</div>
                                    <div class="info-value">{{ is_array($respuesta['DirDes'] ?? null) ? implode(', ', $respuesta['DirDes']) : ($respuesta['DirDes'] ?? 'No disponible') }}</div>
                                </div>
                            </div>

                            {{-- Fecha de entrega estimada --}}
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-calendar"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Fecha de entrega</div>
                                    <div class="info-value">{{ is_array($respuesta['FecEst'] ?? null) ? implode(', ', $respuesta['FecEst']) : ($respuesta['FecEst'] ?? 'No disponible') }}</div>
                                </div>
                            </div>

                            {{-- Hora de entrega (extraída de la fecha) --}}
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Hora de entrega</div>
                                    <div class="info-value">
                                        {{ isset($respuesta['FecEst']) ? date('H:i', strtotime(is_array($respuesta['FecEst']) ? $respuesta['FecEst'][0] : $respuesta['FecEst'])) : 'No disponible' }}
                                    </div>
                                </div>
                            </div>

                            {{-- PQR (Peticiones, Quejas, Reclamos) --}}
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">PQR</div>
                                    <div class="info-value">{{ $pqrTexto }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Separador visual --}}
                    <hr>

                    {{--
                        =========================
                        INFORMACIÓN ADICIONAL
                        =========================
                        Datos complementarios del envío
                    --}}
                    <h5 class="text-secondary mb-3">Información extra</h5>

                    {{-- Receptor que recibió el paquete --}}
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Receptor</div>
                            <div class="info-value">{{ is_array($respuesta['NomRec'] ?? null) ? implode(', ', $respuesta['NomRec']) : ($respuesta['NomRec'] ?? 'No disponible') }}</div>
                        </div>
                    </div>

                    {{-- Forma de pago utilizada --}}
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Forma de pago</div>
                            <div class="info-value">{{ is_array($respuesta['FormPago'] ?? null) ? implode(', ', $respuesta['FormPago']) : ($respuesta['FormPago'] ?? 'No disponible') }}</div>
                        </div>
                    </div>

                    {{-- Tipo de producto enviado --}}
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Producto</div>
                            <div class="info-value">{{ is_array($respuesta['NomProducto'] ?? null) ? implode(', ', $respuesta['NomProducto']) : ($respuesta['NomProducto'] ?? 'No disponible') }}</div>
                        </div>
                    </div>

                    {{-- Placa del vehículo transportador --}}
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Placa vehículo</div>
                            <div class="info-value">{{ is_array($respuesta['Placa'] ?? null) ? implode(', ', $respuesta['Placa']) : ($respuesta['Placa'] ?? 'No disponible') }}</div>
                        </div>
                    </div>

                    {{-- Fecha del último movimiento registrado --}}
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Fecha de último movimiento</div>
                            <div class="info-value">{{ is_array($respuesta['FechaProbable'] ?? null) ? implode(', ', $respuesta['FechaProbable']) : ($respuesta['FechaProbable'] ?? 'No disponible') }}</div>
                        </div>
                    </div>

                    {{--
                        =========================
                        BOTONES DE ACCIÓN
                        =========================
                        Controles para ver comprobante y mapa
                    --}}
                    <div class="buttons-container">
                        {{-- Botón para abrir modal de comprobante --}}
                        <button type="button" class="btn-custom" data-bs-toggle="modal" data-bs-target="#comprobanteModal">
                            <i class="fas fa-receipt me-2"></i>VER COMPROBANTE
                        </button>
                        {{-- Botón para mostrar/ocultar mapa --}}
                        <button type="button" class="btn-custom" id="toggleMapBtn">
                            <i class="fas fa-map-marked-alt me-2"></i>VER MAPA
                        </button>
                    </div>

                    {{--
                        ===============================================
                        CONTENEDOR DEL MAPA INTERACTIVO - DETALLES
                        ===============================================
                        Mapa Leaflet con indicadores de estado y disclaimer externo
                    --}}
                    <div id="mapContainer" class="map-container mt-4" style="display: none;">

                        {{-- Header del mapa con indicadores de estado --}}
                        <div class="map-header">
                            {{-- Indicadores visuales del estado del envío --}}
                            <div class="map-status-indicators">
                                {{-- Estado: Entregado --}}
                                <span class="status-indicator {{ ($idEstadoActual == '3') ? 'status-entregado' : 'status-inactive' }}">
                                    <i class="fas fa-check-circle"></i> Entregado
                                </span>
                                {{-- Estado: Devuelto --}}
                                <span class="status-indicator {{ ($idEstadoActual == '4') ? 'status-devuelto' : 'status-inactive' }}">
                                    <i class="fas fa-undo-alt"></i> Devuelto
                                </span>
                                {{-- Estado: En Proceso --}}
                                <span class="status-indicator {{ ($idEstadoActual == '2') ? 'status-proceso' : 'status-inactive' }}">
                                    <i class="fas fa-truck"></i> En Proceso
                                </span>
                            </div>
                            {{-- Disclaimer informativo sobre disponibilidad del mapa --}}
                            <p class="map-disclaimer">
                                <i class="fas fa-info-circle me-1"></i>
                                El rastreo de envíos en el mapa solo aplica para ciudades principales.
                            </p>
                        </div>

                        {{--
                            Contenedor del mapa sin disclaimer interno
                            Solo esquinas superiores redondeadas para continuidad visual
                        --}}
                        <div class="map-wrapper" style="height: 400px; position: relative; overflow: hidden; border-radius: 8px 8px 0 0;">
                            {{-- Elemento donde se renderiza el mapa Leaflet --}}
                            <div id="map" style="height: 100%; width: 100%;"></div>
                        </div>

                        {{--
                            Disclaimer externo debajo del mapa
                            Información legal sobre precisión de coordenadas
                        --}}
                        <div class="map-external-disclaimer">
                            <div class="disclaimer-content">
                                <i class="fas fa-info-circle me-2 text-muted" aria-hidden="true"></i>
                                <small class="text-muted">
                                    Este mapa utiliza datos de OpenStreetMap para proporcionar coordenadas y una ubicación aproximadas.
                                    Ten en cuenta que, por su naturaleza, las coordenadas son una referencia general y pueden no
                                    representar la ubicación exacta del envío.
                                </small>
                            </div>
                        </div>
                    </div>

                {{-- Mensaje de error si no hay datos disponibles --}}
                @else
                    <div class="alert alert-warning" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No se encontraron datos para esta guía.
                    </div>
                @endif
            </div>

            {{--
                ======================
                PESTAÑA HISTORIAL
                ======================
                Timeline de movimientos del envío con mapa interactivo
            --}}
            <div class="tab-pane fade" id="historial" role="tabpanel" aria-labelledby="historial-tab">
                <h5 class="mb-3">Historial de Movimientos</h5>

                {{-- Verificación de existencia de movimientos --}}
                @if(isset($respuesta['Mov']))
                    @php
                        /**
                         * Re-procesamiento de movimientos para la pestaña historial
                         * Mantiene la misma lógica de normalización
                         */
                        $movimientos = is_array($respuesta['Mov']['InformacionMov'])
                            ? $respuesta['Mov']['InformacionMov']
                            : [$respuesta['Mov']['InformacionMov']];
                    @endphp

                    {{-- Verificación de que existan movimientos para mostrar --}}
                    @if(count($movimientos) > 0)
                        {{--
                            Lista de movimientos con diseño de timeline
                            Cada item representa un evento en la historia del envío
                        --}}
                        <ul class="list-group">
                            @foreach($movimientos as $movimiento)
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    {{-- Información principal del movimiento --}}
                                    <div class="ms-2 me-auto">
                                        {{-- Nombre/descripción del movimiento --}}
                                        <div class="fw-bold">
                                            <i class="fas fa-shipping-fast text-primary me-2"></i>
                                            {{ is_array($movimiento['NomMov'] ?? null) ? implode(', ', $movimiento['NomMov']) : ($movimiento['NomMov'] ?? 'Sin descripción') }}
                                        </div>

                                        {{-- Ubicación de origen del movimiento --}}
                                        <div class="mt-2">
                                            <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                            <strong>Origen:</strong> {{ is_array($movimiento['OriMov'] ?? null) ? implode(', ', $movimiento['OriMov']) : ($movimiento['OriMov'] ?? 'N/A') }}
                                        </div>

                                        {{-- Ubicación de destino del movimiento --}}
                                        <div>
                                            <i class="fas fa-map-marker-alt text-success me-2"></i>
                                            <strong>Destino:</strong> {{ is_array($movimiento['DesMov'] ?? null) ? implode(', ', $movimiento['DesMov']) : ($movimiento['DesMov'] ?? 'N/A') }}
                                        </div>

                                        {{-- Fecha del movimiento --}}
                                        <div class="mt-2">
                                            <i class="fas fa-calendar-alt text-info me-2"></i>
                                            <strong>Fecha:</strong> {{ is_array($movimiento['FecMov'] ?? null) ? implode(', ', $movimiento['FecMov']) : ($movimiento['FecMov'] ?? 'N/A') }}
                                        </div>

                                        {{-- Tipo de movimiento con lógica condicional --}}
                                        @if(isset($movimiento['TipoMov']))
                                            <div class="mt-1">
                                                <i class="fas fa-tag text-secondary me-2"></i>
                                                <strong>Tipo:</strong>
                                                @php
                                                    /**
                                                     * Normalización del tipo de movimiento
                                                     * Maneja arrays tomando el primer elemento
                                                     */
                                                    $tipoMov = is_array($movimiento['TipoMov']) ? $movimiento['TipoMov'][0] : $movimiento['TipoMov'];
                                                @endphp

                                                {{-- Clasificación de tipos de movimiento --}}
                                                @if($tipoMov == 1)
                                                    <span class="tipo-devolucion">Devolución</span>
                                                @elseif($tipoMov == 2)
                                                    <span class="tipo-procesamiento">Novedad en la operación</span>
                                                @elseif($tipoMov == 0)
                                                    <span class="tipo-sin-novedad">Sin novedad</span>
                                                @else
                                                    <span class="tipo-default">{{ $tipoMov }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    {{-- ID del proceso en badge --}}
                                    <div class="d-flex flex-column align-items-end">
                                        <span class="badge bg-secondary mb-1">
                                            ID: {{ is_array($movimiento['IdProc'] ?? null) ? implode(', ', $movimiento['IdProc']) : ($movimiento['IdProc'] ?? 'N/A') }}
                                        </span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>

                        {{--
                            ===============================
                            BOTONES DE ACCIÓN - HISTORIAL
                            ===============================
                            Controles duplicados para la pestaña historial
                        --}}
                        <div class="buttons-container mt-4">
                            {{-- Botón para abrir modal de comprobante --}}
                            <button type="button" class="btn-custom" data-bs-toggle="modal" data-bs-target="#comprobanteModal">
                                <i class="fas fa-receipt me-2"></i>VER COMPROBANTE
                            </button>

                            {{-- Botón para mostrar/ocultar mapa del historial --}}
                            <button type="button" class="btn-custom" id="toggleMapBtnHistorial">
                                <i class="fas fa-map-marked-alt me-2"></i>VER MAPA
                            </button>
                        </div>

                        {{--
                            =============================================
                            CONTENEDOR DEL MAPA INTERACTIVO - HISTORIAL
                            =============================================
                            Segundo mapa Leaflet para la pestaña historial
                        --}}
                        <div id="mapContainerHistorial" class="map-container mt-4" style="display: none;">

                            {{-- Header del mapa con indicadores de estado (duplicado) --}}
                            <div class="map-header">
                                {{-- Indicadores visuales del estado del envío --}}
                                <div class="map-status-indicators">
                                    {{-- Estado: Entregado --}}
                                    <span class="status-indicator {{ ($idEstadoActual == '3') ? 'status-entregado' : 'status-inactive' }}">
                                        <i class="fas fa-check-circle"></i> Entregado
                                    </span>
                                    {{-- Estado: Devuelto --}}
                                    <span class="status-indicator {{ ($idEstadoActual == '4') ? 'status-devuelto' : 'status-inactive' }}">
                                        <i class="fas fa-undo-alt"></i> Devuelto
                                    </span>
                                    {{-- Estado: En Proceso --}}
                                    <span class="status-indicator {{ ($idEstadoActual == '2') ? 'status-proceso' : 'status-inactive' }}">
                                        <i class="fas fa-truck"></i> En Proceso
                                    </span>
                                </div>
                                {{-- Disclaimer informativo sobre disponibilidad del mapa --}}
                                <p class="map-disclaimer">
                                    <i class="fas fa-info-circle me-1"></i>
                                    El rastreo de envíos en el mapa solo aplica para ciudades principales.
                                </p>
                            </div>

                            {{--
                                Contenedor del mapa sin disclaimer interno
                                Solo esquinas superiores redondeadas para continuidad visual
                            --}}
                            <div class="map-wrapper" style="height: 400px; position: relative; overflow: hidden; border-radius: 8px 8px 0 0;">
                                {{-- Elemento donde se renderiza el mapa Leaflet del historial --}}
                                <div id="mapHistorial" style="height: 100%; width: 100%;"></div>
                            </div>

                            {{--
                                Disclaimer externo debajo del mapa
                                Información legal sobre precisión de coordenadas (duplicado)
                            --}}
                            <div class="map-external-disclaimer">
                                <div class="disclaimer-content">
                                    <i class="fas fa-info-circle me-2 text-muted" aria-hidden="true"></i>
                                    <small class="text-muted">
                                        Este mapa utiliza datos de OpenStreetMap para proporcionar coordenadas y una ubicación aproximadas.
                                        Ten en cuenta que, por su naturaleza, las coordenadas son una referencia general y pueden no
                                        representar la ubicación exacta del envío.
                                    </small>
                                </div>
                            </div>
                        </div>

                    {{-- Mensaje si no hay movimientos registrados --}}
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No hay movimientos registrados para esta guía.
                        </div>
                    @endif

                {{-- Mensaje si no hay datos de movimientos --}}
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No se encontraron movimientos para esta guía.
                    </div>
                @endif
            </div>
        </div>

        {{--
            ===========================================
            MODAL PARA VISUALIZACIÓN DE COMPROBANTES
            ===========================================
            Modal Bootstrap XL para mostrar imágenes de comprobantes
            Maneja diferentes formatos y estados de procesamiento
        --}}
        <div class="modal fade" id="comprobanteModal" tabindex="-1" aria-labelledby="comprobanteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">

                    {{-- Header del modal con título dinámico --}}
                    <div class="modal-header">
                        <h5 class="modal-title" id="comprobanteModalLabel">
                            <i class="fas fa-receipt me-2"></i>Comprobante de Guía:
                            {{ is_array($respuesta['NumGui'] ?? null) ? implode(', ', $respuesta['NumGui']) : ($respuesta['NumGui'] ?? 'N/A') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    {{-- Cuerpo del modal con lógica condicional para diferentes estados --}}
                    <div class="modal-body text-center">

                        {{--
                            CASO 1: Imagen procesada
                        --}}
                        @if(isset($trackingRecord) && $trackingRecord->tieneImagen())
                            {{-- Contenedor de la imagen --}}
                            <div class="mb-3">
                                <canvas class="w-100" id="tiffCanvas"></canvas>
                            </div>

                            {{-- Botones de descarga --}}
                            <div class="d-flex justify-content-center gap-3 mt-3">
                                <a href="data:image/png;base64,{{ $trackingRecord->imagen_base64_para_vista }}"
                                download="comprobante-{{ $numeroGuia }}.png"
                                class="btn btn-custom-modal d-flex align-items-center">
                                    <i class="fas fa-download me-2"></i>Descargar Comprobante
                                </a>
                                {{-- Boton cerrar --}}
                                <button type="button"
                                        class="btn btn-custom-modal d-flex align-items-center"
                                        data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>Cerrar
                                </button>
                            </div>

                        {{--
                            CASO 2: Imagen disponible pero no procesada (TIFF)
                            Imagen existe pero no se puede mostrar en navegador
                        --}}
                        @elseif(isset($trackingRecord) && $trackingRecord->imagen_png_binario)
                            <div class="mb-3">
                                <canvas class="w-100" id="tiffCanvas"></canvas>
                            </div>

                            <div class="d-flex justify-content-center gap-3 mt-3">
                                <a href="data:image/png;base64,{{ $trackingRecord->imagen_base64_para_vista }}"
                                download="comprobante-{{ $numeroGuia }}.png"
                                class="btn btn-custom-modal d-flex align-items-center">
                                    <i class="fas fa-download me-2"></i>Descargar Comprobante
                                </a>

                                {{-- Botón Cerrar --}}
                                <button type="button"
                                        class="btn btn-custom-modal d-flex align-items-center"
                                        data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>Cerrar
                                </button>
                            </div>
                        {{--
                            CASO 3: Sin comprobante disponible
                            No hay imagen asociada al envío
                        --}}
                        @else
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No hay comprobante disponible para esta guía.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{--
        ======================================
        CONFIGURACIÓN JAVASCRIPT PARA MAPAS
        ======================================
        Datos serializados para uso en JavaScript del lado cliente
    --}}
    <script>
        /**
         * Datos del envío para JavaScript
         * Normaliza y serializa datos PHP para uso en frontend
         *
         * @type {Object} window.envioData - Objeto global con datos del envío
         * @property {string} ciudadOrigen - Ciudad de recogida normalizada
         * @property {string} ciudadDestino - Ciudad de destino normalizada
         * @property {string} estadoActual - ID del estado actual del envío
         * @property {Array} movimientos - Array de movimientos del envío
         */
        window.envioData = {
            // Normalización de ciudad origen (maneja arrays)
            ciudadOrigen: @json(is_array($respuesta['CiuRem'] ?? null) ? (is_array($respuesta['CiuRem']) ? $respuesta['CiuRem'][0] : $respuesta['CiuRem']) : ''),

            // Normalización de ciudad destino (maneja arrays)
            ciudadDestino: @json(is_array($respuesta['CiuDes'] ?? null) ? (is_array($respuesta['CiuDes']) ? $respuesta['CiuDes'][0] : $respuesta['CiuDes']) : ''),

            // Estado actual del envío
            estadoActual: @json($idEstadoActual ?? ''),

            // Array completo de movimientos
            movimientos: @json($movimientos ?? [])
        };

        /**
         * Log de debug para desarrollo
         * Permite verificar que los datos se están pasando correctamente
         */
        console.log('Datos cargados para el mapa:', window.envioData);
    </script>

    {{--
        ============================================
        LIBRERÍAS EXTERNAS PARA MAPAS INTERACTIVOS
        ============================================
        CDNs de Leaflet y plugins con integridad SHA para seguridad
    --}}

    {{-- CSS principal de Leaflet con verificación de integridad --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

    {{-- JavaScript principal de Leaflet con verificación de integridad --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    {{-- Plugin de routing para cálculo de rutas entre ciudades --}}
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

    {{-- CSS del plugin de routing --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />

    {{--
        Archivo JavaScript personalizado para manejo de mapas
        Contiene la lógica específica para inicialización y control de mapas
    --}}
    <script src="{{ asset('js/tracking-map-leaflet.js') }}"></script>


    {{-- ==> Descargar y ejecutar la biblioteca tiff.js para entender y renderizar imágenes TIFF --}}
    <script src="https://seikichi.github.io/tiff.js/tiff.min.js"></script>
    <script>
        const modalElement = document.getElementById('comprobanteModal');

        modalElement.addEventListener('shown.bs.modal', function () {
            const parentContainer = document.getElementById("tiffCanvas").parentNode;
            const parentWidth = parentContainer.offsetWidth;
            console.log(parentWidth);
                const b64 = '{{ $respuesta['Imagen'] ?? ''}}';

            if (b64) {
                try {
                    // Decode the Base64 string into a binary array.
                    const binary = atob(b64);
                    const length = binary.length;
                    const buffer = new Uint8Array(length);
                    for (let i = 0; i < length; i++) {
                        buffer[i] = binary.charCodeAt(i);
                    }

                    // Create a Tiff object to get the original image dimensions.
                    const tiff = new Tiff({ buffer: buffer.buffer });
                    const originalWidth = tiff.width();
                    const originalHeight = tiff.height();

                    // Calculate the new height to maintain the aspect ratio.
                    const aspectRatio = originalWidth / originalHeight;
                    const newHeight = parentWidth / aspectRatio;

                    // Create a new, correctly-sized canvas element.
                    const finalCanvas = document.createElement('canvas');
                    finalCanvas.id = "tiffCanvas";
                    finalCanvas.className = 'w-100'; // Apply the Bootstrap class for styling.
                    finalCanvas.width = parentWidth;
                    finalCanvas.height = newHeight;

                    // Get the 2D rendering context.
                    const ctx = finalCanvas.getContext('2d');

                    // Create a temporary canvas from the TIFF data.
                    const tiffCanvas = tiff.toCanvas();

                    // Draw the temporary canvas onto the new, correctly-sized canvas.
                    ctx.drawImage(tiffCanvas, 0, 0, parentWidth, newHeight);

                    // Replace the original, empty canvas with the new, resized one.
                    document.getElementById("tiffCanvas").replaceWith(finalCanvas);

                } catch (error) {
                    console.error('Failed to render TIFF image:', error);
                    const canvasContainer = document.getElementById("tiffCanvas").parentNode;
                    canvasContainer.innerHTML = '<p class="text-danger">Error al cargar la imagen</p>';
                }
            } else {
                console.warn('No image data found to render.');
            }
        });
    </script>

@endsection
