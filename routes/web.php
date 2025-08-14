<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrackingServientregaController;

//Muestra formulario para ingresar la guia
Route::get('/consultar', function () {
    return view('consultar'); 
});

// Esta ruta procesará la guía que se envía desde el formulario
Route::post('/consultar', [TrackingServientregaController::class, 'consultarGuia']);

Route::get('/', function(){ return redirect('/consultar');});


/*use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/check-db', function () {
    try {
        $databaseName = DB::connection()->getDatabaseName();
        return "Connected to database: " . $databaseName;
    } catch (\Exception $e) {
        return "Error connecting to database: " . $e->getMessage();
    }
});*/