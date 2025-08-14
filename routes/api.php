<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrackingServientregaController; // importar el controlador

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rutas para la integración con Servientrega
Route::post('/store', [App\Http\Controllers\TrackingServientregaController::class, 'store']);


/*Route::prefix('servientrega')->group(function () {
    // Rastreo
    Route::get('track/{trackingNumber}', [ServientregaController::class, 'track']);
    Route::get('track', [ServientregaController::class, 'track'])->defaults('trackingNumber', '3047885568');
    
    // Creación de envíos
    Route::post('shipment', [ServientregaController::class, 'createShipment']);
    
    // Cotización
    Route::post('quote', [ServientregaController::class, 'generateQuote']);
    
    // Ciudades
    Route::post('cities', [ServientregaController::class, 'getCities']);
    Route::get('service-centers/{daneCode}', [ServientregaController::class, 'getServiceCenters']);
    
    // Historial
    Route::get('history/{type?}', [ServientregaController::class, 'getHistory']);
});*/