<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrackingServientregaController;
use App\Http\Controllers\GuiaEnvioController;
use Illuminate\Support\Facades\Auth;

//Muestra formulario para ingresar la guia
Route::get('/consultar', function () {
    return view('consultar');
});

// Esta ruta procesará la guía que se envía desde el formulario
Route::post('/consultar', [TrackingServientregaController::class, 'consultarGuia'])->name('consultar');
Route::get('/', function(){ return redirect('/consultar');})->name('consultar');

// Ruta para acceso sin formulario de consulta
Route::get('/guia/{numeroGuia}', [TrackingServientregaController::class, 'verGuia'])->name('guia');


// Ruta para la creacion de una nueva guia industrial
/*Route::middleware('auth')->group(function () {
    Route::resource('guias', GuiaEnvioController::class);
    Route::get('guias/{guia}/regenerar', [GuiaEnvioController::class, 'mostrarRegeneracion'])->name('show');
    Route::post('guias/{guia}/regenerar', [GuiaEnvioController::class, 'regenerar'])->name('crear.guia');
});*/

// Rutas de diagnóstico y testing
Route::get('/diagnostico-sistema', [TrackingServientregaController::class, 'diagnosticoSistema']);
Route::get('/test-conversion-simple/{numeroGuia}', [TrackingServientregaController::class, 'testConversionSimple']);
Route::get('/debug-imagen/{numeroGuia}', [TrackingServientregaController::class, 'debugImagen']);
Route::get('/debug-imagen-completo/{numeroGuia}', [TrackingServientregaController::class, 'debugImagenCompleto']);
Route::get('/reprocesar-imagen/{numeroGuia}', [TrackingServientregaController::class, 'reprocesarImagen']);



