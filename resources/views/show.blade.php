@extends('layouts.app')

@section('tituloPagina', 'Detalle de guía creada #' . $guia->id)

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Guía de Envío #{{ $guia->id }}</h4>
                    <div>
                        <span class="badge badge-{{ $guia->estado === 'generada' ? 'success' : ($guia->estado === 'error' ? 'danger' : 'warning') }} fs-6">
                            {{ $guia->estado_formateado }}
                        </span>
                    </div>
                </div>

                <div class="card-body">
                    @if($guia->num_guia)
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle"></i> Número de Guía: <strong>{{ $guia->num_guia }}</strong></h5>
                        </div>
                    @endif

                    <div class="row">
                        <!-- Información del Destinatario -->
                        <div class="col-md-6">
                            <h5 class="text-primary">Destinatario</h5>
                            <table class="table table-sm">
                                <tr><td><strong>Contacto:</strong></td><td>{{ $guia->nom_contacto }}</td></tr>
                                <tr><td><strong>Teléfono:</strong></td><td>{{ $guia->des_telefono ?: 'N/A' }}</td></tr>
                                <tr><td><strong>Ciudad:</strong></td><td>{{ $guia->des_ciudad }}</td></tr>
                                <tr><td><strong>Departamento:</strong></td><td>{{ $guia->des_departamento_destino }}</td></tr>
                                <tr><td><strong>Dirección:</strong></td><td>{{ $guia->des_direccion }}</td></tr>
                                @if($guia->des_correo_electronico)
                                    <tr><td><strong>Email:</strong></td><td>{{ $guia->des_correo_electronico }}</td></tr>
                                @endif
                            </table>
                        </div>

                        <!-- Información del Envío -->
                        <div class="col-md-6">
                            <h5 class="text-primary">Envío</h5>
                            <table class="table table-sm">
                                <tr><td><strong>Contenido:</strong></td><td>{{ $guia->des_dice_contener }}</td></tr>
                                <tr><td><strong>Valor Declarado:</strong></td><td>${{ number_format($guia->num_valor_declarado_total, 0) }}</td></tr>
                                <tr><td><strong>Piezas:</strong></td><td>{{ $guia->num_piezas }}</td></tr>
                                @if($guia->referencia_cliente)
                                    <tr><td><strong>Referencia:</strong></td><td>{{ $guia->referencia_cliente }}</td></tr>
                                @endif
                                @if($guia->num_vlr_flete > 0)
                                    <tr><td><strong>Costo Flete:</strong></td><td>${{ number_format($guia->num_vlr_flete, 0) }}</td></tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    <!-- Unidades de Empaque -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5 class="text-primary">Unidades de Empaque</h5>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Contenido</th>
                                            <th>Dimensiones (cm)</th>
                                            <th>Peso (kg)</th>
                                            <th>Cantidad</th>
                                            <th>Volumen (cm³)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($guia->unidadesEmpaque as $unidad)
                                            <tr>
                                                <td>{{ $unidad->des_dice_contener }}</td>
                                                <td>{{ $unidad->num_alto }} x {{ $unidad->num_ancho }} x {{ $unidad->num_largo }}</td>
                                                <td>{{ $unidad->num_peso }}</td>
                                                <td>{{ $unidad->num_cantidad }}</td>
                                                <td>{{ number_format($unidad->volumen_total, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    @if($guia->observaciones)
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <h6>Observaciones:</h6>
                                <p class="border p-2 bg-light">{{ $guia->observaciones }}</p>
                            </div>
                        </div>
                    @endif

                    @if($guia->mensaje_error)
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="alert alert-danger">
                                    <h6>Error:</h6>
                                    {{ $guia->mensaje_error }}
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Botones de Acción -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <a href="{{ route('guias.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver a la Lista
                            </a>
                            
                            @if($guia->estado === 'error' || $guia->estado === 'borrador')
                                <form method="POST" action="{{ route('guias.regenerar', $guia) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-warning" onclick="return confirm('¿Regenerar la guía?')">
                                        <i class="fas fa-redo"></i> Regenerar Guía
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection