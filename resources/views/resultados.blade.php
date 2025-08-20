@extends('layout/plantilla')

@section('tituloPagina', 'Resultado de la Guía')

@section('contenido')
    <div class="card shadow-sm mt-5">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">
                Detalles de la Guía: 
                {{ is_array($respuesta['NumGui'] ?? null) ? implode(', ', $respuesta['NumGui']) : ($respuesta['NumGui'] ?? 'No disponible') }}
            </h5>
        </div>

        <div class="card-body">
            @if(isset($respuesta['Mov']))
                @php
                    $movimientos = is_array($respuesta['Mov']['InformacionMov']) ? $respuesta['Mov']['InformacionMov'] : [$respuesta['Mov']['InformacionMov']];
                    $ultimoMovimiento = end($movimientos);
                    $estadoActual = is_array($ultimoMovimiento['NomMov'] ?? null) ? implode(', ', $ultimoMovimiento['NomMov']) : ($ultimoMovimiento['NomMov'] ?? 'No disponible');
                    $estadoActualLimpio = strtolower(trim($estadoActual));
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
                            <span class="badge bg-success">
                                {{ is_array($respuesta['EstAct'] ?? null) ? implode(', ', $respuesta['EstAct']) : ($respuesta['EstAct'] ?? 'No disponible') }}
                            </span>
                        </div>

                        <div class="mb-4">
                            <h6 class="text-secondary">Fecha de Estado:</h6>
                            <span class="badge bg-info">
                                {{ is_array($respuesta['FecEst'] ?? null) ? implode(', ', $respuesta['FecEst']) : ($respuesta['FecEst'] ?? 'No disponible') }}
                            </span>
                        </div>

                        <div class="mb-4">
                            <h6 class="text-secondary">Fecha de último movimiento:</h6>
                            <span class="badge bg-info">
                                {{ is_array($respuesta['FechaProbable'] ?? null) ? implode(', ', $respuesta['FechaProbable']) : ($respuesta['FechaProbable'] ?? 'No disponible') }}
                            </span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-4">
                            <h6 class="text-secondary">Número de Piezas:</h6>
                            <span class="badge bg-info">
                                {{ is_array($respuesta['NumPie'] ?? null) ? implode(', ', $respuesta['NumPie']) : ($respuesta['NumPie'] ?? 'No disponible') }}
                            </span>
                        </div>

                        <div class="mb-4">
                            <h6 class="text-secondary">ID Estado Actual:</h6>
                            <span class="badge bg-info">
                                {{ is_array($respuesta['IdEstAct'] ?? null) ? implode(', ', $respuesta['IdEstAct']) : ($respuesta['IdEstAct'] ?? 'No disponible') }}
                            </span>
                        </div>

                        <div class="mb-4">
                            <h6 class="text-secondary">PQR:</h6>
                            <span class="badge bg-info">
                                {{ is_array($respuesta['NumCun'] ?? null) ? implode(', ', $respuesta['NumCun']) : ($respuesta['NumCun'] ?? 'No disponible') }}
                            </span>
                        </div>

                        <div class="mb-4">
                            <h6 class="text-secondary">Tipo de Régimen:</h6>
                            <span class="badge bg-info">
                                {{ is_array($respuesta['Regime'] ?? null) ? implode(', ', $respuesta['Regime']) : ($respuesta['Regime'] ?? 'No disponible') }}
                            </span>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="text-secondary mb-3">Información de Origen (Remitente)</h5>
                        <p><strong>Nombre:</strong> {{ is_array($respuesta['NomRem'] ?? null) ? implode(', ', $respuesta['NomRem']) : ($respuesta['NomRem'] ?? 'No disponible') }}</p>
                        <p><strong>Ciudad:</strong> {{ is_array($respuesta['CiuRem'] ?? null) ? implode(', ', $respuesta['CiuRem']) : ($respuesta['CiuRem'] ?? 'No disponible') }}</p>
                        <p><strong>Dirección:</strong> {{ is_array($respuesta['DirRem'] ?? null) ? implode(', ', $respuesta['DirRem']) : ($respuesta['DirRem'] ?? 'No disponible') }}</p>
                        <p><strong>Fecha de Envío:</strong> {{ is_array($respuesta['FecEnv'] ?? null) ? implode(', ', $respuesta['FecEnv']) : ($respuesta['FecEnv'] ?? 'No disponible') }}</p>
                    </div>

                    <div class="col-md-6">
                        <h5 class="text-secondary mb-3">Información de Destino (Destinatario)</h5>
                        <p><strong>Nombre:</strong> {{ is_array($respuesta['NomDes'] ?? null) ? implode(', ', $respuesta['NomDes']) : ($respuesta['NomDes'] ?? 'No disponible') }}</p>
                        <p><strong>Ciudad:</strong> {{ is_array($respuesta['CiuDes'] ?? null) ? implode(', ', $respuesta['CiuDes']) : ($respuesta['CiuDes'] ?? 'No disponible') }}</p>
                        <p><strong>Dirección:</strong> {{ is_array($respuesta['DirDes'] ?? null) ? implode(', ', $respuesta['DirDes']) : ($respuesta['DirDes'] ?? 'No disponible') }}</p>
                    </div>
                </div>

                <hr>
                <h5 class="text-secondary mb-3">Información extra</h5>
                <p><strong>Receptor:</strong> {{ is_array($respuesta['NomRec'] ?? null) ? implode(', ', $respuesta['NomRec']) : ($respuesta['NomRec'] ?? 'No disponible') }}</p>
                <p><strong>Forma de pago:</strong> {{ is_array($respuesta['FormPago'] ?? null) ? implode(', ', $respuesta['FormPago']) : ($respuesta['FormPago'] ?? 'No disponible') }}</p>
                <p><strong>Producto:</strong> {{ is_array($respuesta['NomProducto'] ?? null) ? implode(', ', $respuesta['NomProducto']) : ($respuesta['NomProducto'] ?? 'No disponible') }}</p>
                <p><strong>Placa vehiculo:</strong> {{ is_array($respuesta['Placa'] ?? null) ? implode(', ', $respuesta['Placa']) : ($respuesta['Placa'] ?? 'No disponible') }}</p>

                <hr>
                <h6 class="text-secondary mb-3">Historial de Movimientos</h6>
                <ul class="list-group">
                    @foreach($movimientos as $movimiento)
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold">{{ is_array($movimiento['NomMov'] ?? null) ? implode(', ', $movimiento['NomMov']) : ($movimiento['NomMov'] ?? 'Sin descripción') }}</div>
                                <span><strong>Origen: </strong>{{ is_array($movimiento['OriMov'] ?? null) ? implode(', ', $movimiento['OriMov']) : ($movimiento['OriMov'] ?? 'N/A') }}</span><br>
                                <span><strong>Destino: </strong>{{ is_array($movimiento['DesMov'] ?? null) ? implode(', ', $movimiento['DesMov']) : ($movimiento['DesMov'] ?? 'N/A') }}</span>
                            </div>

                            <div class="ms-2 me-auto">
                                <div class="fw-bold">ID del proceso:</div>
                                <span class="badge bg-secondary rounded-pill ms-2">{{ is_array($movimiento['IdProc'] ?? null) ? implode(', ', $movimiento['IdProc']) : ($movimiento['IdProc'] ?? 'N/A') }}</span>
                            </div>

                            <div class="ms-2 me-auto">
                                <div class="fw-bold">Fecha del movimiento:</div>
                                <span class="badge bg-secondary rounded-pill ms-2">{{ is_array($movimiento['FecMov'] ?? null) ? implode(', ', $movimiento['FecMov']) : ($movimiento['FecMov'] ?? 'N/A') }}</span>
                            </div>

                            <div class="ms-2 me-auto">
                                <div class="fw-bold">Id del cliente:</div>
                                <span class="badge bg-secondary rounded-pill ms-2">{{ is_array($movimiento['IdViewCliente'] ?? null) ? implode(', ', $movimiento['IdViewCliente']) : ($movimiento['IdViewCliente'] ?? 'N/A') }}</span>
                            </div>

                            <div class="ms-2 me-auto">
                                <div class="fw-bold">Tipo de movimiento:</div>
                                <span class="badge bg-secondary rounded-pill ms-2">{{ is_array($movimiento['TipoMov'] ?? null) ? implode(', ', $movimiento['TipoMov']) : ($movimiento['TipoMov'] ?? 'N/A') }}</span>
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
