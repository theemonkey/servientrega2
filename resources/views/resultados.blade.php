@extends('layout/plantilla')

@section('tituloPagina', 'Resultado de la Guía')

@section('contenido')
    <div class="card shadow-sm mt-5">
        <div class="card-header bg-light">
            <ul class="nav nav-tabs card-header-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="detalle-tab" data-bs-toggle="tab" data-bs-target="#detalle" type="button" role="tab" aria-controls="detalle" aria-selected="true">
                        <i class="fas fa-info-circle me-2"></i>Detalles
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="historial-tab" data-bs-toggle="tab" data-bs-target="#historial" type="button" role="tab" aria-controls="historial" aria-selected="false">
                        <i class="fas fa-history me-2"></i>Historial
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body tab-content" id="myTabContent">
            <!-- TAB DETALLE -->
            <div class="tab-pane fade show active" id="detalle" role="tabpanel" aria-labelledby="detalle-tab">
                @if(isset($respuesta['Mov']))
                    @php
                        $movimientos = is_array($respuesta['Mov']['InformacionMov']) ? $respuesta['Mov']['InformacionMov'] : [$respuesta['Mov']['InformacionMov']];
                        $ultimoMovimiento = end($movimientos);
                        
                        // Mapeo de estados según la tabla proporcionada
                        $estadosMap = [
                            '1' => 'RECIBIDO DEL CLIENTE',
                            '2' => 'EN PROCESAMIENTO', 
                            '3' => 'ENTREGADO',
                            '4' => 'ENTREGADO A REMITENTE',
                            '5' => 'SINIESTRADO'
                        ];
                        
                        // Obtener ID del estado actual (mantener lógica pero no mostrar)
                        $idEstadoActual = is_array($respuesta['IdEstAct'] ?? null) ? implode(', ', $respuesta['IdEstAct']) : ($respuesta['IdEstAct'] ?? null);
                        
                        // Obtener descripción del estado basado en el ID
                        $estadoActualTexto = $estadosMap[$idEstadoActual] ?? ($respuesta['EstAct'] ?? 'No disponible');
                        
                        // Determinar color del badge según el estado
                        $badgeColorMap = [
                            '1' => 'badge-recibido',      // Azul
                            '2' => 'badge-procesamiento', // Amarillo
                            '3' => 'badge-entregado',     // Verde
                            '4' => 'badge-devolucion',    // Naranja
                            '5' => 'badge-siniestrado'    // Rojo
                        ];
                        
                        $badgeColor = $badgeColorMap[$idEstadoActual] ?? 'badge-procesamiento';
                        
                        // Validar NumCun para mostrar "Sin observaciones"
                        $numCun = $respuesta['NumCun'] ?? null;
                        $pqrTexto = 'Sin observaciones';
                        
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

                    {{-- HEADER CON TÍTULO Y ESTADO HORIZONTAL --}}
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="card-title mb-0">Número de guía: 
                                {{ is_array($respuesta['NumGui'] ?? null) ? implode(', ', $respuesta['NumGui']) : ($respuesta['NumGui'] ?? 'No disponible') }}
                            </h5>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="text-secondary me-2"><strong><i class="fas fa-clipboard-check me-2"></i>Estado actual:</strong></span>
                            <span class="badge {{ $badgeColor }}">{{ $estadoActualTexto }}</span>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="text-secondary mb-3">Información de Origen (Remitente)</h5>
                            
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Nombre</div>
                                    <div class="info-value">{{ is_array($respuesta['NomRem'] ?? null) ? implode(', ', $respuesta['NomRem']) : ($respuesta['NomRem'] ?? 'No disponible') }}</div>
                                </div>
                            </div>

                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Ciudad de recogida</div>
                                    <div class="info-value">{{ is_array($respuesta['CiuRem'] ?? null) ? implode(', ', $respuesta['CiuRem']) : ($respuesta['CiuRem'] ?? 'No disponible') }}</div>
                                </div>
                            </div>

                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-home"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Dirección</div>
                                    <div class="info-value">{{ is_array($respuesta['DirRem'] ?? null) ? implode(', ', $respuesta['DirRem']) : ($respuesta['DirRem'] ?? 'No disponible') }}</div>
                                </div>
                            </div>

                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Fecha de Envío</div>
                                    <div class="info-value">{{ is_array($respuesta['FecEnv'] ?? null) ? implode(', ', $respuesta['FecEnv']) : ($respuesta['FecEnv'] ?? 'No disponible') }}</div>
                                </div>
                            </div>

                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-cubes"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Número de Piezas</div>
                                    <div class="info-value">{{ is_array($respuesta['NumPie'] ?? null) ? implode(', ', $respuesta['NumPie']) : ($respuesta['NumPie'] ?? 'No disponible') }}</div>
                                </div>
                            </div>

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

                        <div class="col-md-6">
                            <h5 class="text-secondary mb-3">Información de Destino (Destinatario)</h5>
                            
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Nombre</div>
                                    <div class="info-value">{{ is_array($respuesta['NomDes'] ?? null) ? implode(', ', $respuesta['NomDes']) : ($respuesta['NomDes'] ?? 'No disponible') }}</div>
                                </div>
                            </div>

                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Ciudad de destino</div>
                                    <div class="info-value">{{ is_array($respuesta['CiuDes'] ?? null) ? implode(', ', $respuesta['CiuDes']) : ($respuesta['CiuDes'] ?? 'No disponible') }}</div>
                                </div>
                            </div>

                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-home"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Dirección</div>
                                    <div class="info-value">{{ is_array($respuesta['DirDes'] ?? null) ? implode(', ', $respuesta['DirDes']) : ($respuesta['DirDes'] ?? 'No disponible') }}</div>
                                </div>
                            </div>

                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-calendar"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Fecha de entrega</div>
                                    <div class="info-value">{{ is_array($respuesta['FecEst'] ?? null) ? implode(', ', $respuesta['FecEst']) : ($respuesta['FecEst'] ?? 'No disponible') }}</div>
                                </div>
                            </div>

                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Hora de entrega</div>
                                    <div class="info-value">{{ isset($respuesta['FecEst']) ? date('H:i', strtotime(is_array($respuesta['FecEst']) ? $respuesta['FecEst'][0] : $respuesta['FecEst'])) : 'No disponible' }}</div>
                                </div>
                            </div>

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
                    <hr>
                    <h5 class="text-secondary mb-3">Información extra</h5>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Receptor</div>
                            <div class="info-value">{{ is_array($respuesta['NomRec'] ?? null) ? implode(', ', $respuesta['NomRec']) : ($respuesta['NomRec'] ?? 'No disponible') }}</div>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Forma de pago</div>
                            <div class="info-value">{{ is_array($respuesta['FormPago'] ?? null) ? implode(', ', $respuesta['FormPago']) : ($respuesta['FormPago'] ?? 'No disponible') }}</div>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Producto</div>
                            <div class="info-value">{{ is_array($respuesta['NomProducto'] ?? null) ? implode(', ', $respuesta['NomProducto']) : ($respuesta['NomProducto'] ?? 'No disponible') }}</div>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Placa vehículo</div>
                            <div class="info-value">{{ is_array($respuesta['Placa'] ?? null) ? implode(', ', $respuesta['Placa']) : ($respuesta['Placa'] ?? 'No disponible') }}</div>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Fecha de último movimiento</div>
                            <div class="info-value">{{ is_array($respuesta['FechaProbable'] ?? null) ? implode(', ', $respuesta['FechaProbable']) : ($respuesta['FechaProbable'] ?? 'No disponible') }}</div>
                        </div>
                    </div>

                @else
                    <div class="alert alert-warning" role="alert">
                        No se encontraron datos para esta guía.
                    </div>
                @endif

                {{-- BOTONES PERSONALIZADOS --}}
                    <div class="buttons-container">
                        <button type="button" class="btn-custom">
                            <i class="fas fa-receipt me-2"></i>VER COMPROBANTE
                        </button>
                        <button type="button" class="btn-custom">
                            <i class="fas fa-map-marked-alt me-2"></i>VER MAPA
                        </button>
                    </div>
            </div>

            <!-- TAB HISTORIAL -->
            <div class="tab-pane fade" id="historial" role="tabpanel" aria-labelledby="historial-tab">
                <h5 class="mb-3">Historial de Movimientos</h5>
                
                @if(isset($respuesta['Mov']))
                    @php
                        $movimientos = is_array($respuesta['Mov']['InformacionMov']) ? $respuesta['Mov']['InformacionMov'] : [$respuesta['Mov']['InformacionMov']];
                    @endphp
                    
                    @if(count($movimientos) > 0)
                        <ul class="list-group">
                            @foreach($movimientos as $movimiento)
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-bold">
                                            <i class="fas fa-shipping-fast text-primary me-2"></i>
                                            {{ is_array($movimiento['NomMov'] ?? null) ? implode(', ', $movimiento['NomMov']) : ($movimiento['NomMov'] ?? 'Sin descripción') }}
                                        </div>
                                        <div class="mt-2">
                                            <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                            <strong>Origen:</strong> {{ is_array($movimiento['OriMov'] ?? null) ? implode(', ', $movimiento['OriMov']) : ($movimiento['OriMov'] ?? 'N/A') }}
                                        </div>
                                        <div>
                                            <i class="fas fa-map-marker-alt text-success me-2"></i>
                                            <strong>Destino:</strong> {{ is_array($movimiento['DesMov'] ?? null) ? implode(', ', $movimiento['DesMov']) : ($movimiento['DesMov'] ?? 'N/A') }}
                                        </div>
                                        <div class="mt-2">
                                            <i class="fas fa-calendar-alt text-info me-2"></i>
                                            <strong>Fecha:</strong> {{ is_array($movimiento['FecMov'] ?? null) ? implode(', ', $movimiento['FecMov']) : ($movimiento['FecMov'] ?? 'N/A') }}
                                        </div>
                                        @if(isset($movimiento['TipoMov']))
                                            <div class="mt-1">
                                                <i class="fas fa-tag text-secondary me-2"></i>
                                                <strong>Tipo:</strong> 
                                                @php
                                                    $tipoMov = is_array($movimiento['TipoMov']) ? $movimiento['TipoMov'][0] : $movimiento['TipoMov'];
                                                @endphp
                                                
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
                                    
                                    <div class="d-flex flex-column align-items-end">
                                        <span class="badge bg-secondary mb-1">
                                            ID: {{ is_array($movimiento['IdProc'] ?? null) ? implode(', ', $movimiento['IdProc']) : ($movimiento['IdProc'] ?? 'N/A') }}
                                        </span>
                                        @if(isset($movimiento['IdViewCliente']))
                                            <span class="badge bg-info">
                                                Cliente: {{ is_array($movimiento['IdViewCliente']) ? implode(', ', $movimiento['IdViewCliente']) : $movimiento['IdViewCliente'] }}
                                            </span>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No hay movimientos registrados para esta guía.
                        </div>
                    @endif
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No se encontraron movimientos para esta guía.
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection