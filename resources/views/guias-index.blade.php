@extends('layouts.app')

@section('tituloPagina', 'Lista de Guías de Envío')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Mis Guías de Envío</h4>
                    <a href="{{ route('guias.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Guía
                    </a>
                </div>

                <div class="card-body">
                    @if($guias->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Número Guía</th>
                                        <th>Destinatario</th>
                                        <th>Ciudad</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($guias as $guia)
                                        <tr>
                                            <td>{{ $guia->id }}</td>
                                            <td>
                                                @if($guia->num_guia)
                                                    <strong>{{ $guia->num_guia }}</strong>
                                                @else
                                                    <span class="text-muted">Sin asignar</span>
                                                @endif
                                            </td>
                                            <td>{{ $guia->nom_contacto }}</td>
                                            <td>{{ $guia->des_ciudad }}</td>
                                            <td>
                                                <span class="badge bg-{{ $guia->estado === 'generada' ? 'success' : ($guia->estado === 'error' ? 'danger' : 'warning') }}">
                                                    {{ $guia->estado_formateado }}
                                                </span>
                                            </td>
                                            <td>{{ $guia->created_at->format('d/m/Y H:i') }}</td>
                                            <td>
                                                <a href="{{ route('guias.show', $guia) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> Ver
                                                </a>
                                                @if($guia->estado === 'error' || $guia->estado === 'borrador')
                                                    <a href="{{ route('guias.regenerar.show', $guia) }}" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-redo"></i> Regenerar
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{ $guias->links() }}
                    @else
                        <div class="text-center py-4">
                            <p>No has creado ninguna guía aún.</p>
                            <a href="{{ route('guias.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Crear Primera Guía
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection