@extends('layout/plantilla')

@section('tituloPagina', 'Cotizar Guía Servientrega')

@section('contenido')

    <div class="container mt-5"> {{-- Contenedor para centrar y dar espaciado --}}
        <div class="row">
            <div class="col-md-8 offset-md-2"> {{-- Columna para centrar el formulario --}}

                <h1 class="mb-4 text-center">Generar Cotización Servientrega</h1> {{-- Título centrado --}}

                <form action="/cotizar" method="POST" class="needs-validation" novalidate>
                    @csrf

                    {{-- Campo Guía Asociada (opcional) --}}
                    <div class="mb-3">
                        <label for="guia_id" class="form-label">Guía Asociada (opcional):</label>
                        <input type="number" class="form-control" id="guia_id" name="guia_id" placeholder="Ingrese ID de guía si aplica">
                    </div>

                    {{-- Campo Id Producto --}}
                    <div class="mb-3">
                        <label for="IdProducto" class="form-label">Id Producto:</label>
                        <input type="text" class="form-control" id="IdProducto" name="IdProducto" required placeholder="Ej: 12345">
                        <div class="invalid-feedback">
                            Por favor, ingrese el ID del producto.
                        </div>
                    </div>

                    {{-- Campo Número Piezas --}}
                    <div class="mb-3">
                        <label for="NumeroPiezas" class="form-label">Número Piezas:</label>
                        <input type="number" class="form-control" id="NumeroPiezas" name="NumeroPiezas" required min="1" placeholder="Ej: 1">
                        <div class="invalid-feedback">
                            Por favor, ingrese el número de piezas.
                        </div>
                    </div>

                    {{-- Campo Peso --}}
                    <div class="mb-3">
                        <label for="Peso" class="form-label">Peso (kg):</label>
                        <input type="number" class="form-control" id="Peso" name="Peso" step="0.01" required min="0.01" placeholder="Ej: 5.50">
                        <div class="invalid-feedback">
                            Por favor, ingrese el peso.
                        </div>
                    </div>

                    {{-- Campo Largo --}}
                    <div class="mb-3">
                        <label for="Largo" class="form-label">Largo (cm):</label>
                        <input type="number" class="form-control" id="Largo" name="Largo" step="0.01" required min="0.01" placeholder="Ej: 30.00">
                        <div class="invalid-feedback">
                            Por favor, ingrese el largo.
                        </div>
                    </div>

                    {{-- Campo Ancho --}}
                    <div class="mb-3">
                        <label for="Ancho" class="form-label">Ancho (cm):</label>
                        <input type="number" class="form-control" id="Ancho" name="Ancho" step="0.01" required min="0.01" placeholder="Ej: 20.00">
                        <div class="invalid-feedback">
                            Por favor, ingrese el ancho.
                        </div>
                    </div>

                    {{-- Campo Alto --}}
                    <div class="mb-3">
                        <label for="Alto" class="form-label">Alto (cm):</label>
                        <input type="number" class="form-control" id="Alto" name="Alto" step="0.01" required min="0.01" placeholder="Ej: 15.00">
                        <div class="invalid-feedback">
                            Por favor, ingrese el alto.
                        </div>
                    </div>

                    {{-- Campo Valor Declarado --}}
                    <div class="mb-3">
                        <label for="ValorDeclarado" class="form-label">Valor Declarado ($):</label>
                        <input type="number" class="form-control" id="ValorDeclarado" name="ValorDeclarado" step="0.01" required min="0" placeholder="Ej: 150000.00">
                        <div class="invalid-feedback">
                            Por favor, ingrese el valor declarado.
                        </div>
                    </div>

                    {{-- Campo Id DANE Origen --}}
                    <div class="mb-3">
                        <label for="IdDaneCiudadOrigen" class="form-label">Id DANE Origen:</label>
                        <input type="text" class="form-control" id="IdDaneCiudadOrigen" name="IdDaneCiudadOrigen" required placeholder="Ej: 11001">
                        <div class="invalid-feedback">
                            Por favor, ingrese el ID DANE de la ciudad de origen.
                        </div>
                    </div>

                    {{-- Campo Id DANE Destino --}}
                    <div class="mb-3">
                        <label for="IdDaneCiudadDestino" class="form-label">Id DANE Destino:</label>
                        <input type="text" class="form-control" id="IdDaneCiudadDestino" name="IdDaneCiudadDestino" required placeholder="Ej: 05001">
                        <div class="invalid-feedback">
                            Por favor, ingrese el ID DANE de la ciudad de destino.
                        </div>
                    </div>

                    {{-- Campo Forma Pago --}}
                    <div class="mb-3">
                        <label for="FormaPago" class="form-label">Forma Pago:</label>
                        <input type="text" class="form-control" id="FormaPago" name="FormaPago" required placeholder="Ej: Contado, Crédito">
                        <div class="invalid-feedback">
                            Por favor, ingrese la forma de pago.
                        </div>
                    </div>

                    {{-- Campo Tiempo Entrega --}}
                    <div class="mb-3">
                        <label for="TiempoEntrega" class="form-label">Tiempo Entrega:</label>
                        <input type="text" class="form-control" id="TiempoEntrega" name="TiempoEntrega" required placeholder="Ej: 24 Horas, 48 Horas">
                        <div class="invalid-feedback">
                            Por favor, ingrese el tiempo de entrega.
                        </div>
                    </div>

                    {{-- Campo Medio Transporte --}}
                    <div class="mb-3">
                        <label for="MedioTransporte" class="form-label">Medio Transporte:</label>
                        <input type="text" class="form-control" id="MedioTransporte" name="MedioTransporte" required placeholder="Ej: Terrestre, Aéreo">
                        <div class="invalid-feedback">
                            Por favor, ingrese el medio de transporte.
                        </div>
                    </div>

                    {{-- Campo Num Recaudo (opcional) --}}
                    <div class="mb-3">
                        <label for="NumRecaudo" class="form-label">Num Recaudo (opcional):</label>
                        <input type="text" class="form-control" id="NumRecaudo" name="NumRecaudo" placeholder="Número de recaudo si aplica">
                    </div>

                    <div class="d-grid gap-2"> {{-- Botón centrado y ancho completo --}}
                        <button type="submit" class="btn btn-success btn-lg mt-3">Generar Cotización</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection