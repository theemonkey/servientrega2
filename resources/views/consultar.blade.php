@extends('layout/plantilla')

@section('tituloPagina', 'Consultar Guía Servientrega')


@section('contenido')

 <!-- Indicador de carga elegante -->
    <div class="loading-indicator" id="loadingIndicator">
        <div class="pulse-animation"></div>
        <h6 class="mb-2">Obteniendo informacion</h6>
    </div>
    <div class="main-container" id="mainContainer">
        <div class="d-flex justify-content-center align-items-center mt-5">
            <div class="card shadow-sm p-4" style="width: 100%; max-width: 500px;">
                <div class="card-body">
                    <!--Icono camion envio-->
                    <div class="icono-consulta mb-3">
                        <i class="fas fa-shipping-fast fa-3x"></i>
                    </div>
                    <h5 class="card-title text-center mb-4">Consultar Guía</h5>
                    <form action="/consultar" method="POST" id="consultarForm">
                        @csrf
                        <div class="mb-3">
                            <label for="numero_guia" class="form-label-guia">Número de guía</label>
                            <input 
                                type="text" 
                                class="form-control @error('numero_guia') is-invalid @enderror" 
                                id="numero_guia" 
                                name="numero_guia" 
                                required 
                                placeholder="Ingreso de datos (Ej: 123456789)"
                                pattern="[0-9]+" 
                                inputmode="numeric"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            />
                            @error('numero_guia')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn-consultar btn-primary" id="submitBtn">
                                Consultar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!--Script Animacion boton consultar -->
    <script>
        document.getElementById('consultarForm').addEventListener('submit', function(e) {
            const mainContainer = document.getElementById('mainContainer');
            const loadingIndicator = document.getElementById('loadingIndicator');
            const submitBtn = document.getElementById('submitBtn');
            
            // Deshabilitar botón
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Consultando';
            
            // Aplicar efectos con las transiciones CSS
            mainContainer.classList.add('loading');
            
            // Mostrar indicador después de un pequeño delay
            setTimeout(() => {
                loadingIndicator.classList.add('show');
            }, 100);
        });
    </script>
@endsection