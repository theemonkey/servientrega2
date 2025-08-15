@extends('layout/plantilla')

@section('tituloPagina', 'Rastreo de Guía Servientrega')

@section('contenido')

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">

                <h1 class="mb-4 text-center">Rastreo de Guía Servientrega</h1>

                <form action="/rastreo" method="GET" class="mb-5 needs-validation" novalidate>
                    <div class="input-group input-group-lg"> {{-- Larger input group --}}
                        <label for="numeroGuia" class="visually-hidden">Número de Guía:</label>
                        <input type="text" class="form-control" id="numeroGuia" name="numeroGuia" required placeholder="Ingrese el número de guía para rastrear">
                        <button type="submit" class="btn btn-primary">Rastrear</button>
                        <div class="invalid-feedback">
                            Por favor, ingrese un número de guía.
                        </div>
                    </div>
                </form>

                {{-- Display session error messages --}}
                @if(session('error'))
                    <div class="alert alert-danger text-center" role="alert">
                        {{ session('error') }}
                    </div>
                @endif

                @isset($guia)
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h2 class="card-title mb-0">Datos de la Guía</h2>
                            @if($guia->estadoActual)
                                <span class="badge bg-light text-dark fs-6">{{ $guia->estadoActual->nombre_estado }}</span>
                            @endif
                        </div>
                        <div class="card-body">
                            <p class="card-text"><strong>Número de Guía:</strong> {{ $guia->numero_guia }}</p>
                            <p class="card-text"><strong>Fecha de Envío:</strong> {{ $guia->fecha_envio ? \Carbon\Carbon::parse($guia->fecha_envio)->format('d/m/Y') : 'N/A' }}</p>
                            <p class="card-text"><strong>Fecha Probable de Entrega:</strong> {{ $guia->fecha_probable_entrega ? \Carbon\Carbon::parse($guia->fecha_probable_entrega)->format('d/m/Y H:i') : 'N/A' }}</p>
                            <p class="card-text"><strong>Remitente:</strong> {{ $guia->remitente_nombre }}
                                @if($guia->ciudadRemitente)
                                    ({{ $guia->ciudadRemitente->nombre_ciudad }})
                                @endif
                            </p>
                            <p class="card-text"><strong>Destinatario:</strong> {{ $guia->destinatario_nombre }}
                                @if($guia->ciudadDestino)
                                    ({{ $guia->ciudadDestino->nombre_ciudad }})
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-info text-white">
                            <h3 class="card-title mb-0">Movimientos de la Guía</h3>
                        </div>
                        <div class="card-body">
                            @forelse($guia->movimientos as $mov)
                                <div class="mb-2 p-2 border rounded bg-light">
                                    <p class="mb-0">
                                        <strong>Fecha:</strong> {{ $mov->fecha_movimiento ? \Carbon\Carbon::parse($mov->fecha_movimiento)->format('d/m/Y H:i') : 'N/A' }} <br>
                                        <strong>Estado:</strong> {{ $mov->estado_movimiento }} <br>
                                        <strong>Descripción:</strong> {{ $mov->descripcion_movimiento }}
                                    </p>
                                </div>
                            @empty
                                <p class="text-muted text-center">No hay movimientos registrados para esta guía.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h3 class="card-title mb-0">Cotizaciones Asociadas</h3>
                        </div>
                        <div class="card-body">
                            @forelse($guia->cotizaciones as $cot)
                                <div class="mb-2 p-2 border rounded bg-light">
                                    <p class="mb-0">
                                        <strong>Servicio:</strong> {{ $cot->tipo_servicio }} <br>
                                        <strong>Valor Total:</strong> ${{ number_format($cot->valor_total, 2, ',', '.') }}
                                    </p>
                                </div>
                            @empty
                                <p class="text-muted text-center">No hay cotizaciones asociadas a esta guía.</p>
                            @endforelse
                        </div>
                    </div>
                @elseif(request()->has('numeroGuia'))
                    {{-- This block handles the case where a search was attempted but no guide was found --}}
                    <div class="alert alert-info text-center" role="alert">
                        No se encontró información para el número de guía "{{ request('numeroGuia') }}". Por favor, verifique e intente de nuevo.
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection