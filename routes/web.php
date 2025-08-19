<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrackingServientregaController;
use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\GuiaController;
use App\Http\Controllers\TrackingController;

//Muestra formulario para ingresar la guia
Route::get('/consultar', function () {
    return view('consultar'); 
});

// Esta ruta procesará la guía que se envía desde el formulario
Route::post('/consultar', [TrackingServientregaController::class, 'consultarGuia'])->name('consultar');
Route::get('/', function(){ return redirect('/consultar');})->name('consultar');

Route::post('/crear-guia',  [GuiaController::class, 'crearGuia']);
Route::get('/crear-guia', function(){
    return view('crear-guia');
});

Route::post('/cotizar', [CotizacionController::class, 'generarCotizacion'])->name('cotizar');
Route::get('/cotizar', function(){
    return view('cotizacion');
});


Route::get('/rastreo', [TrackingController::class, 'rastrearGuia'])->name('rastreo');

