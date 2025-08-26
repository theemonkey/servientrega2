<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrackingServientregaController;

//Muestra formulario para ingresar la guia
Route::get('/consultar', function () {
    return view('consultar'); 
});

// Esta ruta procesará la guía que se envía desde el formulario
Route::post('/consultar', [TrackingServientregaController::class, 'consultarGuia'])->name('consultar');
Route::get('/', function(){ return redirect('/consultar');})->name('consultar');

// Ruta para acceso sin formulario de consulta
Route::get('/guia/{numeroGuia}', [TrackingServientregaController::class, 'verGuia'])->name('guia');






