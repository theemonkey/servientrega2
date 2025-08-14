@extends('layout/plantilla')

@section('tituloPagina', 'Resultado de la Guía')

@section('contenido')
    <div class="card shadow-sm mt-5">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Detalles de la Guía: {{ $respuesta['NumGuia'] ?? 'No disponible' }}</h5>
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

                <div class="mb-4">
                    <h6 class="text-secondary">Último estado:</h6>
                    <span class="badge {{ $badgeColor }}">{{ $estadoActual }}</span>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <p class="mb-1 fw-bold">Remitente:</p>
                        <p>{{ $respuesta['NomRem'] ?? 'No disponible' }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <p class="mb-1 fw-bold">Destinatario:</p>
                        <p>{{ $respuesta['NomDes'] ?? 'No disponible' }}</p>
                    </div>
                </div>

                <hr>

                <h6 class="text-secondary mb-3">Historial de Movimientos</h6>
                <ul class="list-group">
                    @foreach($movimientos as $movimiento)
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold">{{ $movimiento['NomMov'] ?? 'Sin descripción' }}</div>
                                {{ $movimiento['DesMov'] ?? 'N/A' }}
                                <span class="badge bg-secondary rounded-pill ms-2">{{ $movimiento['FecMov'] ?? 'N/A' }}</span>
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
