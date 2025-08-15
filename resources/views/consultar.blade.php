@extends('layout/plantilla')

@section('tituloPagina', 'Consultar Guía Servientrega')

@section('contenido')
    <div class="d-flex justify-content-center align-items-center mt-5">
        <div class="card shadow-sm p-4" style="width: 100%; max-width: 500px;">
            <div class="card-body">
                <h5 class="card-title text-center mb-4">Consultar Guía</h5>
                <form action="/consultar" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="numero_guia" class="form-label">Número de Guía</label>
                        <input type="text" class="form-control" id="numero_guia" name="numero_guia" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Consultar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
