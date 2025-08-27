@extends('layouts.app')

@section('tituloPagina', 'Crear Nueva Guía de Envío')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Crear Nueva Guía de Envío</h4>
                </div>

                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('guias.store') }}" id="formGuia">
                        @csrf

                        {{-- Datos del Destinatario --}}
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h5 class="text-primary">Datos del Destinatario</h5>
                                <hr>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nom_contacto" class="form-label">Nombre del Contacto *</label>
                                <input type="text" class="form-control" id="nom_contacto" name="nom_contacto" 
                                       value="{{ old('nom_contacto') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label for="des_telefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="des_telefono" name="des_telefono" 
                                       value="{{ old('des_telefono') }}">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="des_ciudad" class="form-label">Ciudad *</label>
                                <input type="text" class="form-control" id="des_ciudad" name="des_ciudad" 
                                       value="{{ old('des_ciudad') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label for="des_departamento_destino" class="form-label">Departamento *</label>
                                <input type="text" class="form-control" id="des_departamento_destino" name="des_departamento_destino" 
                                       value="{{ old('des_departamento_destino') }}" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="des_direccion" class="form-label">Dirección *</label>
                                <textarea class="form-control" id="des_direccion" name="des_direccion" rows="2" required>{{ old('des_direccion') }}</textarea>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="des_correo_electronico" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="des_correo_electronico" name="des_correo_electronico" 
                                       value="{{ old('des_correo_electronico') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="referencia_cliente" class="form-label">Referencia del Cliente</label>
                                <input type="text" class="form-control" id="referencia_cliente" name="referencia_cliente" 
                                       value="{{ old('referencia_cliente') }}">
                            </div>
                        </div>

                        {{-- Datos del Envío --}}
                        <div class="row mb-4 mt-4">
                            <div class="col-md-12">
                                <h5 class="text-primary">Datos del Envío</h5>
                                <hr>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="des_dice_contener" class="form-label">Descripción del Contenido *</label>
                                <textarea class="form-control" id="des_dice_contener" name="des_dice_contener" rows="2" required>{{ old('des_dice_contener') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="num_valor_declarado_total" class="form-label">Valor Declarado Total ($) *</label>
                                <input type="number" step="0.01" class="form-control" id="num_valor_declarado_total" 
                                       name="num_valor_declarado_total" value="{{ old('num_valor_declarado_total') }}" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="observaciones" class="form-label">Observaciones</label>
                                <textarea class="form-control" id="observaciones" name="observaciones" rows="2">{{ old('observaciones') }}</textarea>
                            </div>
                        </div>

                        {{-- Unidades de Empaque --}}
                        <div class="row mb-4 mt-4">
                            <div class="col-md-12">
                                <h5 class="text-primary">Unidades de Empaque</h5>
                                <hr>
                            </div>
                        </div>

                        <div id="unidades-empaque-container">
                            <div class="unidad-empaque-item border p-3 mb-3 rounded">
                                <div class="row">
                                    <div class="col-md-12 mb-2">
                                        <h6>Paquete #1</h6>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label">Alto (cm) *</label>
                                        <input type="number" step="0.01" class="form-control" name="unidades_empaque[0][num_alto]" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Ancho (cm) *</label>
                                        <input type="number" step="0.01" class="form-control" name="unidades_empaque[0][num_ancho]" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Largo (cm) *</label>
                                        <input type="number" step="0.01" class="form-control" name="unidades_empaque[0][num_largo]" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Peso (kg) *</label>
                                        <input type="number" step="0.01" class="form-control" name="unidades_empaque[0][num_peso]" required>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-3">
                                        <label class="form-label">Cantidad *</label>
                                        <input type="number" class="form-control" name="unidades_empaque[0][num_cantidad]" min="1" value="1" required>
                                    </div>
                                    <div class="col-md-9">
                                        <label class="form-label">Descripción del contenido *</label>
                                        <input type="text" class="form-control" name="unidades_empaque[0][des_dice_contener]" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-secondary" id="agregarUnidad">
                                    <i class="fas fa-plus"></i> Agregar Otra Unidad de Empaque
                                </button>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-paper-plane"></i> Crear Guía
                                </button>
                                <a href="{{ route('guias.index') }}" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-arrow-left"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let contadorUnidades = 1;

document.getElementById('agregarUnidad').addEventListener('click', function() {
    const container = document.getElementById('unidades-empaque-container');
    const nuevaUnidad = `
        <div class="unidad-empaque-item border p-3 mb-3 rounded">
            <div class="row">
                <div class="col-md-10 mb-2">
                    <h6>Paquete #${contadorUnidades + 1}</h6>
                </div>
                <div class="col-md-2 text-end">
                    <button type="button" class="btn btn-sm btn-danger eliminar-unidad">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label">Alto (cm) *</label>
                    <input type="number" step="0.01" class="form-control" name="unidades_empaque[${contadorUnidades}][num_alto]" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ancho (cm) *</label>
                    <input type="number" step="0.01" class="form-control" name="unidades_empaque[${contadorUnidades}][num_ancho]" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Largo (cm) *</label>
                    <input type="number" step="0.01" class="form-control" name="unidades_empaque[${contadorUnidades}][num_largo]" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Peso (kg) *</label>
                    <input type="number" step="0.01" class="form-control" name="unidades_empaque[${contadorUnidades}][num_peso]" required>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-3">
                    <label class="form-label">Cantidad *</label>
                    <input type="number" class="form-control" name="unidades_empaque[${contadorUnidades}][num_cantidad]" min="1" value="1" required>
                </div>
                <div class="col-md-9">
                    <label class="form-label">Descripción del contenido *</label>
                    <input type="text" class="form-control" name="unidades_empaque[${contadorUnidades}][des_dice_contener]" required>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', nuevaUnidad);
    contadorUnidades++;
    
    // Agregar evento para eliminar
    const botonesEliminar = document.querySelectorAll('.eliminar-unidad');
    botonesEliminar.forEach(boton => {
        boton.addEventListener('click', function() {
            this.closest('.unidad-empaque-item').remove();
            actualizarNumeracion();
        });
    });
});

function actualizarNumeracion() {
    const unidades = document.querySelectorAll('.unidad-empaque-item');
    unidades.forEach((unidad, index) => {
        const titulo = unidad.querySelector('h6');
        titulo.textContent = `Paquete #${index + 1}`;
    });
}
</script>
@endpush
@endsection