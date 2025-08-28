@extends('layout/plantilla')

@section('tituloPagina', 'Error - Guía no encontrada')

@section('contenido')
    {{-- Breadcrumb para navegación --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ $urlOrigen ?? '/' }}" class="text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i>Volver
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                Error - Guía: {{ $numeroGuia }}
            </li>
        </ol>
    </nav>

    <div class="card shadow-sm mt-5">
        <div class="card-body text-center py-5">
            <div class="mb-4">
                <i class="fas fa-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
            </div>
            
            <h4 class="card-title text-danger mb-3">Error al consultar la guía</h4>
            
            <div class="alert alert-danger mx-auto" style="max-width: 500px;">
                <strong>Número de guía:</strong> {{ $numeroGuia }}<br>
                <strong>Error:</strong> {{ $mensaje }}
            </div>
            
            <div class="mt-4">
                <p class="text-muted">Posibles causas:</p>
                <ul class="list-unstyled text-muted">
                    <li><i class="fas fa-times-circle text-danger me-2"></i>La guía no existe en el sistema</li>
                    <li><i class="fas fa-times-circle text-danger me-2"></i>Formato de guía incorrecto</li>
                    <li><i class="fas fa-times-circle text-danger me-2"></i>Problemas de conexión con el servicio</li>
                </ul>
            </div>
            
            <div class="mt-4">
                <a href="{{ $urlOrigen ?? '/' }}" class="btn btn-primary me-2">
                    <i class="fas fa-arrow-left me-2"></i>Volver
                </a>
                <a href="/" class="btn btn-outline-secondary">
                    <i class="fas fa-search me-2"></i>Nueva Consulta
                </a>
            </div>
        </div>
    </div>
@endsection