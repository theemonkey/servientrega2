<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Cotizacion;

class CotizacionController extends Controller
{
    private $baseUrl = 'http://web.servientrega.com:8058/cotizadorcorporativo';
    private $token = null;

    private function obtenerToken()
    {
        $response = Http::post($this->baseUrl . '/api/Autenticacion/Login', [
            'login' => env('SERVIENTREGA_LOGIN'),
            'password' => env('SERVIENTREGA_PASSWORD'),
            'codFacturacion' => env('SERVIENTREGA_COD_FACTURACION')
        ]);

        if ($response->successful()) {
            $this->token = $response->json()['token'];
            return true;
        }
        return false;
    }

    public function generarCotizacion(Request $request)
    {
        if (!$this->obtenerToken()) {
            return response()->json(['error' => 'Error al obtener token'], 500);
        }

        $payload = [
            "IdProducto" => $request->IdProducto,
            "NumeroPiezas" => $request->NumeroPiezas,
            "Piezas" => [
                [
                    "Peso" => $request->Peso,
                    "Largo" => $request->Largo,
                    "Ancho" => $request->Ancho,
                    "Alto" => $request->Alto
                ]
            ],
            "ValorDeclarado" => $request->ValorDeclarado,
            "IdDaneCiudadOrigen" => $request->IdDaneCiudadOrigen,
            "IdDaneCiudadDestino" => $request->IdDaneCiudadDestino,
            "EnvioConCobro" => $request->EnvioConCobro,
            "FormaPago" => $request->FormaPago,
            "TiempoEntrega" => $request->TiempoEntrega,
            "MedioTransporte" => $request->MedioTransporte,
            "NumRecaudo" => $request->NumRecaudo
        ];

        $response = Http::withToken($this->token)
            ->post($this->baseUrl . '/api/Cotizacion', $payload);

        if ($response->successful()) {
            $cotizacion = Cotizacion::create([
                'guia_id' => $request->guia_id,
                'tipo_servicio' => $request->IdProducto,
                'tipo_empaque' => null,
                'peso_fisico' => $request->Peso,
                'peso_volumen' => null,
                'largo' => $request->Largo,
                'ancho' => $request->Ancho,
                'alto' => $request->Alto,
                'valor_declarado' => $request->ValorDeclarado,
                'costo_flete' => $response->json()['ValorFlete'],
                'valor_sobretasa' => $response->json()['ValorSobreFlete'],
                'valor_total' => $response->json()['ValorTotal'],
                'origen_id' => $request->origen_id,
                'destino_id' => $request->destino_id
            ]);

            return response()->json($cotizacion);
        }

        return response()->json(['error' => 'Error en la cotización'], 500);
    }
}
