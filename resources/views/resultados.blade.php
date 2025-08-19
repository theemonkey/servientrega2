@extends('layout/plantilla')

@section('tituloPagina', 'Resultado de la Guía')

@section('contenido')
    <div class="card shadow-sm mt-5">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Detalles de la Guía: {{ $respuesta['NumGui'] ?? 'No disponible' }}</h5>
        </div>
        <div class="card-body">
            @if(isset($respuesta['Mov']))
                {{-- Si hay un solo movimiento, lo tratamos como un array de un solo elemento para poder usar la función `end()` --}}
                @php
                    $movimientos = is_array($respuesta['Mov']['InformacionMov']) ? $respuesta['Mov']['InformacionMov'] : [$respuesta['Mov']['InformacionMov']];
                    $ultimoMovimiento = end($movimientos);
                    $estadoActual = $ultimoMovimiento['NomMov'] ?? 'No disponible';

                    // Conversion a minúsculas y eliminamos espacios para una comparación robusta
                    $estadoActualLimpio = strtolower(trim($estadoActual));
                    
                    // Lista de posibles estados de éxito
                    $estadosExito = ['entregado', 'entregado en oficina', 'entrega verificada'];
                    
                    $badgeColor = in_array($estadoActualLimpio, $estadosExito) ? 'bg-success' : 'bg-primary';
                @endphp
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-4">
                        <h6 class="text-secondary">Último estado:</h6>
                        <span class="badge {{ $badgeColor }}">{{ $estadoActual }}</span>
                    </div>

                    <div class="mb-4">
                        <h6 class="text-secondary">Estado Actual:</h6>
                        <span class="badge bg-success">{{ $respuesta['EstAct'] ?? 'No disponible' }}</span>
                    </div>

                    <div class="mb-4">
                        <h6 class="text-secondary">Fecha de Estado:</h6>
                        <span class="badge bg-info">{{ $respuesta['FecEst'] ?? 'No disponible' }}</span>
                    </div>

                    <div class="mb-4">
                        <h6 class="text-secondary">Fecha de ultimo movimiento:</h6>
                        <span class="badge bg-info">{{ $respuesta['FechaProbable'] ?? 'No disponible' }}</span>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-4">
                        <h6 class="text-secondary">Número de Pie:</h6>
                        <span class="badge bg-info">{{ $respuesta['NumPie'] ?? 'No disponible' }}</span>
                    </div>

                    <div class="mb-4">
                        <h6 class="text-secondary">ID Estado Actual:</h6>
                        <span class="badge bg-info">{{ $respuesta['IdEstAct'] ?? 'No disponible' }}</span>
                    </div>

                    <div class="mb-4">
                        <h6 class="text-secondary">PQR:</h6>
                        <span class="badge bg-info">{{ $respuesta['NumCun'] ?? 'No disponible' }}</span>
                    </div>

                    <div class="mb-4">
                        <h6 class="text-secondary">Tipo de Régimen:</h6>
                        <span class="badge bg-info">{{ $respuesta['Regime'] ?? 'No disponible' }}</span>
                    </div>
                </div>
            </div>

            <hr>
            <div class="row">
                <div class="col-md-6">
                    <h5 class="text-primary mb-3">Información de Origen (Remitente)</h5>
                    <div class="mb-3">
                        <p class="mb-1 fw-bold">Nombre del Remitente:</p>
                        <p>{{ $respuesta['NomRem'] ?? 'No disponible' }}</p>
                    </div>
                    <div class="mb-3">
                        <p class="mb-1 fw-bold">Ciudad del Remitente:</p>
                        <p>{{ $respuesta['CiuRem'] ?? 'No disponible' }}</p>
                    </div>
                    <div class="mb-3">
                        <p class="mb-1 fw-bold">Dirección del Remitente:</p>
                        <p>{{ $respuesta['DirRem'] ?? 'No disponible' }}</p>
                    </div>
                    <div class="mb-3">
                        <p class="mb-1 fw-bold">Fecha de Envío:</p>
                        <p>{{ $respuesta['FecEnv'] ?? 'No disponible' }}</p>
                    </div>  
                </div>
                <div class="col-md-6">
                    <h5 class="text-primary mb-3">Información de Destino (Destinatario)</h5>
                    <div class="mb-3">
                        <p class="mb-1 fw-bold">Nombre del Destinatario:</p>
                        <p>{{ $respuesta['NomDes'] ?? 'No disponible' }}</p>
                    </div>
                    <div class="mb-3">
                        <p class="mb-1 fw-bold">Ciudad del Destinatario:</p>
                        <p>{{ $respuesta['CiuDes'] ?? 'No disponible' }}</p>
                    </div>
                    <div class="mb-3">
                        <p class="mb-1 fw-bold">Dirección del Destinatario:</p>
                        <p>{{ $respuesta['DirDes'] ?? 'No disponible' }}</p>
                    </div>
                </div>
            </div>

            <hr>
            <div class="row">
                <div class="col-12">
                    <h5 class="text-primary mb-3">Información de Receptor</h5>
                    <div class="mb-3">
                        <p class="mb-1 fw-bold">Nombre del Receptor:</p>
                        <p>{{ $respuesta['NomRec'] ?? 'No disponible' }}</p>
                    </div>
                </div>
            </div>
                <hr>

                <h6 class="text-secondary mb-3">Historial de Movimientos</h6>
                <ul class="list-group">
                    @foreach($movimientos as $movimiento)
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold">{{ $movimiento['NomMov'] ?? 'Sin descripción' }}</div>
                                <span><strong>Origen: </strong>{{ $movimiento['OriMov'] ?? 'N/A' }}</span><br
                                <span><strong>Destino: </strong>{{ $movimiento['DesMov'] ?? 'N/A' }}</span>
                            </div>

                            <div class="ms-2 me-auto">
                                <div class="fw-bold">ID del proceso:</div>
                                <span class="badge bg-secondary rounded-pill ms-2">{{ $movimiento['IdProc'] ?? 'N/A' }}</span>
                            </div>

                            <div class="ms-2 me-auto">
                                <div class="fw-bold">Fecha del movimiento:</div>
                                <span class="badge bg-secondary rounded-pill ms-2">{{ $movimiento['FecMov'] ?? 'N/A' }}</span>
                            </div>

                            <div class="ms-2 me-auto">
                                <div class="fw-bold">Id del cliente:</div>
                                <span class="badge bg-secondary rounded-pill ms-2">{{ $movimiento['IdViewCliente'] ?? 'N/A' }}</span>
                            </div>
                            <div class="ms-2 me-auto">
                                <div class="fw-bold">Tipo de movimiento:</div>
                                <span class="badge bg-secondary rounded-pill ms-2">{{ $movimiento['TipoMov'] ?? 'N/A' }}</span>
                            </div>

                            
  
                            
                
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="alert alert-warning" role="alert">
                    No se encontraron movimientos para esta guía.
                </div>
            @endif
        </div>
    </div>
@endsection
