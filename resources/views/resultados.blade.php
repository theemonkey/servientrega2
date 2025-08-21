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
                <h5 class="card-title mb-0 me-2">Detalles de la Guía: 
                    {{ is_array($respuesta['NumGui'] ?? null) ? implode(', ', $respuesta['NumGui']) : ($respuesta['NumGui'] ?? 'No disponible') }}
                </h5>

                @if(isset($respuesta['Mov']))
                    @php
                        $movimientos = is_array($respuesta['Mov']['InformacionMov']) ? $respuesta['Mov']['InformacionMov'] : [$respuesta['Mov']['InformacionMov']];
                        $ultimoMovimiento = end($movimientos);
                        $estadoActual = is_array($ultimoMovimiento['NomMov'] ?? null) ? implode(', ', $ultimoMovimiento['NomMov']) : ($ultimoMovimiento['NomMov'] ?? 'No disponible');
                        $estadoActualLimpio = strtolower(trim($estadoActual));
                        $estadosExito = ['entregado', 'entregado en oficina', 'entrega verificada'];
                        $badgeColor = in_array($estadoActualLimpio, $estadosExito) ? 'badge-entrega-verificada' : 'badge-procesamiento';
                    @endphp

                    <div class="row mt-4">
                        <div class="col-md-6 mb-3">
                            <div class="mb-4">
                                <h6 class="text-secondary"><i class="fas fa-info-circle me-2"></i>Último estado:</h6>
                                <span class="badge {{ $badgeColor }}">{{ $estadoActual }}</span>
                            </div>

                            <div class="mb-4">
                                <h6 class="text-secondary"><i class="fas fa-clipboard-check me-2"></i>Estado Actual:</h6>
                                <span class="badge badge-entregado">
                                    {{ is_array($respuesta['EstAct'] ?? null) ? implode(', ', $respuesta['EstAct']) : ($respuesta['EstAct'] ?? 'No disponible') }}
                                </span>
                            </div>

                            <div class="mb-4">
                                <h6 class="text-secondary"><i class="fas fa-calendar-alt me-2"></i>Fecha de Estado:</h6>
                                <span class="badge badge-fecha">
                                    {{ is_array($respuesta['FecEst'] ?? null) ? implode(', ', $respuesta['FecEst']) : ($respuesta['FecEst'] ?? 'No disponible') }}
                                </span>
                            </div>

                            <div class="mb-4">
                                <h6 class="text-secondary"><i class="fas fa-calendar-alt me-2"></i>Fecha de último movimiento:</h6>
                                <span class="badge badge-fecha">
                                    {{ is_array($respuesta['FechaProbable'] ?? null) ? implode(', ', $respuesta['FechaProbable']) : ($respuesta['FechaProbable'] ?? 'No disponible') }}
                                </span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-4">
                                <h6 class="text-secondary"><i class="fas fa-cubes me-2"></i>Número de Piezas:</h6>
                                <span class="badge badge-numero">
                                    {{ is_array($respuesta['NumPie'] ?? null) ? implode(', ', $respuesta['NumPie']) : ($respuesta['NumPie'] ?? 'No disponible') }}
                                </span>
                            </div>

                            <div class="mb-4">
                                <h6 class="text-secondary"><i class="fas fa-hashtag me-2"></i>ID Estado Actual:</h6>
                                <span class="badge badge-numero">
                                    {{ is_array($respuesta['IdEstAct'] ?? null) ? implode(', ', $respuesta['IdEstAct']) : ($respuesta['IdEstAct'] ?? 'No disponible') }}
                                </span>
                            </div>

                            <div class="mb-4">
                                <h6 class="text-secondary"><i class="fas fa-comments me-2"></i>PQR:</h6>
                                <span class="badge badge-numero">
                                    {{ is_array($respuesta['NumCun'] ?? null) ? implode(', ', $respuesta['NumCun']) : ($respuesta['NumCun'] ?? 'No disponible') }}
                                </span>
                            </div>

                            <div class="mb-4">
                                <h6 class="text-secondary"><i class="fas fa-clipboard-list me-2"></i>Tipo de Régimen:</h6>
                                <span class="badge badge-transporte">
                                    {{ is_array($respuesta['Regime'] ?? null) ? implode(', ', $respuesta['Regime']) : ($respuesta['Regime'] ?? 'No disponible') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="text-secondary mb-3">Información de Origen (Remitente)</h5>
                            <p><strong><i class="fas fa-user me-2"></i>Nombre:</strong> {{ is_array($respuesta['NomRem'] ?? null) ? implode(', ', $respuesta['NomRem']) : ($respuesta['NomRem'] ?? 'No disponible') }}</p>
                            <p><strong><i class="fas fa-map-marker-alt me-2"></i>Ciudad:</strong> {{ is_array($respuesta['CiuRem'] ?? null) ? implode(', ', $respuesta['CiuRem']) : ($respuesta['CiuRem'] ?? 'No disponible') }}</p>
                            <p><strong><i class="fas fa-home me-2"></i>Dirección:</strong> {{ is_array($respuesta['DirRem'] ?? null) ? implode(', ', $respuesta['DirRem']) : ($respuesta['DirRem'] ?? 'No disponible') }}</p>
                            <p><strong><i class="fas fa-calendar-alt me-2"></i>Fecha de Envío:</strong> {{ is_array($respuesta['FecEnv'] ?? null) ? implode(', ', $respuesta['FecEnv']) : ($respuesta['FecEnv'] ?? 'No disponible') }}</p>
                        </div>

                        <div class="col-md-6">
                            <h5 class="text-secondary mb-3">Información de Destino (Destinatario)</h5>
                            <p><strong><i class="fas fa-user me-2"></i>Nombre:</strong> {{ is_array($respuesta['NomDes'] ?? null) ? implode(', ', $respuesta['NomDes']) : ($respuesta['NomDes'] ?? 'No disponible') }}</p>
                            <p><strong><i class="fas fa-map-marker-alt me-2"></i>Ciudad:</strong> {{ is_array($respuesta['CiuDes'] ?? null) ? implode(', ', $respuesta['CiuDes']) : ($respuesta['CiuDes'] ?? 'No disponible') }}</p>
                            <p><strong><i class="fas fa-home me-2"></i>Dirección:</strong> {{ is_array($respuesta['DirDes'] ?? null) ? implode(', ', $respuesta['DirDes']) : ($respuesta['DirDes'] ?? 'No disponible') }}</p>
                        </div>
                    </div>

                    <hr>
                    <h5 class="text-secondary mb-3">Información extra</h5>
                    <p><strong><i class="fas fa-user-check me-2"></i>Receptor:</strong> {{ is_array($respuesta['NomRec'] ?? null) ? implode(', ', $respuesta['NomRec']) : ($respuesta['NomRec'] ?? 'No disponible') }}</p>
                    <p><strong><i class="fas fa-credit-card me-2"></i>Forma de pago:</strong> {{ is_array($respuesta['FormPago'] ?? null) ? implode(', ', $respuesta['FormPago']) : ($respuesta['FormPago'] ?? 'No disponible') }}</p>
                    <p><strong><i class="fas fa-box me-2"></i>Producto:</strong> {{ is_array($respuesta['NomProducto'] ?? null) ? implode(', ', $respuesta['NomProducto']) : ($respuesta['NomProducto'] ?? 'No disponible') }}</p>
                    <p><strong><i class="fas fa-truck me-2"></i>Placa vehiculo:</strong> {{ is_array($respuesta['Placa'] ?? null) ? implode(', ', $respuesta['Placa']) : ($respuesta['Placa'] ?? 'No disponible') }}</p>
                @else
                    <div class="alert alert-warning" role="alert">
                        No se encontraron datos para esta guía.
                    </div>
                @endif
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