@extends('layout/plantilla')

@section('tituloPagina', 'Error - Guía no encontrada')

@section('contenido')
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="fas fa-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
                        </div>
                        
                        <h3 class="mb-3">Guía no encontrada</h3>
                        
                        <p class="text-muted mb-4">
                            {{ $mensaje }}
                        </p>
                        
                        @if(isset($numeroGuia))
                            <div class="alert alert-info">
                                <strong>Guía consultada:</strong> #{{ $numeroGuia }}
                            </div>
                        @endif
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="{{ url()->previous() != url()->current() ? url()->previous() : '/' }}" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>Volver
                            </a>
                            <a href="{{ route('tracking.buscar') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-search me-2"></i>Buscar otra guía
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection