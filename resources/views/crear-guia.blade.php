@extends('layout/plantilla')

@section('tituloPagina', 'Crear Guía Servientrega')

@section('contenido')

    <div class="container mt-5"> {{-- Container for centering and spacing --}}
        <div class="row">
            <div class="col-md-8 offset-md-2"> {{-- Column to center the form --}}

                <h1 class="mb-4 text-center">Creación de Guía Servientrega</h1> {{-- Centered title --}}

                <form action="/crear-guia'" method="POST" class="needs-validation" novalidate>
                    @csrf

                    <h2 class="mt-4 mb-3">Datos del Remitente</h2>
                    <hr> {{-- Horizontal line for separation --}}

                    {{-- Remitente Nombre --}}
                    <div class="mb-3">
                        <label for="remitente_nombre" class="form-label">Nombre del Remitente:</label>
                        <input type="text" class="form-control" id="remitente_nombre" name="remitente_nombre" required placeholder="Nombre completo del remitente">
                        <div class="invalid-feedback">
                            Por favor, ingrese el nombre del remitente.
                        </div>
                    </div>

                    {{-- Remitente Dirección --}}
                    <div class="mb-3">
                        <label for="remitente_direccion" class="form-label">Dirección del Remitente:</label>
                        <input type="text" class="form-control" id="remitente_direccion" name="remitente_direccion" required placeholder="Dirección completa del remitente">
                        <div class="invalid-feedback">
                            Por favor, ingrese la dirección del remitente.
                        </div>
                    </div>

                    {{-- Teléfono Remitente --}}
                    <div class="mb-3">
                        <label for="telefono_remitente" class="form-label">Teléfono del Remitente:</label>
                        <input type="text" class="form-control" id="telefono_remitente" name="telefono_remitente" required placeholder="Ej: 3101234567">
                        <div class="invalid-feedback">
                            Por favor, ingrese el teléfono del remitente.
                        </div>
                    </div>

                    <h2 class="mt-5 mb-3">Datos del Destinatario</h2>
                    <hr>

                    {{-- Destinatario Nombre --}}
                    <div class="mb-3">
                        <label for="destinatario_nombre" class="form-label">Nombre del Destinatario:</label>
                        <input type="text" class="form-control" id="destinatario_nombre" name="destinatario_nombre" required placeholder="Nombre completo del destinatario">
                        <div class="invalid-feedback">
                            Por favor, ingrese el nombre del destinatario.
                        </div>
                    </div>

                    {{-- Destinatario Dirección --}}
                    <div class="mb-3">
                        <label for="destinatario_direccion" class="form-label">Dirección del Destinatario:</label>
                        <input type="text" class="form-control" id="destinatario_direccion" name="destinatario_direccion" required placeholder="Dirección completa del destinatario">
                        <div class="invalid-feedback">
                            Por favor, ingrese la dirección del destinatario.
                        </div>
                    </div>

                    {{-- Teléfono Destinatario --}}
                    <div class="mb-3">
                        <label for="telefono_destinatario" class="form-label">Teléfono del Destinatario:</label>
                        <input type="text" class="form-control" id="telefono_destinatario" name="telefono_destinatario" required placeholder="Ej: 3209876543">
                        <div class="invalid-feedback">
                            Por favor, ingrese el teléfono del destinatario.
                        </div>
                    </div>

                    <h2 class="mt-5 mb-3">Detalles del Envío</h2>
                    <hr>

                    {{-- Valor Declarado --}}
                    <div class="mb-3">
                        <label for="valor_declarado" class="form-label">Valor Declarado ($):</label>
                        <input type="number" class="form-control" id="valor_declarado" name="valor_declarado" step="0.01" required min="0" placeholder="Ej: 100000.00">
                        <div class="invalid-feedback">
                            Por favor, ingrese el valor declarado.
                        </div>
                    </div>

                    {{-- Número Piezas --}}
                    <div class="mb-3">
                        <label for="numero_piezas" class="form-label">Número de Piezas:</label>
                        <input type="number" class="form-control" id="numero_piezas" name="numero_piezas" required min="1" placeholder="Ej: 1">
                        <div class="invalid-feedback">
                            Por favor, ingrese el número de piezas.
                        </div>
                    </div>

                    {{-- Ciudad Remitente ID --}}
                    <div class="mb-3">
                        <label for="ciudad_remitente_id" class="form-label">ID Ciudad Remitente (DANE):</label>
                        <input type="number" class="form-control" id="ciudad_remitente_id" name="ciudad_remitente_id" required placeholder="Ej: 11001 (Bogotá)">
                        <div class="invalid-feedback">
                            Por favor, ingrese el ID DANE de la ciudad del remitente.
                        </div>
                    </div>

                    {{-- Ciudad Destino ID --}}
                    <div class="mb-3">
                        <label for="ciudad_destino_id" class="form-label">ID Ciudad Destino (DANE):</label>
                        <input type="number" class="form-control" id="ciudad_destino_id" name="ciudad_destino_id" required placeholder="Ej: 05001 (Medellín)">
                        <div class="invalid-feedback">
                            Por favor, ingrese el ID DANE de la ciudad de destino.
                        </div>
                    </div>

                    <div class="d-grid gap-2"> {{-- Button centered and full width --}}
                        <button type="submit" class="btn btn-success btn-lg mt-4">Crear Guía</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection