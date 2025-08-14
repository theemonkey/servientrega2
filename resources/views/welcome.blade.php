@extends('layout/plantilla')

@section('tituloPagina', 'Mi Primera Prueba con Laravel')

@section('contenido')
    <div class="alert alert-success" role="alert">
        ¡Hola desde Laravel! Este es el contenido de mi vista de prueba.
    </div>
    <button class="btn btn-primary">Botón de prueba</button>
    <i class="fas fa-check"></i> Ícono de Font Awesome
@endsection