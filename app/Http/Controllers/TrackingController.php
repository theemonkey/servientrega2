<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Guia;
use App\Models\MovimientoGuia;
use App\Models\Ciudad;
use App\Models\Estado;
use SoapClient;

class TrackingController extends Controller
{
    private $wsdl = 'https://web.servientrega.com/TrackingEnvios.asmx?WSDL';

    public function rastrearGuia(Request $request)
    {
        $numeroGuia = $request->numeroGuia;

        $client = new SoapClient($this->wsdl, ['trace' => 1]);

        $params = [
            'NumeroGuia' => $numeroGuia,
            'Usuario' => env('SERVIENTREGA_TRACK_USER'),
            'Contrasena' => env('SERVIENTREGA_TRACK_PASS')
        ];

        $result = $client->__soapCall('ConsultarGuia', [$params]);

        if (!isset($result->ConsultarGuiaResult)) {
            return back()->with('error', 'No se pudo rastrear la guía');
        }

        $data = $result->ConsultarGuiaResult;

        // Guardar estado si existe
        $estado = Estado::updateOrCreate(
            ['codigo_estado' => $data->EstadoCodigo ?? null],
            [
                'nombre_estado' => $data->EstadoNombre ?? 'Sin nombre',
                'descripcion' => $data->EstadoDescripcion ?? ''
            ]
        );

        // Guardar ciudades (si las devuelve la API)
        $ciudadOrigen = Ciudad::updateOrCreate(
            ['codigo_dane' => $data->IdDaneCiudadOrigen ?? null],
            ['nombre_ciudad' => $data->CiudadOrigen ?? 'Desconocida']
        );

        $ciudadDestino = Ciudad::updateOrCreate(
            ['codigo_dane' => $data->IdDaneCiudadDestino ?? null],
            ['nombre_ciudad' => $data->CiudadDestino ?? 'Desconocida']
        );

        // Guardar o actualizar guía
        $guia = Guia::updateOrCreate(
            ['numero_guia' => $numeroGuia],
            [
                'fecha_envio' => $data->FechaEnvio ?? now(),
                'numero_piezas' => $data->NumeroPiezas ?? 1,
                'remitente_nombre' => $data->Remitente ?? '',
                'remitente_direccion' => $data->DireccionRemitente ?? '',
                'destinatario_nombre' => $data->Destinatario ?? '',
                'destinatario_direccion' => $data->DireccionDestinatario ?? '',
                'estado_actual_id' => $estado->id,
                'ciudad_remitente_id' => $ciudadOrigen->id,
                'ciudad_destino_id' => $ciudadDestino->id,
                'fecha_probable_entrega' => $data->FechaEntrega ?? null,
                'regimen' => $data->Regimen ?? ''
            ]
        );

        // Guardar movimientos
        if (!empty($data->Movimientos)) {
            foreach ($data->Movimientos as $mov) {
                MovimientoGuia::updateOrCreate(
                    [
                        'guia_id' => $guia->id,
                        'fecha_movimiento' => $mov->Fecha ?? now()
                    ],
                    [
                        'estado_movimiento' => $mov->Estado ?? '',
                        'descripcion_movimiento' => $mov->Descripcion ?? '',
                        'ciudad_movimiento' => $mov->Ciudad ?? ''
                    ]
                );
            }
        }

        // Recargar guía con todas sus relaciones
        $guia->load(['estadoActual', 'ciudadRemitente', 'ciudadDestino', 'cotizaciones', 'movimientos']);

        return view('rastreo', compact('guia'));
    }
}
