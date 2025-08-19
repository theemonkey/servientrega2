<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; 
use App\Models\TrackingServientrega;

class TrackingServientregaController extends Controller
{
    public function consultarGuia(Request $request)
    {
        $numeroGuia = $request->input('numero_guia');

        // Llamada GET al endpoint
        $response = Http::get("https://wssismilenio.servientrega.com/wsrastreoenvios/wsrastreoenvios.asmx/ConsultarGuia", [
            'NumeroGuia' => $numeroGuia
        ]);
        // Imagen convertida a base64
        $xmlResponse = simplexml_load_string($response->body());
        $imagenBase64 = (string)$xmlResponse->xpath('//Imagen')[0];

        if ($response->successful()) {
            // Parsear XML a array
            $xml = simplexml_load_string($response->body());
            $array = json_decode(json_encode($xml), true);

            // Navegar hasta los movimientos
            $movimientos = $array['Mov']['InformacionMov'] ?? [];
            if (!is_array($movimientos)) {
                $movimientos = [$movimientos]; // Si solo hay 1 movimiento
            }

            // Tomar el último movimiento como estado actual
            $ultimoMovimiento = end($movimientos);

            $estado = $ultimoMovimiento['NomMov'] ?? null;
            $ciudad = $ultimoMovimiento['DesMov'] ?? null;
            $fecha = isset($ultimoMovimiento['FecMov']) 
                ? date('Y-m-d', strtotime($ultimoMovimiento['FecMov'])) 
                : null;

            // Guardar en DB
            TrackingServientrega::create([
                'numero_guia' => $numeroGuia,
                'estado' => $estado,
                'ciudad' => $ciudad,
                'fecha' => $fecha,
                'respuesta' => $array, // Se guarda como JSON gracias al casts
                //pendiente agregar mas
            ]);

            return view('resultados', ['respuesta' => $array]);
        }

        return back()->withErrors(['Error al consultar la guía']);
    }
}
